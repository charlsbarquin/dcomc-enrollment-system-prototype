<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\BlockTransferLog;
use App\Models\FormResponse;
use App\Models\Program;
use App\Models\StudentBlockAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Services\AcademicCalendarService;
use App\Services\AcademicNormalizer;
use App\Services\BlockManagementService;
use App\Services\BlockPromotionService;
use App\Services\BlockRebalancingService;
use App\Services\IrregularEnrollmentValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class BlockManagementController extends Controller
{
    public function __construct(
        protected BlockManagementService $blockManagement,
        protected BlockRebalancingService $rebalancing,
        protected BlockPromotionService $promotion
    ) {}

    /**
     * Block Explorer page (Google Drive style: Programs → Year → Blocks).
     */
    public function blockExplorer()
    {
        return view('dashboards.block-explorer');
    }

    /**
     * Students Explorer: merged Block Explorer + Edit Student Record (replaces Student Status for registrar).
     * Same tree layout; table adds Status and Actions (Edit Student Record modal).
     */
    public function studentsExplorer(): View
    {
        $selectedLabel = AcademicCalendarService::getSelectedSchoolYearLabel();
        $availableCourses = collect()
            ->merge(Program::query()->orderBy('program_name')->pluck('program_name'))
            ->merge(User::query()->where('role', User::ROLE_STUDENT)->whereNotNull('course')->distinct()->pluck('course'))
            ->merge(Block::query()->when($selectedLabel, fn ($q) => $q->where('school_year_label', $selectedLabel))->whereNotNull('program')->distinct()->pluck('program'))
            ->merge(\App\Models\Major::query()->where('is_active', true)->distinct()->pluck('program'))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $yearLevels = \App\Models\AcademicYearLevel::where('is_active', true)->orderBy('name')->pluck('name');
        $semesters = \App\Models\AcademicSemester::where('is_active', true)->orderBy('name')->pluck('name');
        $blocksQuery = Block::query()
            ->orderBy('program')
            ->orderBy('year_level')
            ->orderBy('semester')
            ->orderBy('code');
        if ($selectedLabel !== null && $selectedLabel !== '') {
            $activeLabel = AcademicCalendarService::getActiveSchoolYearLabel();
            $blocksQuery->where(function ($q) use ($selectedLabel, $activeLabel) {
                $q->where('school_year_label', $selectedLabel);
                if ($activeLabel !== null && $activeLabel === $selectedLabel) {
                    $q->orWhereNull('school_year_label')->orWhere('school_year_label', '');
                }
            });
        }
        $blocks = $blocksQuery->get();
        $schoolYears = \App\Models\SchoolYear::orderByDesc('start_year')->pluck('label')->values();
        $majorsByProgram = \App\Models\Major::majorsByProgram();

        $actingRole = auth()->user()?->effectiveRole();
        $routeName = $actingRole === User::ROLE_STAFF
            ? 'staff.student-status.update-record'
            : 'registrar.student-status.update-record';
        $updateRouteTemplate = route($routeName, ['id' => '__ID__']);
        $statusOptions = ['pending', 'needs_correction', 'approved', 'scheduled', 'completed', 'rejected'];

        return view('dashboards.students-explorer', compact(
            'availableCourses', 'yearLevels', 'semesters', 'blocks', 'schoolYears', 'majorsByProgram', 'updateRouteTemplate', 'statusOptions'
        ));
    }

    /**
     * GET /registrar/block-explorer/tree — JSON tree for Programs → Year Level → Blocks.
     */
    public function tree(Request $request): JsonResponse
    {
        $programs = Program::orderBy('program_name')->get(['id', 'program_name', 'code']);
        $canonicalOrder = AcademicNormalizer::canonicalYearSemesterOrder();

        // Build tree from programs: every program gets all 8 folders (1st–4th year, First & Second semester)
        $byProgram = [];
        foreach ($programs as $p) {
            $programKey = (string) $p->id;
            $years = [];
            foreach ($canonicalOrder as $pair) {
                $yearKey = $pair['year_level'] . '|' . $pair['semester'];
                $years[$yearKey] = [
                    'id' => 'year-' . $programKey . '-' . md5($yearKey),
                    'label' => $pair['year_level'] . ' — ' . $pair['semester'],
                    'year_level' => $pair['year_level'],
                    'semester' => $pair['semester'],
                    'blocks' => [],
                ];
            }
            $byProgram[$programKey] = [
                'id' => 'program-' . $programKey,
                'label' => $p->program_name,
                'program_id' => $p->id,
                'years' => $years,
            ];
        }

        // Map program name/code -> program id for blocks that reference by string
        $programIdByNameOrCode = [];
        foreach ($programs as $p) {
            $programIdByNameOrCode[trim((string) $p->program_name)] = $p->id;
            if ($p->code !== null && $p->code !== '') {
                $programIdByNameOrCode[trim((string) $p->code)] = $p->id;
            }
        }

        $selectedLabel = AcademicCalendarService::getSelectedSchoolYearLabel();
        $blocksQuery = Block::query()
            ->where('is_active', true)
            ->orderBy('year_level')
            ->orderBy('section_name')
            ->orderBy('code');
        if ($selectedLabel !== null && $selectedLabel !== '') {
            $activeLabel = AcademicCalendarService::getActiveSchoolYearLabel();
            $blocksQuery->where(function ($q) use ($selectedLabel, $activeLabel) {
                $q->where('school_year_label', $selectedLabel);
                if ($activeLabel !== null && $activeLabel === $selectedLabel) {
                    $q->orWhereNull('school_year_label')->orWhere('school_year_label', '');
                }
            });
        }
        $blocks = $blocksQuery->get(['id', 'program_id', 'program', 'year_level', 'section_name', 'semester', 'code', 'capacity', 'max_capacity', 'school_year_label']);
        $blockIds = $blocks->pluck('id')->all();
        $currentCountsByBlock = Block::currentCountsByBlockForSchoolYear($blockIds, $selectedLabel);

        foreach ($blocks as $block) {
            $programId = $block->program_id;
            if ($programId === null && $block->program !== null && $block->program !== '') {
                $programId = $programIdByNameOrCode[trim($block->program)] ?? null;
            }
            $yearLevel = AcademicNormalizer::normalizeYearLevel($block->year_level);
            $semester = AcademicNormalizer::normalizeSemester($block->semester);
            if ($programId === null || $yearLevel === null || $semester === null) {
                continue;
            }
            $programKey = (string) $programId;
            $yearKey = $yearLevel . '|' . $semester;
            if (! isset($byProgram[$programKey]['years'][$yearKey])) {
                continue;
            }
            $byProgram[$programKey]['years'][$yearKey]['blocks'][] = [
                'id' => $block->id,
                'program_id' => (int) $programId,
                'label' => $block->code ?? $block->section_name ?? 'Block ' . $block->id,
                'code' => $block->code,
                'section_name' => $block->section_name,
                'current_size' => $currentCountsByBlock[(int) $block->id] ?? 0,
                'max_capacity' => $block->effectiveMaxCapacity(),
            ];
        }

        // Output: each program with years in fixed order (1st Year First Sem … 4th Year Second Sem)
        $tree = [];
        foreach ($byProgram as $p) {
            $p['years'] = array_values($p['years']);
            foreach ($p['years'] as &$y) {
                $y['blocks'] = array_values($y['blocks']);
            }
            $tree[] = $p;
        }

        return response()->json(['programs' => $programs, 'tree' => $tree]);
    }

    /**
     * GET /registrar/block-explorer/blocks/{block}/students — paginated students in block.
     * Returns full fields for Edit Student Record modal and status (student_type, status_color, latest process_status).
     */
    public function blockStudents(Request $request, Block $block): JsonResponse
    {
        $selectedLabel = AcademicCalendarService::getSelectedSchoolYearLabel();
        if ($selectedLabel !== null && $selectedLabel !== '') {
            $blockLabel = (string) ($block->school_year_label ?? '');
            $activeLabel = AcademicCalendarService::getActiveSchoolYearLabel();
            $match = $blockLabel === $selectedLabel || ($blockLabel === '' && $activeLabel === $selectedLabel);
            if (! $match) {
                abort(404, 'Block not found for the selected school year.');
            }
        }
        $perPage = max(1, min(100, (int) $request->get('per_page', 20)));
        $paginator = User::query()
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
            ->paginate($perPage, [
                'id', 'name', 'first_name', 'last_name', 'middle_name', 'school_id', 'year_level', 'semester',
                'student_status', 'email', 'course', 'major', 'school_year', 'block_id', 'shift', 'student_type',
                'previous_program', 'phone', 'gender', 'status_color', 'created_at',
            ]);

        $userIds = $paginator->pluck('id')->all();
        $latestProcessStatus = [];
        if (! empty($userIds)) {
            $latest = FormResponse::query()
                ->forSelectedSchoolYear()
                ->whereIn('user_id', $userIds)
                ->orderByDesc('id')
                ->get(['user_id', 'process_status'])
                ->unique('user_id');
            foreach ($latest as $fr) {
                $latestProcessStatus[$fr->user_id] = $fr->process_status ?? '';
            }
        }

        $enrollmentSchoolYear = function ($user) {
            if (! $user->created_at) {
                return null;
            }
            return \App\Http\Controllers\AdminAccountController::schoolYearLabelForDate($user->created_at);
        };

        $data = $paginator->getCollection()->map(function ($s) use ($block, $latestProcessStatus, $enrollmentSchoolYear) {
            $inBlockViaAssignment = (int) ($s->block_id ?? 0) !== (int) $block->id;
            return [
                'id' => $s->id,
                'name' => $s->name,
                'first_name' => $s->first_name,
                'last_name' => $s->last_name,
                'middle_name' => $s->middle_name,
                'school_id' => $s->school_id,
                'year_level' => $s->year_level,
                'semester' => $s->semester,
                'email' => $s->email,
                'course' => $s->course,
                'major' => $s->major,
                'school_year' => $s->school_year,
                'enrollment_school_year' => $enrollmentSchoolYear($s),
                'block_id' => $s->block_id,
                'in_block_via_assignment' => $inBlockViaAssignment,
                'shift' => $s->shift,
                'student_type' => $s->student_type,
                'previous_program' => $s->previous_program,
                'phone' => $s->phone,
                'gender' => $s->gender,
                'status_color' => $s->status_color,
                'process_status' => $latestProcessStatus[$s->id] ?? null,
            ];
        })->all();

        return response()->json([
            'current_page' => $paginator->currentPage(),
            'data' => $data,
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    /**
     * GET .../block-explorer/blocks/{block}/print-master-list
     * Printable master student list for a block (Programs → Year → Blocks). All students in block, ordered by last name, first name.
     */
    public function printBlockMasterList(Block $block): View
    {
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
            ->get(['id', 'school_id', 'last_name', 'first_name', 'middle_name', 'email', 'year_level', 'course', 'student_type', 'gender']);

        $block->load('program');

        $backUrl = auth()->user()?->effectiveRole() === 'staff'
            ? route('staff.students-explorer')
            : route('registrar.students-explorer');

        return view('dashboards.print-master-student-list', [
            'block' => $block,
            'students' => $students,
            'back_url' => $backUrl,
        ]);
    }

    /**
     * GET /registrar/students-explorer/students — all students with optional filters (for Students Table view).
     * Same response shape as blockStudents for table/modal compatibility.
     */
    public function allStudents(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->get('per_page', 50)));
        $selectedLabel = AcademicCalendarService::getSelectedSchoolYearLabel();
        $query = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->with('block')
            ->orderBy('last_name')
            ->orderBy('first_name');
        if ($selectedLabel !== null && $selectedLabel !== '') {
            $activeLabel = AcademicCalendarService::getActiveSchoolYearLabel();
            $query->where(function ($q) use ($selectedLabel, $activeLabel) {
                $q->where('school_year', $selectedLabel);
                if ($activeLabel !== null && $activeLabel === $selectedLabel) {
                    $q->orWhereNull('school_year')->orWhere('school_year', '');
                }
            });
        }

        $studentNumber = trim((string) $request->get('student_number', ''));
        $program = trim((string) $request->get('program', ''));
        $schoolYear = trim((string) $request->get('school_year', ''));
        $firstName = trim((string) $request->get('first_name', ''));
        $yearLevel = trim((string) $request->get('year_level', ''));
        $lastName = trim((string) $request->get('last_name', ''));
        $semester = trim((string) $request->get('semester', ''));
        $status = trim((string) $request->get('status', ''));
        $folder = trim((string) $request->get('folder', ''));

        if ($studentNumber !== '') {
            $query->where('school_id', 'like', '%' . $studentNumber . '%');
        }
        if ($program !== '') {
            $query->where(function ($q) use ($program) {
                $q->where('course', 'like', '%' . $program . '%')
                    ->orWhereHas('block', fn ($b) => $b->where('program', 'like', '%' . $program . '%'));
            });
        }
        if ($schoolYear !== '') {
            $query->where('school_year', $schoolYear);
        }
        if ($firstName !== '') {
            $query->where('first_name', 'like', '%' . $firstName . '%');
        }
        if ($yearLevel !== '') {
            $query->where(function ($q) use ($yearLevel) {
                $q->where('year_level', $yearLevel)
                    ->orWhereHas('block', fn ($b) => $b->where('year_level', $yearLevel));
            });
        }
        if ($lastName !== '') {
            $query->where('last_name', 'like', '%' . $lastName . '%');
        }
        if ($semester !== '') {
            $query->where(function ($q) use ($semester) {
                $q->where('semester', $semester)
                    ->orWhereHas('block', fn ($b) => $b->where('semester', $semester));
            });
        }
        if ($status !== '') {
            $query->whereHas('formResponses', fn ($fr) => $fr->where('process_status', $status));
        }
        if ($folder !== '') {
            $query->where(function ($q) use ($folder) {
                $q->where('school_id', 'like', '%' . $folder . '%')
                    ->orWhere('name', 'like', '%' . $folder . '%')
                    ->orWhere('first_name', 'like', '%' . $folder . '%')
                    ->orWhere('last_name', 'like', '%' . $folder . '%')
                    ->orWhere('course', 'like', '%' . $folder . '%')
                    ->orWhere('email', 'like', '%' . $folder . '%');
            });
        }

        $paginator = $query->paginate($perPage, [
            'id', 'name', 'first_name', 'last_name', 'middle_name', 'school_id', 'year_level', 'semester',
            'student_status', 'email', 'course', 'major', 'school_year', 'block_id', 'shift', 'student_type',
            'previous_program', 'phone', 'gender', 'status_color', 'created_at',
        ]);

        $userIds = $paginator->pluck('id')->all();
        $latestProcessStatus = [];
        if (! empty($userIds)) {
            $latest = FormResponse::query()
                ->forSelectedSchoolYear()
                ->whereIn('user_id', $userIds)
                ->orderByDesc('id')
                ->get(['user_id', 'process_status'])
                ->unique('user_id');
            foreach ($latest as $fr) {
                $latestProcessStatus[$fr->user_id] = $fr->process_status ?? '';
            }
        }

        $enrollmentSchoolYear = function ($user) {
            if (! $user->created_at) {
                return null;
            }
            return \App\Http\Controllers\AdminAccountController::schoolYearLabelForDate($user->created_at);
        };

        $data = $paginator->getCollection()->map(function ($s) use ($latestProcessStatus, $enrollmentSchoolYear) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'first_name' => $s->first_name,
                'last_name' => $s->last_name,
                'middle_name' => $s->middle_name,
                'school_id' => $s->school_id,
                'year_level' => $s->year_level,
                'semester' => $s->semester,
                'email' => $s->email,
                'course' => $s->course,
                'major' => $s->major,
                'school_year' => $s->school_year,
                'enrollment_school_year' => $enrollmentSchoolYear($s),
                'block_id' => $s->block_id,
                'shift' => $s->shift,
                'student_type' => $s->student_type,
                'previous_program' => $s->previous_program,
                'phone' => $s->phone,
                'gender' => $s->gender,
                'status_color' => $s->status_color,
                'process_status' => $latestProcessStatus[$s->id] ?? null,
            ];
        })->all();

        return response()->json([
            'current_page' => $paginator->currentPage(),
            'data' => $data,
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    /**
     * GET /registrar/students-explorer/students/{user}/block-assignments — block assignments for a student (irregular).
     */
    public function blockAssignments(User $user): JsonResponse
    {
        $assignments = StudentBlockAssignment::query()
            ->where('user_id', $user->id)
            ->with('block:id,code,program,year_level,semester')
            ->orderBy('id')
            ->get();
        return response()->json([
            'assignments' => $assignments->map(fn ($a) => [
                'id' => $a->id,
                'block_id' => $a->block_id,
                'block' => $a->block ? [
                    'id' => $a->block->id,
                    'code' => $a->block->code,
                    'program' => $a->block->program,
                    'year_level' => $a->block->year_level,
                    'semester' => $a->block->semester,
                ] : null,
            ])->all(),
        ]);
    }

    /**
     * POST /registrar/block-explorer/assign-irregular — assign an irregular student to a block (many blocks allowed).
     */
    public function assignIrregularToBlock(Request $request): JsonResponse
    {
        if (auth()->user()?->effectiveRole() === User::ROLE_STAFF) {
            abort(403, 'Staff cannot assign students to blocks. Only registrar or admin can transfer or move students.');
        }
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'block_id' => ['required', 'integer', 'exists:blocks,id'],
        ]);
        $user = User::findOrFail($validated['user_id']);
        if (! $user->isIrregularType()) {
            throw ValidationException::withMessages(['user_id' => 'Only irregular-type students can be assigned to multiple blocks.']);
        }
        $block = Block::findOrFail($validated['block_id']);
        $exists = StudentBlockAssignment::query()
            ->where('user_id', $user->id)
            ->where('block_id', $block->id)
            ->exists();
        if ($exists) {
            return response()->json(['success' => true, 'message' => 'Already assigned.']);
        }

        // Block must not contain subjects the student has already completed (passed/credited)
        $validationService = app(IrregularEnrollmentValidationService::class);
        [$blockAllowed, $blockMessage] = $validationService->validateBlockAssignmentForIrregular((int) $user->id, (int) $block->id);
        if (! $blockAllowed && $blockMessage !== null) {
            return response()->json(['success' => false, 'message' => $blockMessage], 422);
        }

        StudentBlockAssignment::create([
            'user_id' => $user->id,
            'block_id' => $block->id,
        ]);
        $block->increment('current_size');
        return response()->json(['success' => true, 'message' => 'Assigned to block.']);
    }

    /**
     * DELETE /registrar/block-explorer/assign-irregular — remove an irregular student from a block assignment.
     */
    public function removeIrregularFromBlock(Request $request): JsonResponse
    {
        if (auth()->user()?->effectiveRole() === User::ROLE_STAFF) {
            abort(403, 'Staff cannot remove students from blocks. Only registrar or admin can transfer or move students.');
        }
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'block_id' => ['required', 'integer', 'exists:blocks,id'],
        ]);
        $assignment = StudentBlockAssignment::query()
            ->where('user_id', $validated['user_id'])
            ->where('block_id', $validated['block_id'])
            ->first();
        if ($assignment) {
            $assignment->delete();
            Block::where('id', $validated['block_id'])->decrement('current_size');
        }
        return response()->json(['success' => true, 'message' => 'Removed from block.']);
    }

    /**
     * POST /registrar/blocks/transfer — manual transfer (registrar/admin).
     */
    public function transfer(Request $request): JsonResponse
    {
        if (auth()->user()?->effectiveRole() === User::ROLE_STAFF) {
            abort(403, 'Staff cannot transfer students between blocks. Only registrar or admin can transfer or move students.');
        }
        $validated = $request->validate([
            'from_block_id' => ['required', 'integer', 'exists:blocks,id'],
            'to_block_id' => ['required', 'integer', 'exists:blocks,id'],
            'student_ids' => ['required', 'array'],
            'student_ids.*' => ['integer', 'exists:users,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $result = $this->blockManagement->transferStudents(
                (int) $validated['from_block_id'],
                (int) $validated['to_block_id'],
                $validated['student_ids'],
                $request->user()?->id,
                $validated['reason'] ?? null
            );
            return response()->json(['success' => true, 'moved' => $result['moved'], 'student_ids' => $result['student_ids']]);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['transfer' => $e->getMessage()]);
        }
    }

    /**
     * POST /registrar/blocks/rebalance — trigger auto-rebalance (optional block_id or scope).
     */
    public function rebalance(Request $request): JsonResponse
    {
        if (auth()->user()?->effectiveRole() === User::ROLE_STAFF) {
            abort(403, 'Staff cannot rebalance blocks. Only registrar or admin can transfer or move students.');
        }
        $blockId = $request->input('block_id');
        $programId = $request->input('program_id');
        $yearLevel = $request->input('year_level');
        $semester = $request->input('semester');

        if ($blockId) {
            $moved = $this->rebalancing->rebalanceBlock((int) $blockId);
            return response()->json(['success' => true, 'moved' => $moved]);
        }

        if ($yearLevel && $semester) {
            $moved = $this->rebalancing->rebalanceScope($programId ? (int) $programId : null, $yearLevel, $semester);
            return response()->json(['success' => true, 'moved' => $moved]);
        }

        throw ValidationException::withMessages(['rebalance' => 'Provide block_id or (year_level and semester).']);
    }

    /**
     * POST /registrar/blocks/promotion — run promotion (end of academic year).
     */
    public function promotion(Request $request): JsonResponse
    {
        if (auth()->user()?->effectiveRole() === User::ROLE_STAFF) {
            abort(403, 'Staff cannot run promotion. Only registrar or admin can transfer or move students.');
        }
        $result = $this->promotion->runPromotion();
        return response()->json(['success' => true, 'promoted' => $result['promoted'], 'blocks_created' => $result['blocks_created']]);
    }

    /**
     * GET /registrar/blocks/transfer-log — paginated transfer log (filters: student_id, block_id, transfer_type, from_date, to_date).
     */
    public function transferLog(Request $request): JsonResponse
    {
        $query = BlockTransferLog::with(['student:id,name,school_id', 'fromBlock:id,code', 'toBlock:id,code', 'initiatedBy:id,name'])
            ->orderByDesc('created_at');

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->filled('block_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('from_block_id', $request->block_id)->orWhere('to_block_id', $request->block_id);
            });
        }
        if ($request->filled('transfer_type')) {
            $query->where('transfer_type', $request->transfer_type);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $logs = $query->paginate(max(1, min(100, (int) $request->get('per_page', 20))));
        return response()->json($logs);
    }
}
