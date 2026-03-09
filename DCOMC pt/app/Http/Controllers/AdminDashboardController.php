<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Block;
use App\Models\ClassSchedule;
use App\Models\EnrollmentForm;
use App\Models\FormResponse;
use App\Models\User;
use App\Services\AcademicCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Cockpit dashboard for admin, registrar, staff, unifast, and dean.
     *
     * - Each role has its own URL and own UI: which sidebar and which links (e.g. admin.student-status
     *   vs registrar.student-status) are used is determined only by the current route name, not by
     *   the logged-in user. So dashboards stay static per role; no sharing of one dashboard between roles.
     * - Dashboard data (counts, lists, charts) is the same for every role: no filtering by current user
     *   or role. Everyone sees the same enrollment/process data.
     */
    public function index(Request $request): View
    {
        $academicYear = $request->string('academic_year')->toString();
        $semester = $request->string('semester')->toString();

        $academicYears = EnrollmentForm::query()
            ->whereNotNull('assigned_year')
            ->distinct()
            ->orderByDesc('assigned_year')
            ->pluck('assigned_year');

        $semesters = EnrollmentForm::query()
            ->whereNotNull('assigned_semester')
            ->distinct()
            ->orderBy('assigned_semester')
            ->pluck('assigned_semester');

        $base = FormResponse::query()->with(['user', 'enrollmentForm']);

        if ($academicYear !== '') {
            $base->whereHas('enrollmentForm', fn ($f) => $f->where('assigned_year', $academicYear));
        } else {
            $base->forSelectedSchoolYear();
        }

        if ($semester !== '') {
            $base->whereHas('enrollmentForm', fn ($f) => $f->where('assigned_semester', $semester));
        }

        $qaCounts = [
            'approved' => (clone $base)->where('process_status', 'approved')->count(),
            'scheduled' => (clone $base)->where('process_status', 'scheduled')->count(),
            'completed' => (clone $base)->where('process_status', 'completed')->count(),
        ];

        $approvedCount = (clone $base)->where('approval_status', 'approved')->count();
        $pendingCount = (clone $base)->where('approval_status', 'pending')->count();
        $rejectedCount = (clone $base)->where('approval_status', 'rejected')->count();
        $notApprovedCount = $pendingCount + $rejectedCount;

        $selectedLabel = $academicYear !== '' ? $academicYear : AcademicCalendarService::getSelectedSchoolYearLabel();
        $studentsPopulation = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->when($selectedLabel, fn ($q) => $q->where('school_year', $selectedLabel))
            ->count();

        $programBreakdown = (clone $base)
            ->selectRaw('users.course as label, COUNT(form_responses.id) as count')
            ->join('users', 'users.id', '=', 'form_responses.user_id')
            ->groupBy('users.course')
            ->orderByDesc('count')
            ->get();

        $daragaCount = (clone $base)
            ->whereHas('user', fn ($q) => $q->whereRaw('LOWER(COALESCE(municipality, "")) LIKE ?', ['%daraga%']))
            ->count();
        $legazpiCount = (clone $base)
            ->whereHas('user', fn ($q) => $q->whereRaw('LOWER(COALESCE(municipality, "")) LIKE ?', ['%legazpi%']))
            ->count();
        $guinobatanCount = (clone $base)
            ->whereHas('user', fn ($q) => $q->whereRaw('LOWER(COALESCE(municipality, "")) LIKE ?', ['%guinobatan%']))
            ->count();

        $locationCounts = [
            'Daraga' => $daragaCount,
            'Legazpi' => $legazpiCount,
            'Guinobatan' => $guinobatanCount,
        ];

        $trendRows = (clone $base)
            ->select(['created_at'])
            ->get()
            ->groupBy(fn ($item) => optional($item->created_at)->format('Y-m'))
            ->map(fn ($rows, $period) => (object) ['period' => $period ?: 'N/A', 'count' => $rows->count()])
            ->sortBy('period')
            ->values();

        $totalEnrollees = (clone $base)->count();
        $userIds = $totalEnrollees > 0 ? (clone $base)->pluck('user_id')->unique()->filter()->values()->all() : [];

        $studentTypeBreakdown = (clone $base)
            ->join('users', 'users.id', '=', 'form_responses.user_id')
            ->selectRaw("COALESCE(NULLIF(TRIM(users.student_type), ''), 'Not set') as label, COUNT(form_responses.id) as count")
            ->groupBy('users.student_type')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => (object) ['label' => $row->label, 'count' => $row->count]);

        $genderBreakdown = (clone $base)
            ->join('users', 'users.id', '=', 'form_responses.user_id')
            ->selectRaw("COALESCE(NULLIF(TRIM(users.gender), ''), 'Not set') as label, COUNT(form_responses.id) as count")
            ->groupBy('users.gender')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => (object) ['label' => $row->label, 'count' => $row->count]);

        $financialBreakdown = collect();
        if (!empty($userIds)) {
            $financialBreakdown = Assessment::query()
                ->whereIn('user_id', $userIds)
                ->selectRaw('COALESCE(NULLIF(TRIM(income_classification), ""), "Not classified") as label, COUNT(*) as count')
                ->groupBy('income_classification')
                ->orderByDesc('count')
                ->get();
        }
        if ($financialBreakdown->isEmpty() && !empty($userIds)) {
            $financialBreakdown = User::query()
                ->whereIn('id', $userIds)
                ->selectRaw('COALESCE(NULLIF(TRIM(monthly_income), ""), "Not set") as label, COUNT(*) as count')
                ->groupBy('monthly_income')
                ->orderByDesc('count')
                ->get();
        }

        $blockYear = $academicYear !== '' ? $academicYear : AcademicCalendarService::getSelectedSchoolYearLabel();
        $blockQuery = Block::query()->withCount('students');
        if ($blockYear !== null && $blockYear !== '') {
            $blockQuery->where('school_year_label', $blockYear);
        }
        if ($semester !== '') {
            $blockQuery->where('semester', $semester);
        }
        $blockRows = $blockQuery->orderBy('program')->orderBy('year_level')->orderBy('code')->get()->map(function (Block $block) {
            $cap = (int) ($block->max_capacity ?? $block->capacity ?? $block->max_students ?? 50);
            $count = (int) $block->students_count;
            return (object) [
                'code' => $block->code,
                'program' => $block->program ?? $block->name,
                'year_level' => $block->year_level,
                'student_count' => $count,
                'capacity' => $cap,
                'status' => $cap > 0 && $count >= $cap ? 'Filled' : 'Not filled',
            ];
        });

        $blockRadarData = $blockRows->groupBy('program')->map(fn ($rows) => $rows->sum('student_count'))->sortDesc()->take(12)->all();
        $blockRadarLabels = array_keys($blockRadarData);
        $blockRadarValues = array_values($blockRadarData);

        $completedCount = (int) ($qaCounts['completed'] ?? 0);
        $enrollmentCompletionPct = $totalEnrollees > 0 ? round(100 * $completedCount / $totalEnrollees, 1) : 0;

        $totalBlocks = Block::query()->when($blockYear, fn ($q) => $q->where('school_year_label', $blockYear))->when($semester !== '', fn ($q) => $q->where('semester', $semester))->count();
        $blocksWithSchedules = $totalBlocks > 0 ? ClassSchedule::query()
            ->whereHas('block', function ($q) use ($blockYear, $semester) {
                if ($blockYear) $q->where('school_year_label', $blockYear);
                if ($semester !== '') $q->where('semester', $semester);
            })
            ->distinct()
            ->count('block_id') : 0;
        $testPlanCoveragePct = $totalBlocks > 0 ? round(100 * $blocksWithSchedules / $totalBlocks, 1) : 0;

        $facultyRows = User::query()->whereNotNull('faculty_type')->get();
        $facultyTotal = $facultyRows->count();
        $facultyOverloadCount = $facultyRows->filter(function (User $f) {
            $units = (int) $f->teachingSchedules()->with('subject')->get()->sum(fn ($s) => (int) ($s->subject?->units ?? 0));
            $max = (int) ($f->max_units ?? ($f->faculty_type === 'permanent' ? 24 : 0));
            return $max > 0 && $units > $max;
        })->count();
        $overloadOkPct = $facultyTotal > 0 ? round(100 * ($facultyTotal - $facultyOverloadCount) / $facultyTotal, 1) : 100;

        $radarMetrics = [
            'Enrollment Completion' => min(100, max(0, $enrollmentCompletionPct)),
            'Test Plan Coverage' => min(100, max(0, $testPlanCoveragePct)),
            'Consistency Check Impact' => 100,
            'Overload/Underload OK' => min(100, max(0, $overloadOkPct)),
        ];
        $radarTooltips = [
            'Enrollment Completion' => sprintf('Out of %s total enrollees, %s have completed enrollment.', number_format($totalEnrollees), number_format($completedCount)),
            'Test Plan Coverage' => sprintf('%s of %s blocks have at least one scheduled subject.', number_format($blocksWithSchedules), number_format($totalBlocks)),
            'Consistency Check Impact' => 'Programs following consistency checks.',
            'Overload/Underload OK' => sprintf('%s of %s faculty are not overloaded.', $facultyTotal - $facultyOverloadCount, $facultyTotal),
        ];

        $filters = [
            'academic_year' => $academicYear,
            'semester' => $semester,
        ];

        // Which role's dashboard this is is fixed by the route only (e.g. registrar.dashboard → registrar).
        // Never use auth()->user()->role here: each role has its own route and keeps its own static dashboard.
        $routeName = $request->route()?->getName() ?? 'admin.dashboard';
        $prefix = match (true) {
            str_starts_with($routeName, 'admin.') => 'admin',
            str_starts_with($routeName, 'registrar.') => 'registrar',
            str_starts_with($routeName, 'staff.') => 'staff',
            str_starts_with($routeName, 'unifast.') => 'unifast',
            str_starts_with($routeName, 'dean.') => 'dean',
            default => 'admin',
        };

        $routes = [
            'dashboard' => $prefix . '.dashboard',
            'reports' => $prefix . '.reports',
            'student_status' => $prefix . '.student-status',
        ];
        $routes['analytics'] = in_array($prefix, ['admin', 'registrar', 'staff'], true)
            ? $prefix . '.analytics'
            : $prefix . '.reports';

        $sidebar = $prefix . '-sidebar';
        $isAdmin = $prefix === 'admin';

        $recentApplications = $prefix === 'registrar'
            ? (clone $base)->with(['user', 'enrollmentForm'])->latest()->limit(20)->get()
            : collect();
        $feedbackCount = \App\Models\Feedback::count();
        $totalDisbursement = 0; // UniFAST cockpit summary card; can be wired to disbursement data later

        return view('dashboards.cockpit', compact(
            'approvedCount',
            'pendingCount',
            'rejectedCount',
            'notApprovedCount',
            'studentsPopulation',
            'programBreakdown',
            'locationCounts',
            'trendRows',
            'academicYears',
            'semesters',
            'filters',
            'routes',
            'sidebar',
            'isAdmin',
            'studentTypeBreakdown',
            'genderBreakdown',
            'financialBreakdown',
            'blockRows',
            'blockRadarLabels',
            'blockRadarValues',
            'radarMetrics',
            'radarTooltips',
            'totalEnrollees',
            'recentApplications',
            'feedbackCount',
            'totalDisbursement'
        ));
    }
}
