<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Block;
use App\Models\BlockChangeRequest;
use App\Models\ClassSchedule;
use App\Models\FormResponse;
use App\Models\Room;
use App\Models\User;
use App\Services\AcademicCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /** @return \Illuminate\Database\Eloquent\Builder */
    private function applyYearLevelOrder($query, string $yearLevelColumn = "COALESCE(users.year_level, 'N/A')")
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            return $query->orderByRaw("FIELD({$yearLevelColumn}, '1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year')");
        }
        return $query->orderByRaw("CASE {$yearLevelColumn} WHEN '1st Year' THEN 1 WHEN '2nd Year' THEN 2 WHEN '3rd Year' THEN 3 WHEN '4th Year' THEN 4 WHEN '5th Year' THEN 5 ELSE 99 END");
    }

    /** Return normalized report filters from the request (used by index, print, export). */
    private function getReportFilters(Request $request): array
    {
        $selectedLabel = AcademicCalendarService::getSelectedSchoolYearLabel();
        return [
            'academic_year' => $request->string('academic_year')->toString() ?: ($selectedLabel ?? ''),
            'semester' => $request->string('semester')->toString(),
            'course' => $request->string('course')->toString(),
            'process_status' => $request->string('process_status')->toString(),
        ];
    }

    /** Build the base enrollment query with filters applied. When $forSelectedSchoolYear is false, all years are included (fallback so reports page shows data). */
    private function buildEnrollmentQuery(array $filters, bool $forSelectedSchoolYear = true): \Illuminate\Database\Eloquent\Builder
    {
        $query = FormResponse::query()
            ->with(['user', 'enrollmentForm']);
        if ($forSelectedSchoolYear) {
            $query->forSelectedSchoolYear();
        }
        return $query
            ->when(! empty($filters['academic_year']), fn ($q) => $q->whereHas('enrollmentForm', fn ($f) => $f->where('assigned_year', $filters['academic_year'])))
            ->when(! empty($filters['semester']), fn ($q) => $q->whereHas('enrollmentForm', fn ($f) => $f->where('assigned_semester', $filters['semester'])))
            ->when(! empty($filters['course']), fn ($q) => $q->whereHas('user', fn ($u) => $u->where('course', $filters['course'])))
            ->when(! empty($filters['process_status']), fn ($q) => $q->where('process_status', $filters['process_status']));
    }

    public function index(Request $request): View
    {
        $filters = $this->getReportFilters($request);
        $selectedLabel = AcademicCalendarService::getSelectedSchoolYearLabel();
        $enrollmentQuery = $this->buildEnrollmentQuery($filters);
        if ((clone $enrollmentQuery)->count() === 0) {
            $enrollmentQuery = $this->buildEnrollmentQuery($filters, false);
        }

        $enrollmentRows = (clone $enrollmentQuery)->latest()->limit(500)->get();

        $programBreakdown = (clone $enrollmentQuery)
            ->selectRaw('users.course as label, COUNT(form_responses.id) as count')
            ->join('users', 'users.id', '=', 'form_responses.user_id')
            ->groupBy('users.course')
            ->orderByDesc('count')
            ->get();

        $genderBreakdown = (clone $enrollmentQuery)
            ->selectRaw('users.gender as label, COUNT(form_responses.id) as count')
            ->join('users', 'users.id', '=', 'form_responses.user_id')
            ->groupBy('users.gender')
            ->orderByDesc('count')
            ->get();

        $locationBreakdown = (clone $enrollmentQuery)
            ->selectRaw('users.municipality as label, COUNT(form_responses.id) as count')
            ->join('users', 'users.id', '=', 'form_responses.user_id')
            ->groupBy('users.municipality')
            ->orderByDesc('count')
            ->get();

        $yearSemesterBreakdown = (clone $enrollmentQuery)
            ->selectRaw('enrollment_forms.assigned_year as academic_year, enrollment_forms.assigned_semester as semester, COUNT(form_responses.id) as count')
            ->join('enrollment_forms', 'enrollment_forms.id', '=', 'form_responses.enrollment_form_id')
            ->groupBy('enrollment_forms.assigned_year', 'enrollment_forms.assigned_semester')
            ->orderByDesc('count')
            ->get();

        $yearLevelCol = "COALESCE(users.year_level, 'N/A')";
        $enrollmentByYearLevelQuery = (clone $enrollmentQuery)
            ->join('users', 'users.id', '=', 'form_responses.user_id')
            ->selectRaw("{$yearLevelCol} as year_level, COUNT(form_responses.id) as count")
            ->groupBy(DB::raw($yearLevelCol));
        $enrollmentByYearLevel = $this->applyYearLevelOrder($enrollmentByYearLevelQuery, $yearLevelCol)->get();

        $enrollmentByProgramYearLevelQuery = (clone $enrollmentQuery)
            ->join('users', 'users.id', '=', 'form_responses.user_id')
            ->selectRaw("users.course as program, {$yearLevelCol} as year_level, COUNT(form_responses.id) as count")
            ->groupBy('users.course', DB::raw($yearLevelCol))
            ->orderBy('users.course');
        $enrollmentByProgramYearLevel = $this->applyYearLevelOrder($enrollmentByProgramYearLevelQuery, $yearLevelCol)->get();

        $totalEnrollmentCount = (clone $enrollmentQuery)->count();

        // Workflow pipeline (status buckets + counts; formerly Workflow QA)
        $needsCorrection = (clone $enrollmentQuery)->where('process_status', 'needs_correction')->latest()->get();
        $approvedUnscheduled = (clone $enrollmentQuery)->where('process_status', 'approved')->latest()->get();
        $scheduledPendingAssessment = (clone $enrollmentQuery)->where('process_status', 'scheduled')->latest()->get();
        $completed = (clone $enrollmentQuery)->where('process_status', 'completed')->latest()->get();
        $statusCounts = (clone $enrollmentQuery)
            ->selectRaw('COALESCE(process_status, approval_status, "pending") as status, COUNT(*) as count')
            ->groupBy('status')
            ->orderByDesc('count')
            ->get();

        $daragaCount = (clone $enrollmentQuery)
            ->whereHas('user', fn ($q) => $q->whereRaw('LOWER(COALESCE(municipality, "")) like ?', ['%daraga%']))
            ->count();
        $legazpiCount = (clone $enrollmentQuery)
            ->whereHas('user', fn ($q) => $q->whereRaw('LOWER(COALESCE(municipality, "")) like ?', ['%legazpi%']))
            ->count();
        $guinobatanCount = (clone $enrollmentQuery)
            ->whereHas('user', fn ($q) => $q->whereRaw('LOWER(COALESCE(municipality, "")) like ?', ['%guinobatan%']))
            ->count();

        $studentRowsQuery = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->with('block')
            ->when($filters['course'], fn ($q, $value) => $q->where('course', $value))
            ->when($filters['semester'], fn ($q, $value) => $q->where('semester', $value));
        if ($selectedLabel !== null && $selectedLabel !== '') {
            $studentRowsQuery->where('school_year', $selectedLabel);
        }
        $studentRows = $studentRowsQuery->latest()->limit(300)->get();

        $blockRowsQuery = Block::query()
            ->withCount('students')
            ->when($filters['semester'], fn ($q, $value) => $q->where('semester', $value))
            ->orderBy('program')
            ->orderBy('year_level')
            ->orderBy('code');
        if ($selectedLabel !== null && $selectedLabel !== '') {
            $blockRowsQuery->where('school_year_label', $selectedLabel);
        }
        $blockRows = $blockRowsQuery->get();
        if ($blockRows->isEmpty() && $selectedLabel !== null && $selectedLabel !== '') {
            $blockRows = Block::query()
                ->withCount('students')
                ->when($filters['semester'], fn ($q, $value) => $q->where('semester', $value))
                ->orderBy('program')
                ->orderBy('year_level')
                ->orderBy('code')
                ->get();
        }

        $scheduleRowsQuery = ClassSchedule::query()
            ->with(['block', 'subject', 'room', 'professor']);
        if ($selectedLabel !== null && $selectedLabel !== '') {
            $scheduleRowsQuery->where('school_year', $selectedLabel);
        }
        $scheduleRows = $scheduleRowsQuery
            ->when($filters['semester'], fn ($q, $value) => $q->where('semester', $value))
            ->latest()
            ->limit(500)
            ->get();

        $facultyRows = User::query()
            ->whereNotNull('faculty_type')
            ->with(['teachingSchedules' => fn ($q) => $q->with('subject')])
            ->get()
            ->map(function (User $faculty) {
                $unitLoad = (int) $faculty->teachingSchedules->sum(fn ($schedule) => (int) ($schedule->subject?->units ?? 0));
                $maxUnits = (int) ($faculty->max_units ?? ($faculty->faculty_type === 'permanent' ? 24 : 0));
                return (object) [
                    'name' => $faculty->name,
                    'email' => $faculty->email,
                    'faculty_type' => $faculty->faculty_type,
                    'assigned_units' => $unitLoad,
                    'max_units' => $maxUnits,
                    'is_overload' => $maxUnits > 0 && $unitLoad > $maxUnits,
                ];
            })
            ->values();

        $roomRows = Room::query()
            ->with('schedules')
            ->get()
            ->map(function (Room $room) {
                $hours = $room->schedules->sum(function ($schedule) {
                    return max(0, ((strtotime((string) $schedule->end_time) - strtotime((string) $schedule->start_time)) / 3600));
                });
                return (object) [
                    'code' => $room->code,
                    'name' => $room->name,
                    'capacity' => $room->capacity,
                    'utilization_count' => $room->schedules->count(),
                    'utilization_hours' => round($hours, 2),
                ];
            })
            ->sortByDesc('utilization_count')
            ->values();

        $financialRows = Assessment::query()
            ->with('student')
            ->latest()
            ->limit(300)
            ->get();

        $availableCourses = collect()
            ->merge(\App\Models\Program::query()->orderBy('program_name')->pluck('program_name'))
            ->merge(User::query()->where('role', User::ROLE_STUDENT)->whereNotNull('course')->distinct()->pluck('course'))
            ->filter()
            ->unique()
            ->values();

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

        $recentReports = collect(); // Placeholder: can be replaced with GeneratedReport model when implemented

        $totalStudents = $totalEnrollmentCount;
        $pendingEnrollments = (int) (optional($statusCounts->firstWhere('status', 'pending'))->count ?? 0);
        $totalBlocks = $blockRows->count();
        $blockRequestsCount = BlockChangeRequest::query()->whereIn('status', ['pending'])->count();

        $geographicLabels = collect(['Daraga', 'Legazpi', 'Guinobatan']);
        $geographicData = collect([$daragaCount, $legazpiCount, $guinobatanCount]);
        foreach ($locationBreakdown as $row) {
            $label = $row->label ? trim($row->label) : 'Other';
            if ($label !== '' && ! $geographicLabels->contains($label)) {
                $geographicLabels->push($label);
                $geographicData->push($row->count);
            }
        }
        $addressData = $geographicLabels->zip($geographicData)->map(fn ($pair) => (object) ['label' => $pair[0], 'count' => $pair[1]])->values();

        return view('dashboards.reports-index', compact(
            'filters',
            'enrollmentRows',
            'studentRows',
            'blockRows',
            'scheduleRows',
            'facultyRows',
            'roomRows',
            'financialRows',
            'availableCourses',
            'academicYears',
            'semesters',
            'programBreakdown',
            'genderBreakdown',
            'locationBreakdown',
            'yearSemesterBreakdown',
            'enrollmentByYearLevel',
            'enrollmentByProgramYearLevel',
            'totalEnrollmentCount',
            'daragaCount',
            'legazpiCount',
            'guinobatanCount',
            'needsCorrection',
            'approvedUnscheduled',
            'scheduledPendingAssessment',
            'completed',
            'statusCounts',
            'recentReports',
            'totalStudents',
            'pendingEnrollments',
            'totalBlocks',
            'blockRequestsCount',
            'geographicLabels',
            'geographicData',
            'addressData'
        ));
    }

    public function printReport(Request $request): View
    {
        $data = $this->buildReportData($request);
        $reportType = $request->string('type')->toString() ?: 'enrollment_summary';
        $data['reportType'] = $reportType;
        $data['generatedAt'] = now()->format('F j, Y \a\t g:i A');
        $data['backUrl'] = $this->reportsIndexUrl($request);
        return view('dashboards.reports-print', $data);
    }

    /** Return the System Reports index URL for the current role (used for Back button on print view). */
    private function reportsIndexUrl(Request $request): string
    {
        $route = match (true) {
            $request->routeIs('registrar.*') => 'registrar.reports',
            $request->routeIs('dean.*') => 'dean.reports',
            $request->routeIs('staff.*') => 'staff.reports',
            $request->routeIs('unifast.*') => 'unifast.reports',
            default => 'admin.reports',
        };
        $query = $request->only(['academic_year', 'semester', 'course', 'process_status']);
        return route($route, array_filter($query));
    }

    protected function buildReportData(Request $request): array
    {
        $filters = $this->getReportFilters($request);
        $selectedLabel = AcademicCalendarService::getSelectedSchoolYearLabel();
        $enrollmentQuery = $this->buildEnrollmentQuery($filters);

        $programBreakdown = (clone $enrollmentQuery)
            ->selectRaw('users.course as label, COUNT(form_responses.id) as count')
            ->join('users', 'users.id', '=', 'form_responses.user_id')
            ->groupBy('users.course')
            ->orderByDesc('count')
            ->get();

        $genderBreakdown = (clone $enrollmentQuery)
            ->selectRaw('users.gender as label, COUNT(form_responses.id) as count')
            ->join('users', 'users.id', '=', 'form_responses.user_id')
            ->groupBy('users.gender')
            ->orderByDesc('count')
            ->get();

        $locationBreakdown = (clone $enrollmentQuery)
            ->selectRaw('users.municipality as label, COUNT(form_responses.id) as count')
            ->join('users', 'users.id', '=', 'form_responses.user_id')
            ->groupBy('users.municipality')
            ->orderByDesc('count')
            ->get();

        $yearLevelCol = "COALESCE(users.year_level, 'N/A')";
        $enrollmentByYearLevelQuery = (clone $enrollmentQuery)
            ->join('users', 'users.id', '=', 'form_responses.user_id')
            ->selectRaw("{$yearLevelCol} as year_level, COUNT(form_responses.id) as count")
            ->groupBy(DB::raw($yearLevelCol));
        $enrollmentByYearLevel = $this->applyYearLevelOrder($enrollmentByYearLevelQuery, $yearLevelCol)->get();

        $enrollmentByProgramYearLevelQuery = (clone $enrollmentQuery)
            ->join('users', 'users.id', '=', 'form_responses.user_id')
            ->selectRaw("users.course as program, {$yearLevelCol} as year_level, COUNT(form_responses.id) as count")
            ->groupBy('users.course', DB::raw($yearLevelCol))
            ->orderBy('users.course');
        $enrollmentByProgramYearLevel = $this->applyYearLevelOrder($enrollmentByProgramYearLevelQuery, $yearLevelCol)->get();

        $totalEnrollmentCount = (clone $enrollmentQuery)->count();

        $daragaCount = (clone $enrollmentQuery)->whereHas('user', fn ($q) => $q->whereRaw('LOWER(COALESCE(municipality, "")) like ?', ['%daraga%']))->count();
        $legazpiCount = (clone $enrollmentQuery)->whereHas('user', fn ($q) => $q->whereRaw('LOWER(COALESCE(municipality, "")) like ?', ['%legazpi%']))->count();
        $guinobatanCount = (clone $enrollmentQuery)->whereHas('user', fn ($q) => $q->whereRaw('LOWER(COALESCE(municipality, "")) like ?', ['%guinobatan%']))->count();

        $enrollmentRows = (clone $enrollmentQuery)->latest()->limit(500)->get();

        $blockQuery = Block::query()->withCount('students')
            ->when($filters['semester'], fn ($q, $v) => $q->where('semester', $v));
        if ($selectedLabel) {
            $blockQuery->where('school_year_label', $selectedLabel);
        }
        $blockRows = $blockQuery->orderBy('program')->orderBy('year_level')->orderBy('code')->get();

        $facultyRows = User::query()->whereNotNull('faculty_type')->get()->map(function (User $f) {
            $units = (int) $f->teachingSchedules()->with('subject')->get()->sum(fn ($s) => (int) ($s->subject?->units ?? 0));
            $max = (int) ($f->max_units ?? ($f->faculty_type === 'permanent' ? 24 : 0));
            return (object) ['name' => $f->name, 'email' => $f->email, 'faculty_type' => $f->faculty_type, 'assigned_units' => $units, 'max_units' => $max, 'is_overload' => $max > 0 && $units > $max];
        })->values();

        $roomRows = Room::query()->with('schedules')->get()->map(function (Room $r) {
            $hours = $r->schedules->sum(fn ($s) => max(0, (strtotime((string) $s->end_time) - strtotime((string) $s->start_time)) / 3600));
            return (object) ['code' => $r->code, 'name' => $r->name, 'capacity' => $r->capacity, 'utilization_count' => $r->schedules->count(), 'utilization_hours' => round($hours, 2)];
        })->sortByDesc('utilization_count')->values();

        $financialRows = Assessment::query()->with('student')->latest()->limit(500)->get();

        return compact(
            'filters',
            'programBreakdown',
            'genderBreakdown',
            'locationBreakdown',
            'enrollmentByYearLevel',
            'enrollmentByProgramYearLevel',
            'totalEnrollmentCount',
            'daragaCount',
            'legazpiCount',
            'guinobatanCount',
            'enrollmentRows',
            'blockRows',
            'facultyRows',
            'roomRows',
            'financialRows'
        );
    }

    public function export(Request $request): StreamedResponse
    {
        $type = $request->string('type')->toString() ?: 'enrollment';
        $filters = $this->getReportFilters($request);
        $selectedLabel = AcademicCalendarService::getSelectedSchoolYearLabel();

        return response()->streamDownload(function () use ($type, $filters, $selectedLabel) {
            $out = fopen('php://output', 'w');

            switch ($type) {
                case 'students':
                    fputcsv($out, ['Student', 'Email', 'Course', 'Year', 'Semester', 'Block']);
                    $studentQuery = User::query()->where('role', User::ROLE_STUDENT)->with('block')
                        ->when(! empty($filters['course']), fn ($q) => $q->where('course', $filters['course']))
                        ->when(! empty($filters['semester']), fn ($q) => $q->where('semester', $filters['semester']));
                    if ($selectedLabel !== null && $selectedLabel !== '') {
                        $studentQuery->where('school_year', $selectedLabel);
                    }
                    foreach ($studentQuery->latest()->get() as $student) {
                        fputcsv($out, [
                            $student->name,
                            $student->email,
                            $student->resolved_program ?? $student->course,
                            $student->resolved_year_level ?? $student->year_level,
                            $student->resolved_semester ?? $student->semester,
                            $student->block?->code ?? $student->block?->name,
                        ]);
                    }
                    break;
                case 'blocks':
                    fputcsv($out, ['Code', 'Program', 'Year', 'Semester', 'Shift', 'Capacity', 'Students Count']);
                    $blockQuery = Block::query()->withCount('students')
                        ->when(! empty($filters['semester']), fn ($q) => $q->where('semester', $filters['semester']));
                    if ($selectedLabel !== null && $selectedLabel !== '') {
                        $blockQuery->where('school_year_label', $selectedLabel);
                    }
                    foreach ($blockQuery->orderBy('program')->orderBy('year_level')->orderBy('code')->get() as $block) {
                        fputcsv($out, [
                            $block->code ?? $block->name,
                            $block->program,
                            $block->year_level,
                            $block->semester,
                            $block->shift,
                            $block->capacity ?? $block->max_students ?? 50,
                            $block->students_count,
                        ]);
                    }
                    break;
                case 'schedules':
                    fputcsv($out, ['Block', 'Subject', 'Professor', 'Room', 'Day', 'Start', 'End']);
                    $scheduleQuery = ClassSchedule::query()->with(['block', 'subject', 'professor', 'room'])
                        ->when(! empty($filters['semester']), fn ($q) => $q->where('semester', $filters['semester']));
                    if ($selectedLabel !== null && $selectedLabel !== '') {
                        $scheduleQuery->where('school_year', $selectedLabel);
                    }
                    foreach ($scheduleQuery->latest()->get() as $row) {
                        fputcsv($out, [
                            $row->block?->code ?? $row->block?->name,
                            trim(($row->subject?->code ?? '').' '.($row->subject?->title ?? '')),
                            $row->professor?->name,
                            $row->room?->name,
                            $row->day_of_week,
                            $row->start_time,
                            $row->end_time,
                        ]);
                    }
                    break;
                case 'faculty':
                    fputcsv($out, ['Faculty', 'Email', 'Type', 'Assigned Units', 'Max Units', 'Overload']);
                    foreach (User::query()->whereNotNull('faculty_type')->with(['teachingSchedules' => fn ($q) => $q->with('subject')])->get() as $faculty) {
                        $units = (int) $faculty->teachingSchedules->sum(fn ($s) => (int) ($s->subject?->units ?? 0));
                        $max = (int) ($faculty->max_units ?? ($faculty->faculty_type === 'permanent' ? 24 : 0));
                        fputcsv($out, [$faculty->name, $faculty->email, $faculty->faculty_type, $units, $max, ($max > 0 && $units > $max) ? 'YES' : 'NO']);
                    }
                    break;
                case 'rooms':
                    fputcsv($out, ['Room', 'Code', 'Capacity', 'Schedule Count']);
                    foreach (Room::withCount('schedules')->get() as $room) {
                        fputcsv($out, [$room->name, $room->code, $room->capacity, $room->schedules_count]);
                    }
                    break;
                case 'financial':
                    fputcsv($out, ['Student', 'Total Assessed', 'Income Class', 'Assessment Status', 'UniFAST Eligible']);
                    $financialQuery = Assessment::query()->with('student')->latest();
                    if ($selectedLabel) {
                        $financialQuery->where('school_year', $selectedLabel);
                    }
                    if (! empty($filters['semester'])) {
                        $financialQuery->where('semester', $filters['semester']);
                    }
                    foreach ($financialQuery->get() as $row) {
                        fputcsv($out, [
                            $row->student?->name,
                            $row->total_assessed,
                            $row->income_classification,
                            $row->assessment_status,
                            $row->unifast_eligible ? 'YES' : 'NO',
                        ]);
                    }
                    break;
                default:
                    fputcsv($out, ['Student', 'Course', 'Workflow Status', 'Form Cohort', 'Submitted At']);
                    $enrollmentQuery = $this->buildEnrollmentQuery($filters);
                    foreach ($enrollmentQuery->with(['user', 'enrollmentForm'])->latest()->get() as $row) {
                        fputcsv($out, [
                            $row->user?->name,
                            $row->user?->course,
                            $row->process_status ?? $row->approval_status ?? 'pending',
                            ($row->enrollmentForm?->assigned_year ?? '').' / '.($row->enrollmentForm?->assigned_semester ?? ''),
                            optional($row->created_at)->format('Y-m-d H:i:s'),
                        ]);
                    }
                    break;
            }

            fclose($out);
        }, "report-{$type}.csv", ['Content-Type' => 'text/csv']);
    }
}

