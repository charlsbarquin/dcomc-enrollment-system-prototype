<?php

namespace App\Http\Controllers;

use App\Models\AcademicYearLevel;
use App\Models\AcademicSemester;
use App\Models\Block;
use App\Models\ClassSchedule;
use App\Models\CorScope;
use App\Models\Department;
use App\Models\Program;
use App\Models\ProfessorSubjectAssignment;
use App\Models\Room;
use App\Models\SchoolYear;
use App\Models\ScopeScheduleSlot;
use App\Models\StudentCorRecord;
use App\Services\CorDeploymentService;
use App\Services\ProfessorWorkloadService;
use App\Services\SchedulingScopeService;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DeanScheduleByScopeController extends Controller
{
    /**
     * Schedule by Program for Dean: same folder structure as registrar, but programs/rooms/professors
     * filtered by dean's department. Backend enforces department_id on every action.
     */
    public function scheduleByScope(Request $request): View|RedirectResponse
    {
        $dean = auth()->user();
        $departmentId = $this->deanDepartmentId($dean);
        if ($departmentId === null) {
            return redirect()->route('dean.dashboard')
                ->with('error', 'Your account is not assigned to a department. Contact the administrator.');
        }

        $program = $request->query('program');
        $year = $request->query('year');
        $semester = $request->query('semester');
        $schoolYear = $request->query('school_year');

        $programs = Program::where('department_id', $departmentId)
            ->orderBy('program_name')
            ->pluck('program_name')
            ->all();
        $yearLevels = AcademicYearLevel::where('is_active', true)->orderBy('name')->pluck('name');
        $semesters = AcademicSemester::where('is_active', true)->orderBy('name')->pluck('name');
        $schoolYears = SchoolYear::query()->orderByDesc('start_year')->pluck('label')->all();
        $displayLabels = config('fee_programs.display_labels', []);

        if ($program !== null && $program !== '' && $year !== null && $year !== '' && $semester !== null && $semester !== '') {
            // Default school year so deploy form and COR archive get a value when URL has no school_year
            if ($schoolYear === null || trim($schoolYear ?? '') === '') {
                $schoolYear = $schoolYears[0] ?? null;
            }
            return $this->scheduleScopeTable($program, $year, $semester, $schoolYear, $programs, $yearLevels, $semesters, $schoolYears, $displayLabels, $departmentId);
        }
        if ($program !== null && $program !== '' && $year !== null && $year !== '') {
            return $this->scheduleScopeSemesterFolders($program, $year, $semesters, $displayLabels, $programs);
        }
        if ($program !== null && $program !== '') {
            return $this->scheduleScopeYearFolders($program, $yearLevels, $displayLabels, $programs);
        }

        return $this->scheduleScopeProgramFolders($programs, $displayLabels);
    }

    private function scheduleScopeProgramFolders(array $programs, array $displayLabels): View
    {
        $scheduleUrl = route('dean.schedule.by-scope');
        return view('dashboards.dean-schedule-by-scope', [
            'viewMode' => 'programs',
            'programs' => $programs,
            'displayLabels' => $displayLabels,
            'breadcrumb' => [['label' => 'Schedule by Program', 'url' => $scheduleUrl]],
        ]);
    }

    private function scheduleScopeYearFolders(string $program, $yearLevels, array $displayLabels, array $programs): View
    {
        $scheduleUrl = route('dean.schedule.by-scope');
        $programLabel = $displayLabels[$program] ?? $program;
        return view('dashboards.dean-schedule-by-scope', [
            'viewMode' => 'years',
            'program' => $program,
            'programLabel' => $programLabel,
            'yearLevels' => $yearLevels,
            'displayLabels' => $displayLabels,
            'programs' => $programs,
            'breadcrumb' => [
                ['label' => 'Schedule by Program', 'url' => $scheduleUrl],
                ['label' => $programLabel, 'url' => $scheduleUrl . '?program=' . urlencode($program)],
            ],
        ]);
    }

    private function scheduleScopeSemesterFolders(string $program, string $year, $semesters, array $displayLabels, array $programs): View
    {
        $scheduleUrl = route('dean.schedule.by-scope');
        $programLabel = $displayLabels[$program] ?? $program;
        return view('dashboards.dean-schedule-by-scope', [
            'viewMode' => 'semesters',
            'program' => $program,
            'programLabel' => $programLabel,
            'year' => $year,
            'semesters' => $semesters,
            'displayLabels' => $displayLabels,
            'programs' => $programs,
            'breadcrumb' => [
                ['label' => 'Schedule by Program', 'url' => $scheduleUrl],
                ['label' => $programLabel, 'url' => $scheduleUrl . '?program=' . urlencode($program)],
                ['label' => $year, 'url' => $scheduleUrl . '?program=' . urlencode($program) . '&year=' . urlencode($year)],
            ],
        ]);
    }

    private function scheduleScopeTable(string $program, string $year, string $semester, ?string $schoolYear, array $programs, $yearLevels, $semesters, array $schoolYears, array $displayLabels, int $departmentId): View|RedirectResponse
    {
        $programModel = Program::where('program_name', $program)->where('department_id', $departmentId)->first();
        if (!$programModel) {
            return redirect()->route('dean.schedule.by-scope')
                ->with('error', 'You are not authorized to manage this program.');
        }

        $yearLevelModel = AcademicYearLevel::where('name', $year)->first();
        if (!$yearLevelModel) {
            return redirect()->route('dean.schedule.by-scope')
                ->with('error', 'Invalid year level.');
        }

        $schoolYear = $schoolYear !== null && $schoolYear !== '' ? $schoolYear : ($schoolYears[0] ?? null);

        $subjects = collect();
        $slotsBySubject = [];

        $corScope = null;
        if ($schoolYear) {
            $corScope = CorScope::findForScope(
                $programModel->id,
                $yearLevelModel->id,
                $semester,
                $schoolYear,
                null
            );
        }
        if ($corScope) {
            $subjects = $corScope->subjects()->where('subjects.is_active', true)->orderBy('subjects.code')->get();
        }
        if ($subjects->isEmpty()) {
            $subjects = Subject::query()
                ->forProgramAndYear($programModel->id, $yearLevelModel->id)
                ->where('semester', $semester)
                ->where('is_active', true)
                ->orderBy('code')
                ->get();
        }

        $slotsQuery = ScopeScheduleSlot::query()
            ->where('program_id', $programModel->id)
            ->where('academic_year_level_id', $yearLevelModel->id)
            ->where('semester', $semester)
            ->with(['room', 'professor']);
        if ($schoolYear) {
            $slotsQuery->where(function ($q) use ($schoolYear) {
                $q->where('school_year', $schoolYear)->orWhereNull('school_year');
            });
        }
        $slots = $slotsQuery->get();
        foreach ($slots as $slot) {
            $slotsBySubject[$slot->subject_id][] = $slot;
        }

        $scopeService = app(SchedulingScopeService::class);
        $dean = auth()->user();
        $rooms = Room::query()
            ->where('is_active', true)
            ->when(true, fn ($q) => $scopeService->scopeRoomsForViewer($q, $dean))
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $department = Department::find($programModel->department_id);
        $allProfessorsAllSubjects = $department && $department->all_professors_all_subjects;

        $currentScopeSlotCounts = ScopeScheduleSlot::query()
            ->where('program_id', $programModel->id)
            ->where('academic_year_level_id', $yearLevelModel->id)
            ->where('semester', $semester)
            ->when($schoolYear, fn ($q) => $q->where(function ($q2) use ($schoolYear) {
                $q2->where('school_year', $schoolYear)->orWhereNull('school_year');
            }))
            ->whereNotNull('professor_id')
            ->selectRaw('professor_id, count(*) as cnt')
            ->groupBy('professor_id')
            ->pluck('cnt', 'professor_id')
            ->all();

        $professorsPerSubject = [];
        foreach ($subjects as $subject) {
            if ($allProfessorsAllSubjects) {
                // When "all teachers all subjects" is ON, include all professors visible to the dean
                // (same set as Manage Professor: scope only, no department_id filter).
                $professorsPerSubject[$subject->id] = User::query()
                    ->whereNotNull('faculty_type')
                    ->when(true, fn ($q) => $scopeService->scopeProfessorsForViewer($q, $dean))
                    ->orderBy('name')
                    ->get(['id', 'name', 'email', 'faculty_type', 'max_units']);
                continue;
            }

            $eligibleIds = ProfessorSubjectAssignment::getEligibleProfessorIds($subject->id, $semester, $schoolYear);
            $professorLimitMap = User::query()
                ->whereIn('id', $eligibleIds)
                ->pluck('schedule_selection_limit', 'id')
                ->all();
            $filteredIds = array_values(array_filter($eligibleIds, function ($profId) use ($currentScopeSlotCounts, $professorLimitMap) {
                $limit = isset($professorLimitMap[$profId]) ? (int) $professorLimitMap[$profId] : null;
                if ($limit === null || $limit <= 0) {
                    return true;
                }
                $current = (int) ($currentScopeSlotCounts[$profId] ?? 0);
                return $current < $limit;
            }));
            if (empty($filteredIds)) {
                $professorsPerSubject[$subject->id] = collect();
                continue;
            }
            $professorsPerSubject[$subject->id] = User::query()
                ->whereIn('id', $filteredIds)
                ->whereNotNull('faculty_type')
                ->when(true, fn ($q) => $scopeService->scopeProfessorsForViewer($q, $dean))
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'faculty_type', 'max_units']);
        }

        $scheduleUrl = route('dean.schedule.by-scope');
        $programLabel = $displayLabels[$program] ?? $program;
        $tableUrl = $scheduleUrl . '?program=' . urlencode($program) . '&year=' . urlencode($year) . '&semester=' . urlencode($semester) . ($schoolYear ? '&school_year=' . urlencode($schoolYear) : '');
        $slotIndex = 0;
        $slotRows = [];
        // Show every subject: either its existing slot(s) or one placeholder row (slot=null) so pushed subjects appear and the dean can fill day/time/room/professor.
        foreach ($subjects as $subject) {
            $slots = $slotsBySubject[$subject->id] ?? [];
            if (! empty($slots)) {
                foreach ($slots as $slot) {
                    $slotRows[] = ['subject' => $subject, 'slot' => $slot, 'slot_index' => $slotIndex++];
                }
            } else {
                // No slot yet (e.g. after Registrar Push): show one row with blank fields so the dean can assign schedule.
                $slotRows[] = ['subject' => $subject, 'slot' => null, 'slot_index' => $slotIndex++];
            }
        }
        $nextSlotIndex = $slotIndex;
        $availableSubjectsForAdd = $subjects->filter(fn ($s) => !isset($slotsBySubject[$s->id]) || empty($slotsBySubject[$s->id]))->values();

        // Room occupancy: Schedule by Program + block schedules + deployed COR archive (same school year + semester only).
        $scopeRoomSlots = ScopeScheduleSlot::query()
            ->whereNotNull('room_id')
            ->where('semester', $semester)
            ->when($schoolYear !== null && $schoolYear !== '', function ($q) use ($schoolYear) {
                $q->where(function ($qq) use ($schoolYear) {
                    $qq->where('school_year', $schoolYear)->orWhereNull('school_year');
                });
            })
            ->get(['id', 'room_id', 'day_of_week', 'start_time', 'end_time', 'program_id', 'school_year', 'semester']);
        $classRoomSlots = ClassSchedule::query()
            ->whereNotNull('room_id')
            ->where('semester', $semester)
            ->when($schoolYear !== null && $schoolYear !== '', function ($q) use ($schoolYear) {
                $q->where(function ($qq) use ($schoolYear) {
                    $qq->where('school_year', $schoolYear)->orWhereNull('school_year');
                });
            })
            ->get(['id', 'room_id', 'day_of_week', 'start_time', 'end_time', 'school_year', 'semester']);
        // Single source of truth: room/professor occupancy = Scope slots + Class schedules + COR Archive.
        // COR archive must always be included so dropdowns and save validation never allow double-booking.
        $corArchiveRoomOcc = $this->roomOccupancyFromCorArchive($schoolYear, $semester);
        // #region agent log
        $logPath = base_path('debug-743649.log');
        $logLine = json_encode(['sessionId' => '743649', 'hypothesisId' => 'H1', 'message' => 'COR room occupancy', 'data' => ['school_year' => $schoolYear, 'semester' => $semester, 'corArchiveRoomCount' => count($corArchiveRoomOcc), 'first' => $corArchiveRoomOcc[0] ?? null], 'timestamp' => (int) (microtime(true) * 1000)]) . "\n";
        @file_put_contents($logPath, $logLine, FILE_APPEND);
        // #endregion

        $roomIdsList = $rooms->pluck('id')->all();
        foreach ($slotRows as &$row) {
            $slot = $row['slot'];
            $day = ($slot && $slot->day_of_week !== null) ? (int) $slot->day_of_week : 1;
            $start = ($slot && $slot->start_time) ? \Carbon\Carbon::parse($slot->start_time)->format('H:i') : '08:00';
            $end = ($slot && $slot->end_time) ? \Carbon\Carbon::parse($slot->end_time)->format('H:i') : '09:00';
            $currentSlotId = $slot?->id;

            $unavailableRoomIds = [];
            foreach ($scopeRoomSlots as $occ) {
                if ((int) $occ->day_of_week !== $day) {
                    continue;
                }
                if ($occ->id === $currentSlotId) {
                    continue;
                }
                $occStart = $occ->start_time ? \Carbon\Carbon::parse($occ->start_time)->format('H:i') : '08:00';
                $occEnd = $occ->end_time ? \Carbon\Carbon::parse($occ->end_time)->format('H:i') : '09:00';
                if ($this->timesOverlap($start, $end, $occStart, $occEnd)) {
                    $unavailableRoomIds[(int) $occ->room_id] = true;
                }
            }
            foreach ($classRoomSlots as $occ) {
                if ((int) $occ->day_of_week !== $day) {
                    continue;
                }
                $occStart = $occ->start_time ? \Carbon\Carbon::parse($occ->start_time)->format('H:i') : '08:00';
                $occEnd = $occ->end_time ? \Carbon\Carbon::parse($occ->end_time)->format('H:i') : '09:00';
                if ($this->timesOverlap($start, $end, $occStart, $occEnd)) {
                    $unavailableRoomIds[(int) $occ->room_id] = true;
                }
            }
            foreach ($corArchiveRoomOcc as $occ) {
                if ((int) $occ['day_of_week'] !== $day) {
                    continue;
                }
                if ($this->timesOverlap($start, $end, $occ['start_time'], $occ['end_time'])) {
                    $unavailableRoomIds[(int) $occ['room_id']] = true;
                }
            }
            $row['available_room_ids'] = array_values(array_filter($roomIdsList, fn ($id) => !isset($unavailableRoomIds[$id])));
        }
        unset($row);

        $formatRoomOcc = function ($s) {
            return [
                'room_id' => (int) $s->room_id,
                'day_of_week' => (int) $s->day_of_week,
                'start_time' => $s->start_time ? \Carbon\Carbon::parse($s->start_time)->format('H:i') : '08:00',
                'end_time' => $s->end_time ? \Carbon\Carbon::parse($s->end_time)->format('H:i') : '09:00',
            ];
        };
        $roomOccupancyForJs = $scopeRoomSlots->map($formatRoomOcc)
            ->concat($classRoomSlots->map($formatRoomOcc))
            ->concat(collect($corArchiveRoomOcc))
            ->values()->all();
        // #region agent log
        $logLine2 = json_encode(['sessionId' => '743649', 'hypothesisId' => 'H2', 'message' => 'roomOccupancyForJs', 'data' => ['total' => count($roomOccupancyForJs), 'fromCor' => count($corArchiveRoomOcc)], 'timestamp' => (int) (microtime(true) * 1000)]) . "\n";
        @file_put_contents($logPath, $logLine2, FILE_APPEND);
        // #endregion

        // Professor occupancy: Schedule by Program + block schedules + deployed COR archive (same school year + semester only).
        $scopeProfSlots = ScopeScheduleSlot::query()
            ->whereNotNull('professor_id')
            ->where('semester', $semester)
            ->when($schoolYear !== null && $schoolYear !== '', function ($q) use ($schoolYear) {
                $q->where(function ($qq) use ($schoolYear) {
                    $qq->where('school_year', $schoolYear)->orWhereNull('school_year');
                });
            })
            ->get(['professor_id', 'day_of_week', 'start_time', 'end_time', 'school_year', 'semester']);
        $classProfSlots = ClassSchedule::query()
            ->whereNotNull('professor_id')
            ->where('semester', $semester)
            ->when($schoolYear !== null && $schoolYear !== '', function ($q) use ($schoolYear) {
                $q->where(function ($qq) use ($schoolYear) {
                    $qq->where('school_year', $schoolYear)->orWhereNull('school_year');
                });
            })
            ->get(['professor_id', 'day_of_week', 'start_time', 'end_time', 'school_year', 'semester']);
        $corArchiveProfOcc = $this->professorOccupancyFromCorArchive($schoolYear, $semester);
        $formatProfOcc = function ($s) {
            return [
                'professor_id' => (int) $s->professor_id,
                'day_of_week' => (int) $s->day_of_week,
                'start_time' => $s->start_time ? \Carbon\Carbon::parse($s->start_time)->format('H:i') : '08:00',
                'end_time' => $s->end_time ? \Carbon\Carbon::parse($s->end_time)->format('H:i') : '09:00',
            ];
        };
        $professorOccupancyForJs = $scopeProfSlots->map($formatProfOcc)
            ->concat($classProfSlots->map($formatProfOcc))
            ->concat(collect($corArchiveProfOcc))
            ->values()->all();

        $days = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
        // Show all active blocks for this program/year/semester so dean can deploy or archive regardless of block's school_year_label (fix: blocks were excluded when label differed from selected year, e.g. 2025-2026 vs 2026-2027)
        $blocks = Block::query()
            ->where('program_id', $programModel->id)
            ->where('year_level', $year)
            ->where('semester', $semester)
            ->where('is_active', true)
            ->orderBy('code')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'shift']);
        

        $scheduleSlotScriptData = [
            'days' => $days,
            'rooms' => $rooms->map(fn ($r) => ['id' => $r->id, 'name' => $r->code ?? $r->name])->values()->all(),
            'roomOccupancy' => $roomOccupancyForJs,
            'professorOccupancy' => $professorOccupancyForJs,
            'professorsBySubject' => collect($professorsPerSubject)->map(fn ($list) => $list->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'faculty_type' => $p->faculty_type ?? '', 'max_units' => $p->max_units ?? 0])->values()->all())->all(),
            'subjects' => $subjects->map(fn ($s) => ['id' => $s->id, 'code' => $s->code, 'title' => $s->title, 'units' => $s->units])->values()->all(),
            'availableSubjectsForAdd' => $availableSubjectsForAdd->map(fn ($s) => ['id' => $s->id, 'code' => $s->code, 'title' => $s->title, 'units' => $s->units])->values()->all(),
            'nextSlotIndex' => $nextSlotIndex,
        ];
        return view('dashboards.dean-schedule-by-scope', [
            'viewMode' => 'table',
            'program' => $program,
            'programLabel' => $programLabel,
            'year' => $year,
            'semester' => $semester,
            'school_year' => $schoolYear,
            'schoolYears' => $schoolYears,
            'program_id' => $programModel->id,
            'academic_year_level_id' => $yearLevelModel->id,
            'blocks' => $blocks,
            'subjects' => $subjects,
            'slotsBySubject' => $slotsBySubject,
            'slotRows' => $slotRows,
            'rooms' => $rooms,
            'professorsPerSubject' => $professorsPerSubject,
            'yearLevels' => $yearLevels,
            'semesters' => $semesters,
            'programs' => $programs,
            'displayLabels' => $displayLabels,
            'scheduleSlotScriptData' => $scheduleSlotScriptData,
            'availableSubjectsForAdd' => $availableSubjectsForAdd,
            'hasScheduleSlots' => collect($slotRows)->contains(fn ($r) => isset($r['slot']) && $r['slot'] !== null),
            'breadcrumb' => [
                ['label' => 'Schedule by Program', 'url' => $scheduleUrl],
                ['label' => $programLabel, 'url' => $scheduleUrl . '?program=' . urlencode($program)],
                ['label' => $year, 'url' => $scheduleUrl . '?program=' . urlencode($program) . '&year=' . urlencode($year)],
                ['label' => $semester, 'url' => $tableUrl],
            ],
        ]);
    }

    public function saveScopeScheduleSlots(Request $request): RedirectResponse
    {
        $dean = auth()->user();
        $departmentId = $this->deanDepartmentId($dean);
        if ($departmentId === null) {
            return redirect()->route('dean.dashboard')
                ->with('error', 'Your account is not assigned to a department.');
        }

        $validated = $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'academic_year_level_id' => ['required', 'integer', 'exists:academic_year_levels,id'],
            'semester' => ['required', 'string', 'max:100'],
            'school_year' => ['nullable', 'string', 'max:100'],
            'slots' => ['nullable', 'array'],
            'slots.*.subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'slots.*.day_of_week' => ['nullable', 'integer', 'min:1', 'max:7'],
            'slots.*.start_time' => ['nullable', 'string', 'date_format:H:i'],
            'slots.*.end_time' => ['nullable', 'string', 'date_format:H:i'],
            'slots.*.room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'slots.*.professor_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $slotsInput = $validated['slots'] ?? [];
        $slotsInput = $this->normalizeScopeSlotsFromFirstPerSubject($slotsInput);

        $program = Program::find($validated['program_id']);
        if (!$program || (int) $program->department_id !== (int) $departmentId) {
            return back()->withErrors(['program_id' => 'You are not authorized to manage this program.'])->withInput();
        }

        $department = Department::find($departmentId);
        $allProfessorsAllSubjects = $department && $department->all_professors_all_subjects;

        $programId = (int) $validated['program_id'];
        $yearLevelId = (int) $validated['academic_year_level_id'];
        $semester = trim($validated['semester']);
        $schoolYear = isset($validated['school_year']) && $validated['school_year'] !== '' ? trim($validated['school_year']) : null;

        $scopeService = app(SchedulingScopeService::class);
        foreach ($slotsInput as $row) {
            if (!empty($row['room_id'])) {
                $room = Room::find($row['room_id']);
                if (!$room || !$scopeService->roomScopeCompatibleWithDean($room, $dean)) {
                    return back()->withErrors(['slots' => 'You cannot assign a room from another department.'])->withInput();
                }
            }
            if (!empty($row['professor_id'])) {
                $prof = User::find($row['professor_id']);
                if (!$prof || !$scopeService->professorScopeCompatibleWithDean($prof, $dean)) {
                    return back()->withErrors(['slots' => 'You cannot assign a professor from another department.'])->withInput();
                }
                // When "Set all teachers to handle all subjects" is on, allow any professor in scope regardless of subject assignment.
                if (! $allProfessorsAllSubjects && ! ProfessorSubjectAssignment::isProfessorEligibleForSubject(
                    (int) $row['professor_id'],
                    (int) $row['subject_id'],
                    $semester,
                    $schoolYear
                )) {
                    return back()->withErrors(['slots' => 'One or more professors are not assigned to teach that subject for this semester/school year. Assign subjects in Manage Professor first.'])->withInput();
                }
            }
        }

        $workloadService = app(ProfessorWorkloadService::class);
        $filledSlots = array_filter($slotsInput, fn ($r) => !empty($r['start_time']) && !empty($r['end_time']) && !empty($r['day_of_week']));
        foreach ($filledSlots as $row) {
            if (empty($row['professor_id'])) {
                continue;
            }
            $prof = User::find($row['professor_id']);
            if ($prof) {
                $err = $workloadService->validateEmploymentRules(
                    $prof,
                    (int) $row['day_of_week'],
                    $row['start_time'],
                    $row['end_time']
                );
                if ($err) {
                    return back()->withErrors(['slots' => $err . ' (Professor: ' . ($prof->name ?? '') . ')'])->withInput();
                }
                // Enforce max units: compute subject units and existing assigned ClassSchedule units
                $subject = \App\Models\Subject::find($row['subject_id']);
                $subjectUnits = $subject ? (int) ($subject->units ?? 0) : 0;
                if ($workloadService->willExceedMaxUnits($prof, $subjectUnits, $semester, $schoolYear)) {
                    return back()->withErrors(['slots' => 'Assigning this subject would exceed maximum teaching units for Professor: ' . ($prof->name ?? '')])->withInput();
                }
            }
        }

        $conflictErr = $this->detectScopeSlotConflicts($filledSlots);
        if ($conflictErr) {
            return back()->withErrors(['slots' => $conflictErr])->withInput();
        }

        // Mandatory: reject save if any slot conflicts with COR Archive (same room or professor at same day/time).
        $corRoomOcc = $this->roomOccupancyFromCorArchive($schoolYear, $semester);
        $corProfOcc = $this->professorOccupancyFromCorArchive($schoolYear, $semester);
        $corConflictErr = $this->detectScopeSlotConflictsWithCorArchive($filledSlots, $corRoomOcc, $corProfOcc);
        if ($corConflictErr) {
            return back()->withErrors(['slots' => $corConflictErr])->withInput();
        }

        DB::transaction(function () use ($programId, $yearLevelId, $semester, $schoolYear, $slotsInput, $workloadService) {
            $deleteQuery = ScopeScheduleSlot::query()
                ->where('program_id', $programId)
                ->where('academic_year_level_id', $yearLevelId)
                ->where('semester', $semester);
            if ($schoolYear) {
                $deleteQuery->where(function ($q) use ($schoolYear) {
                    $q->where('school_year', $schoolYear)->orWhereNull('school_year');
                });
            }
            $deleteQuery->delete();
            foreach ($slotsInput as $row) {
                $subjectId = (int) $row['subject_id'];
                $dayOfWeek = isset($row['day_of_week']) && $row['day_of_week'] !== '' && $row['day_of_week'] !== null ? (int) $row['day_of_week'] : null;
                $startTime = !empty($row['start_time']) ? $row['start_time'] : null;
                $endTime = !empty($row['end_time']) ? $row['end_time'] : null;
                $professorId = !empty($row['professor_id']) ? (int) $row['professor_id'] : null;
                $isOverload = false;
                if ($professorId && $startTime && $endTime) {
                    $prof = User::find($professorId);
                    if ($prof) {
                        $isOverload = $workloadService->shouldMarkTimeOverload(
                            $prof,
                            $startTime,
                            $endTime
                        );
                    }
                }
                ScopeScheduleSlot::create([
                    'program_id' => $programId,
                    'academic_year_level_id' => $yearLevelId,
                    'semester' => $semester,
                    'school_year' => $schoolYear,
                    'subject_id' => $subjectId,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'room_id' => !empty($row['room_id']) ? (int) $row['room_id'] : null,
                    'professor_id' => $professorId,
                    'is_overload' => $isOverload,
                ]);
            }
        });

        $slotsSaved = count(array_filter($slotsInput, fn ($r) => !empty($r['start_time']) && !empty($r['end_time'])));
        

        $programName = $program->program_name ?? '';
        $redirectUrl = route('dean.schedule.by-scope') . '?program=' . urlencode($programName) . '&year=' . urlencode((string) $request->input('year')) . '&semester=' . urlencode($semester);
        if ($schoolYear) {
            $redirectUrl .= '&school_year=' . urlencode($schoolYear);
        }
        return redirect()->to($redirectUrl)->with('success', 'Schedule saved.');
    }

    /**
     * Deploy COR: validate schedule complete, then create immutable student_cor_records for students in scope.
     * Only Dean can deploy. Archive is read-only.
     */
    public function deployCor(Request $request): RedirectResponse
    {
        $dean = auth()->user();
        if ($dean->role !== 'dean') {
            return redirect()->route('dean.dashboard')->with('error', 'Only Dean can deploy COR.');
        }
        $departmentId = $this->deanDepartmentId($dean);
        if ($departmentId === null) {
            return redirect()->route('dean.dashboard')->with('error', 'Your account is not assigned to a department.');
        }

        $validated = $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'academic_year_level_id' => ['required', 'integer', 'exists:academic_year_levels,id'],
            'block_id' => ['required', 'integer', 'exists:blocks,id'],
            'shift' => ['nullable', 'string', 'max:50'],
            'semester' => ['required', 'string', 'max:100'],
            'school_year' => ['nullable', 'string', 'max:100'],
        ]);

        $program = Program::find($validated['program_id']);
        if (!$program || (int) $program->department_id !== (int) $departmentId) {
            return back()->with('error', 'You are not authorized to deploy COR for this program.');
        }

        $block = Block::find($validated['block_id']);
        $shift = $validated['shift'] ?? $block?->shift ?? null;

        // Ensure school year is set so COR Archive and student COR resolve correctly
        $schoolYear = isset($validated['school_year']) && trim($validated['school_year'] ?? '') !== ''
            ? trim($validated['school_year'])
            : (SchoolYear::query()->orderByDesc('start_year')->value('label') ?? $block?->school_year_label ?? '');

        $service = app(CorDeploymentService::class);
        $result = $service->deploy(
            (int) $validated['program_id'],
            (int) $validated['academic_year_level_id'],
            (int) $validated['block_id'],
            $shift,
            trim($validated['semester']),
            $schoolYear,
            (int) $dean->id
        );

        // Always redirect to COR Archive with the block id in the path so the dean sees the correct scope and block (with or without records).
        $yearLevelName = AcademicYearLevel::find($validated['academic_year_level_id'])?->name ?? '';
        $archiveParams = [
            'programId' => $validated['program_id'],
            'yearLevel' => $yearLevelName,
            'semester' => trim($validated['semester']),
            'deployedBlock' => (int) $validated['block_id'],
        ];
        $archiveUrl = route('cor.archive.show', $archiveParams);
        if ($schoolYear !== '' && $schoolYear !== null) {
            $archiveUrl .= '?' . http_build_query(['school_year' => $schoolYear]);
        }

        if (!$result['success']) {
            
            return redirect()->to($archiveUrl)->with('error', $result['message']);
        }

        $msg = $result['message'];
        if (isset($result['students_count'], $result['records_count'])) {
            $msg .= ' Students: ' . $result['students_count'] . ', Records: ' . $result['records_count'];
        }
        
        return redirect()->to($archiveUrl)->with('success', $msg)->with('deployed_block_id', (int) $validated['block_id']);
    }

    /**
     * Archive-only fetch: save the current working schedule into COR Archive (no student push).
     * This allows the dean to 'fetch' the schedule template into the archive even if some
     * subjects lack professor/time or the block has no students.
     */
    public function fetchCor(Request $request): RedirectResponse
    {
        $dean = auth()->user();
        if ($dean->role !== 'dean') {
            return redirect()->route('dean.dashboard')->with('error', 'Only Dean can perform this action.');
        }
        $departmentId = $this->deanDepartmentId($dean);
        if ($departmentId === null) {
            return redirect()->route('dean.dashboard')->with('error', 'Your account is not assigned to a department.');
        }

        $validated = $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'academic_year_level_id' => ['required', 'integer', 'exists:academic_year_levels,id'],
            'block_id' => ['required', 'integer', 'exists:blocks,id'],
            'shift' => ['nullable', 'string', 'max:50'],
            'semester' => ['required', 'string', 'max:100'],
            'school_year' => ['nullable', 'string', 'max:100'],
        ]);

        $program = Program::find($validated['program_id']);
        if (!$program || (int) $program->department_id !== (int) $departmentId) {
            return back()->with('error', 'You are not authorized to archive COR for this program.');
        }

        $block = Block::find($validated['block_id']);

        $schoolYear = isset($validated['school_year']) && trim($validated['school_year'] ?? '') !== ''
            ? trim($validated['school_year'])
            : (SchoolYear::query()->orderByDesc('start_year')->value('label') ?? $block?->school_year_label ?? '');

        $service = app(CorDeploymentService::class);
        $result = $service->deployArchiveOnly(
            (int) $validated['program_id'],
            (int) $validated['academic_year_level_id'],
            (int) $validated['block_id'],
            $validated['shift'] ?? $block?->shift ?? null,
            trim($validated['semester']),
            $schoolYear,
            (int) $dean->id
        );

        $yearLevelName = AcademicYearLevel::find($validated['academic_year_level_id'])?->name ?? '';
        $archiveParams = [
            'programId' => $validated['program_id'],
            'yearLevel' => $yearLevelName,
            'semester' => trim($validated['semester']),
            'deployedBlock' => (int) $validated['block_id'],
        ];
        $archiveUrl = route('cor.archive.show', $archiveParams);
        if ($schoolYear !== '' && $schoolYear !== null) {
            $archiveUrl .= '?' . http_build_query(['school_year' => $schoolYear]);
        }

        if (!$result['success']) {
            return redirect()->route('dean.schedule.by-scope')->with('error', $result['message']);
        }

        $msg = $result['message'] ?? 'Archive saved.';
        if (isset($result['records_count'])) {
            $msg .= ' Records: ' . $result['records_count'];
        }

        // Redirect to COR Archive show for this scope/block so the dean sees the saved schedule in the right folder.
        $archiveUrl = route('cor.archive.show', $archiveParams);
        if ($schoolYear !== '' && $schoolYear !== null) {
            $archiveUrl .= '?' . http_build_query(['school_year' => $schoolYear]);
        }
        return redirect()->to($archiveUrl)->with('success', $msg)->with('deployed_block_id', (int) $validated['block_id']);
    }

    /**
     * For each subject, slots with empty start/end/room/professor get values from the first slot of that subject that has them.
     * Original order of slots is preserved.
     */
    private function normalizeScopeSlotsFromFirstPerSubject(array $slots): array
    {
        $refBySubject = [];
        foreach ($slots as $row) {
            $sid = (int) $row['subject_id'];
            if (!isset($refBySubject[$sid]) && !empty($row['start_time']) && !empty($row['end_time'])) {
                $refBySubject[$sid] = $row;
            }
        }
        $normalized = [];
        foreach ($slots as $row) {
            $sid = (int) $row['subject_id'];
            $ref = $refBySubject[$sid] ?? null;
            if ($ref !== null) {
                if (empty($row['start_time'])) {
                    $row['start_time'] = $ref['start_time'];
                }
                if (empty($row['end_time'])) {
                    $row['end_time'] = $ref['end_time'];
                }
                if (empty($row['room_id']) && !empty($ref['room_id'])) {
                    $row['room_id'] = $ref['room_id'];
                }
                if (empty($row['professor_id']) && !empty($ref['professor_id'])) {
                    $row['professor_id'] = $ref['professor_id'];
                }
            }
            $normalized[] = $row;
        }
        return $normalized;
    }

    /**
     * Check if submitted slots conflict with COR archive (room or professor already used at same day/time in archive).
     *
     * @param array<int, array{day_of_week?: int, start_time?: string, end_time?: string, room_id?: int, professor_id?: int}> $slots
     * @param array<int, array{room_id: int, day_of_week: int, start_time: string, end_time: string}> $corRoomOcc
     * @param array<int, array{professor_id: int, day_of_week: int, start_time: string, end_time: string}> $corProfOcc
     */
    private function detectScopeSlotConflictsWithCorArchive(array $slots, array $corRoomOcc, array $corProfOcc): ?string
    {
        foreach ($slots as $row) {
            $day = isset($row['day_of_week']) && $row['day_of_week'] !== '' && $row['day_of_week'] !== null ? (int) $row['day_of_week'] : null;
            $start = ! empty($row['start_time']) ? $row['start_time'] : null;
            $end = ! empty($row['end_time']) ? $row['end_time'] : null;
            $roomId = ! empty($row['room_id']) ? (int) $row['room_id'] : null;
            $profId = ! empty($row['professor_id']) ? (int) $row['professor_id'] : null;
            if ($day === null || $start === null || $end === null) {
                continue;
            }
            if ($roomId !== null) {
                foreach ($corRoomOcc as $occ) {
                    if ((int) $occ['day_of_week'] !== $day || (int) $occ['room_id'] !== $roomId) {
                        continue;
                    }
                    if ($this->timesOverlap($start, $end, $occ['start_time'], $occ['end_time'])) {
                        return 'Schedule conflict with COR Archive: room is already used at this day/time in a deployed or archived COR. Choose another room or time.';
                    }
                }
            }
            if ($profId !== null) {
                foreach ($corProfOcc as $occ) {
                    if ((int) $occ['day_of_week'] !== $day || (int) $occ['professor_id'] !== $profId) {
                        continue;
                    }
                    if ($this->timesOverlap($start, $end, $occ['start_time'], $occ['end_time'])) {
                        return 'Schedule conflict with COR Archive: professor is already assigned at this day/time in a deployed or archived COR. Choose another professor or time.';
                    }
                }
            }
        }
        return null;
    }

    /**
     * Check for conflicts within the submitted slots: same professor or same room, same day, overlapping time.
     */
    private function detectScopeSlotConflicts(array $slots): ?string
    {
        $slots = array_values($slots);
        for ($i = 0; $i < count($slots); $i++) {
            $a = $slots[$i];
            $dayA = (int) $a['day_of_week'];
            $startA = $a['start_time'];
            $endA = $a['end_time'];
            $profA = !empty($a['professor_id']) ? (int) $a['professor_id'] : null;
            $roomA = !empty($a['room_id']) ? (int) $a['room_id'] : null;
            for ($j = $i + 1; $j < count($slots); $j++) {
                $b = $slots[$j];
                if ($dayA !== (int) $b['day_of_week']) {
                    continue;
                }
                $startB = $b['start_time'];
                $endB = $b['end_time'];
                if ($this->timesOverlap($startA, $endA, $startB, $endB)) {
                    if ($profA !== null && !empty($b['professor_id']) && $profA === (int) $b['professor_id']) {
                        return 'Schedule conflict: same professor has overlapping time on the same day.';
                    }
                    if ($roomA !== null && !empty($b['room_id']) && $roomA === (int) $b['room_id']) {
                        return 'Schedule conflict: same room has overlapping time on the same day.';
                    }
                }
            }
        }
        return null;
    }

    /**
     * Room occupancy entries from deployed COR archive (student_cor_records).
     * Uses room_name_snapshot resolved to room_id; one record can span multiple days (days_snapshot).
     *
     * @return array<int, array{room_id: int, day_of_week: int, start_time: string, end_time: string}>
     */
    private function roomOccupancyFromCorArchive(?string $schoolYear = null, ?string $semester = null): array
    {
        $records = StudentCorRecord::query()
            ->whereNotNull('room_name_snapshot')
            ->where('room_name_snapshot', '!=', '')
            ->when($schoolYear !== null && $schoolYear !== '', function ($q) use ($schoolYear) {
                $q->where(function ($qq) use ($schoolYear) {
                    $qq->where('school_year', $schoolYear)->orWhereNull('school_year');
                });
            })
            ->when($semester !== null && $semester !== '', function ($q) use ($semester) {
                $q->whereRaw('LOWER(TRIM(semester)) = ?', [strtolower(trim($semester))]);
            })
            ->select(['room_name_snapshot', 'days_snapshot', 'start_time_snapshot', 'end_time_snapshot', 'school_year', 'semester'])
            ->distinct()
            ->get();
        $list = [];
        $dayMap = ['mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6, 'sun' => 7];
        foreach ($records as $r) {
            $roomName = trim((string) $r->room_name_snapshot);
            if ($roomName === '') {
                continue;
            }
            $roomIds = Room::query()
                ->where(function ($q) use ($roomName) {
                    $q->where('code', $roomName)->orWhere('name', $roomName);
                })
                ->pluck('id')
                ->all();
            if (empty($roomIds)) {
                $roomIds = Room::query()
                    ->where(function ($q) use ($roomName) {
                        $q->where('code', 'LIKE', '%' . $roomName . '%')->orWhere('name', 'LIKE', '%' . $roomName . '%');
                    })
                    ->pluck('id')
                    ->all();
            }
            $days = $this->parseDaysSnapshot($r->days_snapshot, $dayMap);
            // #region agent log
            if (empty($roomIds) || empty($days)) {
                $agentLogPath = base_path('debug-743649.log');
                @file_put_contents($agentLogPath, json_encode(['sessionId' => '743649', 'hypothesisId' => 'H3', 'message' => 'COR room resolve', 'data' => ['room_name_snapshot' => $roomName, 'roomIdsCount' => count($roomIds), 'daysSnapshot' => $r->days_snapshot, 'daysParsed' => $days], 'timestamp' => (int) (microtime(true) * 1000)]) . "\n", FILE_APPEND);
            }
            // #endregion
            $start = $r->start_time_snapshot ? \Carbon\Carbon::parse($r->start_time_snapshot)->format('H:i') : '08:00';
            $end = $r->end_time_snapshot ? \Carbon\Carbon::parse($r->end_time_snapshot)->format('H:i') : '09:00';
            foreach ($days as $day) {
                foreach ($roomIds as $roomId) {
                    $list[] = ['room_id' => (int) $roomId, 'day_of_week' => $day, 'start_time' => $start, 'end_time' => $end];
                }
            }
        }
        return $list;
    }

    /**
     * Professor occupancy entries from deployed COR archive (student_cor_records).
     *
     * @return array<int, array{professor_id: int, day_of_week: int, start_time: string, end_time: string}>
     */
    private function professorOccupancyFromCorArchive(?string $schoolYear = null, ?string $semester = null): array
    {
        $records = StudentCorRecord::query()
            ->whereNotNull('professor_id')
            ->when($schoolYear !== null && $schoolYear !== '', function ($q) use ($schoolYear) {
                $q->where(function ($qq) use ($schoolYear) {
                    $qq->where('school_year', $schoolYear)->orWhereNull('school_year');
                });
            })
            ->when($semester !== null && $semester !== '', function ($q) use ($semester) {
                $q->whereRaw('LOWER(TRIM(semester)) = ?', [strtolower(trim($semester))]);
            })
            ->select(['professor_id', 'days_snapshot', 'start_time_snapshot', 'end_time_snapshot', 'school_year', 'semester'])
            ->distinct()
            ->get();
        $list = [];
        $dayMap = ['mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6, 'sun' => 7];
        foreach ($records as $r) {
            $days = $this->parseDaysSnapshot($r->days_snapshot, $dayMap);
            $start = $r->start_time_snapshot ? \Carbon\Carbon::parse($r->start_time_snapshot)->format('H:i') : '08:00';
            $end = $r->end_time_snapshot ? \Carbon\Carbon::parse($r->end_time_snapshot)->format('H:i') : '09:00';
            foreach ($days as $day) {
                $list[] = ['professor_id' => (int) $r->professor_id, 'day_of_week' => $day, 'start_time' => $start, 'end_time' => $end];
            }
        }
        return $list;
    }

    /**
     * @param array<string, int> $dayMap
     * @return array<int>
     */
    private function parseDaysSnapshot(?string $daysSnapshot, array $dayMap): array
    {
        if ($daysSnapshot === null || trim($daysSnapshot) === '') {
            return [];
        }
        $parts = array_map('trim', preg_split('/[\s,]+/', $daysSnapshot, -1, PREG_SPLIT_NO_EMPTY));
        $days = [];
        foreach ($parts as $p) {
            $key = strtolower(substr($p, 0, 3));
            if (isset($dayMap[$key])) {
                $days[(int) $dayMap[$key]] = true;
            }
            $num = (int) $p;
            if ($num >= 1 && $num <= 7) {
                $days[$num] = true;
            }
        }
        return array_keys($days);
    }

    private function timesOverlap(string $startA, string $endA, string $startB, string $endB): bool
    {
        $sA = strtotime($startA);
        $eA = strtotime($endA);
        $sB = strtotime($startB);
        $eB = strtotime($endB);
        return $sA < $eB && $eA > $sB;
    }

    private function deanDepartmentId($user): ?int
    {
        if (! $user) {
            return null;
        }
        $switch = session('role_switch');
        if ($user->role === 'admin' && is_array($switch) && ($switch['active'] ?? false) && ($switch['as_role'] ?? '') === 'dean') {
            $sessionDeptId = $switch['department_id'] ?? null;
            return $sessionDeptId !== null && $sessionDeptId !== '' ? (int) $sessionDeptId : null;
        }
        if ($user->role !== 'dean') {
            return null;
        }
        $id = $user->department_id;
        return $id !== null && $id !== '' ? (int) $id : null;
    }
}
