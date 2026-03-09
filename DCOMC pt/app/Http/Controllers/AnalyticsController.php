<?php

namespace App\Http\Controllers;

use App\Models\FormResponse;
use App\Models\User;
use App\Services\AcademicCalendarService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $filters = [
            'course' => $request->string('course')->toString(),
            'academic_year' => $request->string('academic_year')->toString(),
            'gender' => $request->string('gender')->toString(),
            'semester' => $request->string('semester')->toString(),
            'status' => $request->string('status')->toString(),
            'from_date' => $request->string('from_date')->toString(),
            'to_date' => $request->string('to_date')->toString(),
        ];

        $selectedLabel = AcademicCalendarService::getSelectedSchoolYearLabel();
        $studentQuery = User::query()->where('role', User::ROLE_STUDENT);
        if ($selectedLabel !== null && $selectedLabel !== '') {
            $studentQuery->where('school_year', $selectedLabel);
        }
        if ($filters['course']) {
            $studentQuery->where('course', $filters['course']);
        }
        if ($filters['gender']) {
            $studentQuery->where('gender', $filters['gender']);
        }
        if ($filters['semester']) {
            $studentQuery->where('semester', $filters['semester']);
        }

        $studentIds = (clone $studentQuery)->pluck('id');

        $responseQuery = FormResponse::query()->forSelectedSchoolYear()->whereIn('user_id', $studentIds);
        if ($filters['status']) {
            $responseQuery->where('approval_status', $filters['status']);
        }
        if ($filters['academic_year']) {
            $responseQuery->whereHas('enrollmentForm', fn ($q) => $q->where('assigned_year', $filters['academic_year']));
        }
        if ($filters['semester']) {
            $responseQuery->whereHas('enrollmentForm', fn ($q) => $q->where('assigned_semester', $filters['semester']));
        }
        if ($filters['from_date']) {
            $responseQuery->whereDate('created_at', '>=', $filters['from_date']);
        }
        if ($filters['to_date']) {
            $responseQuery->whereDate('created_at', '<=', $filters['to_date']);
        }

        $totalStudents = $studentIds->count();
        $totalResponses = (clone $responseQuery)->count();
        $approvedCount = (clone $responseQuery)->where('approval_status', 'approved')->count();
        $pendingCount = (clone $responseQuery)->where('approval_status', 'pending')->count();
        $rejectedCount = (clone $responseQuery)->where('approval_status', 'rejected')->count();
        $unapprovedCount = $pendingCount + $rejectedCount;

        $programBreakdown = (clone $studentQuery)
            ->selectRaw('course, COUNT(*) as count')
            ->groupBy('course')
            ->orderByDesc('count')
            ->get();

        $genderBreakdown = (clone $studentQuery)
            ->selectRaw('gender, COUNT(*) as count')
            ->groupBy('gender')
            ->orderByDesc('count')
            ->get();

        $locationBreakdown = (clone $studentQuery)
            ->selectRaw('municipality, COUNT(*) as count')
            ->groupBy('municipality')
            ->orderByDesc('count')
            ->get();

        $daragaCount = (clone $studentQuery)->whereRaw('LOWER(COALESCE(municipality, "")) like ?', ['%daraga%'])->count();
        $legazpiCount = (clone $studentQuery)->whereRaw('LOWER(COALESCE(municipality, "")) like ?', ['%legazpi%'])->count();
        $guinobatanCount = (clone $studentQuery)->whereRaw('LOWER(COALESCE(municipality, "")) like ?', ['%guinobatan%'])->count();

        $trendRows = (clone $responseQuery)
            ->select(['created_at'])
            ->get()
            ->groupBy(fn ($item) => optional($item->created_at)->format('Y-m'))
            ->map(fn ($rows, $period) => (object) ['period' => $period ?: 'N/A', 'count' => $rows->count()])
            ->sortBy('period')
            ->values();

        $availableCourses = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->whereNotNull('course')
            ->distinct()
            ->orderBy('course')
            ->pluck('course');

        $academicYears = \App\Models\EnrollmentForm::query()
            ->whereNotNull('assigned_year')
            ->distinct()
            ->orderByDesc('assigned_year')
            ->pluck('assigned_year');

        $semesters = \App\Models\EnrollmentForm::query()
            ->whereNotNull('assigned_semester')
            ->distinct()
            ->orderBy('assigned_semester')
            ->pluck('assigned_semester');

        $geographicLabels = $locationBreakdown->pluck('municipality')->map(fn ($m) => $m ?: 'N/A')->values()->all();
        $geographicData = $locationBreakdown->pluck('count')->values()->all();
        
        $departmentData = $programBreakdown;

        $viewPrefix = 'dashboards';
        if (request()->routeIs('admin.*')) $viewPrefix = 'admin';
        elseif (request()->routeIs('registrar.*')) $viewPrefix = 'registrar';
        elseif (request()->routeIs('staff.*')) $viewPrefix = 'staff';
        elseif (request()->routeIs('dean.*')) $viewPrefix = 'dean';
        elseif (request()->routeIs('unifast.*')) $viewPrefix = 'unifast';

        // #region agent log
        file_put_contents(base_path('debug-3083bc.log'), json_encode([
            'sessionId' => '3083bc',
            'id' => 'log_' . time(),
            'timestamp' => time() * 1000,
            'location' => 'AnalyticsController.php:index',
            'message' => 'Controller accessed',
            'data' => [
                'route' => request()->route()->getName(),
                'viewPrefix' => $viewPrefix,
                'programBreakdownCount' => count($programBreakdown),
                'filters' => $filters,
            ],
            'hypothesisId' => 'H-C'
        ]) . "\n", FILE_APPEND);
        // #endregion

        return view($viewPrefix . '.analytics', compact(
            'filters',
            'totalStudents',
            'totalResponses',
            'approvedCount',
            'pendingCount',
            'rejectedCount',
            'unapprovedCount',
            'programBreakdown',
            'departmentData',
            'genderBreakdown',
            'locationBreakdown',
            'trendRows',
            'availableCourses',
            'academicYears',
            'semesters',
            'daragaCount',
            'legazpiCount',
            'guinobatanCount',
            'geographicLabels',
            'geographicData'
        ));
    }

    public function printAnalytics(Request $request): View
    {
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $filters = [
            'course' => $request->string('course')->toString(),
            'academic_year' => $request->string('academic_year')->toString(),
            'gender' => $request->string('gender')->toString(),
            'semester' => $request->string('semester')->toString(),
            'status' => $request->string('status')->toString(),
            'from_date' => $request->string('from_date')->toString(),
            'to_date' => $request->string('to_date')->toString(),
        ];

        $selectedLabel = AcademicCalendarService::getSelectedSchoolYearLabel();
        $studentQuery = User::query()->where('role', User::ROLE_STUDENT);
        if ($selectedLabel !== null && $selectedLabel !== '') {
            $studentQuery->where('school_year', $selectedLabel);
        }
        if ($filters['course']) {
            $studentQuery->where('course', $filters['course']);
        }
        if ($filters['gender']) {
            $studentQuery->where('gender', $filters['gender']);
        }
        if ($filters['semester']) {
            $studentQuery->where('semester', $filters['semester']);
        }

        $studentIds = (clone $studentQuery)->pluck('id');

        $responseQuery = FormResponse::query()->forSelectedSchoolYear()->whereIn('user_id', $studentIds);
        if ($filters['status']) {
            $responseQuery->where('approval_status', $filters['status']);
        }
        if ($filters['academic_year']) {
            $responseQuery->whereHas('enrollmentForm', fn ($q) => $q->where('assigned_year', $filters['academic_year']));
        }
        if ($filters['semester']) {
            $responseQuery->whereHas('enrollmentForm', fn ($q) => $q->where('assigned_semester', $filters['semester']));
        }
        if ($filters['from_date']) {
            $responseQuery->whereDate('created_at', '>=', $filters['from_date']);
        }
        if ($filters['to_date']) {
            $responseQuery->whereDate('created_at', '<=', $filters['to_date']);
        }

        $totalStudents = $studentIds->count();
        $totalResponses = (clone $responseQuery)->count();
        $approvedCount = (clone $responseQuery)->where('approval_status', 'approved')->count();
        $pendingCount = (clone $responseQuery)->where('approval_status', 'pending')->count();
        $rejectedCount = (clone $responseQuery)->where('approval_status', 'rejected')->count();

        $programBreakdown = (clone $studentQuery)
            ->selectRaw('course, COUNT(*) as count')
            ->groupBy('course')
            ->orderByDesc('count')
            ->get();

        $genderBreakdown = (clone $studentQuery)
            ->selectRaw('gender, COUNT(*) as count')
            ->groupBy('gender')
            ->orderByDesc('count')
            ->get();

        $locationBreakdown = (clone $studentQuery)
            ->selectRaw('municipality, COUNT(*) as count')
            ->groupBy('municipality')
            ->orderByDesc('count')
            ->get();

        $daragaCount = (clone $studentQuery)->whereRaw('LOWER(COALESCE(municipality, "")) like ?', ['%daraga%'])->count();
        $legazpiCount = (clone $studentQuery)->whereRaw('LOWER(COALESCE(municipality, "")) like ?', ['%legazpi%'])->count();
        $guinobatanCount = (clone $studentQuery)->whereRaw('LOWER(COALESCE(municipality, "")) like ?', ['%guinobatan%'])->count();

        $generatedAt = now()->format('F j, Y \a\t g:i A');
        $academicYear = $filters['academic_year'] ?: 'All Academic Years';
        $semester = $filters['semester'] ?: 'All Semesters';
        $totalEnrolled = $totalStudents;
        $backRoute = request()->routeIs('unifast.*') ? 'unifast.analytics' : (request()->routeIs('dean.*') ? 'dean.analytics' : (request()->routeIs('registrar.*') ? 'registrar.analytics' : (request()->routeIs('staff.*') ? 'staff.analytics' : 'admin.analytics')));
        $backUrl = route($backRoute, array_filter($filters));

        $statusLabel = sprintf('Current term (%s, %s)', $academicYear, $semester);
        $enrollmentSummaryRows = (clone $studentQuery)
            ->selectRaw("
                COALESCE(course, 'N/A') as program,
                COALESCE(year_level, '—') as year_level,
                SUM(CASE WHEN COALESCE(gender, '') = 'Male' THEN 1 ELSE 0 END) as male_count,
                SUM(CASE WHEN COALESCE(gender, '') = 'Female' THEN 1 ELSE 0 END) as female_count,
                COUNT(*) as total_enrolled
            ")
            ->groupBy('course', 'year_level')
            ->orderByRaw('COALESCE(course, "zzz")')
            ->orderByRaw('COALESCE(year_level, "zzz")')
            ->get()
            ->map(function ($row) use ($statusLabel) {
                $row->status_text = $statusLabel;
                return $row;
            });

        return view('dashboards.analytics-print', compact(
            'filters',
            'totalStudents',
            'totalResponses',
            'approvedCount',
            'pendingCount',
            'rejectedCount',
            'programBreakdown',
            'genderBreakdown',
            'locationBreakdown',
            'daragaCount',
            'legazpiCount',
            'guinobatanCount',
            'generatedAt',
            'academicYear',
            'semester',
            'totalEnrolled',
            'enrollmentSummaryRows',
            'backUrl'
        ));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $filters = [
            'course' => $request->string('course')->toString(),
            'academic_year' => $request->string('academic_year')->toString(),
            'gender' => $request->string('gender')->toString(),
            'semester' => $request->string('semester')->toString(),
            'status' => $request->string('status')->toString(),
            'from_date' => $request->string('from_date')->toString(),
            'to_date' => $request->string('to_date')->toString(),
        ];

        $studentQuery = User::query()->where('role', User::ROLE_STUDENT);
        if ($filters['course']) {
            $studentQuery->where('course', $filters['course']);
        }
        if ($filters['gender']) {
            $studentQuery->where('gender', $filters['gender']);
        }
        if ($filters['semester']) {
            $studentQuery->where('semester', $filters['semester']);
        }
        $studentIds = (clone $studentQuery)->pluck('id');

        $rows = FormResponse::query()
            ->with('user.block')
            ->whereIn('user_id', $studentIds)
            ->when($filters['status'], fn ($q, $value) => $q->where('approval_status', $value))
            ->when($filters['academic_year'], fn ($q, $value) => $q->whereHas('enrollmentForm', fn ($f) => $f->where('assigned_year', $value)))
            ->when($filters['semester'], fn ($q, $value) => $q->whereHas('enrollmentForm', fn ($f) => $f->where('assigned_semester', $value)))
            ->when($filters['from_date'], fn ($q, $value) => $q->whereDate('created_at', '>=', $value))
            ->when($filters['to_date'], fn ($q, $value) => $q->whereDate('created_at', '<=', $value))
            ->orderByDesc('created_at')
            ->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Student Name', 'Email', 'Course', 'Gender', 'Semester', 'Status', 'Submitted At']);
            foreach ($rows as $row) {
                $u = $row->user;
                fputcsv($out, [
                    $u?->name ?? 'N/A',
                    $u?->email ?? 'N/A',
                    $u ? ($u->resolved_program ?? $u->course) : 'N/A',
                    $u?->gender ?? 'N/A',
                    $u ? ($u->resolved_semester ?? $u->semester) : 'N/A',
                    $row->approval_status,
                    optional($row->created_at)->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($out);
        }, 'enrollment-analytics.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}

