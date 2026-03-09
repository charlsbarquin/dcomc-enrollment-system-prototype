<?php

namespace App\Http\Controllers;

use App\Models\AcademicSemester;
use App\Models\AcademicYearLevel;
use App\Models\CorScope;
use App\Models\Fee;
use App\Models\Program;
use App\Models\SchoolYear;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CorScopeController extends Controller
{
    public function index(): View
    {
        $scopes = CorScope::query()
            ->with(['program', 'academicYearLevel', 'scopeSubjects', 'scopeFees'])
            ->orderBy('program_id')
            ->orderBy('academic_year_level_id')
            ->orderBy('semester')
            ->orderBy('school_year')
            ->get();

        return view('dashboards.cor-scopes-index', compact('scopes'));
    }

    public function create(Request $request): View
    {
        $programs = Program::orderBy('program_name')->get();
        $yearLevels = AcademicYearLevel::where('is_active', true)->orderBy('name')->get();
        $semesters = AcademicSemester::where('is_active', true)->orderBy('name')->pluck('name');
        $schoolYears = SchoolYear::query()->orderByDesc('start_year')->pluck('label');

        $programId = $request->filled('program_id') ? (int) $request->program_id : null;
        $yearLevelId = $request->filled('academic_year_level_id') ? (int) $request->academic_year_level_id : null;
        $subjects = collect();
        $fees = collect();
        if ($programId && $yearLevelId) {
            $subjects = Subject::query()
                ->forProgramAndYear($programId, $yearLevelId)
                ->where('is_active', true)
                ->orderBy('semester')
                ->orderBy('code')
                ->get();
            $fees = Fee::feesForScope($programId, $yearLevelId);
        }

        return view('dashboards.cor-scopes-form', [
            'scope' => null,
            'programs' => $programs,
            'yearLevels' => $yearLevels,
            'semesters' => $semesters,
            'schoolYears' => $schoolYears,
            'subjects' => $subjects,
            'fees' => $fees,
            'selectedSubjectIds' => $request->input('subject_ids', []),
            'selectedFeeIds' => $request->input('fee_ids', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'academic_year_level_id' => ['required', 'integer', 'exists:academic_year_levels,id'],
            'semester' => ['required', 'string', 'max:100'],
            'school_year' => ['required', 'string', 'max:100'],
            'major' => ['nullable', 'string', 'max:255'],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
            'fee_ids' => ['nullable', 'array'],
            'fee_ids.*' => ['integer', 'exists:fees,id'],
        ]);

        $programId = (int) $validated['program_id'];
        $yearLevelId = (int) $validated['academic_year_level_id'];
        $subjectIds = array_values(array_unique(array_filter($validated['subject_ids'] ?? [], 'is_numeric')));
        $feeIds = array_values(array_unique(array_filter($validated['fee_ids'] ?? [], 'is_numeric')));

        $err = $this->validateSubjectsAndFeesForScope($subjectIds, $feeIds, $programId, $yearLevelId);
        if ($err) {
            return back()->withErrors(['scope' => $err])->withInput();
        }

        $existing = CorScope::findForScope(
            $programId,
            $yearLevelId,
            $validated['semester'],
            $validated['school_year'],
            $validated['major'] ?? null
        );
        if ($existing) {
            return back()->withErrors(['scope' => 'A COR Scope already exists for this Program, Year Level, Semester, and School Year.'])->withInput();
        }

        $scope = CorScope::create([
            'program_id' => $programId,
            'academic_year_level_id' => $yearLevelId,
            'semester' => trim($validated['semester']),
            'school_year' => trim($validated['school_year']),
            'major' => $request->filled('major') ? trim($validated['major']) : null,
            'created_by' => auth()->id(),
        ]);

        foreach ($subjectIds as $sid) {
            $scope->scopeSubjects()->firstOrCreate(['subject_id' => $sid]);
        }
        foreach ($feeIds as $fid) {
            $scope->scopeFees()->firstOrCreate(['fee_id' => $fid]);
        }

        return redirect()->route(request()->routeIs('admin.*') ? 'admin.settings.cor-scopes.index' : 'registrar.cor-scopes.index')->with('success', 'COR Scope created. Schedules for this configuration will auto-load these subjects and fees.');
    }

    public function edit(int $id): View
    {
        $scope = CorScope::with(['program', 'academicYearLevel', 'scopeSubjects', 'scopeFees'])->findOrFail($id);
        $programs = Program::orderBy('program_name')->get();
        $yearLevels = AcademicYearLevel::where('is_active', true)->orderBy('name')->get();
        $semesters = AcademicSemester::where('is_active', true)->orderBy('name')->pluck('name');
        $schoolYears = SchoolYear::query()->orderByDesc('start_year')->pluck('label');

        $subjects = Subject::query()
            ->forProgramAndYear($scope->program_id, $scope->academic_year_level_id)
            ->where('is_active', true)
            ->orderBy('semester')
            ->orderBy('code')
            ->get();
        $fees = Fee::feesForScope($scope->program_id, $scope->academic_year_level_id);

        $selectedSubjectIds = $scope->getDefaultSubjectIds();
        $selectedFeeIds = $scope->scopeFees()->pluck('fee_id')->all();

        return view('dashboards.cor-scopes-form', [
            'scope' => $scope,
            'programs' => $programs,
            'yearLevels' => $yearLevels,
            'semesters' => $semesters,
            'schoolYears' => $schoolYears,
            'subjects' => $subjects,
            'fees' => $fees,
            'selectedSubjectIds' => $selectedSubjectIds,
            'selectedFeeIds' => $selectedFeeIds,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $scope = CorScope::findOrFail($id);

        $validated = $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'academic_year_level_id' => ['required', 'integer', 'exists:academic_year_levels,id'],
            'semester' => ['required', 'string', 'max:100'],
            'school_year' => ['required', 'string', 'max:100'],
            'major' => ['nullable', 'string', 'max:255'],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
            'fee_ids' => ['nullable', 'array'],
            'fee_ids.*' => ['integer', 'exists:fees,id'],
        ]);

        $programId = (int) $validated['program_id'];
        $yearLevelId = (int) $validated['academic_year_level_id'];
        $subjectIds = array_values(array_unique(array_filter($validated['subject_ids'] ?? [], 'is_numeric')));
        $feeIds = array_values(array_unique(array_filter($validated['fee_ids'] ?? [], 'is_numeric')));

        $err = $this->validateSubjectsAndFeesForScope($subjectIds, $feeIds, $programId, $yearLevelId);
        if ($err) {
            return back()->withErrors(['scope' => $err])->withInput();
        }

        $scope->update([
            'program_id' => $programId,
            'academic_year_level_id' => $yearLevelId,
            'semester' => trim($validated['semester']),
            'school_year' => trim($validated['school_year']),
            'major' => $request->filled('major') ? trim($validated['major']) : null,
        ]);

        $scope->scopeSubjects()->whereNotIn('subject_id', $subjectIds)->delete();
        foreach ($subjectIds as $sid) {
            $scope->scopeSubjects()->firstOrCreate(['subject_id' => $sid]);
        }

        $scope->scopeFees()->whereNotIn('fee_id', $feeIds)->delete();
        foreach ($feeIds as $fid) {
            $scope->scopeFees()->firstOrCreate(['fee_id' => $fid]);
        }

        return redirect()->route(request()->routeIs('admin.*') ? 'admin.settings.cor-scopes.index' : 'registrar.cor-scopes.index')->with('success', 'COR Scope updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $scope = CorScope::findOrFail($id);
        $scope->delete();
        return redirect()->route(request()->routeIs('admin.*') ? 'admin.settings.cor-scopes.index' : 'registrar.cor-scopes.index')->with('success', 'COR Scope removed.');
    }

    /**
     * Validate that all subject and fee IDs belong to the given program and year level.
     */
    private function validateSubjectsAndFeesForScope(array $subjectIds, array $feeIds, int $programId, int $yearLevelId): ?string
    {
        if (! empty($subjectIds)) {
            $validSubjectIds = Subject::query()
                ->forProgramAndYear($programId, $yearLevelId)
                ->whereIn('id', $subjectIds)
                ->pluck('id')
                ->all();
            if (count($subjectIds) !== count($validSubjectIds)) {
                return 'One or more subjects do not belong to this program and year level.';
            }
        }
        if (! empty($feeIds)) {
            $validFeeIds = Fee::query()
                ->forProgramAndYear($programId, $yearLevelId)
                ->whereIn('id', $feeIds)
                ->pluck('id')
                ->all();
            if (count($feeIds) !== count($validFeeIds)) {
                return 'One or more fees do not belong to this program and year level.';
            }
        }
        return null;
    }
}
