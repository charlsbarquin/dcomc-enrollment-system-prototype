<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\FormResponse;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Centralized enrollment approval: second-semester rule, user update, block assignment,
 * FormResponse update, and Assessment. Used by both Admin and Registrar.
 */
class EnrollmentApprovalService
{
    public function __construct(
        protected BlockAssignmentService $blockAssignmentService
    ) {}

    /**
     * Validate that enrollment to Second Semester is allowed (student was enrolled
     * in First Semester of the same school year, or is Transferee/Returnee).
     *
     * @throws ValidationException when not allowed
     */
    public function validateSecondSemesterEnrollment(
        User $user,
        string $incomingSemester,
        string $enrollmentSchoolYearLabel
    ): void {
        $incomingSem = trim($incomingSemester);
        if (! in_array($incomingSem, ['Second Semester', '2nd Semester'], true)) {
            return;
        }

        $studentType = strtolower(trim((string) ($user->student_type ?? '')));
        if (in_array($studentType, ['transferee', 'returnee'], true)) {
            return;
        }

        $userSy = trim((string) ($user->school_year ?? ''));
        $userSem = trim((string) ($user->semester ?? ''));
        $wasEnrolledFirstSem = $userSy === $enrollmentSchoolYearLabel
            && in_array($userSem, ['First Semester', '1st Semester'], true);

        if (! $wasEnrolledFirstSem) {
            throw ValidationException::withMessages([
                'error' => 'Enrollment to Second Semester requires that the student was enrolled in First Semester of the same school year, or has Student Type Transferee or Returnee.',
            ]);
        }
    }

    /**
     * Approve an enrollment application: update user, assign block, update response, create/update assessment.
     *
     * @return array{success: bool, message?: string}
     */
    public function approve(FormResponse $application): array
    {
        $application->load(['user', 'enrollmentForm', 'preferredBlock', 'assignedBlock']);

        if (! $application->user || ! $application->enrollmentForm) {
            return ['success' => false, 'message' => 'Application has missing user/form reference.'];
        }

        if (
            empty($application->enrollmentForm->incoming_year_level)
            || empty($application->enrollmentForm->incoming_semester)
        ) {
            return ['success' => false, 'message' => 'The form destination year/semester is not configured.'];
        }

        $form = $application->enrollmentForm;
        $user = $application->user;
        $enrollmentSy = preg_match('/^\d{4}-\d{4}$/', trim((string) ($form->assigned_year ?? '')))
            ? trim($form->assigned_year)
            : AcademicCalendarService::getActiveSchoolYearLabel();

        try {
            $this->validateSecondSemesterEnrollment(
                $user,
                $form->incoming_semester,
                $enrollmentSy ?? ''
            );
        } catch (ValidationException $e) {
            return ['success' => false, 'message' => $e->validator->errors()->first('error')];
        }

        $studentType = strtolower(trim((string) ($user->student_type ?? '')));
        $updates = [
            'year_level' => $form->incoming_year_level,
            'semester' => $form->incoming_semester,
            'student_status' => 'Enrolled',
        ];
        if ($enrollmentSy !== null && $enrollmentSy !== '') {
            $updates['school_year'] = $enrollmentSy;
        }
        if (in_array($studentType, ['returnee', 'transferee'], true)) {
            $updates['student_type'] = 'Regular';
            $updates['status_color'] = null;
        }
        $user->update($updates);

        $assignedBlock = $this->blockAssignmentService->assignStudentToBlock(
            $user->fresh(),
            $form->incoming_year_level,
            $form->incoming_semester,
            $application->preferred_block_id
        );

        $application->update([
            'approval_status' => 'approved',
            'process_status' => 'approved',
            'process_notes' => 'Enrollment approved by ' . (Auth::check() ? Auth::user()->name : 'System'),
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'reviewed_by_role' => Auth::check() && method_exists(Auth::user(), 'effectiveRole')
                ? Auth::user()->effectiveRole()
                : null,
            'assigned_block_id' => $assignedBlock?->id,
        ]);

        // One assessment per student per school year per semester.
        Assessment::updateOrCreate(
            [
                'user_id' => $user->id,
                'school_year' => $enrollmentSy,
                'semester' => $form->incoming_semester,
            ],
            [
                'income_classification' => $user->monthly_income ?? null,
                'assessment_status' => 'pending',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]
        );

        return ['success' => true];
    }
}
