<?php

namespace App\Services;

use App\Models\Block;
use App\Models\ClassSchedule;
use App\Models\Fee;
use App\Models\FormResponse;
use App\Models\ScheduleTemplate;
use App\Models\SchoolYear;
use App\Models\StudentCorRecord;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Build COR (Certificate of Registration) view data for a student.
 * Used by student self-view and by staff/registrar when viewing or printing a student's COR.
 */
class CorViewService
{
    // #region agent log
    private function dbg(string $runId, string $hypothesisId, string $location, string $message, array $data = []): void
    {
        try {
            $payload = [
                'sessionId' => '52ecf9',
                'runId' => $runId,
                'hypothesisId' => $hypothesisId,
                'location' => $location,
                'message' => $message,
                'data' => $data,
                'timestamp' => (int) round(microtime(true) * 1000),
            ];
            $payload['data']['base_path'] = base_path();
            @file_put_contents(base_path('debug-52ecf9.log'), json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
        }
    }
    // #endregion

    /**
     * Build view data for the student-cor view.
     *
     * @param  User  $student
     * @param  string|null  $requestedSchoolYear
     * @param  Block|null  $contextBlock  When set (e.g. Print All COR), use this block for COR lookup and display instead of student's primary block.
     * @return array{student: User, latestResponse: FormResponse|null, schedules: Collection, deployedTemplate: ScheduleTemplate|null, corSubjects: array, corFees: array, schoolYear: string, semester: string, availableSchoolYears: array, selectedSchoolYear: string|null, noCorForSelectedYear: bool, corFromRegistrar: bool}
     */
    public function buildCorData(User $student, ?string $requestedSchoolYear = null, ?Block $contextBlock = null): array
    {
        // #region agent log
        $this->dbg('run1', 'H1', 'CorViewService.php:buildCorData:entry', 'buildCorData called', [
            'has_context_block' => $contextBlock !== null,
            'context_block_id' => $contextBlock?->id,
            'student_has_block_id' => ! empty($student->block_id),
            'requested_school_year_present' => $requestedSchoolYear !== null && trim((string) $requestedSchoolYear) !== '',
        ]);
        // #endregion

        if ($contextBlock !== null) {
            $student->setRelation('block', $contextBlock);
        } else {
            $student->load('block');
        }
        $latestResponse = FormResponse::query()
            ->with('enrollmentForm')
            ->where('user_id', $student->id)
            ->latest()
            ->first();

        $isSchoolYear = fn ($v) => $v !== null && $v !== '' && preg_match('/^\d{4}\s*-\s*\d{4}$/', trim((string) $v));
        $userYear = $student->school_year;
        $formYear = $latestResponse?->enrollmentForm?->assigned_year;
        $blockYear = $student->block?->school_year_label;
        $fallbackYear = SchoolYear::query()->orderByDesc('start_year')->value('label');

        $detectedSchoolYear = null;
        if ($isSchoolYear($userYear)) {
            $detectedSchoolYear = trim($userYear);
        } elseif ($isSchoolYear($formYear)) {
            $detectedSchoolYear = trim($formYear);
        } elseif ($isSchoolYear($blockYear)) {
            $detectedSchoolYear = trim($blockYear);
        } elseif ($isSchoolYear($fallbackYear)) {
            $detectedSchoolYear = trim($fallbackYear);
        }

        $requestedSchoolYear = $requestedSchoolYear !== null && $requestedSchoolYear !== '' ? trim($requestedSchoolYear) : null;
        $studentSchoolYear = ($requestedSchoolYear !== null && $requestedSchoolYear !== '' && $isSchoolYear($requestedSchoolYear))
            ? $requestedSchoolYear
            : $detectedSchoolYear;

        $currentSchoolYear = $fallbackYear !== null && $isSchoolYear($fallbackYear) ? trim((string) $fallbackYear) : null;
        $normalizeYear = fn ($v) => $v !== null ? trim((string) $v) : '';
        $isEnrolledInCurrent = false;
        if ($currentSchoolYear !== null) {
            $cur = $normalizeYear($currentSchoolYear);
            if ($isSchoolYear($userYear) && strcasecmp($normalizeYear($userYear), $cur) === 0) {
                $isEnrolledInCurrent = true;
            }
            if (! $isEnrolledInCurrent && $isSchoolYear($formYear) && strcasecmp($normalizeYear($formYear), $cur) === 0) {
                $isEnrolledInCurrent = true;
            }
            if (! $isEnrolledInCurrent && $isSchoolYear($blockYear) && strcasecmp($normalizeYear($blockYear), $cur) === 0) {
                $isEnrolledInCurrent = true;
            }
        }
        $excludeCurrentYear = $currentSchoolYear !== null && ! $isEnrolledInCurrent;

        $isIrregular = $student->isIrregularType();

        // #region agent log
        $this->dbg('run1', 'H2', 'CorViewService.php:84', 'computed blockIdForQuery inputs', [
            'has_context_block' => $contextBlock !== null,
            'context_block_id' => $contextBlock?->id,
            'student_block_id' => $student->block_id,
            'is_irregular' => $isIrregular,
        ]);
        // #endregion

        $blockIdForQuery = $contextBlock !== null ? $contextBlock->id : $student->block_id;
        $availableSchoolYearsQuery = StudentCorRecord::query()
            ->where(function ($q) use ($student, $blockIdForQuery) {
                $q->where('student_id', $student->id)
                    ->orWhere(function ($q2) use ($blockIdForQuery) {
                        $q2->whereNull('student_id')->where('block_id', $blockIdForQuery);
                    });
            })
            ->when($excludeCurrentYear, fn ($q) => $q->where(function ($qq) use ($currentSchoolYear) {
                $qq->whereNull('school_year')->orWhere('school_year', '!=', $currentSchoolYear);
            }))
            ->whereNotNull('school_year')
            ->where('school_year', '!=', '');
        if ($isIrregular) {
            $availableSchoolYearsQuery->where('cor_source', StudentCorRecord::COR_SOURCE_CREATE_SCHEDULE);
        } else {
            $availableSchoolYearsQuery->where(function ($q) {
                $q->whereNull('cor_source')->orWhere('cor_source', StudentCorRecord::COR_SOURCE_SCHEDULE_BY_PROGRAM);
            });
        }
        $availableSchoolYears = $availableSchoolYearsQuery->distinct()->orderByRaw('school_year DESC')->pluck('school_year')->values()->all();
        if ($detectedSchoolYear !== null && $detectedSchoolYear !== '' && ! in_array($detectedSchoolYear, $availableSchoolYears, true)) {
            $availableSchoolYears = array_values(array_unique(array_merge([$detectedSchoolYear], $availableSchoolYears)));
            rsort($availableSchoolYears);
        }
        if ($excludeCurrentYear && $currentSchoolYear !== null) {
            $availableSchoolYears = array_values(array_filter($availableSchoolYears, fn ($v) => strcasecmp(trim((string) $v), $currentSchoolYear) !== 0));
        }

        $normalize = fn ($v) => preg_replace('/\s+/', ' ', trim((string) ($v ?? '')));
        $same = fn ($a, $b) => ($a === '' && $b === '') || strcasecmp($a, $b) === 0;

        $block = $student->block;
        $program = $normalize($block?->program ?? $student->course);
        $yearLevel = $normalize($block?->year_level ?? $student->year_level);
        $semester = $normalize($block?->semester ?? $student->semester);
        $major = $normalize($student->major);
        $studentSy = $studentSchoolYear !== null && $studentSchoolYear !== '' ? $normalize($studentSchoolYear) : '';
        $scopeSemester = $normalize($block?->semester ?? $student->semester);
        $scopeSchoolYear = $studentSy !== '' ? $studentSy : null;
        $studentProgramId = $block?->program_id;
        $explicitYearChosen = $requestedSchoolYear !== null && $requestedSchoolYear !== '' && $isSchoolYear($requestedSchoolYear);
        $blockIdForCor = $contextBlock !== null ? $contextBlock->id : $student->block_id;

        $corRecords = $this->loadCorRecords($student, $isIrregular, $studentProgramId, $scopeSemester, $scopeSchoolYear, $excludeCurrentYear, $currentSchoolYear, $explicitYearChosen, $blockIdForCor);
        $deployedTemplate = null;
        $corSubjects = [];
        $corFees = [];
        $schoolYearForView = $studentSchoolYear ?? 'N/A';
        $semesterForView = $student->resolved_semester ?? $student->semester ?? 'N/A';
        $programIdForFees = null;
        $academicYearLevelIdForFees = null;

        if ($corRecords->isNotEmpty()) {
            $first = $corRecords->first();
            $schoolYearForView = $first->school_year ?? $schoolYearForView;
            $semesterForView = $first->semester ?? $semesterForView;
            $programIdForFees = $first->program_id;
            $yearLevelNameForFees = $first->year_level !== null && trim((string) $first->year_level) !== ''
                ? trim((string) $first->year_level)
                : ($student->block?->year_level ?? $student->year_level ?? null);
            $academicYearLevelIdForFees = $yearLevelNameForFees
                ? \App\Models\AcademicYearLevel::where('name', $yearLevelNameForFees)->value('id')
                : null;
            foreach ($corRecords->groupBy('subject_id') as $subjectId => $records) {
                $r = $records->first();
                $subject = $r->subject;
                if (! $subject) {
                    continue;
                }
                $start = $r->start_time_snapshot;
                $end = $r->end_time_snapshot;
                if ($start instanceof \Carbon\Carbon) {
                    $start = $start->format('H:i');
                } else {
                    $start = is_string($start) ? trim($start) : '';
                }
                if ($end instanceof \Carbon\Carbon) {
                    $end = $end->format('H:i');
                } else {
                    $end = is_string($end) ? trim($end) : '';
                }
                $scheduleText = trim(implode(' ', array_filter([
                    $r->days_snapshot,
                    $start && $end ? "{$start}-{$end}" : '',
                    $r->room_name_snapshot,
                    $r->professor_name_snapshot ? "({$r->professor_name_snapshot})" : '',
                ]))) ?: 'TBA';
                $corSubjects[] = ['subject' => $subject, 'schedule_text' => $scheduleText];
            }
            $enabledFees = Fee::feesForScope($programIdForFees, $academicYearLevelIdForFees);
            foreach ($enabledFees as $fee) {
                $corFees[] = [
                    'name' => $fee->getDisplayNameAttribute(),
                    'category' => $fee->feeCategory?->name ?? '',
                    'amount' => (float) $fee->amount,
                ];
            }
        } elseif (! $explicitYearChosen && ! $isIrregular) {
            $deployedTemplate = $this->findDeployedTemplate($student, $normalize, $same, $program, $yearLevel, $semester, $studentSy, $major);
            if ($deployedTemplate) {
                $subjectIds = $deployedTemplate->getSubjectIds();
                if (! empty($subjectIds)) {
                    $subjectsOrdered = Subject::query()->whereIn('id', $subjectIds)->get()->keyBy('id');
                    $blockSchedules = ClassSchedule::query()
                        ->with(['subject', 'room', 'professor'])
                        ->where('block_id', $student->block?->id ?? $student->block_id)
                        ->get()
                        ->groupBy('subject_id');
                    foreach ($subjectIds as $sid) {
                        $subject = $subjectsOrdered->get($sid);
                        if (! $subject) {
                            continue;
                        }
                        $scheduleText = 'TBA';
                        $blockSubSchedules = $blockSchedules->get($sid);
                        if ($blockSubSchedules && $blockSubSchedules->isNotEmpty()) {
                            $parts = $blockSubSchedules->map(fn ($s) => sprintf('Day %s %s-%s %s', $s->day_of_week, $s->start_time, $s->end_time, $s->room?->name ?? ''));
                            $scheduleText = $parts->implode('; ');
                        }
                        $corSubjects[] = ['subject' => $subject, 'schedule_text' => $scheduleText];
                    }
                }
                $feeEntries = $deployedTemplate->getFeeEntries();
                if (! empty($feeEntries)) {
                    $programIdForFees = $deployedTemplate->program_id;
                    $academicYearLevelIdForFees = $deployedTemplate->academic_year_level_id;
                    $feeIds = array_column($feeEntries, 'fee_id');
                    $feesById = ($programIdForFees && $academicYearLevelIdForFees)
                        ? Fee::query()->forProgramAndYear($programIdForFees, $academicYearLevelIdForFees)->whereIn('id', $feeIds)->get()->keyBy('id')
                        : Fee::query()->whereIn('id', $feeIds)->get()->keyBy('id');
                    foreach ($feeEntries as $entry) {
                        $fee = $feesById->get($entry['fee_id']);
                        if ($fee) {
                            $amount = isset($entry['amount']) ? (float) $entry['amount'] : (float) $fee->amount;
                            $corFees[] = ['name' => $fee->name, 'category' => $fee->category ?? '', 'amount' => $amount];
                        }
                    }
                }
                $schoolYearForView = $deployedTemplate->school_year ?? $studentSchoolYear ?? 'N/A';
                $semesterForView = $student->resolved_semester ?? $student->semester ?? 'N/A';
            }
        } else {
            $schoolYearForView = $studentSchoolYear ?? 'N/A';
            $semesterForView = $student->resolved_semester ?? $student->semester ?? 'N/A';
        }

        $schedules = collect();
        $schoolYear = $schoolYearForView;
        $semester = $semesterForView;
        $selectedSchoolYear = $studentSchoolYear;
        $noCorForSelectedYear = $explicitYearChosen && $corRecords->isEmpty() && empty($corSubjects);
        $corFromRegistrar = $corRecords->isNotEmpty() && $corRecords->first()->cor_source === StudentCorRecord::COR_SOURCE_CREATE_SCHEDULE;

        // #region agent log
        $this->dbg('run1', 'H3', 'CorViewService.php:buildCorData:exit', 'buildCorData finished', [
            'cor_records_count' => $corRecords->count(),
            'available_school_years_count' => is_array($availableSchoolYears ?? null) ? count($availableSchoolYears) : null,
            'no_cor_for_selected_year' => (bool) $noCorForSelectedYear,
        ]);
        // #endregion

        return compact('student', 'latestResponse', 'schedules', 'deployedTemplate', 'corSubjects', 'corFees', 'schoolYear', 'semester', 'availableSchoolYears', 'selectedSchoolYear', 'noCorForSelectedYear', 'corFromRegistrar');
    }

    private function loadCorRecords(
        User $student,
        bool $isIrregular,
        $studentProgramId,
        string $scopeSemester,
        ?string $scopeSchoolYear,
        bool $excludeCurrentYear,
        ?string $currentSchoolYear,
        bool $explicitYearChosen,
        $blockIdForCor = null
    ): Collection {
        $blockId = $blockIdForCor !== null ? $blockIdForCor : $student->block_id;
        $blockScope = fn ($q) => $q->where(function ($qq) {
            $qq->whereNull('cor_source')->orWhere('cor_source', StudentCorRecord::COR_SOURCE_SCHEDULE_BY_PROGRAM);
        });

        if ($isIrregular) {
            $corRecords = StudentCorRecord::query()
                ->where(function ($q) use ($student, $blockId) {
                    $q->where('student_id', $student->id)
                        ->orWhere(function ($q2) use ($student, $blockId) {
                            $q2->whereNull('student_id')->where('block_id', $blockId);
                        });
                })
                ->where('cor_source', StudentCorRecord::COR_SOURCE_CREATE_SCHEDULE)
                ->when($blockId !== null, fn ($q) => $q->where('block_id', $blockId))
                ->when($studentProgramId !== null, fn ($q) => $q->where('program_id', $studentProgramId))
                ->when($scopeSemester !== '', fn ($q) => $q->where('semester', $scopeSemester))
                ->when($scopeSchoolYear !== null && $scopeSchoolYear !== '', fn ($q) => $q->where('school_year', $scopeSchoolYear))
                ->when($excludeCurrentYear, fn ($q) => $q->where(function ($qq) use ($currentSchoolYear) {
                    $qq->whereNull('school_year')->orWhere('school_year', '!=', $currentSchoolYear);
                }))
                ->with('subject')
                ->orderBy('subject_id')
                ->get();
        } else {
            $corRecords = StudentCorRecord::query()
                ->where('student_id', $student->id)
                ->where('block_id', $blockId)
                ->where($blockScope)
                ->when($studentProgramId !== null, fn ($q) => $q->where('program_id', $studentProgramId))
                ->when($scopeSemester !== '', fn ($q) => $q->where('semester', $scopeSemester))
                ->when($scopeSchoolYear !== null && $scopeSchoolYear !== '', fn ($q) => $q->where('school_year', $scopeSchoolYear))
                ->when($excludeCurrentYear, fn ($q) => $q->where(function ($qq) use ($currentSchoolYear) {
                    $qq->whereNull('school_year')->orWhere('school_year', '!=', $currentSchoolYear);
                }))
                ->with('subject')
                ->orderBy('subject_id')
                ->get();
        }

        if (! $explicitYearChosen && $corRecords->isEmpty() && $scopeSchoolYear !== null && $scopeSchoolYear !== '') {
            if ($isIrregular) {
                $corRecords = StudentCorRecord::query()
                    ->where(function ($q) use ($student, $blockId) {
                        $q->where('student_id', $student->id)
                            ->orWhere(function ($q2) use ($blockId) {
                                $q2->whereNull('student_id')->where('block_id', $blockId);
                            });
                    })
                    ->where('cor_source', StudentCorRecord::COR_SOURCE_CREATE_SCHEDULE)
                    ->when($blockId !== null, fn ($q) => $q->where('block_id', $blockId))
                    ->when($studentProgramId !== null, fn ($q) => $q->where('program_id', $studentProgramId))
                    ->when($scopeSemester !== '', fn ($q) => $q->where('semester', $scopeSemester))
                    ->when($excludeCurrentYear, fn ($q) => $q->where(function ($qq) use ($currentSchoolYear) {
                        $qq->whereNull('school_year')->orWhere('school_year', '!=', $currentSchoolYear);
                    }))
                    ->with('subject')
                    ->orderBy('subject_id')
                    ->get();
            } else {
                $corRecords = StudentCorRecord::query()
                    ->where('student_id', $student->id)
                    ->where('block_id', $blockId)
                    ->where($blockScope)
                    ->when($studentProgramId !== null, fn ($q) => $q->where('program_id', $studentProgramId))
                    ->when($scopeSemester !== '', fn ($q) => $q->where('semester', $scopeSemester))
                    ->when($excludeCurrentYear, fn ($q) => $q->where(function ($qq) use ($currentSchoolYear) {
                        $qq->whereNull('school_year')->orWhere('school_year', '!=', $currentSchoolYear);
                    }))
                    ->with('subject')
                    ->orderBy('subject_id')
                    ->get();
            }
        }

        if (! $explicitYearChosen && $corRecords->isEmpty()) {
            if ($isIrregular) {
                $corRecords = StudentCorRecord::query()
                    ->where(function ($q) use ($student, $blockId) {
                        $q->where('student_id', $student->id)
                            ->orWhere(function ($q2) use ($blockId) {
                                $q2->whereNull('student_id')->where('block_id', $blockId);
                            });
                    })
                    ->where('cor_source', StudentCorRecord::COR_SOURCE_CREATE_SCHEDULE)
                    ->when($blockId !== null, fn ($q) => $q->where('block_id', $blockId))
                    ->when($studentProgramId !== null, fn ($q) => $q->where('program_id', $studentProgramId))
                    ->when($scopeSemester !== '', fn ($q) => $q->where('semester', $scopeSemester))
                    ->when($scopeSchoolYear !== null && $scopeSchoolYear !== '', fn ($q) => $q->where('school_year', $scopeSchoolYear))
                    ->when($excludeCurrentYear, fn ($q) => $q->where(function ($qq) use ($currentSchoolYear) {
                        $qq->whereNull('school_year')->orWhere('school_year', '!=', $currentSchoolYear);
                    }))
                    ->with('subject')
                    ->orderBy('subject_id')
                    ->get();
            } else {
                $corRecords = StudentCorRecord::query()
                    ->where('student_id', $student->id)
                    ->where('block_id', $blockId)
                    ->where($blockScope)
                    ->when($studentProgramId !== null, fn ($q) => $q->where('program_id', $studentProgramId))
                    ->when($excludeCurrentYear, fn ($q) => $q->where(function ($qq) use ($currentSchoolYear) {
                        $qq->whereNull('school_year')->orWhere('school_year', '!=', $currentSchoolYear);
                    }))
                    ->with('subject')
                    ->orderBy('subject_id')
                    ->get();
            }
        }

        if (! $isIrregular && $corRecords->isEmpty() && $blockId !== null) {
            $blockCor = StudentCorRecord::query()
                ->whereNull('student_id')
                ->where('block_id', $blockId)
                ->when($studentProgramId !== null, fn ($q) => $q->where('program_id', $studentProgramId))
                ->when($scopeSemester !== '', fn ($q) => $q->where('semester', $scopeSemester))
                ->when($scopeSchoolYear !== null && $scopeSchoolYear !== '', fn ($q) => $q->where('school_year', $scopeSchoolYear))
                ->when($excludeCurrentYear, fn ($q) => $q->where(function ($qq) use ($currentSchoolYear) {
                    $qq->whereNull('school_year')->orWhere('school_year', '!=', $currentSchoolYear);
                }))
                ->when(true, $blockScope)
                ->with('subject')
                ->orderBy('subject_id')
                ->get();
            if ($blockCor->isEmpty() && $scopeSchoolYear !== null && $scopeSchoolYear !== '') {
                $blockCor = StudentCorRecord::query()
                    ->whereNull('student_id')
                    ->where('block_id', $blockId)
                    ->when($studentProgramId !== null, fn ($q) => $q->where('program_id', $studentProgramId))
                    ->when($scopeSemester !== '', fn ($q) => $q->where('semester', $scopeSemester))
                    ->when($excludeCurrentYear, fn ($q) => $q->where(function ($qq) use ($currentSchoolYear) {
                        $qq->whereNull('school_year')->orWhere('school_year', '!=', $currentSchoolYear);
                    }))
                    ->when(true, $blockScope)
                    ->with('subject')
                    ->orderBy('subject_id')
                    ->get();
            }
            if ($blockCor->isEmpty()) {
                $blockCor = StudentCorRecord::query()
                    ->whereNull('student_id')
                    ->where('block_id', $blockId)
                    ->when($studentProgramId !== null, fn ($q) => $q->where('program_id', $studentProgramId))
                    ->when($excludeCurrentYear, fn ($q) => $q->where(function ($qq) use ($currentSchoolYear) {
                        $qq->whereNull('school_year')->orWhere('school_year', '!=', $currentSchoolYear);
                    }))
                    ->when(true, $blockScope)
                    ->with('subject')
                    ->orderBy('subject_id')
                    ->get();
            }
            if ($blockCor->isNotEmpty()) {
                $corRecords = $blockCor;
            }
        }

        return $corRecords;
    }

    private function findDeployedTemplate(User $student, callable $normalize, callable $same, string $program, string $yearLevel, string $semester, string $studentSy, string $major): ?ScheduleTemplate
    {
        $candidates = ScheduleTemplate::query()
            ->where('is_active', true)
            ->orderByDesc('id')
            ->get();

        return $candidates->first(function ($t) use ($normalize, $same, $program, $yearLevel, $semester, $studentSy, $major, $student) {
            $tProgram = $normalize($t->program);
            $tYearLevel = $normalize($t->year_level);
            $tSemester = $normalize($t->semester);
            $tSy = $normalize($t->school_year);
            $tMajor = $normalize($t->major);
            if (! $same($tProgram, $program)) {
                return false;
            }
            if (! $same($tYearLevel, $yearLevel)) {
                return false;
            }
            if (! $same($tSemester, $semester)) {
                return false;
            }
            if ($studentSy !== '' && ! $same($tSy, $studentSy)) {
                return false;
            }
            if ($tMajor !== '' && ! $same($tMajor, $major)) {
                return false;
            }
            $effectiveBlockId = $student->block?->id ?? $student->block_id;
            if ($t->block_id !== null && $effectiveBlockId !== null && (int) $t->block_id !== (int) $effectiveBlockId) {
                return false;
            }
            return true;
        });
    }
}
