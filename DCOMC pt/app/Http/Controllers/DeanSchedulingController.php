<?php

namespace App\Http\Controllers;

use App\Models\AcademicYearLevel;
use App\Models\Block;
use App\Models\ClassSchedule;
use App\Models\FormResponse;
use App\Models\Program;
use App\Models\Room;
use App\Models\ScopeScheduleSlot;
use App\Models\Subject;
use App\Models\User;
use App\Services\ProfessorWorkloadService;
use App\Services\SchedulingScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DeanSchedulingController extends Controller
{
    public function index(): View
    {
        $dean = auth()->user();
        $departmentId = $this->deanDepartmentId($dean);
        $scope = $departmentId !== null ? null : $this->deanProgramScope($dean);

        $schedules = ClassSchedule::query()
            ->with(['block', 'subject', 'room', 'professor'])
            ->when($departmentId !== null, function ($query) use ($departmentId) {
                $query->whereHas('block', function ($b) use ($departmentId) {
                    $b->whereHas('program', fn ($p) => $p->where('department_id', $departmentId));
                });
            })
            ->when($departmentId === null && $scope, function ($query) use ($scope) {
                $query->whereHas('block', fn ($b) => $b->where('program', 'like', '%' . $scope . '%'));
            })
            ->latest()
            ->get();

        $blocks = Block::query()
            ->with('program')
            ->where('is_active', true)
            ->when($departmentId !== null, fn ($q) => $q->whereHas('program', fn ($p) => $p->where('department_id', $departmentId)))
            ->when($departmentId === null && $scope, fn ($q) => $q->where('program', 'like', '%' . $scope . '%'))
            ->orderBy('code')
            ->get();
        $subjects = Subject::query()
            ->with(['program', 'academicYearLevel'])
            ->where('is_active', true)
            ->when($departmentId !== null, fn ($q) => $q->whereHas('program', fn ($p) => $p->where('department_id', $departmentId)))
            ->when($departmentId === null && $scope, fn ($q) => $q->whereHas('program', fn ($p) => $p->where('program_name', 'like', '%' . $scope . '%')))
            ->orderBy('code')
            ->get();
        $scopeService = app(SchedulingScopeService::class);
        $rooms = Room::query()
            ->where('is_active', true)
            ->when($departmentId !== null, fn ($q) => $scopeService->scopeRoomsForViewer($q, $dean))
            ->orderBy('name')
            ->get();
        $professors = User::query()
            ->whereNotNull('faculty_type')
            ->when($departmentId !== null, fn ($q) => $scopeService->scopeProfessorsForViewer($q, $dean))
            ->when($departmentId === null && $scope, fn ($q) => $q->where(function ($inner) use ($scope) {
                $inner->whereNull('program_scope')->orWhere('program_scope', '')->orWhere('program_scope', 'like', '%' . $scope . '%');
            }))
            ->orderBy('name')
            ->get();

        $preselectProfessorId = request()->integer('professor_id', 0);

        return view('dashboards.dean-scheduling', compact('schedules', 'blocks', 'subjects', 'rooms', 'professors', 'scope', 'preselectProfessorId'));
    }

    public function roomUtilization(): View
    {
        $dean = auth()->user();
        $departmentId = $this->deanDepartmentId($dean);
        $scope = $departmentId === null ? $this->deanProgramScope($dean) : null;
        $scopeService = app(SchedulingScopeService::class);
        $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

        $rooms = Room::query()
            ->where('is_active', true)
            ->when($departmentId !== null, fn ($q) => $scopeService->scopeRoomsForViewer($q, $dean))
            ->with([
                'schedules.block.program',
                'schedules.subject',
                'schedules.professor',
                'scopeScheduleSlots.program',
                'scopeScheduleSlots.academicYearLevel',
                'scopeScheduleSlots.professor',
            ])
            ->orderBy('name')
            ->get()
            ->map(function (Room $room) use ($scope, $departmentId, $dayNames) {
                $scopedClassSchedules = $room->schedules->filter(function ($schedule) use ($departmentId) {
                    if ($departmentId === null) {
                        return true;
                    }
                    $prog = $schedule->block?->program;
                    return $prog && (int) $prog->department_id === (int) $departmentId;
                });

                $scopedScopeSlots = $room->scopeScheduleSlots->filter(function ($slot) use ($departmentId) {
                    if ($departmentId === null) {
                        return true;
                    }
                    $prog = $slot->program;
                    return $prog && (int) $prog->department_id === (int) $departmentId;
                });

                $occupancySlots = [];

                foreach ($scopedScopeSlots as $slot) {
                    $day = $slot->day_of_week;
                    $start = $slot->start_time ? \Carbon\Carbon::parse($slot->start_time)->format('h:i A') : '—';
                    $end = $slot->end_time ? \Carbon\Carbon::parse($slot->end_time)->format('h:i A') : '—';
                    $profName = $slot->professor?->name ?? 'TBA';
                    $progName = $slot->program?->program_name ?? 'N/A';
                    $yearName = $slot->academicYearLevel?->name ?? '';
                    $sem = $slot->semester ?? '';
                    $occupancySlots[] = (object) [
                        'source' => 'Schedule by Program',
                        'line' => sprintf('Occupied by %s on %s from %s to %s (Schedule by Program – %s, %s, %s)', $profName, $dayNames[$day] ?? 'Day ' . $day, $start, $end, $progName, $yearName, $sem),
                    ];
                }

                foreach ($scopedClassSchedules as $schedule) {
                    $day = $schedule->day_of_week ?? 0;
                    $start = $schedule->start_time ? \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') : '—';
                    $end = $schedule->end_time ? \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') : '—';
                    $profName = $schedule->professor?->name ?? 'TBA';
                    $blockProg = $schedule->block?->program ? (is_object($schedule->block->program) ? $schedule->block->program->program_name ?? '' : (string) $schedule->block->program) : '';
                    $occupancySlots[] = (object) [
                        'source' => 'Class Schedule',
                        'line' => sprintf('Occupied by %s on %s from %s to %s (Block: %s)', $profName, $dayNames[$day] ?? 'Day ' . $day, $start, $end, $schedule->block?->code ?? $blockProg),
                    ];
                }

                $totalSlots = count($occupancySlots);
                $status = $totalSlots > 0 ? $totalSlots . ' slot' . ($totalSlots !== 1 ? 's' : '') . ' (Occupied)' : 'Available';

                return (object) [
                    'id' => $room->id,
                    'name' => $room->name,
                    'code' => $room->code ?? 'N/A',
                    'capacity' => $room->capacity ?? 'N/A',
                    'status' => $status,
                    'occupancy_slots' => $occupancySlots,
                    'schedule_count' => $totalSlots,
                ];
            })
            ->sortByDesc('schedule_count')
            ->values();

        return view('dashboards.dean-room-utilization', compact('rooms', 'scope'));
    }

    public function facultyAvailability(): View
    {
        $dean = auth()->user();
        $scopeService = app(SchedulingScopeService::class);
        $departmentId = $this->deanDepartmentId($dean);
        $scope = $departmentId === null ? $this->deanProgramScope($dean) : null;

        $faculty = User::query()
            ->whereNotNull('faculty_type')
            ->when(true, fn ($q) => $scopeService->scopeProfessorsForViewer($q, $dean))
            ->with(['teachingSchedules.subject', 'teachingSchedules.block.program'])
            ->orderBy('name')
            ->get()
            ->map(function (User $professor) use ($departmentId, $scope) {
                $scopedSchedules = $professor->teachingSchedules->filter(function ($schedule) use ($departmentId, $scope) {
                    if ($departmentId !== null) {
                        $prog = $schedule->block?->program;
                        return $prog && (int) $prog->department_id === (int) $departmentId;
                    }
                    if (! $scope) {
                        return true;
                    }
                    $prog = $schedule->block?->program;
                    $programName = is_object($prog) ? ($prog->program_name ?? '') : (string) ($schedule->block->program ?? '');
                    return str_contains(strtolower($programName), strtolower($scope));
                });

                $assignedUnits = (int) $scopedSchedules->sum(fn ($schedule) => (int) ($schedule->subject?->units ?? 0));
                $maxUnits = (int) ($professor->max_units ?? ($professor->faculty_type === 'permanent' ? 24 : 0));
                $employmentLabel = $professor->faculty_type ? ucfirst(str_replace('-', ' ', $professor->faculty_type)) : '—';

                return (object) [
                    'id' => $professor->id,
                    'name' => $professor->name,
                    'email' => $professor->email,
                    'gender' => $professor->gender ?? '—',
                    'faculty_type' => $professor->faculty_type,
                    'employment_label' => $employmentLabel,
                    'program_scope' => $professor->program_scope,
                    'assigned_units' => $assignedUnits,
                    'max_units' => $maxUnits,
                    'is_overload' => $maxUnits > 0 && $assignedUnits > $maxUnits,
                    'schedules' => $scopedSchedules,
                ];
            });

        return view('dashboards.dean-faculty-availability', compact('faculty', 'scope'));
    }

    public function facultyAvailabilityShow(User $professor): View|RedirectResponse
    {
        $dean = auth()->user();
        $scopeService = app(SchedulingScopeService::class);
        if (!$scopeService->professorScopeCompatibleWithDean($professor, $dean)) {
            return redirect()->route('dean.faculty-availability')->with('error', 'You cannot view this professor.');
        }

        $departmentId = $this->deanDepartmentId($dean);
        $scope = $departmentId === null ? $this->deanProgramScope($dean) : null;

        $schedulesQuery = ClassSchedule::query()
            ->where('professor_id', $professor->id)
            ->with(['subject', 'block.program']);
        if ($departmentId !== null) {
            $schedulesQuery->whereHas('block.program', fn ($q) => $q->where('department_id', $departmentId));
        } elseif ($scope) {
            $schedulesQuery->whereHas('block', fn ($b) => $b->where('program', 'like', '%' . $scope . '%'));
        } else {
            // No department/scope filter: show all schedules for this professor
        }
        $schedules = $schedulesQuery->orderBy('day_of_week')->orderBy('start_time')->get();

        $regularSchedules = $schedules->where('is_overload', false)->values();
        $overloadSchedules = $schedules->where('is_overload', true)->values();

        $dayNames = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

        $totalRegularUnits = (int) $regularSchedules->sum(fn ($s) => (int) ($s->subject->units ?? 0));
        $totalOverloadUnits = (int) $overloadSchedules->sum(fn ($s) => (int) ($s->subject->units ?? 0));

        return view('dashboards.dean-faculty-availability-show', [
            'professor' => $professor,
            'regularSchedules' => $regularSchedules,
            'overloadSchedules' => $overloadSchedules,
            'dayNames' => $dayNames,
            'totalRegularUnits' => $totalRegularUnits,
            'totalOverloadUnits' => $totalOverloadUnits,
        ]);
    }

    public function availableRooms(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'school_year' => ['nullable', 'string', 'max:50'],
            'semester' => ['nullable', 'string', 'max:50'],
        ]);

        $dean = auth()->user();
        $departmentId = $this->deanDepartmentId($dean);
        $scopeService = app(SchedulingScopeService::class);
        $rooms = Room::query()
            ->where('is_active', true)
            ->when($departmentId !== null, fn ($q) => $scopeService->scopeRoomsForViewer($q, $dean))
            ->whereNotIn('id', function ($query) use ($validated) {
                $query->select('room_id')
                    ->from('class_schedules')
                    ->where('day_of_week', $validated['day_of_week'])
                    ->when(!empty($validated['school_year']), function ($q) use ($validated) {
                        $q->where('school_year', $validated['school_year']);
                    })
                    ->when(!empty($validated['semester']), function ($q) use ($validated) {
                        $q->where('semester', $validated['semester']);
                    })
                    ->where(function ($inner) use ($validated) {
                        $inner->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                            ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                            ->orWhere(function ($cover) use ($validated) {
                                $cover->where('start_time', '<=', $validated['start_time'])
                                    ->where('end_time', '>=', $validated['end_time']);
                            });
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'capacity', 'building']);

        return response()->json(['rooms' => $rooms]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dean = auth()->user();
        $departmentId = $this->deanDepartmentId($dean);
        $scope = $departmentId === null ? $this->deanProgramScope($dean) : null;

        $validated = $request->validate([
            'block_id' => ['required', 'exists:blocks,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'professor_id' => ['required', 'exists:users,id'],
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'school_year' => ['nullable', 'string', 'max:50'],
            'semester' => ['nullable', 'string', 'max:50'],
        ]);

        $block = Block::with('program')->findOrFail($validated['block_id']);
        $subject = Subject::with('program', 'academicYearLevel')->findOrFail($validated['subject_id']);

        $scopeService = app(SchedulingScopeService::class);
        if ($departmentId !== null) {
            $blockProg = $block->program;
            if (!$blockProg || (int) $blockProg->department_id !== (int) $departmentId) {
                return back()->withErrors(['schedule' => 'You are not authorized to manage this program.'])->withInput();
            }
            if (!$subject->program || (int) $subject->program->department_id !== (int) $departmentId) {
                return back()->withErrors(['schedule' => 'You are not authorized to assign this subject.'])->withInput();
            }
            $room = Room::find($validated['room_id']);
            if (!$room || !$scopeService->roomScopeCompatibleWithDean($room, $dean)) {
                return back()->withErrors(['schedule' => 'You cannot assign a room from another department.'])->withInput();
            }
            $prof = User::find($validated['professor_id']);
            if (!$prof || !$scopeService->professorScopeCompatibleWithDean($prof, $dean)) {
                return back()->withErrors(['schedule' => 'You cannot assign a professor from another department.'])->withInput();
            }
        }

        $programId = Program::where('program_name', trim($block->program ?? $block->program?->program_name ?? ''))->value('id') ?? $block->program_id;
        $yearLevelId = AcademicYearLevel::where('name', trim($block->year_level ?? ''))->value('id');
        if (! $programId || ! $yearLevelId || $subject->program_id != $programId || $subject->academic_year_level_id != $yearLevelId) {
            return back()->withErrors(['schedule' => 'This subject does not belong to this program or year level.'])->withInput();
        }

        if ($departmentId === null && $scope) {
            if (
                ! str_contains(strtolower((string) ($block->program ?? '')), strtolower($scope)) ||
                ! str_contains(strtolower((string) ($subject->program?->program_name ?? '')), strtolower($scope))
            ) {
                return back()->withErrors(['schedule' => 'You can only manage schedules within your assigned program scope.'])->withInput();
            }
        }

        // Room conflict check
        $roomConflict = $this->hasTimeConflict(
            ClassSchedule::query()
                ->where('room_id', $validated['room_id'])
                ->where('day_of_week', $validated['day_of_week']),
            $validated['start_time'],
            $validated['end_time']
        );

        if ($roomConflict) {
            return back()->withErrors(['schedule' => 'Room is already occupied at the selected time.'])->withInput();
        }

        // Professor conflict check
        $profConflict = $this->hasTimeConflict(
            ClassSchedule::query()
                ->where('professor_id', $validated['professor_id'])
                ->where('day_of_week', $validated['day_of_week']),
            $validated['start_time'],
            $validated['end_time']
        );

        if ($profConflict) {
            return back()->withErrors(['schedule' => 'Professor already has a class at the selected time.'])->withInput();
        }

        // Block conflict check (prevents student overlap inside one block timetable)
        $blockConflict = $this->hasTimeConflict(
            ClassSchedule::query()
                ->where('block_id', $validated['block_id'])
                ->where('day_of_week', $validated['day_of_week']),
            $validated['start_time'],
            $validated['end_time']
        );

        if ($blockConflict) {
            return back()->withErrors(['schedule' => 'This block already has a class at the selected time.'])->withInput();
        }

        $professor = User::findOrFail($validated['professor_id']);
        $workloadService = app(ProfessorWorkloadService::class);
        $facultyRuleError = $workloadService->validateEmploymentRules(
            $professor,
            (int) $validated['day_of_week'],
            $validated['start_time'],
            $validated['end_time']
        );
        if ($facultyRuleError !== null) {
            return back()->withErrors(['schedule' => $facultyRuleError])->withInput();
        }

        $subject = Subject::with('program')->findOrFail($validated['subject_id']);
        $subjectUnits = (int) ($subject->units ?? 0);
        $existingUnits = (int) ClassSchedule::query()
            ->where('professor_id', $professor->id)
            ->with('subject')
            ->get()
            ->sum(fn ($schedule) => (int) ($schedule->subject?->units ?? 0));

        $maxUnits = (int) ($professor->max_units ?? ($professor->faculty_type === 'permanent' ? 24 : 0));
        if ($maxUnits > 0 && ($existingUnits + $subjectUnits) > $maxUnits) {
            return back()->withErrors([
                'schedule' => 'Professor has exceeded maximum teaching units.',
            ])->withInput();
        }

        $isOverload = $workloadService->shouldMarkTimeOverload($professor, $validated['start_time'] ?? '08:00', $validated['end_time'] ?? '09:00');

        ClassSchedule::create([
            ...$validated,
            'assigned_by' => auth()->id(),
            'status' => 'active',
            'is_overload' => $isOverload,
        ]);

        $studentIds = User::query()->where('block_id', $validated['block_id'])->pluck('id');
        FormResponse::query()
            ->whereIn('user_id', $studentIds)
            ->where('process_status', 'approved')
            ->update([
                'process_status' => 'scheduled',
                'process_notes' => 'Schedule assigned by dean.',
            ]);

        if ($maxUnits > 0 || $existingUnits > 0) {
            $professor->update([
                'assigned_units' => $existingUnits + $subjectUnits,
                'max_units' => $maxUnits > 0 ? $maxUnits : $professor->max_units,
            ]);
        }

        $message = 'Schedule assigned successfully.';
        if ($isOverload) {
            $message = 'Professor is now on overload time (beyond 5:00 PM).';
        }
        return back()->with('success', $message);
    }

    public function storeSubject(Request $request): RedirectResponse
    {
        $dean = auth()->user();
        $departmentId = $this->deanDepartmentId($dean);
        $scope = $departmentId === null ? $this->deanProgramScope($dean) : null;

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'units' => ['required', 'integer', 'min:1', 'max:6'],
            'program' => ['required', 'string', 'max:255'],
            'major' => ['nullable', 'string', 'max:255'],
            'year_level' => ['required', 'string', 'max:255'],
            'semester' => ['required', 'string', 'max:255'],
        ]);

        $programModel = Program::where('program_name', trim($validated['program']))->firstOrFail();
        $yearLevelModel = AcademicYearLevel::where('name', trim($validated['year_level']))->firstOrFail();

        if ($departmentId !== null && (int) $programModel->department_id !== (int) $departmentId) {
            return back()->withErrors(['subject' => 'You are not authorized to add subjects for this program.'])->withInput();
        }
        if ($departmentId === null && $scope && ! str_contains(strtolower($programModel->program_name), strtolower($scope))) {
            return back()->withErrors(['subject' => 'You can only add subjects under your assigned program scope.'])->withInput();
        }

        Subject::create([
            'code' => trim($validated['code']),
            'title' => trim($validated['title']),
            'units' => (int) $validated['units'],
            'program_id' => $programModel->id,
            'academic_year_level_id' => $yearLevelModel->id,
            'major' => $validated['major'] ? trim($validated['major']) : null,
            'semester' => trim($validated['semester']),
            'is_active' => true,
        ]);
        return back()->with('success', 'Subject added successfully.');
    }

    public function storeRoom(Request $request): RedirectResponse
    {
        $dean = auth()->user();
        $departmentId = $this->deanDepartmentId($dean);

        $validated = $request->validate([
            'room_code' => ['required', 'string', 'max:50', 'unique:rooms,code'],
            'room_name' => ['required', 'string', 'max:255'],
            'room_capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'room_building' => ['nullable', 'string', 'max:255'],
        ]);

        Room::create([
            'code' => $validated['room_code'],
            'name' => $validated['room_name'],
            'capacity' => $validated['room_capacity'],
            'building' => $validated['room_building'] ?? null,
            'is_active' => true,
            'department_id' => $departmentId,
        ]);

        return back()->with('success', 'Room added successfully.');
    }

    private function hasTimeConflict($baseQuery, string $startTime, string $endTime): bool
    {
        return (clone $baseQuery)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($inner) use ($startTime, $endTime) {
                        $inner->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            })
            ->exists();
    }

    private function deanProgramScope(?User $dean): ?string
    {
        $scope = trim((string) ($dean?->program_scope ?? ''));
        return $scope !== '' ? $scope : null;
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
}

