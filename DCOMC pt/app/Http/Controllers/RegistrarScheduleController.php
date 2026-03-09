<?php

namespace App\Http\Controllers;

use App\Models\AcademicYearLevel;
use App\Models\AcademicSemester;
use App\Models\Block;
use App\Models\CorScope;
use App\Models\Fee;
use App\Models\Program;
use App\Models\Room;
use App\Models\ScheduleTemplate;
use App\Models\SchoolYear;
use App\Models\ScopeScheduleSlot;
use App\Models\Subject;
use App\Models\User;
use App\Models\ClassSchedule;
use App\Models\StudentBlockAssignment;
use App\Models\StudentCorRecord;
use App\Services\AcademicCalendarService;
use App\Services\IrregularEnrollmentValidationService;
use App\Services\SchedulingScopeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegistrarScheduleController extends Controller
{
    /**
     * Schedule by Program: same folder structure as Subject Settings (program → year → semester).
     * At leaf, registrar edits subject schedule (time, day, room, professor) and saves.
     */
    public function scheduleByScope(Request $request): View|RedirectResponse
    {
        $program = $request->query('program');
        $year = $request->query('year');
        $semester = $request->query('semester');
        $programs = config('fee_programs.programs', \App\Models\Program::orderBy('program_name')->pluck('program_name')->all());
        $yearLevels = AcademicYearLevel::where('is_active', true)->orderBy('name')->pluck('name');
        $semesters = AcademicSemester::where('is_active', true)->orderBy('name')->pluck('name');
        $displayLabels = config('fee_programs.display_labels', []);

        if ($program !== null && $program !== '' && $year !== null && $year !== '' && $semester !== null && $semester !== '') {
            return $this->scheduleScopeTable($program, $year, $semester, $programs, $yearLevels, $semesters, $displayLabels);
        }
        if ($program !== null && $program !== '' && $year !== null && $year !== '') {
            return $this->scheduleScopeSemesterFolders($program, $year, $semesters, $displayLabels);
        }
        if ($program !== null && $program !== '') {
            return $this->scheduleScopeYearFolders($program, $yearLevels, $displayLabels);
        }

        return $this->scheduleScopeProgramFolders($programs, $displayLabels);
    }

    private function scheduleScopeProgramFolders($programs, array $displayLabels): View
    {
        $scheduleUrl = route('registrar.schedule.by-scope');
        return view('dashboards.schedule-by-scope', [
            'viewMode' => 'programs',
            'programs' => $programs,
            'displayLabels' => $displayLabels,
            'breadcrumb' => [['label' => 'Schedule', 'url' => $scheduleUrl]],
        ]);
    }

    private function scheduleScopeYearFolders(string $program, $yearLevels, array $displayLabels): View
    {
        $scheduleUrl = route('registrar.schedule.by-scope');
        $programLabel = $displayLabels[$program] ?? $program;
        return view('dashboards.schedule-by-scope', [
            'viewMode' => 'years',
            'program' => $program,
            'programLabel' => $programLabel,
            'yearLevels' => $yearLevels,
            'displayLabels' => $displayLabels,
            'breadcrumb' => [
                ['label' => 'Schedule', 'url' => $scheduleUrl],
                ['label' => $programLabel, 'url' => $scheduleUrl . '?program=' . urlencode($program)],
            ],
        ]);
    }

    private function scheduleScopeSemesterFolders(string $program, string $year, $semesters, array $displayLabels): View
    {
        $scheduleUrl = route('registrar.schedule.by-scope');
        $programLabel = $displayLabels[$program] ?? $program;
        return view('dashboards.schedule-by-scope', [
            'viewMode' => 'semesters',
            'program' => $program,
            'programLabel' => $programLabel,
            'year' => $year,
            'semesters' => $semesters,
            'displayLabels' => $displayLabels,
            'breadcrumb' => [
                ['label' => 'Schedule', 'url' => $scheduleUrl],
                ['label' => $programLabel, 'url' => $scheduleUrl . '?program=' . urlencode($program)],
                ['label' => $year, 'url' => $scheduleUrl . '?program=' . urlencode($program) . '&year=' . urlencode($year)],
            ],
        ]);
    }

    private function scheduleScopeTable(string $program, string $year, string $semester, $programs, $yearLevels, $semesters, array $displayLabels): View
    {
        $programModel = Program::where('program_name', $program)->first();
        $yearLevelModel = AcademicYearLevel::where('name', $year)->first();
        $subjects = collect();
        $subjectIdsInSchedule = [];
        if ($programModel && $yearLevelModel) {
            $subjects = Subject::query()
                ->forProgramAndYear($programModel->id, $yearLevelModel->id)
                ->where('semester', $semester)
                ->where('is_active', true)
                ->orderBy('code')
                ->get();
            $subjectIdsInSchedule = ScopeScheduleSlot::query()
                ->where('program_id', $programModel->id)
                ->where('academic_year_level_id', $yearLevelModel->id)
                ->where('semester', $semester)
                ->distinct()
                ->pluck('subject_id')
                ->all();
        }

        $scheduleUrl = route('registrar.schedule.by-scope');
        $programLabel = $displayLabels[$program] ?? $program;
        return view('dashboards.schedule-by-scope', [
            'viewMode' => 'table',
            'program' => $program,
            'programLabel' => $programLabel,
            'year' => $year,
            'semester' => $semester,
            'program_id' => $programModel?->id,
            'academic_year_level_id' => $yearLevelModel?->id,
            'subjects' => $subjects,
            'subjectIdsInSchedule' => $subjectIdsInSchedule,
            'yearLevels' => $yearLevels,
            'semesters' => $semesters,
            'programs' => $programs,
            'displayLabels' => $displayLabels,
            'breadcrumb' => [
                ['label' => 'Schedule', 'url' => $scheduleUrl],
                ['label' => $programLabel, 'url' => $scheduleUrl . '?program=' . urlencode($program)],
                ['label' => $year, 'url' => $scheduleUrl . '?program=' . urlencode($program) . '&year=' . urlencode($year)],
                ['label' => $semester, 'url' => $scheduleUrl . '?program=' . urlencode($program) . '&year=' . urlencode($year) . '&semester=' . urlencode($semester)],
            ],
        ]);
    }

    /**
     * Add a subject to the schedule for this scope. Creates one slot with default time (Mon 08:00–09:00).
     * Dean can then set Day, Start, End, Room, Professor on their Schedule by Program page.
     */
    public function addScopeScheduleSlot(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'academic_year_level_id' => ['required', 'integer', 'exists:academic_year_levels,id'],
            'semester' => ['required', 'string', 'max:100'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
        ]);

        $programId = (int) $validated['program_id'];
        $yearLevelId = (int) $validated['academic_year_level_id'];
        $semester = trim($validated['semester']);
        $subjectId = (int) $validated['subject_id'];

        $subject = Subject::query()
            ->forProgramAndYear($programId, $yearLevelId)
            ->where('semester', $semester)
            ->where('id', $subjectId)
            ->first();

        if (! $subject) {
            return back()->with('error', 'Subject does not belong to this program, year level, or semester.');
        }

        ScopeScheduleSlot::create([
            'program_id' => $programId,
            'academic_year_level_id' => $yearLevelId,
            'semester' => $semester,
            'subject_id' => $subjectId,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room_id' => null,
            'professor_id' => null,
            'school_year' => null,
        ]);

        $program = Program::find($programId)?->program_name;
        $year = AcademicYearLevel::find($yearLevelId)?->name;
        return redirect()
            ->to(route('registrar.schedule.by-scope') . '?program=' . urlencode((string) $program) . '&year=' . urlencode((string) $year) . '&semester=' . urlencode($semester))
            ->with('success', 'Subject added to schedule. Dean can set day, time, room, and professor.');
    }

    /**
     * Remove a subject from the schedule for this scope. Deletes all slots for that subject in this program/year/semester.
     * Changes are reflected on the dean Schedule by Program page.
     */
    public function deleteScopeScheduleSlot(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'academic_year_level_id' => ['required', 'integer', 'exists:academic_year_levels,id'],
            'semester' => ['required', 'string', 'max:100'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
        ]);

        $deleted = ScopeScheduleSlot::query()
            ->where('program_id', (int) $validated['program_id'])
            ->where('academic_year_level_id', (int) $validated['academic_year_level_id'])
            ->where('semester', trim($validated['semester']))
            ->where('subject_id', (int) $validated['subject_id'])
            ->delete();

        $program = Program::find((int) $validated['program_id'])?->program_name;
        $year = AcademicYearLevel::find((int) $validated['academic_year_level_id'])?->name;
        return redirect()
            ->to(route('registrar.schedule.by-scope') . '?program=' . urlencode((string) $program) . '&year=' . urlencode((string) $year) . '&semester=' . urlencode(trim($validated['semester'])))
            ->with('success', $deleted > 0 ? 'Subject removed from schedule.' : 'Subject was not in the schedule.');
    }

    public function index(): View
    {
        $templates = ScheduleTemplate::query()
            ->with('block')
            ->orderByDesc('created_at')
            ->get();

        $schoolYears = SchoolYear::query()
            ->orderByDesc('start_year')
            ->pluck('label');

        return view('dashboards.registrar-schedule-templates', compact('templates', 'schoolYears'));
    }

    /**
     * Build workspace data for the irregular Create Schedule (schedule table + deploy).
     * Used by both the Create Schedule tab (single workspace) and the legacy edit route.
     */
    public function getScheduleWorkspaceData(ScheduleTemplate $template): array
    {
        $dayNames = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

        // Load all active subjects; deduplicate by raw_subject_id (one per raw subject, no duplicates in dropdown)
        $allSubjectsFull = Subject::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'title', 'units', 'raw_subject_id', 'program_id']);
        $byRawKey = $allSubjectsFull->groupBy(function ($s) {
            return $s->raw_subject_id !== null ? (string) $s->raw_subject_id : 's' . $s->id;
        });
        $representatives = $byRawKey->map(fn ($group) => $group->sortBy('id')->first())->values();
        $subjectIdToRepresentativeId = [];
        foreach ($byRawKey as $group) {
            $rep = $group->sortBy('id')->first();
            $repId = $rep?->id;
            if ($repId === null) {
                continue;
            }
            foreach ($group as $s) {
                $subjectIdToRepresentativeId[$s->id] = $repId;
            }
        }

        // COR Archive slots: merge by raw group; include block and overload so irregulars know which group they walk in with
        $corArchiveSlots = ScopeScheduleSlot::query()
            ->with(['room', 'professor', 'block', 'academicYearLevel'])
            ->whereNotNull('day_of_week')
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->get();
        $slotsBySubject = [];
        foreach ($representatives as $rep) {
            $subjectIdsInGroup = $byRawKey->get($rep->raw_subject_id !== null ? (string) $rep->raw_subject_id : 's' . $rep->id)->pluck('id')->all();
            foreach ($corArchiveSlots as $s) {
                if (! in_array((int) $s->subject_id, $subjectIdsInGroup, true)) {
                    continue;
                }
                $start = $s->start_time ? \Carbon\Carbon::parse($s->start_time)->format('H:i') : '08:00';
                $end = $s->end_time ? \Carbon\Carbon::parse($s->end_time)->format('H:i') : '09:00';
                $day = (int) $s->day_of_week;
                $roomName = $s->room ? trim($s->room->code ?? $s->room->name ?? '') : '';
                $profName = $s->professor ? trim($s->professor->name ?? '') : '';
                $overload = $s->is_overload === true || (int) ($s->getRawOriginal('is_overload') ?? 0) === 1;
                $blockLabel = '';
                $effectiveBlockId = $s->block_id;
                if ($s->block) {
                    $b = $s->block;
                    $blockLabel = trim($b->code ?? $b->name ?? '');
                    if ($blockLabel === '' && ($b->program || $b->year_level)) {
                        $blockLabel = trim(($b->program ?? '') . ' ' . ($b->year_level ?? '') . ($b->section_name ? ' - ' . $b->section_name : ''));
                    }
                }
                if ($blockLabel === '' && $effectiveBlockId === null && $s->program_id && $s->semester) {
                    $yearLevelName = $s->academicYearLevel ? trim($s->academicYearLevel->name ?? '') : null;
                    $scopeBlock = Block::query()
                        ->where('program_id', (int) $s->program_id)
                        ->where('semester', trim($s->semester))
                        ->when($yearLevelName !== null && $yearLevelName !== '', fn ($q) => $q->where('year_level', $yearLevelName))
                        ->where('is_active', true)
                        ->orderBy('code')
                        ->first();
                    if ($scopeBlock) {
                        $effectiveBlockId = $scopeBlock->id;
                        $blockLabel = trim($scopeBlock->code ?? $scopeBlock->name ?? '');
                        if ($blockLabel === '') {
                            $blockLabel = trim(($scopeBlock->program ?? '') . ' ' . ($scopeBlock->year_level ?? '') . ($scopeBlock->section_name ? ' - ' . $scopeBlock->section_name : ''));
                        }
                    }
                }
                $slotData = [
                    'day_of_week' => $day,
                    'start_time' => $start,
                    'end_time' => $end,
                    'room_id' => $s->room_id,
                    'professor_id' => $s->professor_id,
                    'block_id' => $effectiveBlockId,
                    'block_name' => $blockLabel,
                    'room_name' => $roomName,
                    'professor_name' => $profName,
                    'day_name' => $dayNames[$day] ?? 'Day ' . $day,
                    'is_overload' => $overload,
                ];
                $profPart = $profName . ($overload ? ' (OVERLOAD)' : '');
                $label = ($dayNames[$day] ?? '') . ' ' . $start . '-' . $end . ' | ' . $roomName . ' | ' . $profPart . ($blockLabel !== '' ? ' | ' . $blockLabel : '');
                $slotData['label'] = $label;
                if (! isset($slotsBySubject[$rep->id])) {
                    $slotsBySubject[$rep->id] = [];
                }
                $slotsBySubject[$rep->id][] = $slotData;
            }
        }

        $slots = $template->getSlots();
        $subjectIdsInSchedule = array_values(array_unique(array_column($slots, 'subject_id')));
        $rawKeysInSchedule = $allSubjectsFull->whereIn('id', $subjectIdsInSchedule)->map(function ($s) {
            return $s->raw_subject_id !== null ? (string) $s->raw_subject_id : 's' . $s->id;
        })->unique()->values();
        $availableSubjectsForAdd = $representatives->filter(function ($r) use ($rawKeysInSchedule) {
            $key = $r->raw_subject_id !== null ? (string) $r->raw_subject_id : 's' . $r->id;
            return ! $rawKeysInSchedule->contains($key);
        })->values();

        $subjectsById = $allSubjectsFull->keyBy('id');
        $slotRows = [];
        $slotIndex = 0;
        foreach ($slots as $slot) {
            $subject = $subjectsById->get((int) ($slot['subject_id'] ?? 0));
            if ($subject) {
                $programId = isset($slot['program_id']) && $slot['program_id'] ? (int) $slot['program_id'] : ($subject->program_id ?? null);
                $yearLevel = isset($slot['year_level']) && $slot['year_level'] !== '' ? trim((string) $slot['year_level']) : null;
                $slotOptions = ($programId && $subject->id) ? $this->buildSlotOptionsForProgramSubject($programId, $subject->id, $yearLevel) : [];
                $slotRows[] = [
                    'program_id' => $programId,
                    'year_level' => $yearLevel,
                    'subject' => $subject,
                    'slot' => $slot,
                    'slot_index' => $slotIndex++,
                    'slot_options' => $slotOptions,
                ];
            }
        }

        $programs = Program::orderBy('program_name')->get(['id', 'program_name', 'code']);
        $scheduleScriptData = [
            'nextSlotIndex' => $slotIndex,
            'programs' => $programs->map(fn ($p) => ['id' => $p->id, 'name' => $p->program_name ?? $p->code ?? (string) $p->id])->values()->all(),
            'subjectsForProgramUrl' => route('registrar.schedule.templates.subjects-for-program'),
            'subjectsForAllProgramsUrl' => route('registrar.schedule.templates.subjects-for-all-programs'),
            'slotsForScopeUrl' => route('registrar.schedule.templates.slots-for-scope'),
            'conflictsUrl' => route('registrar.schedule.templates.conflicts', $template->id),
            'templateSemester' => $template->semester ? trim((string) $template->semester) : '',
        ];

        $allSubjects = $representatives;
        $yearLevels = AcademicYearLevel::where('is_active', true)->orderBy('name')->pluck('name');
        $semesters = \App\Models\AcademicSemester::where('is_active', true)->orderBy('name')->pluck('name');
        $schoolYears = SchoolYear::query()->orderByDesc('start_year')->pluck('label');

        return compact(
            'template', 'allSubjects', 'slotRows', 'dayNames', 'scheduleScriptData', 'availableSubjectsForAdd',
            'slotsBySubject', 'subjectIdToRepresentativeId', 'programs', 'yearLevels', 'semesters', 'schoolYears'
        );
    }

    public function edit(int $id): View
    {
        $template = ScheduleTemplate::findOrFail($id);
        $data = $this->getScheduleWorkspaceData($template);
        return view('dashboards.registrar-schedule-edit', $data);
    }

    /**
     * GET /registrar/schedule/subjects?program_id=1&academic_year_level_id=2
     * Returns JSON list of subjects for the given program and year level only.
     */
    public function subjectsForScope(Request $request): JsonResponse
    {
        $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'academic_year_level_id' => ['required', 'integer', 'exists:academic_year_levels,id'],
        ]);

        $subjects = Subject::query()
            ->forProgramAndYear((int) $request->program_id, (int) $request->academic_year_level_id)
            ->where('is_active', true)
            ->orderBy('semester')
            ->orderBy('code')
            ->get();

        return response()->json([
            'subjects' => $subjects->map(fn ($s) => [
                'id' => (string) $s->id,
                'label' => trim(($s->code ?? '') . ' - ' . ($s->title ?? '')),
                'units' => (int) $s->units,
            ]),
        ]);
    }

    /**
     * GET /registrar/schedule/fees?program_id=1&academic_year_level_id=2
     * Returns JSON list of fees for the given program and year level only (strict isolation).
     */
    public function feesForScope(Request $request): JsonResponse
    {
        $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'academic_year_level_id' => ['required', 'integer', 'exists:academic_year_levels,id'],
        ]);

        $fees = Fee::feesForScope((int) $request->program_id, (int) $request->academic_year_level_id);

        return response()->json([
            'fees' => $fees->map(fn ($f) => [
                'id' => (string) $f->id,
                'name' => $f->name,
                'category' => $f->feeCategory?->name ?? $f->category ?? '',
                'amount' => (float) $f->amount,
            ]),
        ]);
    }

    /**
     * GET /registrar/schedule/available-rooms?day_of_week=1&start_time=08:00&end_time=09:00
     * Returns JSON list of rooms that are not occupied for the given day and time range.
     * Considers both scope_schedule_slots and class_schedules for conflicts.
     */
    public function availableRooms(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'string', 'date_format:H:i'],
            'end_time' => ['required', 'string', 'date_format:H:i'],
        ]);

        $start = $validated['start_time'];
        $end = $validated['end_time'];
        $day = (int) $validated['day_of_week'];

        $occupiedRoomIds = collect();

        $scopeOccupied = ScopeScheduleSlot::query()
            ->whereNotNull('room_id')
            ->where('day_of_week', $day)
            ->whereRaw('start_time < ? AND end_time > ?', [$end, $start])
            ->pluck('room_id');
        $occupiedRoomIds = $occupiedRoomIds->merge($scopeOccupied);

        $classOccupied = ClassSchedule::query()
            ->whereNotNull('room_id')
            ->where('day_of_week', $day)
            ->whereRaw('start_time < ? AND end_time > ?', [$end, $start])
            ->pluck('room_id');
        $occupiedRoomIds = $occupiedRoomIds->merge($classOccupied);

        $occupiedRoomIds = $occupiedRoomIds->unique()->filter()->values()->all();

        $rooms = Room::query()
            ->where('is_active', true)
            ->whereNotIn('id', $occupiedRoomIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json([
            'rooms' => $rooms->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->code ?? $r->name,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $title = $request->input('title');
        if (! is_string($title) || trim($title) === '') {
            $title = 'New Schedule Form';
        }
        $title = str()->limit(trim($title), 255);

        $template = ScheduleTemplate::create([
            'title' => $title,
            'description' => null,
            'program' => null,
            'major' => null,
            'year_level' => null,
            'semester' => null,
            'school_year' => null,
            'block_id' => null,
            'created_by' => auth()->id(),
            'template' => ['subject_ids' => [], 'fees' => []],
            'is_active' => false,
        ]);

        return redirect()->route('registrar.schedule.templates.edit', $template->id)->with('success', 'Schedule created. Add subjects to the table, set time/day/room/professor, add students for deployment, then Deploy.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $template = ScheduleTemplate::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slots' => ['nullable', 'array'],
            'slots.*.program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'slots.*.year_level' => ['nullable', 'string', 'max:100'],
            'slots.*.subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'slots.*.slot_data' => ['nullable', 'string'],
        ]);

        $slots = [];
        $subjectIds = [];
        foreach ($validated['slots'] ?? [] as $row) {
            $subjectId = (int) $row['subject_id'];
            $programId = isset($row['program_id']) && $row['program_id'] ? (int) $row['program_id'] : (Subject::where('id', $subjectId)->value('program_id') ?? null);
            $yearLevel = isset($row['year_level']) && $row['year_level'] !== '' ? trim((string) $row['year_level']) : null;
            $slotData = $row['slot_data'] ?? '';
            $subjectIds[] = $subjectId;
            $decoded = $slotData !== '' ? json_decode($slotData, true) : null;
            if (is_array($decoded)) {
                $slotRow = [
                    'program_id' => $programId,
                    'year_level' => $yearLevel,
                    'subject_id' => $subjectId,
                    'day_of_week' => isset($decoded['day_of_week']) ? (int) $decoded['day_of_week'] : null,
                    'start_time' => isset($decoded['start_time']) && preg_match('/^\d{2}:\d{2}$/', (string) $decoded['start_time']) ? $decoded['start_time'] : null,
                    'end_time' => isset($decoded['end_time']) && preg_match('/^\d{2}:\d{2}$/', (string) $decoded['end_time']) ? $decoded['end_time'] : null,
                    'room_id' => isset($decoded['room_id']) && $decoded['room_id'] !== '' ? (int) $decoded['room_id'] : null,
                    'professor_id' => isset($decoded['professor_id']) && $decoded['professor_id'] !== '' ? (int) $decoded['professor_id'] : null,
                    'block_id' => isset($decoded['block_id']) && $decoded['block_id'] !== '' && $decoded['block_id'] !== null ? (int) $decoded['block_id'] : null,
                    'is_overload' => ! empty($decoded['is_overload']),
                ];
                if ($programId && $slotRow['day_of_week'] !== null && $slotRow['start_time'] !== null && $slotRow['end_time'] !== null) {
                    $deployedBlockIds = $this->getDeployedBlockIdsForProgram($programId);
                    if ($slotRow['block_id'] === null || ! in_array($slotRow['block_id'], $deployedBlockIds, true)) {
                        return back()->withErrors(['slots' => 'Selected slot is not in the COR Archive for this program. Deploy the schedule for the block in COR Archive first.'])->withInput();
                    }
                    $slotExistsInArchive = ScopeScheduleSlot::query()
                        ->where('program_id', $programId)
                        ->where('subject_id', $subjectId)
                        ->where('day_of_week', $slotRow['day_of_week'])
                        ->where('start_time', $slotRow['start_time'])
                        ->where('end_time', $slotRow['end_time'])
                        ->where('room_id', $slotRow['room_id'])
                        ->where('professor_id', $slotRow['professor_id'])
                        ->where('block_id', $slotRow['block_id'])
                        ->whereIn('block_id', $deployedBlockIds)
                        ->exists();
                    if (! $slotExistsInArchive) {
                        return back()->withErrors(['slots' => 'Selected slot is not in the COR Archive for this program. Deploy the schedule for the block in COR Archive first.'])->withInput();
                    }
                }
                $slots[] = $slotRow;
            }
        }
        $subjectIds = array_values(array_unique($subjectIds));

        $currentTemplate = $template->template ?? ['subject_ids' => [], 'fees' => [], 'slots' => []];
        $template->update([
            'title' => $validated['title'],
            'template' => [
                'subject_ids' => $subjectIds,
                'fees' => $currentTemplate['fees'] ?? [],
                'slots' => $slots,
            ],
        ]);

        return back()->with('success', 'Schedule updated.');
    }

    public function updateSubjects(Request $request, int $id): RedirectResponse
    {
        $template = ScheduleTemplate::findOrFail($id);
        $request->validate(['subject_ids_json' => ['nullable', 'string']]);

        $subjectIds = collect(json_decode($request->input('subject_ids_json', '[]'), true) ?: [])
            ->filter(fn ($v) => filled($v) && is_numeric($v))
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();

        $program = $template->program ? trim($template->program) : null;
        $year_level = $template->year_level ? trim($template->year_level) : null;
        $programId = $template->program_id ?: ($program ? Program::where('program_name', $program)->value('id') : null);
        $academicYearLevelId = $template->academic_year_level_id ?: ($year_level ? AcademicYearLevel::where('name', $year_level)->value('id') : null);
        if (! empty($subjectIds) && ($program === null || $program === '' || $year_level === null || $year_level === '')) {
            return back()->withErrors(['subject_ids_json' => 'Set Program and Year Level on this schedule form before adding subjects.']);
        }
        if (! empty($subjectIds)) {
            $err = $this->validateSubjectIdsForScope($subjectIds, $program, $year_level, $programId, $academicYearLevelId);
            if ($err) {
                return back()->withErrors(['subject_ids_json' => $err])->withInput();
            }
        }

        $current = $template->template ?? ['subject_ids' => [], 'fees' => []];
        $template->update([
            'template' => array_merge($current, ['subject_ids' => array_values($subjectIds)]),
        ]);

        return back()->with('success', 'Subjects saved for this schedule form.');
    }

    /**
     * Validate that all subject IDs belong to the given program and year level.
     * Prefers program_id and academic_year_level_id when provided (ID-based enforcement).
     * Returns error message string or null if valid.
     */
    private function validateSubjectIdsForScope(
        array $subjectIds,
        ?string $program,
        ?string $yearLevel,
        ?int $programId = null,
        ?int $academicYearLevelId = null
    ): ?string {
        if ($programId === null && $program !== null && $program !== '') {
            $programId = Program::where('program_name', trim($program))->value('id');
        }
        if ($academicYearLevelId === null && $yearLevel !== null && $yearLevel !== '') {
            $academicYearLevelId = AcademicYearLevel::where('name', trim($yearLevel))->value('id');
        }
        if (! $programId || ! $academicYearLevelId) {
            return 'Invalid program or year level.';
        }
        $validIds = Subject::query()
            ->forProgramAndYear($programId, $academicYearLevelId)
            ->whereIn('id', $subjectIds)
            ->pluck('id')
            ->all();
        $invalid = array_diff($subjectIds, $validIds);
        if (! empty($invalid)) {
            return 'This subject does not belong to this program or year level.';
        }
        return null;
    }

    /**
     * Validate that all fee IDs in the entries belong to the given program and year level.
     * Returns error message string or null if valid.
     */
    private function validateFeeIdsForScope(array $feeEntries, ?int $programId, ?int $academicYearLevelId): ?string
    {
        if (! $programId || ! $academicYearLevelId) {
            return 'Invalid program or year level.';
        }
        $feeIds = array_values(array_unique(array_column($feeEntries, 'fee_id')));
        $validIds = Fee::query()
            ->forProgramAndYear($programId, $academicYearLevelId)
            ->whereIn('id', $feeIds)
            ->pluck('id')
            ->all();
        $invalid = array_diff($feeIds, $validIds);
        if (! empty($invalid)) {
            return 'This fee does not belong to this program or year level.';
        }
        return null;
    }

    public function destroy(int $id): RedirectResponse
    {
        $template = ScheduleTemplate::findOrFail($id);
        $template->delete();

        return back()->with('success', 'Schedule removed.');
    }

    /**
     * Deploy this Create Schedule to the students listed in the request only.
     * Creates student_cor_records with cor_source = create_schedule so shifters see this in View COR.
     */
    public function deploy(int $id): RedirectResponse
    {
        $template = ScheduleTemplate::findOrFail($id);
        $request = request();

        $programId = $template->program_id ? (int) $template->program_id : null;
        $yearLevelName = trim((string) ($template->year_level ?? ''));
        $semester = trim((string) ($template->semester ?? ''));
        $schoolYear = trim((string) ($template->school_year ?? ''));

        $studentIds = is_array($request->input('student_ids')) ? $request->input('student_ids') : [];
        $studentIds = array_values(array_filter(array_map('intval', $studentIds)));

        if (empty($studentIds)) {
            return back()
                ->withErrors(['deploy' => 'Add at least one student to the table below, then click Deploy to selected students.'])
                ->withInput();
        }

        $slots = $template->getSlots();
        if (empty($slots)) {
            return back()
                ->withErrors(['deploy' => 'Set time, day, room, and professor for each subject before deploying.'])
                ->withInput();
        }
        $roomIds = array_values(array_unique(array_filter(array_column($slots, 'room_id'))));
        $professorIds = array_values(array_unique(array_filter(array_column($slots, 'professor_id'))));
        $rooms = $roomIds ? Room::whereIn('id', $roomIds)->get()->keyBy('id') : collect();
        $professors = $professorIds ? User::whereIn('id', $professorIds)->get()->keyBy('id') : collect();

        $dayNames = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
        $snapshots = [];
        foreach ($slots as $slot) {
            $subjectId = (int) ($slot['subject_id'] ?? 0);
            if (! $subjectId) {
                continue;
            }
            $day = (int) ($slot['day_of_week'] ?? 1);
            $start = isset($slot['start_time']) && preg_match('/^\d{2}:\d{2}$/', (string) $slot['start_time']) ? $slot['start_time'] : '08:00';
            $end = isset($slot['end_time']) && preg_match('/^\d{2}:\d{2}$/', (string) $slot['end_time']) ? $slot['end_time'] : '09:00';
            $roomId = isset($slot['room_id']) ? (int) $slot['room_id'] : null;
            $professorId = isset($slot['professor_id']) ? (int) $slot['professor_id'] : null;
            $blockId = isset($slot['block_id']) && $slot['block_id'] !== '' && $slot['block_id'] !== null ? (int) $slot['block_id'] : null;
            $slotProgramId = isset($slot['program_id']) && $slot['program_id'] ? (int) $slot['program_id'] : $programId;
            $roomName = $roomId && $rooms->has($roomId) ? ($rooms[$roomId]->code ?? $rooms[$roomId]->name ?? '') : '';
            $profName = $professorId && $professors->has($professorId) ? ($professors[$professorId]->name ?? '') : '';
            $isOverload = ! empty($slot['is_overload']);
            $snapshots[] = [
                'subject_id' => $subjectId,
                'program_id' => $slotProgramId,
                'professor_id' => $professorId ?: null,
                'professor_name_snapshot' => $profName,
                'room_name_snapshot' => $roomName,
                'days_snapshot' => $dayNames[$day] ?? 'Day ' . $day,
                'start_time_snapshot' => $start,
                'end_time_snapshot' => $end,
                'is_overload' => $isOverload,
                'block_id' => $blockId,
            ];
        }

        if (empty($snapshots)) {
            return back()->withErrors(['deploy' => 'No valid slots to deploy.'])->withInput();
        }

        $subjectIdsFromSlots = array_values(array_unique(array_filter(array_column($snapshots, 'subject_id'))));
        $validationService = app(IrregularEnrollmentValidationService::class);

        // Curriculum: each student may only receive subjects that are in their program + year level + semester (Subject Settings)
        [$curriculumValid, $curriculumErrors] = $validationService->validateDeployCurriculum(
            $studentIds,
            $subjectIdsFromSlots,
            $semester
        );
        if (! $curriculumValid) {
            return back()
                ->withErrors(['deploy' => $validationService->formatDeployErrorsForMessage($curriculumErrors)])
                ->withInput();
        }

        // Irregular enrollment validation: no retaking completed subjects, no same-term duplicate
        [$deployValid, $deployErrors] = $validationService->validateDeployForIrregulars(
            $studentIds,
            $subjectIdsFromSlots,
            $schoolYear,
            $semester
        );
        if (! $deployValid) {
            return back()
                ->withErrors(['deploy' => $validationService->formatDeployErrorsForMessage($deployErrors)])
                ->withInput();
        }

        $students = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->whereIn('id', $studentIds)
            ->get()
            ->keyBy('id');

        $now = now();
        $records = [];
        foreach ($studentIds as $sid) {
            if (! $students->has($sid)) {
                continue;
            }
            foreach ($snapshots as $snap) {
                $records[] = [
                    'student_id' => $sid,
                    'subject_id' => $snap['subject_id'],
                    'professor_id' => $snap['professor_id'] ?? null,
                    'is_overload' => $snap['is_overload'] ?? false,
                    'professor_name_snapshot' => $snap['professor_name_snapshot'],
                    'room_name_snapshot' => $snap['room_name_snapshot'],
                    'days_snapshot' => $snap['days_snapshot'],
                    'start_time_snapshot' => $snap['start_time_snapshot'],
                    'end_time_snapshot' => $snap['end_time_snapshot'],
                    'program_id' => $snap['program_id'] ?? $programId,
                    'year_level' => $yearLevelName,
                    'block_id' => $snap['block_id'] ?? null,
                    'shift' => null,
                    'semester' => $semester,
                    'school_year' => $schoolYear,
                    'cor_source' => StudentCorRecord::COR_SOURCE_CREATE_SCHEDULE,
                    'deployed_by' => auth()->id(),
                    'deployed_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $blockIdsFromSlots = array_values(array_unique(array_filter(array_column($snapshots, 'block_id'))));

        DB::transaction(function () use ($studentIds, $yearLevelName, $semester, $schoolYear, $records, $blockIdsFromSlots) {
            foreach ($studentIds as $sid) {
                StudentCorRecord::query()
                    ->where('student_id', $sid)
                    ->where('cor_source', StudentCorRecord::COR_SOURCE_CREATE_SCHEDULE)
                    ->delete();
            }
            if (! empty($records)) {
                StudentCorRecord::insert($records);
            }
            // Merge block assignments so irregulars can appear in multiple blocks/years (Philippine setting)
            foreach ($studentIds as $sid) {
                foreach ($blockIdsFromSlots as $blockId) {
                    if (! $blockId) {
                        continue;
                    }
                    $exists = StudentBlockAssignment::query()
                        ->where('user_id', $sid)
                        ->where('block_id', $blockId)
                        ->exists();
                    if (! $exists) {
                        StudentBlockAssignment::create(['user_id' => $sid, 'block_id' => $blockId]);
                        Block::where('id', $blockId)->increment('current_size');
                    }
                }
            }
        });

        $template->update(['is_active' => true]);
        $count = count(array_unique(array_column($records, 'student_id')));

        return back()->with('success', "Schedule deployed to {$count} student(s). They will see it in View COR.");
    }

    /**
     * Search/list students for Create Schedule deploy. Only irregular-type students
     * (student_type = Irregular or Shifter, or status_color = yellow). Used by
     * combobox: empty q returns all; with q filters by name/email/school_id.
     * Optional year_level filters to that year only (e.g. "3rd Year").
     */
    public function studentsSearch(Request $request): JsonResponse
    {
        $q = $request->input('q', '');
        $q = is_string($q) ? trim($q) : '';
        $yearLevel = $request->input('year_level', '');
        $yearLevel = is_string($yearLevel) ? trim($yearLevel) : '';

        $query = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->where(function ($qry) {
                $qry->whereIn('student_type', ['Irregular', 'Shifter'])
                    ->orWhere('status_color', 'yellow');
            });

        if ($yearLevel !== '') {
            $query->where('year_level', $yearLevel);
        }

        if ($q !== '') {
            $query->where(function ($query) use ($q) {
                $query->where('name', 'like', '%' . $q . '%')
                    ->orWhere('email', 'like', '%' . $q . '%')
                    ->orWhere('school_id', 'like', '%' . $q . '%');
            });
        }

        $students = $query->orderBy('name')
            ->limit($q !== '' ? 50 : 500)
            ->get(['id', 'name', 'email', 'school_id']);

        return response()->json([
            'students' => $students->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'email' => $s->email ?? '',
                'student_id' => $s->school_id ?? '',
            ])->values()->all(),
        ]);
    }

    /**
     * POST /registrar/schedule/forms/{id}/conflicts
     * Returns per-student subject conflicts (already completed / already enrolled this term)
     * for the current Create Schedule template and selected students.
     */
    public function conflicts(Request $request, int $id): JsonResponse
    {
        $template = ScheduleTemplate::findOrFail($id);

        $validated = $request->validate([
            'student_ids' => ['required', 'array'],
            'student_ids.*' => ['integer'],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['integer'],
        ]);

        $studentIds = array_values(array_unique(array_filter(array_map('intval', $validated['student_ids'] ?? []))));
        if (empty($studentIds)) {
            return response()->json(['conflicts_by_student' => []]);
        }

        $subjectIds = [];
        if (isset($validated['subject_ids']) && is_array($validated['subject_ids'])) {
            $subjectIds = array_values(array_unique(array_filter(array_map('intval', $validated['subject_ids']))));
        }

        if (empty($subjectIds)) {
            $slots = $template->getSlots();
            $subjectIds = array_values(array_unique(array_filter(array_column($slots, 'subject_id'))));
        }

        if (empty($subjectIds)) {
            return response()->json(['conflicts_by_student' => []]);
        }

        $schoolYear = trim((string) ($template->school_year ?? ''));
        $semester = trim((string) ($template->semester ?? ''));

        $validationService = app(IrregularEnrollmentValidationService::class);
        [, $errors] = $validationService->validateDeployForIrregulars(
            $studentIds,
            $subjectIds,
            $schoolYear,
            $semester
        );

        if (empty($errors)) {
            return response()->json(['conflicts_by_student' => []]);
        }

        $subjectIdsInErrors = array_unique(array_column($errors, 'subject_id'));
        $subjects = Subject::query()
            ->whereIn('id', $subjectIdsInErrors)
            ->get()
            ->keyBy('id');

        $codeLabels = [
            IrregularEnrollmentValidationService::CODE_ALREADY_COMPLETED => 'already completed',
            IrregularEnrollmentValidationService::CODE_DUPLICATE_THIS_TERM => 'already enrolled this term',
        ];

        $byStudent = [];
        foreach ($errors as $err) {
            $sid = (int) $err['student_id'];
            $subjId = (int) $err['subject_id'];
            $subject = $subjects->get($subjId);
            if (! isset($byStudent[$sid])) {
                $byStudent[$sid] = [];
            }
            $key = (string) $subjId;
            if (isset($byStudent[$sid][$key])) {
                continue;
            }
            $byStudent[$sid][$key] = [
                'subject_id' => $subjId,
                'subject_code' => $subject?->code ?? '',
                'subject_title' => $subject?->title ?? '',
                'code' => $err['code'],
                'reason' => $codeLabels[$err['code']] ?? $err['code'],
            ];
        }

        $byStudent = array_map(function (array $subjects) {
            return array_values($subjects);
        }, $byStudent);

        return response()->json([
            'conflicts_by_student' => $byStudent,
        ]);
    }

    /**
     * Block IDs that have at least one deployed COR (StudentCorRecord) for this program.
     * Optionally filter by year_level (block's year_level) so irregulars see slots for that program+year only.
     *
     * @return array<int>
     */
    private function getDeployedBlockIdsForProgram(int $programId, ?string $yearLevel = null): array
    {
        $blockIds = StudentCorRecord::query()
            ->where('program_id', $programId)
            ->whereNotNull('block_id')
            ->distinct()
            ->pluck('block_id')
            ->all();
        if ($yearLevel !== null && $yearLevel !== '' && ! empty($blockIds)) {
            $blockIds = Block::query()
                ->whereIn('id', $blockIds)
                ->where('year_level', $yearLevel)
                ->pluck('id')
                ->all();
        }
        return $blockIds;
    }

    /**
     * GET /registrar/schedule/forms/subjects-for-program?program_id=1
     * Returns subjects that have at least one slot in COR Archive for this program (only in deployed blocks).
     * No year-level filter — supports irregular paths across years.
     */
    public function subjectsForProgram(Request $request): JsonResponse
    {
        $request->validate(['program_id' => ['required', 'integer', 'exists:programs,id']]);
        $programId = (int) $request->program_id;

        $deployedBlockIds = $this->getDeployedBlockIdsForProgram($programId);
        if (empty($deployedBlockIds)) {
            return response()->json(['subjects' => []]);
        }

        $subjectIdsFromSlots = ScopeScheduleSlot::query()
            ->where('program_id', $programId)
            ->whereIn('block_id', $deployedBlockIds)
            ->distinct()
            ->pluck('subject_id')
            ->filter()
            ->values()
            ->all();
        $subjectIdsFromArchive = StudentCorRecord::query()
            ->where('program_id', $programId)
            ->whereIn('block_id', $deployedBlockIds)
            ->distinct()
            ->pluck('subject_id')
            ->filter()
            ->values()
            ->all();
        $subjectIdsInArchive = array_values(array_unique(array_merge($subjectIdsFromSlots, $subjectIdsFromArchive)));
        if (empty($subjectIdsInArchive)) {
            return response()->json(['subjects' => []]);
        }

        $subjects = Subject::query()
            ->where('is_active', true)
            ->whereIn('id', $subjectIdsInArchive)
            ->orderBy('code')
            ->get(['id', 'code', 'title', 'units', 'raw_subject_id']);
        $byRaw = $subjects->groupBy(fn ($s) => $s->raw_subject_id !== null ? (string) $s->raw_subject_id : 's' . $s->id);
        $representatives = $byRaw->map(fn ($g) => $g->sortBy('id')->first())->values();

        return response()->json([
            'subjects' => $representatives->map(fn ($s) => [
                'id' => $s->id,
                'code' => $s->code ?? '',
                'title' => $s->title ?? '',
                'units' => (int) ($s->units ?? 0),
            ])->values()->all(),
        ]);
    }

    /**
     * GET /registrar/schedule/forms/subjects-for-all-programs?semester=First Semester
     * Returns subjects from all programs (for irregular cross-program picking). Optional semester filter for current term.
     * Each subject includes program_id and program_name so the Schedule table row can show the correct program.
     */
    public function subjectsForAllPrograms(Request $request): JsonResponse
    {
        $semester = $request->input('semester');
        $semester = is_string($semester) ? trim($semester) : '';

        $query = Subject::query()
            ->with('program')
            ->where('is_active', true)
            ->when($semester !== '', fn ($q) => $q->where('semester', $semester))
            ->orderBy('code');

        $subjects = $query->get(['id', 'code', 'title', 'units', 'raw_subject_id', 'program_id']);
        $byRaw = $subjects->groupBy(fn ($s) => $s->raw_subject_id !== null ? (string) $s->raw_subject_id : 's' . $s->id);
        $representatives = $byRaw->map(fn ($g) => $g->sortBy('id')->first())->values();

        return response()->json([
            'subjects' => $representatives->map(function ($s) {
                $program = $s->relationLoaded('program') ? $s->program : null;
                return [
                    'id' => $s->id,
                    'code' => $s->code ?? '',
                    'title' => $s->title ?? '',
                    'units' => (int) ($s->units ?? 0),
                    'program_id' => $s->program_id ? (int) $s->program_id : null,
                    'program_name' => $program ? ($program->program_name ?? $program->code ?? '') : '',
                ];
            })->values()->all(),
        ]);
    }

    /**
     * Build COR Archive slot options for a program + subject (raw group). Optional year_level filters to blocks of that year.
     * Format: [Day] [Time] | [Room] | [Professor] | [Block].
     */
    private function buildSlotOptionsForProgramSubject(int $programId, int $subjectId, ?string $yearLevel = null): array
    {
        $subject = Subject::query()->where('id', $subjectId)->first(['id', 'raw_subject_id']);
        if (! $subject) {
            return [];
        }
        $subjectIdsInGroup = $subject->raw_subject_id
            ? Subject::query()->where('is_active', true)->where('raw_subject_id', $subject->raw_subject_id)->pluck('id')->all()
            : [$subjectId];
        if (empty($subjectIdsInGroup)) {
            $subjectIdsInGroup = [$subjectId];
        }

        $deployedBlockIds = $this->getDeployedBlockIdsForProgram($programId, $yearLevel);
        if (empty($deployedBlockIds)) {
            return [];
        }

        $dayNames = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
        $slots = ScopeScheduleSlot::query()
            ->with(['room', 'professor', 'block', 'academicYearLevel'])
            ->where('program_id', $programId)
            ->whereIn('subject_id', $subjectIdsInGroup)
            ->whereIn('block_id', $deployedBlockIds)
            ->whereNotNull('day_of_week')
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->get();
        $options = [];
        foreach ($slots as $s) {
            $start = $s->start_time ? \Carbon\Carbon::parse($s->start_time)->format('H:i') : '08:00';
            $end = $s->end_time ? \Carbon\Carbon::parse($s->end_time)->format('H:i') : '09:00';
            $day = (int) $s->day_of_week;
            $roomName = $s->room ? trim($s->room->code ?? $s->room->name ?? '') : '';
            $profName = $s->professor ? trim($s->professor->name ?? '') : '';
            $overload = $s->is_overload === true || (int) ($s->getRawOriginal('is_overload') ?? 0) === 1;
            $blockLabel = '';
            $effectiveBlockId = $s->block_id;
            if ($s->block) {
                $b = $s->block;
                $blockLabel = trim($b->code ?? $b->name ?? '');
                if ($blockLabel === '' && ($b->program || $b->year_level)) {
                    $blockLabel = trim(($b->program ?? '') . ' ' . ($b->year_level ?? '') . ($b->section_name ? ' - ' . $b->section_name : ''));
                }
            }
            if ($blockLabel === '' && $effectiveBlockId === null && $s->program_id && $s->semester) {
                $yearLevelName = $s->academicYearLevel ? trim($s->academicYearLevel->name ?? '') : null;
                $scopeBlock = Block::query()
                    ->where('program_id', (int) $s->program_id)
                    ->where('semester', trim($s->semester))
                    ->when($yearLevelName !== null && $yearLevelName !== '', fn ($q) => $q->where('year_level', $yearLevelName))
                    ->where('is_active', true)
                    ->orderBy('code')
                    ->first();
                if ($scopeBlock) {
                    $effectiveBlockId = $scopeBlock->id;
                    $blockLabel = trim($scopeBlock->code ?? $scopeBlock->name ?? '');
                    if ($blockLabel === '') {
                        $blockLabel = trim(($scopeBlock->program ?? '') . ' ' . ($scopeBlock->year_level ?? '') . ($scopeBlock->section_name ? ' - ' . $scopeBlock->section_name : ''));
                    }
                }
            }
            $profPart = $profName . ($overload ? ' (OVERLOAD)' : '');
            $label = ($dayNames[$day] ?? '') . ' ' . $start . '-' . $end . ' | ' . $roomName . ' | ' . $profPart . ($blockLabel !== '' ? ' | ' . $blockLabel : '');
            // Exclude options where the room or professor is already occupied at the same day/time in other schedules (global conflict)
            $roomConflicting = false;
            $profConflicting = false;
            if (! empty($s->room_id)) {
                $roomConflicting = ScopeScheduleSlot::query()
                    ->whereNotNull('room_id')
                    ->where('room_id', $s->room_id)
                    ->where('day_of_week', $day)
                    ->whereRaw('start_time < ? AND end_time > ?', [$end, $start])
                    ->where('program_id', '!=', $programId)
                    ->exists();
                if (! $roomConflicting) {
                    $roomConflicting = ClassSchedule::query()
                        ->whereNotNull('room_id')
                        ->where('room_id', $s->room_id)
                        ->where('day_of_week', $day)
                        ->whereRaw('start_time < ? AND end_time > ?', [$end, $start])
                        ->exists();
                }
                if (! $roomConflicting && $roomName !== '') {
                    $roomConflicting = StudentCorRecord::query()
                        ->whereNotNull('room_name_snapshot')
                        ->where('room_name_snapshot', $roomName)
                        ->where('start_time_snapshot', $start)
                        ->where('end_time_snapshot', $end)
                        ->where('days_snapshot', 'like', '%' . ($dayNames[$day] ?? '') . '%')
                        ->exists();
                }
            }
            if (! empty($s->professor_id)) {
                $profConflicting = ScopeScheduleSlot::query()
                    ->whereNotNull('professor_id')
                    ->where('professor_id', $s->professor_id)
                    ->where('day_of_week', $day)
                    ->whereRaw('start_time < ? AND end_time > ?', [$end, $start])
                    ->where('program_id', '!=', $programId)
                    ->exists();
                if (! $profConflicting) {
                    $profConflicting = ClassSchedule::query()
                        ->whereNotNull('professor_id')
                        ->where('professor_id', $s->professor_id)
                        ->where('day_of_week', $day)
                        ->whereRaw('start_time < ? AND end_time > ?', [$end, $start])
                        ->exists();
                }
                if (! $profConflicting && $profName !== '') {
                    $profConflicting = StudentCorRecord::query()
                        ->whereNotNull('professor_name_snapshot')
                        ->where('professor_name_snapshot', $profName)
                        ->where('start_time_snapshot', $start)
                        ->where('end_time_snapshot', $end)
                        ->where('days_snapshot', 'like', '%' . ($dayNames[$day] ?? '') . '%')
                        ->exists();
                }
            }

            if ($roomConflicting || $profConflicting) {
                // skip this option because it conflicts with existing bookings in other programs/courses
                continue;
            }

            $options[] = [
                'day_of_week' => $day,
                'start_time' => $start,
                'end_time' => $end,
                'room_id' => $s->room_id,
                'professor_id' => $s->professor_id,
                'block_id' => $effectiveBlockId,
                'block_name' => $blockLabel,
                'room_name' => $roomName,
                'professor_name' => $profName,
                'is_overload' => $overload,
                'label' => $label,
            ];
        }

        // COR Archive: deployed schedules live in student_cor_records (scope_schedule_slots are cleared on deploy).
        // Build options from StudentCorRecord so irregular Schedule table shows the same slots as COR Archive.
        $existingLabels = array_column($options, 'label');
        $corRecords = StudentCorRecord::query()
            ->where('program_id', $programId)
            ->whereIn('subject_id', $subjectIdsInGroup)
            ->whereIn('block_id', $deployedBlockIds)
            ->whereNotNull('days_snapshot')
            ->select('block_id', 'days_snapshot', 'start_time_snapshot', 'end_time_snapshot', 'room_name_snapshot', 'professor_name_snapshot', 'is_overload')
            ->distinct()
            ->get();
        $dayNameToNum = ['Mon' => 1, 'Tue' => 2, 'Wed' => 3, 'Thu' => 4, 'Fri' => 5, 'Sat' => 6, 'Sun' => 7];
        foreach ($corRecords as $rec) {
            $start = $rec->start_time_snapshot ? \Carbon\Carbon::parse($rec->start_time_snapshot)->format('H:i') : '08:00';
            $end = $rec->end_time_snapshot ? \Carbon\Carbon::parse($rec->end_time_snapshot)->format('H:i') : '09:00';
            $daysStr = trim((string) $rec->days_snapshot);
            $firstDay = 1;
            if ($daysStr !== '') {
                $parts = array_map('trim', explode(',', $daysStr));
                $firstDayPart = $parts[0] ?? '';
                $firstDay = $dayNameToNum[$firstDayPart] ?? 1;
            }
            $roomName = trim((string) ($rec->room_name_snapshot ?? ''));
            $profName = trim((string) ($rec->professor_name_snapshot ?? ''));
            $overload = (bool) $rec->is_overload;
            $profPart = $profName . ($overload ? ' (OVERLOAD)' : '');
            $blockLabel = '';
            $blockId = (int) $rec->block_id;
            if ($blockId) {
                $b = Block::find($blockId);
                if ($b) {
                    $blockLabel = trim($b->code ?? $b->name ?? '');
                    if ($blockLabel === '' && ($b->program || $b->year_level)) {
                        $blockLabel = trim(($b->program ?? '') . ' ' . ($b->year_level ?? '') . ($b->section_name ? ' - ' . $b->section_name : ''));
                    }
                }
            }
            $label = ($dayNames[$firstDay] ?? '') . ' ' . $start . '-' . $end . ' | ' . $roomName . ' | ' . $profPart . ($blockLabel !== '' ? ' | ' . $blockLabel : '');
            // Exclude if this archived record's room or professor is already booked at same day/time elsewhere
            $roomConflicting = false;
            $profConflicting = false;
            if ($roomName !== '') {
                // try to map room name/code to a Room id first
                $roomModel = \App\Models\Room::query()
                    ->where(function($q) use ($roomName) {
                        $q->where('code', $roomName)->orWhere('name', $roomName);
                    })->first(['id']);
                if ($roomModel) {
                    $roomId = $roomModel->id;
                    $roomConflicting = ScopeScheduleSlot::query()
                        ->whereNotNull('room_id')
                        ->where('room_id', $roomId)
                        ->where('day_of_week', $firstDay)
                        ->whereRaw('start_time < ? AND end_time > ?', [$end, $start])
                        ->where('program_id', '!=', $programId)
                        ->exists();
                    if (! $roomConflicting) {
                        $roomConflicting = ClassSchedule::query()
                            ->whereNotNull('room_id')
                            ->where('room_id', $roomId)
                            ->where('day_of_week', $firstDay)
                            ->whereRaw('start_time < ? AND end_time > ?', [$end, $start])
                            ->exists();
                    }
                }
                if (! $roomConflicting) {
                    // Exclude the current slot (same block + day) so we don't treat this COR record as conflicting with itself
                    $roomConflicting = StudentCorRecord::query()
                        ->whereNotNull('room_name_snapshot')
                        ->where('room_name_snapshot', $roomName)
                        ->where('days_snapshot', 'like', '%' . ($dayNames[$firstDay] ?? '') . '%')
                        ->where(function ($q) use ($rec, $start, $end) {
                            $q->where('block_id', '!=', $rec->block_id)
                                ->orWhereNull('block_id')
                                ->orWhere('days_snapshot', '!=', $rec->days_snapshot)
                                ->orWhereRaw('TIME(start_time_snapshot) != ?', [$start])
                                ->orWhereRaw('TIME(end_time_snapshot) != ?', [$end]);
                        })
                        ->whereRaw('TIME(start_time_snapshot) = ?', [$start])
                        ->whereRaw('TIME(end_time_snapshot) = ?', [$end])
                        ->exists();
                }
            }
            if ($profName !== '') {
                $profConflicting = ScopeScheduleSlot::query()
                    ->whereNotNull('professor_id')
                    ->where('day_of_week', $firstDay)
                    ->whereRaw('start_time < ? AND end_time > ?', [$end, $start])
                    ->where('program_id', '!=', $programId)
                    ->where('professor_id', '!=', null)
                    ->exists();
                if (! $profConflicting) {
                    $profConflicting = ClassSchedule::query()
                        ->whereNotNull('professor_id')
                        ->where('day_of_week', $firstDay)
                        ->whereRaw('start_time < ? AND end_time > ?', [$end, $start])
                        ->exists();
                }
                if (! $profConflicting) {
                    // Exclude the current slot so we don't treat this COR record as conflicting with itself
                    $profConflicting = StudentCorRecord::query()
                        ->whereNotNull('professor_name_snapshot')
                        ->where('professor_name_snapshot', $profName)
                        ->where('days_snapshot', 'like', '%' . ($dayNames[$firstDay] ?? '') . '%')
                        ->where(function ($q) use ($rec, $start, $end) {
                            $q->where('block_id', '!=', $rec->block_id)
                                ->orWhereNull('block_id')
                                ->orWhere('days_snapshot', '!=', $rec->days_snapshot)
                                ->orWhereRaw('TIME(start_time_snapshot) != ?', [$start])
                                ->orWhereRaw('TIME(end_time_snapshot) != ?', [$end]);
                        })
                        ->whereRaw('TIME(start_time_snapshot) = ?', [$start])
                        ->whereRaw('TIME(end_time_snapshot) = ?', [$end])
                        ->exists();
                }
            }
            if (in_array($label, $existingLabels, true) || $roomConflicting || $profConflicting) {
                continue;
            }
            $existingLabels[] = $label;
            $options[] = [
                'day_of_week' => $firstDay,
                'start_time' => $start,
                'end_time' => $end,
                'room_id' => null,
                'professor_id' => null,
                'block_id' => $blockId ?: null,
                'block_name' => $blockLabel,
                'room_name' => $roomName,
                'professor_name' => $profName,
                'is_overload' => $overload,
                'label' => $label,
            ];
        }

        return $options;
    }

    /**
     * GET /registrar/schedule/forms/slots-for-scope?program_id=1&subject_id=2&year_level=1st Year
     * Returns COR Archive slot options for this program, subject, and optional year level (filters by block year_level).
     */
    public function slotsForScope(Request $request): JsonResponse
    {
        $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'year_level' => ['nullable', 'string', 'max:100'],
        ]);
        $yearLevel = $request->input('year_level');
        $yearLevel = is_string($yearLevel) ? trim($yearLevel) : null;
        $options = $this->buildSlotOptionsForProgramSubject((int) $request->program_id, (int) $request->subject_id, $yearLevel !== '' ? $yearLevel : null);
        return response()->json(['slots' => $options]);
    }

    public function undeploy(int $id): RedirectResponse
    {
        $template = ScheduleTemplate::findOrFail($id);

        if (! $template->is_active) {
            return back()->with('success', 'Schedule is already undeployed.');
        }

        $template->update(['is_active' => false]);

        return back()->with('success', 'Schedule undeployed. Students will no longer see it in View COR.');
    }

    /**
     * Program Schedule (registrar): same data as dean Schedule by Program (scope_schedule_slots).
     * Registrar can add/remove subjects only; no Day, Start, End, Room, Professor. No Deploy/Archive.
     */
    public function programSchedule(Request $request): View|RedirectResponse
    {
        $program = $request->query('program');
        $year = $request->query('year');
        $semester = $request->query('semester');
        $schoolYear = $request->query('school_year') ?: AcademicCalendarService::getSelectedSchoolYearLabel();
        $programs = Program::orderBy('program_name')->get(['id', 'program_name', 'code']);
        $programNames = $programs->pluck('program_name')->all();
        $yearLevels = AcademicYearLevel::where('is_active', true)->orderBy('name')->pluck('name');
        $semesters = AcademicSemester::where('is_active', true)->orderBy('name')->pluck('name');
        $schoolYears = SchoolYear::query()->orderByDesc('start_year')->pluck('label')->all();
        $displayLabels = config('fee_programs.display_labels', []);
        $scheduleUrl = route('registrar.program-schedule.index');

        if ($program !== null && $program !== '' && $year !== null && $year !== '' && $semester !== null && $semester !== '') {
            return $this->programScheduleTable($program, $year, $semester, $schoolYear, $programNames, $yearLevels, $semesters, $schoolYears, $displayLabels, $scheduleUrl);
        }
        if ($program !== null && $program !== '' && $year !== null && $year !== '') {
            $programLabel = $displayLabels[$program] ?? $program;
            return view('dashboards.registrar-program-schedule', [
                'viewMode' => 'semesters',
                'program' => $program,
                'programLabel' => $programLabel,
                'year' => $year,
                'semesters' => $semesters,
                'schoolYears' => $schoolYears,
                'school_year' => $schoolYear,
                'breadcrumb' => [
                    ['label' => 'Program Schedule', 'url' => $scheduleUrl],
                    ['label' => $programLabel, 'url' => $scheduleUrl . '?program=' . urlencode($program)],
                    ['label' => $year, 'url' => $scheduleUrl . '?program=' . urlencode($program) . '&year=' . urlencode($year)],
                ],
            ]);
        }
        if ($program !== null && $program !== '') {
            $programLabel = $displayLabels[$program] ?? $program;
            $defaultSchoolYear = $schoolYear ?: ($schoolYears[0] ?? null);
            return view('dashboards.registrar-program-schedule', [
                'viewMode' => 'years',
                'program' => $program,
                'programLabel' => $programLabel,
                'yearLevels' => $yearLevels,
                'schoolYears' => $schoolYears,
                'school_year' => $defaultSchoolYear,
                'breadcrumb' => [
                    ['label' => 'Program Schedule', 'url' => $scheduleUrl],
                    ['label' => $programLabel, 'url' => $scheduleUrl . '?program=' . urlencode($program)],
                ],
            ]);
        }

        return view('dashboards.registrar-program-schedule', [
            'viewMode' => 'programs',
            'programs' => $programs,
            'displayLabels' => $displayLabels,
            'breadcrumb' => [['label' => 'Program Schedule', 'url' => $scheduleUrl]],
        ]);
    }

    private function programScheduleTable(
        string $program,
        string $year,
        string $semester,
        ?string $schoolYear,
        $programNames,
        $yearLevels,
        $semesters,
        array $schoolYears,
        array $displayLabels,
        string $scheduleUrl
    ): View {
        $programModel = Program::where('program_name', $program)->first();
        $yearLevelModel = AcademicYearLevel::where('name', $year)->first();
        $subjects = collect();
        $slotsBySubject = [];
        $corScope = null;
        if ($programModel && $yearLevelModel) {
            if ($schoolYear) {
                $corScope = CorScope::findForScope($programModel->id, $yearLevelModel->id, $semester, $schoolYear, null);
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
        }
        // Same data as Dean Schedule by Program: one row per subject (with slot or null) so pushed subjects and dean edits are visible.
        $rows = [];
        foreach ($subjects as $subject) {
            $slots = $slotsBySubject[$subject->id] ?? [];
            $slot = ! empty($slots) ? $slots[0] : null;
            $rows[] = ['subject' => $subject, 'slot' => $slot];
        }
        $availableSubjectsForAdd = $subjects->filter(fn ($s) => ! isset($slotsBySubject[$s->id]) || empty($slotsBySubject[$s->id]))->values();
        $programLabel = $displayLabels[$program] ?? $program;
        $tableUrl = $scheduleUrl . '?program=' . urlencode($program) . '&year=' . urlencode($year) . '&semester=' . urlencode($semester) . ($schoolYear ? '&school_year=' . urlencode($schoolYear) : '');
        return view('dashboards.registrar-program-schedule', [
            'viewMode' => 'table',
            'program' => $program,
            'programLabel' => $programLabel,
            'year' => $year,
            'semester' => $semester,
            'school_year' => $schoolYear,
            'schoolYears' => $schoolYears,
            'program_id' => $programModel?->id,
            'academic_year_level_id' => $yearLevelModel?->id,
            'rows' => $rows,
            'availableSubjectsForAdd' => $availableSubjectsForAdd,
            'breadcrumb' => [
                ['label' => 'Program Schedule', 'url' => $scheduleUrl],
                ['label' => $programLabel, 'url' => $scheduleUrl . '?program=' . urlencode($program)],
                ['label' => $year, 'url' => $scheduleUrl . '?program=' . urlencode($program) . '&year=' . urlencode($year)],
                ['label' => $semester, 'url' => $tableUrl],
            ],
        ]);
    }

    public function saveProgramSchedule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'academic_year_level_id' => ['required', 'integer', 'exists:academic_year_levels,id'],
            'semester' => ['required', 'string', 'max:100'],
            'school_year' => ['nullable', 'string', 'max:100'],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
        ]);
        $programId = (int) $validated['program_id'];
        $yearLevelId = (int) $validated['academic_year_level_id'];
        $semester = trim($validated['semester']);
        $schoolYear = isset($validated['school_year']) && $validated['school_year'] !== '' ? trim($validated['school_year']) : null;
        $subjectIds = array_values(array_unique(array_filter(array_map('intval', $validated['subject_ids'] ?? []))));

        DB::transaction(function () use ($programId, $yearLevelId, $semester, $schoolYear, $subjectIds) {
            $query = ScopeScheduleSlot::query()
                ->where('program_id', $programId)
                ->where('academic_year_level_id', $yearLevelId)
                ->where('semester', $semester);
            if ($schoolYear) {
                $query->where(function ($q) use ($schoolYear) {
                    $q->where('school_year', $schoolYear)->orWhereNull('school_year');
                });
            }
            $existingSubjectIds = $query->distinct()->pluck('subject_id')->all();
            foreach ($existingSubjectIds as $subjId) {
                if (!in_array((int) $subjId, $subjectIds, true)) {
                    $del = ScopeScheduleSlot::query()
                        ->where('program_id', $programId)
                        ->where('academic_year_level_id', $yearLevelId)
                        ->where('semester', $semester)
                        ->where('subject_id', $subjId);
                    if ($schoolYear) {
                        $del->where(function ($q2) use ($schoolYear) {
                            $q2->where('school_year', $schoolYear)->orWhereNull('school_year');
                        });
                    }
                    $del->delete();
                }
            }
            foreach ($subjectIds as $subjectId) {
                $exists = ScopeScheduleSlot::query()
                    ->where('program_id', $programId)
                    ->where('academic_year_level_id', $yearLevelId)
                    ->where('semester', $semester)
                    ->where('subject_id', $subjectId)
                    ->when($schoolYear, fn ($q) => $q->where(function ($q2) use ($schoolYear) {
                        $q2->where('school_year', $schoolYear)->orWhereNull('school_year');
                    }))->exists();
                if (!$exists) {
                    ScopeScheduleSlot::create([
                        'program_id' => $programId,
                        'academic_year_level_id' => $yearLevelId,
                        'semester' => $semester,
                        'school_year' => $schoolYear,
                        'subject_id' => $subjectId,
                        'day_of_week' => null,
                        'start_time' => null,
                        'end_time' => null,
                        'room_id' => null,
                        'professor_id' => null,
                    ]);
                }
            }
        });

        $program = Program::find($programId);
        $programName = $program ? $program->program_name : '';
        $year = AcademicYearLevel::find($yearLevelId)?->name ?? '';
        $url = route('registrar.program-schedule.index') . '?program=' . urlencode($programName) . '&year=' . urlencode($year) . '&semester=' . urlencode($semester);
        if ($schoolYear) {
            $url .= '&school_year=' . urlencode($schoolYear);
        }
        return redirect()->to($url)->with('success', 'Program schedule saved. Dean can set day, time, room, and professor on Schedule by Program.');
    }
}

