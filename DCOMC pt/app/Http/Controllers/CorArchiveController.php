<?php

namespace App\Http\Controllers;

use App\Models\AcademicSemester;
use App\Models\AcademicYearLevel;
use App\Models\Block;
use App\Models\Program;
use App\Models\SchoolYear;
use App\Models\StudentCorRecord;
use App\Services\AcademicCalendarService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * COR Archive: read-only view of deployed COR by Program / Year Level / Semester / School Year.
 * Dean: department scope. Registrar: all. Students: own records only (use StudentServicesController).
 */
class CorArchiveController extends Controller
{
    /**
     * Folder: Program → Year Level → Semester → COR.
     * Lists programs (Dean: department; Registrar: all).
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $actingRole = $user->effectiveRole();
        $programs = Program::query()->orderBy('program_name');
        if ($user->role === 'dean' && $user->department_id) {
            $programs->where('department_id', $user->department_id);
        }
        $programs = $programs->get(['id', 'program_name', 'code']);

        $indexRoute = $actingRole === 'registrar'
            ? 'registrar.cor.archive.index'
            : ($actingRole === 'staff' ? 'staff.cor.archive.index' : 'cor.archive.index');
        return view('dashboards.cor-archive-index', [
            'programs' => $programs,
            'breadcrumb' => [['label' => 'COR Archive', 'url' => route($indexRoute)]],
            'archive_index_route' => $indexRoute,
            'archive_program_route' => $actingRole === 'registrar'
                ? 'registrar.cor.archive.program'
                : ($actingRole === 'staff' ? 'staff.cor.archive.program' : 'cor.archive.program'),
            'archive_show_route' => $actingRole === 'registrar'
                ? 'registrar.cor.archive.show'
                : ($actingRole === 'staff' ? 'staff.cor.archive.show' : 'cor.archive.show'),
        ]);
    }

    /**
     * Program folder: School Year dropdown + prebuilt Year Level folders (from AcademicYearLevel).
     */
    public function program(Request $request, int $programId): View
    {
        $user = $request->user();
        $actingRole = $user->effectiveRole();
        $program = Program::find($programId);
        if (!$program) {
            abort(404);
        }
        if ($user->role === 'dean' && $user->department_id && (int) $program->department_id !== (int) $user->department_id) {
            abort(403, 'You cannot view this program archive.');
        }

        $schoolYears = SchoolYear::query()->orderByDesc('start_year')->pluck('label')->all();
        $selectedSchoolYear = $request->query('school_year', AcademicCalendarService::getSelectedSchoolYearLabel() ?? $schoolYears[0] ?? null);
        $selectedSchoolYear = $selectedSchoolYear ? trim($selectedSchoolYear) : null;

        // Prebuild: all active year levels (folder structure does not depend on deployed COR)
        $yearLevels = AcademicYearLevel::where('is_active', true)->orderBy('name')->pluck('name');

        $indexRoute = $actingRole === 'registrar'
            ? 'registrar.cor.archive.index'
            : ($actingRole === 'staff' ? 'staff.cor.archive.index' : 'cor.archive.index');
        $programRoute = $actingRole === 'registrar'
            ? 'registrar.cor.archive.program'
            : ($actingRole === 'staff' ? 'staff.cor.archive.program' : 'cor.archive.program');
        $yearRoute = $actingRole === 'registrar'
            ? 'registrar.cor.archive.year'
            : ($actingRole === 'staff' ? 'staff.cor.archive.year' : 'cor.archive.year');
        $deleteRoute = $actingRole === 'registrar'
            ? 'registrar.cor.archive.delete-block'
            : ($actingRole === 'staff' ? 'staff.cor.archive.delete-block' : 'cor.archive.delete-block');

        return view('dashboards.cor-archive-program', [
            'program' => $program,
            'schoolYears' => $schoolYears,
            'selectedSchoolYear' => $selectedSchoolYear,
            'yearLevels' => $yearLevels,
            'breadcrumb' => [
                ['label' => 'COR Archive', 'url' => route($indexRoute)],
                ['label' => $program->program_name ?? 'Program', 'url' => route($programRoute, ['programId' => $programId])],
            ],
            'archive_index_route' => $indexRoute,
            'archive_program_route' => $programRoute,
            'archive_year_route' => $yearRoute,
            'archive_show_route' => $actingRole === 'registrar'
                ? 'registrar.cor.archive.show'
                : ($actingRole === 'staff' ? 'staff.cor.archive.show' : 'cor.archive.show'),
        ]);
    }

    /**
     * Year Level folder: prebuilt Semester folders (from AcademicSemester).
     */
    public function year(Request $request, int $programId, string $yearLevel): View
    {
        $yearLevel = trim($yearLevel);
        $user = $request->user();
        $actingRole = $user->effectiveRole();
        $program = Program::find($programId);
        if (!$program) {
            abort(404);
        }
        if ($user->role === 'dean' && $user->department_id && (int) $program->department_id !== (int) $user->department_id) {
            abort(403, 'You cannot view this program archive.');
        }

        $schoolYears = SchoolYear::query()->orderByDesc('start_year')->pluck('label')->all();
        $selectedSchoolYear = $request->query('school_year', AcademicCalendarService::getSelectedSchoolYearLabel() ?? $schoolYears[0] ?? null);
        $selectedSchoolYear = $selectedSchoolYear ? trim($selectedSchoolYear) : null;

        // Prebuild: all active semesters (folder structure does not depend on deployed COR)
        $semesters = AcademicSemester::where('is_active', true)->orderBy('name')->pluck('name');

        $indexRoute = $actingRole === 'registrar'
            ? 'registrar.cor.archive.index'
            : ($actingRole === 'staff' ? 'staff.cor.archive.index' : 'cor.archive.index');
        $programRoute = $actingRole === 'registrar'
            ? 'registrar.cor.archive.program'
            : ($actingRole === 'staff' ? 'staff.cor.archive.program' : 'cor.archive.program');
        $yearRoute = $actingRole === 'registrar'
            ? 'registrar.cor.archive.year'
            : ($actingRole === 'staff' ? 'staff.cor.archive.year' : 'cor.archive.year');

        return view('dashboards.cor-archive-year', [
            'program' => $program,
            'yearLevel' => $yearLevel,
            'schoolYears' => $schoolYears,
            'selectedSchoolYear' => $selectedSchoolYear,
            'semesters' => $semesters,
            'breadcrumb' => [
                ['label' => 'COR Archive', 'url' => route($indexRoute)],
                ['label' => $program->program_name ?? 'Program', 'url' => route($programRoute, ['programId' => $programId])],
                ['label' => $yearLevel, 'url' => route($yearRoute, ['programId' => $programId, 'yearLevel' => $yearLevel, 'school_year' => $selectedSchoolYear])],
            ],
            'archive_index_route' => $indexRoute,
            'archive_program_route' => $programRoute,
            'archive_year_route' => $yearRoute,
            'archive_show_route' => $actingRole === 'registrar'
                ? 'registrar.cor.archive.show'
                : ($actingRole === 'staff' ? 'staff.cor.archive.show' : 'cor.archive.show'),
        ]);
    }

    /**
     * Archive for a scope: Program + Year Level + Semester.
     * Prebuilt: all blocks for this scope (from Block model). Shows deployed COR per block or "No COR deployed".
     */
    public function show(Request $request, $programId, string $yearLevel, string $semester, $deployedBlock = null): View
    {
        $programId = (int) $programId;
        $yearLevel = trim($yearLevel);
        $semester = trim($semester);

        $user = $request->user();
        $program = Program::find($programId);
        if (!$program) {
            abort(404);
        }
        if ($user->role === 'dean' && $user->department_id && (int) $program->department_id !== (int) $user->department_id) {
            abort(403, 'You cannot view this program archive.');
        }

        $schoolYears = SchoolYear::query()->orderByDesc('start_year')->pluck('label')->all();
        $selectedSchoolYear = $request->query('school_year', AcademicCalendarService::getSelectedSchoolYearLabel() ?? $schoolYears[0] ?? null);
        $selectedSchoolYear = $selectedSchoolYear ? trim($selectedSchoolYear) : null;

        // Prebuild: blocks for this program, year level, semester (and optional school year).
        // Use flexible matching for year_level and semester so "1st Year"/"First Year" and "First Semester"/"1st Semester" both match.
        $blocks = Block::query()
            ->where('program_id', $programId)
            ->where('is_active', true)
            ->where(function ($q) use ($yearLevel) {
                $q->where('year_level', $yearLevel)
                    ->orWhereRaw('LOWER(TRIM(year_level)) = ?', [strtolower(trim($yearLevel))]);
            })
            ->where(function ($q) use ($semester) {
                $q->where('semester', $semester)
                    ->orWhereRaw('LOWER(TRIM(semester)) = ?', [strtolower(trim($semester))]);
            })
            ->when($selectedSchoolYear !== null && $selectedSchoolYear !== '', function ($q) use ($selectedSchoolYear) {
                $q->where(function ($q2) use ($selectedSchoolYear) {
                    $q2->where('school_year_label', $selectedSchoolYear)
                        ->orWhereNull('school_year_label')
                        ->orWhere('school_year_label', '');
                });
            })
            ->orderBy('code')
            ->orderBy('name')
            ->get();

        // Include every block that has deployed COR for this program in the selected school year
        $blockIdsWithRecordsQuery = StudentCorRecord::query()
            ->where('program_id', $programId)
            ->whereNotNull('block_id');
        if ($selectedSchoolYear !== null && $selectedSchoolYear !== '') {
            $blockIdsWithRecordsQuery->where('school_year', $selectedSchoolYear);
        }
        $blockIdsWithRecords = $blockIdsWithRecordsQuery->distinct()->pluck('block_id');
        // When arriving from deploy redirect, always include the deployed block id so it appears even if inactive or not yet in DB query (path param or query param)
        $ensureBlockId = $deployedBlock ?? $request->query('deployed_block');
        if ($ensureBlockId !== null && $ensureBlockId !== '') {
            $blockIdsWithRecords = $blockIdsWithRecords->push((int) $ensureBlockId)->unique()->values();
        }
        // Include any block that has deployed COR for this program (no semester filter) so the block you deployed to always appears even if semester is stored differently (e.g. "1st Semester" vs "First Semester")
        if ($blockIdsWithRecords->isNotEmpty()) {
            $extraBlocks = Block::query()
                ->whereIn('id', $blockIdsWithRecords)
                ->where('program_id', $programId)
                ->where('is_active', true)
                ->orderBy('code')
                ->orderBy('name')
                ->get();
            $blocks = $blocks->merge($extraBlocks)->unique('id')->values()->sortBy('name')->values();
        }

        // Build records per block by block_id + program_id, scoped to selected school year.
        $recordsByBlock = collect();
        foreach ($blocks as $b) {
            $bid = (int) $b->id;
            $recQuery = StudentCorRecord::query()
                ->where('program_id', $programId)
                ->where('block_id', $bid)
                ->with(['subject', 'block'])
                ->orderBy('subject_id');
            if ($selectedSchoolYear !== null && $selectedSchoolYear !== '') {
                $recQuery->where('school_year', $selectedSchoolYear);
            }
            $recordsByBlock[$bid] = $recQuery->get();
        }

        // Sort blocks so those with deployed COR appear first (avoids "No COR deployed" appearing above the block that has COR when names duplicate)
        $blocks = $blocks->sortByDesc(fn ($b) => ($recordsByBlock[(int) $b->id] ?? collect())->count())->values();

        // Pass as array with integer keys so view lookup $recordsByBlock[$blockId] is reliable
        $recordsByBlockArray = [];
        foreach ($recordsByBlock as $k => $v) {
            $recordsByBlockArray[(int) $k] = $v;
        }

        // If we just redirected here after deploy, ensure the deployed block and its records are definitely included.
        // Prefer path segment (cannot be stripped by redirects) then query param then session flash. Use route() to read param in case of binding quirks.
        $deployedBlockFromRoute = $request->route('deployedBlock');
        $deployedBlockId = ($deployedBlockFromRoute !== null && $deployedBlockFromRoute !== '') ? $deployedBlockFromRoute : (($deployedBlock !== null && $deployedBlock !== '') ? $deployedBlock : ($request->query('deployed_block') ?? session('deployed_block_id')));
        $deployedBlockAdded = false;
        $recordsForDeployedCount = 0;
        $deployedBlockRecords = collect();
        if ($deployedBlockId !== null && $deployedBlockId !== '') {
            $deployedBlockId = (int) $deployedBlockId;
            $deployedBlock = Block::find($deployedBlockId);
            if ($deployedBlock && (int) $deployedBlock->program_id === $programId) {
                if (!$blocks->contains('id', $deployedBlockId)) {
                    $blocks = $blocks->push($deployedBlock)->values();
                    $deployedBlockAdded = true;
                }
                $recs = StudentCorRecord::query()
                    ->where('program_id', $programId)
                    ->where('block_id', $deployedBlockId)
                    ->with(['subject', 'block'])
                    ->orderBy('subject_id')
                    ->get();
                $deployedBlockRecords = $recs;
                $recordsByBlockArray[$deployedBlockId] = $recs;
                $recordsForDeployedCount = $recs->count();
                // Put the deployed block first so it's the first one shown on the page
                $blocks = $blocks->sortBy(fn ($b) => (int) $b->id === $deployedBlockId ? 0 : 1)->values();
            }
        }

        $actingRole = $user->effectiveRole();
        $indexRoute = $actingRole === 'registrar'
            ? 'registrar.cor.archive.index'
            : ($actingRole === 'staff' ? 'staff.cor.archive.index' : 'cor.archive.index');
        $programRoute = $actingRole === 'registrar'
            ? 'registrar.cor.archive.program'
            : ($actingRole === 'staff' ? 'staff.cor.archive.program' : 'cor.archive.program');
        $yearRoute = $actingRole === 'registrar'
            ? 'registrar.cor.archive.year'
            : ($actingRole === 'staff' ? 'staff.cor.archive.year' : 'cor.archive.year');
        $deleteRoute = $actingRole === 'registrar'
            ? 'registrar.cor.archive.delete-block'
            : ($actingRole === 'staff' ? 'staff.cor.archive.delete-block' : 'cor.archive.delete-block');

        return view('dashboards.cor-archive-show', [
            'program' => $program,
            'yearLevel' => $yearLevel,
            'semester' => $semester,
            'schoolYears' => $schoolYears,
            'selectedSchoolYear' => $selectedSchoolYear,
            'academic_year_level_id' => AcademicYearLevel::where('name', $yearLevel)->value('id'),
            'blocks' => $blocks,
            'recordsByBlock' => $recordsByBlockArray,
            'deployedBlockId' => (isset($deployedBlockId) && $deployedBlockId !== null && $deployedBlockId !== '' && (int) $deployedBlockId > 0) ? (int) $deployedBlockId : null,
            'deployedBlockRecords' => $deployedBlockRecords,
            'breadcrumb' => [
                ['label' => 'COR Archive', 'url' => route($indexRoute)],
                ['label' => $program->program_name ?? 'Program', 'url' => route($programRoute, ['programId' => $programId, 'school_year' => $selectedSchoolYear])],
                ['label' => $yearLevel, 'url' => route($yearRoute, ['programId' => $programId, 'yearLevel' => $yearLevel, 'school_year' => $selectedSchoolYear])],
                ['label' => $semester . ' — Blocks', 'url' => ''],
            ],
            'archive_delete_route' => $deleteRoute,
        ]);
    }

    /**
     * Delete deployed COR records for a specific block and scope.
     * This is a manual, irreversible operation. Registrar can delete any; Dean only for own department.
     */
    public function deleteBlockArchive(Request $request)
    {
        $user = $request->user();
        $actingRole = method_exists($user, 'effectiveRole') ? $user->effectiveRole() : ($user->role ?? null);
        if (!in_array($actingRole, ['dean', 'registrar'], true)) {
            abort(403, 'Only Dean or Registrar can delete COR archive records.');
        }

        $validated = $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'year_level' => ['required', 'string'],
            'semester' => ['required', 'string'],
            'block_id' => ['required', 'integer', 'exists:blocks,id'],
            'school_year' => ['nullable', 'string'],
        ]);

        $program = Program::find($validated['program_id']);
        if (! $program) {
            return back()->with('error', 'Program not found.');
        }

        if ($actingRole === 'dean' && $user->department_id && (int) $program->department_id !== (int) $user->department_id) {
            abort(403, 'You are not authorized to delete archive for this program.');
        }

        $blockId = (int) $validated['block_id'];
        $yearLevel = trim($validated['year_level']);
        $semester = trim($validated['semester']);
        $schoolYear = isset($validated['school_year']) && trim($validated['school_year']) !== '' ? trim($validated['school_year']) : null;

        $query = StudentCorRecord::query()
            ->where('program_id', $program->id)
            ->where('block_id', $blockId)
            ->where('year_level', $yearLevel)
            ->where('semester', $semester);

        if ($schoolYear !== null) {
            $query->where('school_year', $schoolYear);
        } else {
            $query->where(function ($q) {
                $q->whereNull('school_year')->orWhere('school_year', '');
            });
        }

        $count = $query->count();
        if ($count === 0) {
            return back()->with('error', 'No archive records found for that block and scope.');
        }

        // Perform delete
        $query->delete();

        return back()->with('success', 'Deleted ' . $count . ' archived COR record(s) for the selected block.');
    }

    /**
     * Example archive retrieval query: read-only records for a scope.
     * Used by students (own records), registrar (all), dean (department).
     */
    public static function archiveRetrievalQuery(int $programId, string $yearLevel, string $semester, string $schoolYear, ?int $blockId = null, ?int $studentId = null)
    {
        $query = StudentCorRecord::query()
            ->where('program_id', $programId)
            ->where('year_level', $yearLevel)
            ->where('semester', $semester)
            ->where('school_year', $schoolYear)
            ->with(['subject']);
        if ($blockId !== null) {
            $query->where('block_id', $blockId);
        }
        if ($studentId !== null) {
            $query->where('student_id', $studentId);
        }
        return $query->orderBy('subject_id')->get();
    }
}
