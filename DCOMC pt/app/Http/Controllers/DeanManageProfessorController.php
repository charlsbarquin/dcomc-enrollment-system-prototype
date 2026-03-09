<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\ClassSchedule;
use App\Models\Department;
use App\Models\ProfessorSubjectAssignment;
use App\Models\RawSubject;
use App\Models\StudentCorRecord;
use App\Models\Subject;
use App\Models\User;
use App\Services\ProfessorWorkloadService;
use App\Services\SchedulingScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DeanManageProfessorController extends Controller
{
    public function index(Request $request): View
    {
        $dean = $request->user();
        $scopeService = app(SchedulingScopeService::class);
        $workloadService = app(ProfessorWorkloadService::class);

        $professors = User::query()
            ->whereNotNull('faculty_type')
            ->when(true, fn ($q) => $scopeService->scopeProfessorsForViewer($q, $dean))
            ->orderBy('name')
            ->get();

        $semester = $request->get('semester');
        $schoolYear = $request->get('school_year');

        $dayAbbrev = ['', 'M', 'T', 'W', 'Th', 'F', 'Sat', 'Sun'];

        $professors = $professors->map(function (User $p) use ($workloadService, $semester, $schoolYear, $dayAbbrev) {
            $scheduleUnits = $workloadService->getTotalAssignedUnits($p->id, $semester, $schoolYear);
            $assignmentUnits = (int) ProfessorSubjectAssignment::where('professor_id', $p->id)->with(['rawSubject', 'subject'])->get()->sum(function ($a) {
            $d = $a->rawSubject ?? $a->subject?->rawSubject ?? $a->subject;
            return (int) ($d->units ?? 0);
        });
            $totalUnits = $scheduleUnits + $assignmentUnits;
            $maxUnits = (int) ($p->max_units ?? $workloadService->defaultMaxUnits($workloadService->getEmploymentType($p)));
            $unitOverload = $maxUnits > 0 && $totalUnits > $maxUnits;
            $schedules = ClassSchedule::query()
                ->where('professor_id', $p->id)
                ->when($semester, fn ($q) => $q->where('semester', $semester))
                ->when($schoolYear, fn ($q) => $q->where('school_year', $schoolYear))
                ->with(['block', 'subject'])
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();
            $hasTimeOverload = $schedules->contains('is_overload', true);
            $scheduleRows = [];
            $timeOverloadSlots = [];
            foreach ($schedules as $s) {
                $block = $s->block;
                $yrBlock = $block ? trim(($block->year_level ?? '') . '-' . ($block->program ?? $block->name ?? '')) : '—';
                $start = $s->start_time ? substr((string) $s->start_time, 0, 5) : '—';
                $end = $s->end_time ? substr((string) $s->end_time, 0, 5) : '—';
                $day = $dayAbbrev[$s->day_of_week] ?? (string) $s->day_of_week;
                $scheduleDisplay = $day . ' ' . $start . '-' . $end . ($s->day_of_week <= 5 ? '' : '');
                $scheduleRows[] = (object) [
                    'course_code' => $s->subject->code ?? '—',
                    'course_description' => $s->subject->title ?? '—',
                    'yr_program_block' => $yrBlock,
                    'units' => (int) ($s->subject->units ?? 0),
                    'schedule' => $scheduleDisplay,
                    'is_overload' => (bool) $s->is_overload,
                ];
                if ($s->is_overload) {
                    $timeOverloadSlots[] = (object) [
                        'course' => ($s->subject->code ?? '') . ' ' . ($s->subject->title ?? ''),
                        'schedule' => $scheduleDisplay,
                    ];
                }
            }
            return (object) [
                'id' => $p->id,
                'name' => $p->name,
                'email' => $p->email,
                'gender' => $p->gender ?? '—',
                'employment_type' => $p->faculty_type ?? 'cos',
                'total_units' => $totalUnits,
                'max_units' => $maxUnits,
                'schedule_selection_limit' => $p->schedule_selection_limit,
                'unit_overload' => $unitOverload,
                'time_overload' => $hasTimeOverload,
                'indicator' => $this->indicator($totalUnits, $maxUnits, $unitOverload, $hasTimeOverload),
                'schedule_rows' => $scheduleRows,
                'time_overload_slots' => $timeOverloadSlots,
            ];
        });

        $departmentId = $this->deanDepartmentId($dean);
        $department = $departmentId ? Department::find($departmentId) : null;
        $allProfessorsAllSubjects = $department && $department->all_professors_all_subjects;

        return view('dashboards.dean.manage-professor-index', [
            'professors' => $professors,
            'semester' => $semester,
            'school_year' => $schoolYear,
            'all_professors_all_subjects' => $allProfessorsAllSubjects,
            'department' => $department,
        ]);
    }

    /**
     * Set "all teachers handle all subjects" for the dean's department.
     * When ON, Schedule by Program shows all department professors for every subject.
     */
    public function toggleAllProfessorsAllSubjects(Request $request): RedirectResponse
    {
        $dean = $request->user();
        $departmentId = $this->deanDepartmentId($dean);
        if ($departmentId === null) {
            return redirect()->route('dean.manage-professor.index')->with('error', 'Your account is not assigned to a department.');
        }
        $department = Department::findOrFail($departmentId);
        $department->all_professors_all_subjects = $request->boolean('all_professors_all_subjects');
        $department->save();
        $label = $department->all_professors_all_subjects ? 'All teachers can be chosen for any subject in Schedule by Program.' : 'Only assigned subjects (per professor) apply in Schedule by Program.';
        return redirect()->route('dean.manage-professor.index')->with('success', $label);
    }

    /**
     * Update a professor's schedule selection limit (max times they can be chosen in Schedule by Program per scope).
     */
    public function updateScheduleSelectionLimit(Request $request, User $professor): RedirectResponse|JsonResponse
    {
        $dean = $request->user();
        $scopeService = app(SchedulingScopeService::class);
        if (!$scopeService->professorScopeCompatibleWithDean($professor, $dean)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
            }
            return back()->with('error', 'You cannot update this professor.');
        }
        $validated = $request->validate([
            'schedule_selection_limit' => ['nullable', 'integer', 'min:0', 'max:255'],
        ]);
        $limit = isset($validated['schedule_selection_limit']) ? (int) $validated['schedule_selection_limit'] : null;
        if ($limit === 0) {
            $limit = null;
        }
        $professor->schedule_selection_limit = $limit;
        $professor->save();
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'schedule_selection_limit' => $professor->schedule_selection_limit]);
        }
        return back()->with('success', 'Schedule selection limit updated.');
    }

    public function show(Request $request, User $professor): View|RedirectResponse
    {
        $dean = $request->user();
        $scopeService = app(SchedulingScopeService::class);
        if (!$scopeService->professorScopeCompatibleWithDean($professor, $dean)) {
            abort(403, 'You cannot manage this professor.');
        }
        if (!$professor->faculty_type) {
            abort(404, 'Not a professor.');
        }

        $workloadService = app(ProfessorWorkloadService::class);
        $semester = $request->get('semester');
        $schoolYear = $request->get('school_year');

        $schedules = ClassSchedule::query()
            ->where('professor_id', $professor->id)
            ->when($semester, fn ($q) => $q->where('semester', $semester))
            ->when($schoolYear, fn ($q) => $q->where('school_year', $schoolYear))
            ->with(['block', 'subject', 'room'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $scheduleUnits = $workloadService->getTotalAssignedUnits($professor->id, $semester, $schoolYear);
        $assignmentUnits = (int) ProfessorSubjectAssignment::where('professor_id', $professor->id)->with('subject')->get()->sum(fn ($a) => (int) ($a->subject->units ?? 0));
        $totalUnits = $scheduleUnits + $assignmentUnits;
        $maxUnits = (int) ($professor->max_units ?? $workloadService->defaultMaxUnits($workloadService->getEmploymentType($professor)));
        $unitOverload = $maxUnits > 0 && $totalUnits > $maxUnits;
        $hasTimeOverload = $schedules->contains('is_overload', true);

        $assignedSubjects = ProfessorSubjectAssignment::where('professor_id', $professor->id)->with(['rawSubject', 'subject'])->orderByRaw('COALESCE(raw_subject_id, subject_id)')->get();
        $dayNames = ['', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        return view('dashboards.dean.manage-professor-show', [
            'professor' => $professor,
            'schedules' => $schedules,
            'assigned_subjects' => $assignedSubjects,
            'total_units' => $totalUnits,
            'max_units' => $maxUnits,
            'unit_overload' => $unitOverload,
            'time_overload' => $hasTimeOverload,
            'day_names' => $dayNames,
            'semester' => $semester,
            'school_year' => $schoolYear,
        ]);
    }

    public function updateMaxUnits(Request $request, User $professor): RedirectResponse|JsonResponse
    {
        $dean = $request->user();
        $scopeService = app(SchedulingScopeService::class);
        if (!$scopeService->professorScopeCompatibleWithDean($professor, $dean)) {
            abort(403, 'You cannot manage this professor.');
        }

        $validated = $request->validate([
            'max_units' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $professor->update(['max_units' => (int) $validated['max_units']]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'max_units' => $professor->max_units]);
        }
        return back()->with('success', 'Unit limit updated.');
    }

    /**
     * Teaching load report: spreadsheet-style table (like the attached image) with filters by employment type and gender.
     */
    public function teachingLoad(Request $request): View
    {
        $dean = $request->user();
        $scopeService = app(SchedulingScopeService::class);
        $workloadService = app(ProfessorWorkloadService::class);

        $professors = User::query()
            ->whereNotNull('faculty_type')
            ->when(true, fn ($q) => $scopeService->scopeProfessorsForViewer($q, $dean))
            ->orderBy('name')
            ->get();

        $filterEmployment = $request->get('employment', '');
        $filterGender = $request->get('gender', '');
        $semester = $request->get('semester');
        $schoolYear = $request->get('school_year');

        if ($filterEmployment !== '') {
            $professors = $professors->filter(fn ($p) => strtolower((string) ($p->faculty_type ?? '')) === strtolower($filterEmployment));
        }
        if ($filterGender !== '') {
            $professors = $professors->filter(fn ($p) => strtolower((string) ($p->gender ?? '')) === strtolower($filterGender));
        }

        $rows = [];
        $dayNames = ['', 'M', 'T', 'W', 'Th', 'F', 'Sat', 'Sun'];
        foreach ($professors as $professor) {
            $schedules = ClassSchedule::query()
                ->where('professor_id', $professor->id)
                ->when($semester, fn ($q) => $q->where('semester', $semester))
                ->when($schoolYear, fn ($q) => $q->where('school_year', $schoolYear))
                ->with(['block', 'subject'])
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();

            $totalUnits = 0;
            $hasOverload = false;
            foreach ($schedules as $s) {
                $units = (int) ($s->subject->units ?? 0);
                $totalUnits += $units;
                if ($s->is_overload) {
                    $hasOverload = true;
                }
            }
            $maxUnits = (int) ($professor->max_units ?? $workloadService->defaultMaxUnits($workloadService->getEmploymentType($professor)));
            if ($maxUnits > 0 && $totalUnits > $maxUnits) {
                $hasOverload = true;
            }

            foreach ($schedules as $s) {
                $dayLabel = $dayNames[$s->day_of_week] ?? (string) $s->day_of_week;
                $timeRange = substr((string) $s->start_time, 0, 5) . '–' . substr((string) $s->end_time, 0, 5);
                $block = $s->block;
                $yrBlock = $block ? trim(($block->year_level ?? '') . '-' . ($block->program ?? $block->name ?? '')) : '—';
                $rows[] = (object) [
                    'professor_name' => $professor->name,
                    'employment_type' => $professor->faculty_type ?? 'COS',
                    'gender' => $professor->gender ?? '—',
                    'course_code' => $s->subject->code ?? '—',
                    'course_description' => $s->subject->title ?? '—',
                    'yr_program_block' => $yrBlock,
                    'units' => (int) ($s->subject->units ?? 0),
                    'schedule' => $dayLabel . ' ' . $timeRange,
                    'is_overload' => $s->is_overload,
                    'total_units' => null,
                    'show_overload_label' => false,
                ];
            }
            if ($schedules->isNotEmpty()) {
                $lastIdx = count($rows) - 1;
                $rows[$lastIdx]->total_units = $totalUnits;
                $rows[$lastIdx]->show_overload_label = $hasOverload;
            } else {
                $rows[] = (object) [
                    'professor_name' => $professor->name,
                    'employment_type' => $professor->faculty_type ?? 'COS',
                    'gender' => $professor->gender ?? '—',
                    'course_code' => '—',
                    'course_description' => 'No assignments',
                    'yr_program_block' => '—',
                    'units' => 0,
                    'schedule' => '—',
                    'is_overload' => false,
                    'total_units' => 0,
                    'show_overload_label' => false,
                ];
            }
        }

        $employmentTypes = ['permanent' => 'Permanent', 'cos' => 'COS', 'part-time' => 'Part-time'];
        $genders = ['Male' => 'Male', 'Female' => 'Female'];

        return view('dashboards.dean.manage-professor-teaching-load', [
            'rows' => $rows,
            'filter_employment' => $filterEmployment,
            'filter_gender' => $filterGender,
            'employment_types' => $employmentTypes,
            'genders' => $genders,
            'semester' => $semester,
            'school_year' => $schoolYear,
        ]);
    }

    /**
     * JSON: professor overview + deployed COR data for View Profile modal.
     * Fetches from COR Archive (StudentCorRecord) - normal schedule and overload (5pm onwards).
     */
    public function viewProfileData(Request $request, User $professor): JsonResponse
    {
        $dean = $request->user();
        $scopeService = app(SchedulingScopeService::class);
        if (!$scopeService->professorScopeCompatibleWithDean($professor, $dean)) {
            abort(403);
        }

        $records = StudentCorRecord::query()
            ->where(function ($q) use ($professor) {
                $q->where('professor_id', $professor->id)
                    ->orWhere(function ($q2) use ($professor) {
                        $q2->whereNull('professor_id')
                            ->where(function ($q3) use ($professor) {
                                $q3->where('professor_name_snapshot', $professor->name)
                                    ->orWhere('professor_name_snapshot', $professor->name . ' (OVERLOAD)');
                            });
                    });
            })
            ->with(['subject', 'block.program'])
            ->orderBy('program_id')
            ->orderBy('year_level')
            ->orderBy('block_id')
            ->orderBy('subject_id')
            ->get();

        // Deduplicate: one row per (program, block, subject) — archive has one record per student
        $seen = [];
        $records = $records->filter(function ($r) use (&$seen) {
            $key = ($r->program_id ?? '') . '-' . ($r->block_id ?? '') . '-' . ($r->subject_id ?? '');
            if (isset($seen[$key])) {
                return false;
            }
            $seen[$key] = true;
            return true;
        })->values();

        $normalSchedule = [];
        $overloadSchedule = [];
        $normalUnitsTotal = 0;
        $overloadUnitsTotal = 0;

        foreach ($records as $r) {
            $subject = $r->subject;
            $block = $r->block;
            $program = $block?->program;
            $yr = $block?->year_level ?? $r->year_level ?? '';
            $progName = $program?->program_name ?? $program?->code ?? ($block?->program ?? '');
            $blockName = $block?->name ?? $block?->code ?? '';
            $yrProgramBlock = trim($yr . ($progName ? '-' . $progName : '') . ($blockName ? ' ' . $blockName : ''));
            if ($yrProgramBlock === '') {
                $yrProgramBlock = '—';
            }

            $units = (int) ($subject->units ?? 0);
            $scheduleStr = ($r->days_snapshot ?? '') . ' ' . ($r->start_time_snapshot ? \Carbon\Carbon::parse($r->start_time_snapshot)->format('g:i A') : '') . '-' . ($r->end_time_snapshot ? \Carbon\Carbon::parse($r->end_time_snapshot)->format('g:i A') : '');

            $row = [
                'course_code' => $subject->code ?? '—',
                'course_description' => $subject->title ?? '—',
                'yr_program_block' => $yrProgramBlock,
                'units' => $units,
                'schedule' => trim($scheduleStr),
                'room' => $r->room_name_snapshot ?? '—',
            ];

            $isOverload = (bool) ($r->is_overload ?? false);
            if (!$isOverload && $r->professor_name_snapshot && stripos($r->professor_name_snapshot, 'overload') !== false) {
                $isOverload = true;
            }
            if (!$isOverload && $r->end_time_snapshot) {
                $end = \Carbon\Carbon::parse($r->end_time_snapshot);
                if ($end->format('H:i') >= '17:00') {
                    $isOverload = true;
                }
            }

            if ($isOverload) {
                $overloadSchedule[] = $row;
                $overloadUnitsTotal += $units;
            } else {
                $normalSchedule[] = $row;
                $normalUnitsTotal += $units;
            }
        }

        return response()->json([
            'professor' => [
                'id' => $professor->id,
                'name' => $professor->name,
                'email' => $professor->email ?? '',
                'employment_type' => strtoupper($professor->faculty_type ?? 'COS'),
                'gender' => $professor->gender ?? '—',
            ],
            'normal_schedule' => $normalSchedule,
            'overload_schedule' => $overloadSchedule,
            'normal_units_total' => $normalUnitsTotal,
            'overload_units_total' => $overloadUnitsTotal,
        ]);
    }

    /**
     * JSON: professor details + current subject assignments + subjects list for dropdown (for Assign subjects popup).
     */
    public function assignmentsData(Request $request, User $professor): JsonResponse
    {
        $dean = $request->user();
        $scopeService = app(SchedulingScopeService::class);
        if (!$scopeService->professorScopeCompatibleWithDean($professor, $dean)) {
            abort(403);
        }
        $workloadService = app(ProfessorWorkloadService::class);
        $maxUnits = (int) ($professor->max_units ?? $workloadService->defaultMaxUnits($workloadService->getEmploymentType($professor)));

        $assignments = ProfessorSubjectAssignment::query()
            ->where('professor_id', $professor->id)
            ->with(['rawSubject', 'subject'])
            ->get()
            ->map(function ($a) {
                $display = $a->rawSubject ?? $a->subject?->rawSubject ?? $a->subject;
                return [
                    'subject_id' => $a->raw_subject_id ?? $a->subject_id,
                    'code' => $display->code ?? '',
                    'title' => $display->title ?? '',
                    'units' => (int) ($display->units ?? 0),
                ];
            });

        // Dropdown: unique raw subjects (no duplicates), optionally scoped to dean's programs
        $departmentId = $this->deanDepartmentId($dean);
        $scope = $this->deanProgramScope($dean);
        $rawSubjectIds = null;
        if ($departmentId !== null || ($scope !== null && $scope !== '')) {
            $subjectsQuery = Subject::query()->where('is_active', true)->select('raw_subject_id');
            if ($departmentId !== null) {
                $subjectsQuery->whereHas('program', fn ($q) => $q->where('department_id', $departmentId));
            } elseif ($scope !== null && $scope !== '') {
                $subjectsQuery->whereHas('program', fn ($q) => $q->where('program_name', 'like', '%' . $scope . '%'));
            }
            $rawSubjectIds = $subjectsQuery->whereNotNull('raw_subject_id')->pluck('raw_subject_id')->unique()->values()->all();
        }
        $rawQuery = RawSubject::query()->where('is_active', true)->orderBy('code');
        if ($rawSubjectIds !== null && count($rawSubjectIds) > 0) {
            $rawQuery->whereIn('id', $rawSubjectIds);
        } elseif ($rawSubjectIds !== null) {
            $rawQuery->whereRaw('1 = 0');
        }
        $subjects = $rawQuery->get()->map(fn ($s) => [
            'id' => $s->id,
            'code' => $s->code ?? '',
            'title' => $s->title ?? '',
            'units' => (int) ($s->units ?? 0),
        ]);

        $assignedUnits = $assignments->sum(fn ($a) => (int) ($a['units'] ?? 0));

        // include schedule slots for this professor (for the modal display)
        $semester = $request->get('semester');
        $schoolYear = $request->get('school_year');
        $schedulesQuery = \App\Models\ClassSchedule::query()
            ->where('professor_id', $professor->id)
            ->when($semester, fn($q) => $q->where('semester', $semester))
            ->when($schoolYear, fn($q) => $q->where('school_year', $schoolYear))
            ->with(['block', 'subject', 'room'])
            ->orderBy('day_of_week')
            ->orderBy('start_time');
        $schedules = $schedulesQuery->get();

        $dayNames = ['', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $schedulesArr = $schedules->map(function ($s) use ($dayNames) {
            return [
                'id' => $s->id,
                'course_code' => $s->subject->code ?? '',
                'course_title' => $s->subject->title ?? '',
                'units' => (int) ($s->subject->units ?? 0),
                'yr_program_block' => $s->block ? trim(($s->block->year_level ?? '') . '-' . ($s->block->program ?? $s->block->name ?? '')) : '',
                'day' => $dayNames[$s->day_of_week] ?? (string) $s->day_of_week,
                'start_time' => $s->start_time ? substr((string)$s->start_time,0,5) : '',
                'end_time' => $s->end_time ? substr((string)$s->end_time,0,5) : '',
                'room' => $s->room->name ?? $s->room->code ?? '',
                'is_overload' => (bool) ($s->is_overload ?? false),
            ];
        })->values()->all();

        $scheduleUnits = array_sum(array_map(fn($r) => (int) ($r['units'] ?? 0), $schedulesArr));

        $overloadArr = array_values(array_filter($schedulesArr, fn($r) => !empty($r['is_overload'])));

        return response()->json([
            'professor' => [
                'id' => $professor->id,
                'name' => $professor->name,
                'email' => $professor->email ?? '',
                'employment_type' => $professor->faculty_type ?? 'cos',
                'gender' => $professor->gender ?? '—',
                'max_units' => $maxUnits,
                'assigned_units' => $assignedUnits,
                'schedule_selection_limit' => $professor->schedule_selection_limit ?? null,
            ],
            'assignments' => $assignments->values()->all(),
            'subjects' => $subjects->values()->all(),
            'schedules' => $schedulesArr,
            'overload_schedules' => $overloadArr,
            'schedule_units' => $scheduleUnits,
            'total_units' => $scheduleUnits + $assignedUnits,
        ]);
    }

    /**
     * Store subject assignments for a professor (from Assign subjects popup). No room, date, time.
     */
    public function storeAssignments(Request $request, User $professor): JsonResponse|RedirectResponse
    {
        $dean = $request->user();
        $scopeService = app(SchedulingScopeService::class);
        if (!$scopeService->professorScopeCompatibleWithDean($professor, $dean)) {
            abort(403);
        }

        $rawSubjectIds = $request->input('subject_ids', []);
        if (!is_array($rawSubjectIds)) {
            $rawSubjectIds = [];
        }
        $rawSubjectIds = array_values(array_filter(array_map('intval', $rawSubjectIds)));

        $workloadService = app(ProfessorWorkloadService::class);
        $maxUnits = (int) ($professor->max_units ?? $workloadService->defaultMaxUnits($workloadService->getEmploymentType($professor)));

        $rawSubjects = RawSubject::query()->whereIn('id', $rawSubjectIds)->get()->keyBy('id');
        $totalUnits = 0;
        foreach ($rawSubjectIds as $id) {
            $totalUnits += (int) ($rawSubjects->get($id)?->units ?? 0);
        }
        if ($maxUnits > 0 && $totalUnits > $maxUnits) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'This professor has maximum units.'], 422);
            }
            return back()->withErrors(['subject_ids' => 'This professor has maximum units.']);
        }

        DB::transaction(function () use ($professor, $rawSubjectIds) {
            ProfessorSubjectAssignment::where('professor_id', $professor->id)->delete();
            foreach ($rawSubjectIds as $rawId) {
                if ($rawId > 0) {
                    ProfessorSubjectAssignment::create(['professor_id' => $professor->id, 'raw_subject_id' => $rawId]);
                }
            }
        });

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Subjects assigned.']);
        }
        return back()->with('success', 'Subjects assigned.');
    }

    private function indicator(int $total, int $max, bool $unitOverload, bool $timeOverload): string
    {
        if ($unitOverload || $timeOverload) {
            return 'red';
        }
        if ($max > 0 && $total >= $max * 0.9) {
            return 'yellow';
        }
        return 'green';
    }

    private function deanDepartmentId(?User $dean): ?int
    {
        if (! $dean) {
            return null;
        }
        $switch = session('role_switch');
        if ($dean->role === 'admin' && is_array($switch) && ($switch['active'] ?? false) && ($switch['as_role'] ?? '') === 'dean') {
            $sessionDeptId = $switch['department_id'] ?? null;
            return $sessionDeptId !== null && $sessionDeptId !== '' ? (int) $sessionDeptId : null;
        }
        if ($dean->role !== 'dean') {
            return null;
        }
        $id = $dean->department_id;
        return $id !== null && $id !== '' ? (int) $id : null;
    }

    private function deanProgramScope(?User $dean): ?string
    {
        $scope = trim((string) ($dean?->program_scope ?? ''));
        return $scope !== '' ? $scope : null;
    }
}
