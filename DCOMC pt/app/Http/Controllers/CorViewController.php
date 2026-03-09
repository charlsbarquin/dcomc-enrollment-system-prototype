<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\User;
use App\Services\AcademicCalendarService;
use App\Services\CorViewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * COR view/print for staff and registrar: single student COR and print-all COR for a block.
 */
class CorViewController extends Controller
{
    public function __construct(
        protected CorViewService $corView
    ) {}

    /**
     * Ensure the current user can act as staff/registrar for COR.
     */
    protected function authorizeCorAccess(): void
    {
        // #region agent log
        try {
            $payload = [
                'sessionId' => '52ecf9',
                'runId' => 'run2',
                'hypothesisId' => 'H0',
                'location' => 'CorViewController.php:authorizeCorAccess:entry',
                'message' => 'authorizeCorAccess called',
                'data' => [
                    'base_path' => base_path(),
                    'effective_role' => auth()->user()?->effectiveRole(),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ];
            @file_put_contents(base_path('debug-52ecf9.log'), json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
        }
        // #endregion

        $role = auth()->user()?->effectiveRole();
        if (! in_array($role, [User::ROLE_REGISTRAR, User::ROLE_STAFF], true)) {
            abort(403, 'Access denied.');
        }
    }

    /**
     * Back URL to Students Explorer (staff or registrar).
     */
    protected function studentsExplorerUrl(): string
    {
        $role = auth()->user()?->effectiveRole();
        return $role === User::ROLE_STAFF
            ? route('staff.students-explorer')
            : route('registrar.students-explorer');
    }

    /**
     * View or print a single student's COR (staff/registrar).
     */
    public function viewStudentCor(Request $request, User $user): View
    {
        $this->authorizeCorAccess();
        if ($user->role !== User::ROLE_STUDENT) {
            throw new NotFoundHttpException('User is not a student.');
        }
        $student = $user;

        // #region agent log
        try {
            $payload = [
                'sessionId' => '52ecf9',
                'runId' => 'run2',
                'hypothesisId' => 'H5',
                'location' => 'CorViewController.php:viewStudentCor',
                'message' => 'viewStudentCor reached',
                'data' => [
                    'base_path' => base_path(),
                    'student_id' => $student->id,
                    'student_has_block_id' => (bool) $student->block_id,
                    'requested_school_year' => trim($request->string('school_year')->toString()),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ];
            @file_put_contents(base_path('debug-52ecf9.log'), json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
        }
        // #endregion

        if (! $student->block_id) {
            return view('dashboards.student-cor', [
                'student' => $student,
                'latestResponse' => null,
                'schedules' => collect(),
                'deployedTemplate' => null,
                'corSubjects' => [],
                'corFees' => [],
                'schoolYear' => 'N/A',
                'semester' => 'N/A',
                'availableSchoolYears' => [],
                'selectedSchoolYear' => null,
                'noCorForSelectedYear' => false,
                'corFromRegistrar' => false,
                'back_url' => $this->studentsExplorerUrl(),
                'cor_form_action' => auth()->user()?->effectiveRole() === User::ROLE_STAFF
                    ? route('staff.students.cor', $student)
                    : route('registrar.students.cor', $student),
            ]);
        }
        $requestedSchoolYear = trim($request->string('school_year')->toString());
        $data = $this->corView->buildCorData(
            $student,
            $requestedSchoolYear !== '' ? $requestedSchoolYear : null
        );
        $data['back_url'] = $this->studentsExplorerUrl();
        $data['cor_form_action'] = auth()->user()?->effectiveRole() === User::ROLE_STAFF
            ? route('staff.students.cor', $student)
            : route('registrar.students.cor', $student);

        return view('dashboards.student-cor', $data);
    }

    /**
     * Print all CORs for students in a block (one page per student, single print action).
     */
    public function printBlockCors(Block $block): View
    {
        $this->authorizeCorAccess();

        // #region agent log
        try {
            $payload = [
                'sessionId' => '52ecf9',
                'runId' => 'run2',
                'hypothesisId' => 'H6',
                'location' => 'CorViewController.php:printBlockCors:entry',
                'message' => 'printBlockCors reached',
                'data' => [
                    'base_path' => base_path(),
                    'block_id' => $block->id,
                    'block_school_year_label' => (string) ($block->school_year_label ?? ''),
                    'selected_school_year_label' => (string) (AcademicCalendarService::getSelectedSchoolYearLabel() ?? ''),
                    'active_school_year_label' => (string) (AcademicCalendarService::getActiveSchoolYearLabel() ?? ''),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ];
            @file_put_contents(base_path('debug-52ecf9.log'), json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
        }
        // #endregion

        $selectedLabel = AcademicCalendarService::getSelectedSchoolYearLabel();
        if ($selectedLabel !== null && $selectedLabel !== '') {
            $blockLabel = (string) ($block->school_year_label ?? '');
            $activeLabel = AcademicCalendarService::getActiveSchoolYearLabel();
            $match = $blockLabel === $selectedLabel || ($blockLabel === '' && $activeLabel === $selectedLabel);
            if (! $match) {
                abort(404, 'Block not found for the selected school year.');
            }
        }

        $students = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->where(function ($q) use ($block) {
                $q->where('block_id', $block->id)
                    ->orWhereExists(function ($q2) use ($block) {
                        $q2->select(DB::raw(1))
                            ->from('student_block_assignments')
                            ->whereColumn('student_block_assignments.user_id', 'users.id')
                            ->where('student_block_assignments.block_id', $block->id);
                    });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // #region agent log
        try {
            $payload = [
                'sessionId' => '52ecf9',
                'runId' => 'run1',
                'hypothesisId' => 'H4',
                'location' => 'CorViewController.php:printBlockCors',
                'message' => 'printBlockCors students loaded',
                'data' => [
                    'block_id' => $block->id,
                    'selected_label_present' => $selectedLabel !== null && $selectedLabel !== '',
                    'students_count' => $students->count(),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ];
            @file_put_contents(base_path('debug-52ecf9.log'), json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
        }
        // #endregion

        $corDataList = [];
        foreach ($students as $student) {
            $corDataList[] = $this->corView->buildCorData($student, $selectedLabel, $block);
        }

        return view('dashboards.print-all-cor', [
            'block' => $block,
            'corDataList' => $corDataList,
            'back_url' => $this->studentsExplorerUrl(),
        ]);
    }
}
