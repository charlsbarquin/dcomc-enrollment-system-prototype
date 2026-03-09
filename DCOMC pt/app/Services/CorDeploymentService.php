<?php

namespace App\Services;

use App\Models\AcademicYearLevel;
use App\Models\Block;
use App\Models\ScopeScheduleSlot;
use App\Models\StudentCorRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * COR Deployment: validate schedule, build immutable snapshots, deploy to students.
 * Archive is read-only; deployed data must not change when schedule is edited later.
 */
class CorDeploymentService
{
    public static function dayName(int $dayOfWeek): string
    {
        $days = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
        return $days[$dayOfWeek] ?? 'Day ' . $dayOfWeek;
    }

    /**
     * Validate that schedule is complete for deployment.
     * Returns null if valid; otherwise array of error messages.
     *
     * @return array<string>|null
     */
    public function validateForDeployment(
        int $programId,
        int $academicYearLevelId,
        ?int $blockId,
        ?string $shift,
        string $semester,
        string $schoolYear
    ): ?array {
        $errors = [];

        $slots = ScopeScheduleSlot::query()
            ->where('program_id', $programId)
            ->where('academic_year_level_id', $academicYearLevelId)
            ->where('semester', $semester)
            ->where(function ($q) use ($schoolYear) {
                $q->where('school_year', $schoolYear)->orWhereNull('school_year');
            });
        if ($blockId !== null) {
            $slots->where(function ($q) use ($blockId) {
                $q->where('block_id', $blockId)->orWhereNull('block_id');
            });
        }
        if ($shift !== null && $shift !== '') {
            $slots->where(function ($q) use ($shift) {
                $q->where('shift', $shift)->orWhereNull('shift');
            });
        }
        $slots = $slots->with(['subject', 'room', 'professor'])->get();

        $bySubject = $slots->groupBy('subject_id');
        foreach ($bySubject as $subjectId => $subjectSlots) {
            $first = $subjectSlots->first();
            $subject = $first->subject;
            $code = $subject ? $subject->code : "Subject #{$subjectId}";
            foreach ($subjectSlots as $slot) {
                if (empty($slot->professor_id)) {
                    $errors[] = "Subject {$code}: professor is not assigned for one or more days.";
                    break;
                }
                if (empty($slot->start_time) || empty($slot->end_time)) {
                    $errors[] = "Subject {$code}: start/end time is missing.";
                    break;
                }
                if (empty($slot->room_id)) {
                    $errors[] = "Subject {$code}: room is not assigned for one or more days.";
                    break;
                }
            }
        }

        if ($slots->isEmpty()) {
            $errors[] = 'No schedule slots found for this Program, Year Level, Block, Shift, Semester, and School Year.';
        }

        return $errors === [] ? null : $errors;
    }

    /**
     * Build snapshot rows (one per subject) from scope slots: professor name, room name, days, time.
     *
     * @param \Illuminate\Support\Collection<int, ScopeScheduleSlot> $slots
     * @return array<int, array{subject_id: int, professor_id: ?int, professor_name_snapshot: string, room_name_snapshot: string, days_snapshot: string, start_time_snapshot: string, end_time_snapshot: string, is_overload: bool}>
     */
    public function buildSnapshotFromSlots($slots): array
    {
        $bySubject = $slots->groupBy('subject_id');
        $snapshots = [];
        $workload = app(\App\Services\ProfessorWorkloadService::class);
        foreach ($bySubject as $subjectId => $subjectSlots) {
            $first = $subjectSlots->sortBy('day_of_week')->first();
            $profName = $first->professor ? $first->professor->name : '';
            $professorId = $first->professor_id ?? null;
            // Use stored is_overload if set; otherwise infer from permanent + outside 8-5.
            $isOverload = (bool) ($first->is_overload ?? false);
            if (!$isOverload && $first->professor) {
                try {
                    $isOverload = $workload->getEmploymentType($first->professor) === \App\Services\ProfessorWorkloadService::EMPLOYMENT_PERMANENT
                        && !$workload->isWithinStandardHours($first->start_time ?? '08:00', $first->end_time ?? '09:00');
                } catch (\Throwable $e) {
                    // ignore
                }
            }
            if ($isOverload) {
                $profName = $profName . ' (OVERLOAD)';
            }
            $roomName = $first->room ? ($first->room->code ?? $first->room->name ?? '') : '';
            $days = $subjectSlots->sortBy('day_of_week')->map(fn ($s) => self::dayName((int) $s->day_of_week))->unique()->values()->implode(', ');
            $start = $first->start_time ? (\Carbon\Carbon::parse($first->start_time)->format('H:i') ?? '') : '';
            $end = $first->end_time ? (\Carbon\Carbon::parse($first->end_time)->format('H:i') ?? '') : '';
            $snapshots[(int) $subjectId] = [
                'subject_id' => (int) $subjectId,
                'professor_id' => $professorId,
                'professor_name_snapshot' => $profName,
                'room_name_snapshot' => $roomName,
                'days_snapshot' => $days,
                'start_time_snapshot' => $start,
                'end_time_snapshot' => $end,
                'is_overload' => $isOverload,
            ];
        }
        return $snapshots;
    }

    /**
     * Deploy COR: fetch students for the scope, create immutable student_cor_records.
     *
     * @return array{success: bool, message: string, students_count?: int, records_count?: int}
     */
    public function deploy(
        int $programId,
        int $academicYearLevelId,
        ?int $blockId,
        ?string $shift,
        string $semester,
        string $schoolYear,
        int $deployedByUserId
    ): array {
        $errors = $this->validateForDeployment($programId, $academicYearLevelId, $blockId, $shift, $semester, $schoolYear);
        if ($errors !== null) {
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        $students = $this->fetchStudentsForScope($programId, $academicYearLevelId, $blockId, $shift, $semester, $schoolYear);
        $permissiveFallbackUsed = false;
        if ($students->isEmpty() && $blockId !== null) {
            // Try a permissive fallback: select students by block_id if strict scope returned none
            $fallbackStudents = User::query()->where('role', 'student')->where('block_id', $blockId)->get();
            if ($fallbackStudents->isNotEmpty()) {
                $students = $fallbackStudents;
                $permissiveFallbackUsed = true;
            }
        }
        $creatingArchiveOnly = $students->isEmpty();

        $slots = ScopeScheduleSlot::query()
            ->where('program_id', $programId)
            ->where('academic_year_level_id', $academicYearLevelId)
            ->where('semester', $semester)
            ->where(function ($q) use ($schoolYear) {
                $q->where('school_year', $schoolYear)->orWhereNull('school_year');
            });
        if ($blockId !== null) {
            $slots->where(function ($q) use ($blockId) {
                $q->where('block_id', $blockId)->orWhereNull('block_id');
            });
        }
        if ($shift !== null && $shift !== '') {
            $slots->where(function ($q) use ($shift) {
                $q->where('shift', $shift)->orWhereNull('shift');
            });
        }
        $slots = $slots->with(['subject', 'room', 'professor'])->get();

        $yearLevelModel = AcademicYearLevel::find($academicYearLevelId);
        $yearLevelName = $yearLevelModel ? $yearLevelModel->name : '';

        $snapshots = $this->buildSnapshotFromSlots($slots);

        $now = now();
        $records = [];
        if ($creatingArchiveOnly) {
            // Create one set of snapshot rows without student_id so the archive shows the deployed schedule
            foreach ($snapshots as $snap) {
                $records[] = [
                    'student_id' => null,
                    'subject_id' => $snap['subject_id'],
                    'professor_id' => $snap['professor_id'] ?? null,
                    'is_overload' => $snap['is_overload'] ?? false,
                    'professor_name_snapshot' => $snap['professor_name_snapshot'],
                    'room_name_snapshot' => $snap['room_name_snapshot'],
                    'days_snapshot' => $snap['days_snapshot'],
                    'start_time_snapshot' => $snap['start_time_snapshot'],
                    'end_time_snapshot' => $snap['end_time_snapshot'],
                    'program_id' => $programId,
                    'year_level' => $yearLevelName,
                    'block_id' => $blockId,
                    'shift' => $shift,
                    'semester' => $semester,
                    'school_year' => $schoolYear,
                    'cor_source' => \App\Models\StudentCorRecord::COR_SOURCE_SCHEDULE_BY_PROGRAM,
                    'deployed_by' => $deployedByUserId,
                    'deployed_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        } else {
            foreach ($students as $student) {
                foreach ($snapshots as $snap) {
                    $records[] = [
                        'student_id' => $student->id,
                        'subject_id' => $snap['subject_id'],
                        'professor_id' => $snap['professor_id'] ?? null,
                        'is_overload' => $snap['is_overload'] ?? false,
                        'professor_name_snapshot' => $snap['professor_name_snapshot'],
                        'room_name_snapshot' => $snap['room_name_snapshot'],
                        'days_snapshot' => $snap['days_snapshot'],
                        'start_time_snapshot' => $snap['start_time_snapshot'],
                        'end_time_snapshot' => $snap['end_time_snapshot'],
                        'program_id' => $programId,
                        'year_level' => $yearLevelName,
                        'block_id' => $blockId,
                        'shift' => $shift,
                        'semester' => $semester,
                        'school_year' => $schoolYear,
                        'cor_source' => \App\Models\StudentCorRecord::COR_SOURCE_SCHEDULE_BY_PROGRAM,
                        'deployed_by' => $deployedByUserId,
                        'deployed_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        DB::transaction(function () use ($programId, $academicYearLevelId, $yearLevelName, $blockId, $shift, $semester, $schoolYear, $records) {
            StudentCorRecord::query()
                ->where('program_id', $programId)
                ->where('year_level', $yearLevelName)
                ->where('semester', $semester)
                ->where('school_year', $schoolYear)
                ->where(function ($q) {
            $q->whereNull('cor_source')->orWhere('cor_source', \App\Models\StudentCorRecord::COR_SOURCE_SCHEDULE_BY_PROGRAM);
        })
                ->when($blockId !== null, fn ($q) => $q->where('block_id', $blockId))
                ->when($shift !== null && $shift !== '', fn ($q) => $q->where('shift', $shift))
                ->delete();
            if ($records !== []) {
                StudentCorRecord::insert($records);
            }

            // Clear the working schedule so the dean gets a blank table for the next block
            $slotDelete = ScopeScheduleSlot::query()
                ->where('program_id', $programId)
                ->where('academic_year_level_id', $academicYearLevelId)
                ->where('semester', $semester);
            if ($schoolYear !== null && $schoolYear !== '') {
                $slotDelete->where(function ($q) use ($schoolYear) {
                    $q->where('school_year', $schoolYear)->orWhereNull('school_year');
                });
            }
            $slotDelete->delete();
        });

        $recordsCount = count($records);
        
        if ($recordsCount === 0) {
            Log::info('COR_DEPLOY_ZERO_RECORDS', ['program_id' => $programId, 'block_id' => $blockId, 'students_count' => $students->count()]);
            return [
                'success' => false,
                'message' => 'No COR records were created. Ensure the block has students and the schedule has at least one subject with professor and time assigned.',
            ];
        }

        Log::info('COR_DEPLOY_SUCCESS', ['program_id' => $programId, 'block_id' => $blockId, 'records_count' => $recordsCount]);
        $msg = 'COR deployed and archived successfully. The schedule has been cleared — you can now build a new schedule for the next block.';
        if ($permissiveFallbackUsed) {
            $msg = 'COR deployed using permissive student fallback (students selected by block_id). ' . $msg;
        }
        return [
            'success' => true,
            'message' => $msg,
            'students_count' => $students->count(),
            'records_count' => $recordsCount,
        ];
    }

    /**
     * Archive-only deploy: create archive snapshot rows without assigning to students.
     * This bypasses the strict validation that requires every subject to have professor/time.
     * Use when the dean wants to save the working schedule into COR Archive even if some
     * students are not yet assigned or some subjects are missing professor/time.
     *
     * @return array{success: bool, message: string, records_count?: int}
     */
    public function deployArchiveOnly(
        int $programId,
        int $academicYearLevelId,
        ?int $blockId,
        ?string $shift,
        string $semester,
        string $schoolYear,
        int $deployedByUserId
    ): array {
        $slots = ScopeScheduleSlot::query()
            ->where('program_id', $programId)
            ->where('academic_year_level_id', $academicYearLevelId)
            ->where('semester', $semester)
            ->where(function ($q) use ($schoolYear) {
                $q->where('school_year', $schoolYear)->orWhereNull('school_year');
            });
        if ($blockId !== null) {
            $slots->where(function ($q) use ($blockId) {
                $q->where('block_id', $blockId)->orWhereNull('block_id');
            });
        }
        if ($shift !== null && $shift !== '') {
            $slots->where(function ($q) use ($shift) {
                $q->where('shift', $shift)->orWhereNull('shift');
            });
        }
        $slots = $slots->with(['subject', 'room', 'professor'])->get();

        if ($slots->isEmpty()) {
            return ['success' => false, 'message' => 'No schedule slots found to archive for this scope.'];
        }

        $yearLevelModel = AcademicYearLevel::find($academicYearLevelId);
        $yearLevelName = $yearLevelModel ? $yearLevelModel->name : '';

        $snapshots = $this->buildSnapshotFromSlots($slots);

        $now = now();
        $records = [];
        foreach ($snapshots as $snap) {
            $records[] = [
                'student_id' => null,
                'subject_id' => $snap['subject_id'],
                'professor_id' => $snap['professor_id'] ?? null,
                'is_overload' => $snap['is_overload'] ?? false,
                'professor_name_snapshot' => $snap['professor_name_snapshot'],
                'room_name_snapshot' => $snap['room_name_snapshot'],
                'days_snapshot' => $snap['days_snapshot'],
                'start_time_snapshot' => $snap['start_time_snapshot'],
                'end_time_snapshot' => $snap['end_time_snapshot'],
                'program_id' => $programId,
                'year_level' => $yearLevelName,
                'block_id' => $blockId,
                'shift' => $shift,
                'semester' => $semester,
                'school_year' => $schoolYear,
                'cor_source' => \App\Models\StudentCorRecord::COR_SOURCE_SCHEDULE_BY_PROGRAM,
                'deployed_by' => $deployedByUserId,
                'deployed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::transaction(function () use ($programId, $yearLevelName, $semester, $schoolYear, $blockId, $shift, $records, $academicYearLevelId) {
            $archiveDelete = StudentCorRecord::query()
                ->where('program_id', $programId)
                ->where('year_level', $yearLevelName)
                ->where('semester', $semester)
                ->where(function ($q) {
                    $q->whereNull('cor_source')->orWhere('cor_source', \App\Models\StudentCorRecord::COR_SOURCE_SCHEDULE_BY_PROGRAM);
                })
                ->when($blockId !== null, fn ($q) => $q->where('block_id', $blockId))
                ->when($shift !== null && $shift !== '', fn ($q) => $q->where('shift', $shift));
            if ($schoolYear !== null && $schoolYear !== '') {
                $archiveDelete->where('school_year', $schoolYear);
            } else {
                $archiveDelete->where(function ($q) {
                    $q->whereNull('school_year')->orWhere('school_year', '');
                });
            }
            $archiveDelete->delete();
            if ($records !== []) {
                StudentCorRecord::insert($records);
            }

            // Clear the working schedule so the dean gets a blank table for the next block
            $slotDelete = ScopeScheduleSlot::query()
                ->where('program_id', $programId)
                ->where('academic_year_level_id', $academicYearLevelId)
                ->where('semester', $semester);
            if ($schoolYear !== null && $schoolYear !== '') {
                $slotDelete->where(function ($q) use ($schoolYear) {
                    $q->where('school_year', $schoolYear)->orWhereNull('school_year');
                });
            }
            $slotDelete->delete();
        });

        $recordsCount = count($records);
        return [
            'success' => true,
            'message' => 'Archive saved successfully.' . ($recordsCount > 0 ? " Records: {$recordsCount}" : ''),
            'records_count' => $recordsCount,
        ];
    }

    /**
     * Fetch students: program, year level, block, semester, school year, status active.
     */
    public function fetchStudentsForScope(
        int $programId,
        int $academicYearLevelId,
        ?int $blockId,
        ?string $shift,
        string $semester,
        string $schoolYear
    ) {
        $yearLevel = \App\Models\AcademicYearLevel::find($academicYearLevelId);
        $yearLevelName = $yearLevel ? $yearLevel->name : '';

        $query = User::query()
            ->where('role', 'student')
            ->where(function ($q) {
                $q->whereNull('student_status')->orWhere('student_status', 'active')->orWhere('student_status', '')->orWhere('student_status', 'Enrolled')->orWhere('student_status', 'Regular');
            });

        if ($blockId !== null) {
            $block = Block::find($blockId);
            if (!$block || (int) $block->program_id !== $programId) {
                return collect();
            }
            if ($yearLevelName !== '' && strcasecmp(trim((string)$block->year_level), trim((string)$yearLevelName)) !== 0) {
                return collect();
            }
            if (strcasecmp(trim((string)$block->semester), trim((string)$semester)) !== 0) {
                return collect();
            }
            if ($schoolYear !== '' && $block->school_year_label !== null && strcasecmp(trim((string)$block->school_year_label), trim((string)$schoolYear)) !== 0) {
                return collect();
            }
            if ($shift !== null && $shift !== '' && strcasecmp(trim((string)$block->shift), trim((string)$shift)) !== 0) {
                return collect();
            }
            $query->where('block_id', $blockId);
            return $query->get();
        }

        $blockIds = Block::query()
            ->where('program_id', $programId)
            ->whereRaw('LOWER(TRIM(year_level)) = ?', [strtolower(trim((string)$yearLevelName))])
            ->whereRaw('LOWER(TRIM(semester)) = ?', [strtolower(trim((string)$semester))]);
        if ($schoolYear !== '') {
            $blockIds->where(function ($q) use ($schoolYear) {
                $q->whereRaw('LOWER(TRIM(school_year_label)) = ?', [strtolower(trim((string)$schoolYear))])->orWhereNull('school_year_label');
            });
        }
        if ($shift !== null && $shift !== '') {
            $blockIds->whereRaw('LOWER(TRIM(shift)) = ?', [strtolower(trim((string)$shift))]);
        }
        $blockIds = $blockIds->pluck('id')->all();
        if ($blockIds === []) {
            return collect();
        }
        $query->whereIn('block_id', $blockIds);
        return $query->get();
    }
}
