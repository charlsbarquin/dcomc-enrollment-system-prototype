<?php

namespace App\Services;

use App\Models\AcademicYearLevel;
use App\Models\Block;
use App\Models\ClassSchedule;
use App\Models\Program;
use App\Models\StudentCorRecord;
use App\Models\StudentSubjectCompletion;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Validates irregular student enrollment and block assignment using subject completion history.
 * Prevents retaking passed/credited subjects and same-term duplicate enrollment.
 */
class IrregularEnrollmentValidationService
{
    public const CODE_ALREADY_COMPLETED = 'ALREADY_COMPLETED';
    public const CODE_DUPLICATE_THIS_TERM = 'DUPLICATE_THIS_TERM';
    public const CODE_NOT_IN_CURRICULUM = 'NOT_IN_CURRICULUM';

    /**
     * Check if a student can enroll in a subject for the given term.
     *
     * @return array{0: bool, 1: string|null} [allowed, reason code]
     */
    public function canEnrollInSubject(
        int $studentId,
        int $subjectId,
        string $schoolYear,
        string $semester
    ): array {
        $schoolYear = trim($schoolYear);
        $semester = trim($semester);

        // 1) Already completed (passed/credited)?
        $completed = StudentSubjectCompletion::query()
            ->where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->whereIn('status', StudentSubjectCompletion::completedStatuses())
            ->exists();

        if ($completed) {
            return [false, self::CODE_ALREADY_COMPLETED];
        }

        // 2) Already enrolled this term (any cor_source)?
        $alreadyEnrolled = StudentCorRecord::query()
            ->where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->where('school_year', $schoolYear)
            ->where('semester', $semester)
            ->exists();

        if ($alreadyEnrolled) {
            return [false, self::CODE_DUPLICATE_THIS_TERM];
        }

        return [true, null];
    }

    /**
     * Validate deploy: for each (student, subject) check canEnrollInSubject.
     *
     * @param  array<int>  $studentIds
     * @param  array<int>  $subjectIds
     * @return array{0: bool, 1: array<int, array{student_id: int, subject_id: int, code: string}>}
     */
    public function validateDeployForIrregulars(
        array $studentIds,
        array $subjectIds,
        string $schoolYear,
        string $semester
    ): array {
        $errors = [];
        foreach ($studentIds as $studentId) {
            foreach ($subjectIds as $subjectId) {
                [$allowed, $code] = $this->canEnrollInSubject($studentId, $subjectId, $schoolYear, $semester);
                if (! $allowed && $code !== null) {
                    $errors[] = [
                        'student_id' => $studentId,
                        'subject_id' => $subjectId,
                        'code' => $code,
                    ];
                }
            }
        }

        return [count($errors) === 0, $errors];
    }

    /**
     * Validate that each (student, subject) in a deploy is in the student's curriculum
     * (Subject Settings: program + year level + semester). Uses raw_subject_id so a subject
     * offered in another program (e.g. BCAED) is allowed if it exists in the student's program (e.g. BEED).
     *
     * @param  array<int>  $studentIds
     * @param  array<int>  $subjectIds
     * @return array{0: bool, 1: array<int, array{student_id: int, subject_id: int, code: string}>}
     */
    public function validateDeployCurriculum(
        array $studentIds,
        array $subjectIds,
        string $semester
    ): array {
        $semester = trim($semester);
        if (empty($studentIds) || empty($subjectIds)) {
            return [true, []];
        }

        $students = User::query()
            ->whereIn('id', $studentIds)
            ->get(['id', 'course', 'year_level'])
            ->keyBy('id');

        $programByName = Program::query()->pluck('id', 'program_name')->all();
        $yearLevelByName = AcademicYearLevel::query()->pluck('id', 'name')->all();

        $slotSubjects = Subject::query()
            ->whereIn('id', $subjectIds)
            ->get(['id', 'raw_subject_id'])
            ->keyBy('id');

        $errors = [];
        foreach ($studentIds as $studentId) {
            $student = $students->get($studentId);
            if (! $student) {
                continue;
            }
            $course = trim((string) ($student->course ?? ''));
            $yearLevelName = trim((string) ($student->year_level ?? ''));
            $programId = $programByName[$course] ?? null;
            $yearLevelId = $yearLevelByName[$yearLevelName] ?? null;

            foreach ($subjectIds as $subjectId) {
                $slotSubj = $slotSubjects->get($subjectId);
                if (! $slotSubj) {
                    continue;
                }
                $inCurriculum = false;
                if ($programId !== null && $yearLevelId !== null) {
                    $inCurriculum = Subject::query()
                        ->where('program_id', $programId)
                        ->where('academic_year_level_id', $yearLevelId)
                        ->where('semester', $semester)
                        ->where('is_active', true)
                        ->where(function ($q) use ($slotSubj) {
                            $q->where('id', $slotSubj->id);
                            if ($slotSubj->raw_subject_id !== null) {
                                $q->orWhere('raw_subject_id', $slotSubj->raw_subject_id);
                            }
                        })
                        ->exists();
                }
                if (! $inCurriculum) {
                    $errors[] = [
                        'student_id' => $studentId,
                        'subject_id' => $subjectId,
                        'code' => self::CODE_NOT_IN_CURRICULUM,
                    ];
                }
            }
        }

        return [count($errors) === 0, $errors];
    }

    /**
     * Validate block assignment for an irregular: block must not contain subjects the student already completed.
     * Uses block's school_year_label and semester; resolves subjects from ClassSchedule for that block/term.
     *
     * @return array{0: bool, 1: string|null} [allowed, message]
     */
    public function validateBlockAssignmentForIrregular(int $studentId, int $blockId): array
    {
        $block = Block::find($blockId);
        if (! $block) {
            return [false, 'Block not found.'];
        }

        $schoolYear = trim((string) ($block->school_year_label ?? ''));
        $semester = trim((string) ($block->semester ?? ''));

        // Subjects offered in this block for this term (from ClassSchedule)
        $subjectIds = ClassSchedule::query()
            ->where('block_id', $blockId)
            ->when($schoolYear !== '', fn ($q) => $q->where('school_year', $schoolYear))
            ->when($semester !== '', fn ($q) => $q->where('semester', $semester))
            ->distinct()
            ->pluck('subject_id')
            ->filter()
            ->values()
            ->all();

        if (empty($subjectIds)) {
            // No subjects in schedule for this term — allow assignment (block may be empty or use COR archive only)
            return [true, null];
        }

        $completedSubjectIds = [];
        foreach ($subjectIds as $subjectId) {
            [$allowed, $code] = $this->canEnrollInSubject($studentId, (int) $subjectId, $schoolYear, $semester);
            if (! $allowed && $code === self::CODE_ALREADY_COMPLETED) {
                $completedSubjectIds[] = (int) $subjectId;
            }
        }

        if (count($completedSubjectIds) > 0) {
            $names = \App\Models\Subject::query()
                ->whereIn('id', $completedSubjectIds)
                ->pluck('title')
                ->all();

            return [
                false,
                'This block includes subject(s) the student has already completed: ' . implode(', ', $names) . '. Assign to a different block or remove those subjects from the block.',
            ];
        }

        return [true, null];
    }

    /**
     * Format deploy validation errors for user-facing message (student names, subject codes).
     */
    public function formatDeployErrorsForMessage(array $errors): string
    {
        if (empty($errors)) {
            return '';
        }

        $studentIds = array_unique(array_column($errors, 'student_id'));
        $subjectIds = array_unique(array_column($errors, 'subject_id'));
        $students = \App\Models\User::query()->whereIn('id', $studentIds)->get()->keyBy('id');
        $subjects = \App\Models\Subject::query()->whereIn('id', $subjectIds)->get()->keyBy('id');

        $lines = [];
        $byCode = [
            self::CODE_ALREADY_COMPLETED => 'already completed',
            self::CODE_DUPLICATE_THIS_TERM => 'already enrolled this term',
            self::CODE_NOT_IN_CURRICULUM => 'not in student\'s program curriculum (check Subject Settings: program, year, semester)',
        ];
        foreach ($errors as $err) {
            $name = $students->get($err['student_id'])?->name ?? 'Student #' . $err['student_id'];
            $subj = $subjects->get($err['subject_id'])?->code ?? $subjects->get($err['subject_id'])?->title ?? 'Subject #' . $err['subject_id'];
            $reason = $byCode[$err['code']] ?? $err['code'];
            $lines[] = "{$name} – {$subj} ({$reason})";
        }

        return 'Cannot deploy: ' . implode('; ', array_slice($lines, 0, 10)) . (count($lines) > 10 ? ' (and ' . (count($lines) - 10) . ' more)' : '');
    }
}
