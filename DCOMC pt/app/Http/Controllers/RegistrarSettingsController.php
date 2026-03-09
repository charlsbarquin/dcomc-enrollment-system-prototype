<?php

namespace App\Http\Controllers;

use App\Models\AcademicSemester;
use App\Models\AcademicYearLevel;
use App\Models\FeeCategory;
use App\Models\Program;
use App\Models\SchoolYear;
use App\Models\AcademicCalendarSetting;
use App\Models\RawSubject;
use App\Services\AcademicCalendarService;
use App\Models\Subject;
use App\Models\Fee;
use App\Models\Block;
use App\Models\CorScope;
use App\Models\ScopeScheduleSlot;
use App\Models\Room;
use App\Models\User;
use App\Services\BlockAssignmentService;
use App\Services\SchedulingScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegistrarSettingsController extends Controller
{
    public function schoolYears()
    {
        $startYear = now()->month >= 6 ? now()->year : now()->year - 1;
        $endYear = $startYear + 1;
        $label = $startYear . '-' . $endYear;

        SchoolYear::firstOrCreate(
            ['label' => $label],
            [
                'start_year' => $startYear,
                'end_year' => $endYear,
            ]
        );

        $schoolYears = SchoolYear::orderByDesc('start_year')->get();
        $calendar = AcademicCalendarSetting::with('activeSchoolYear')->first();
        $monthNames = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];
        $currentMonth = (int) now()->month;
        $promptOpenEnrollment = $calendar && $currentMonth > (int) ($calendar->second_semester_end_month ?? 5);

        return view('dashboards.settings-school-years', compact('schoolYears', 'calendar', 'monthNames', 'promptOpenEnrollment'));
    }

    public function storeAcademicCalendar(Request $request)
    {
        $validated = $request->validate([
            'active_school_year_id' => ['nullable', 'integer', 'exists:school_years,id'],
            'first_semester_start_month' => ['required', 'integer', 'min:1', 'max:12'],
            'first_semester_end_month' => ['required', 'integer', 'min:1', 'max:12'],
            'second_semester_start_month' => ['required', 'integer', 'min:1', 'max:12'],
            'second_semester_end_month' => ['required', 'integer', 'min:1', 'max:12'],
            'midyear_start_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'midyear_end_month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $calendar = AcademicCalendarSetting::first();
        if (!$calendar) {
            $calendar = new AcademicCalendarSetting();
            $calendar->active_school_year_id = null;
            $calendar->first_semester_start_month = 8;
            $calendar->first_semester_end_month = 12;
            $calendar->second_semester_start_month = 1;
            $calendar->second_semester_end_month = 5;
        }

        $calendar->first_semester_start_month = (int) $validated['first_semester_start_month'];
        $calendar->first_semester_end_month = (int) $validated['first_semester_end_month'];
        $calendar->second_semester_start_month = (int) $validated['second_semester_start_month'];
        $calendar->second_semester_end_month = (int) $validated['second_semester_end_month'];
        $calendar->midyear_start_month = !empty($validated['midyear_start_month']) ? (int) $validated['midyear_start_month'] : null;
        $calendar->midyear_end_month = !empty($validated['midyear_end_month']) ? (int) $validated['midyear_end_month'] : null;
        $calendar->save();

        $syId = !empty($validated['active_school_year_id']) ? (int) $validated['active_school_year_id'] : null;
        $resetCount = AcademicCalendarService::setActiveSchoolYear($syId);

        $msg = 'Academic calendar settings saved.';
        if ($resetCount > 0) {
            $msg .= ' All students have been set to Not Enrolled for the new term. They must re-enroll to regain Enrolled status.';
        }
        return back()->with('success', $msg);
    }

    public function generateSchoolYear(Request $request)
    {
        $request->validate([
            'start_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'end_year' => ['required', 'integer', 'min:2000', 'max:2101'],
        ]);

        if (((int) $request->end_year - (int) $request->start_year) !== 1) {
            return back()->withErrors(['start_year' => 'School year must span exactly one year (e.g., 2025-2026).']);
        }

        $label = $request->start_year . '-' . $request->end_year;

        SchoolYear::firstOrCreate(
            ['label' => $label],
            [
                'start_year' => $request->start_year,
                'end_year' => $request->end_year,
            ]
        );

        return back()->with('success', 'School year added.');
    }

    public function clearSchoolYears()
    {
        SchoolYear::query()->delete();

        return back()->with('success', 'All school years have been cleared.');
    }

    public function semesters()
    {
        $semesters = AcademicSemester::orderBy('name')->get();

        return view('dashboards.settings-semesters', compact('semesters'));
    }

    public function storeSemester(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100', 'in:'.implode(',', AcademicSemester::CANONICAL), 'unique:academic_semesters,name'],
        ]);

        AcademicSemester::create([
            'name' => trim($request->name),
            'is_active' => true,
        ]);

        return back()->with('success', 'Semester added.');
    }

    public function toggleSemester($id)
    {
        $semester = AcademicSemester::findOrFail($id);
        $semester->update([
            'is_active' => ! $semester->is_active,
        ]);

        return back()->with('success', 'Semester visibility updated.');
    }

    public function yearLevels()
    {
        $yearLevels = AcademicYearLevel::orderBy('name')->get();

        return view('dashboards.settings-year-levels', compact('yearLevels'));
    }

    public function storeYearLevel(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100', 'in:'.implode(',', AcademicYearLevel::CANONICAL), 'unique:academic_year_levels,name'],
        ]);

        AcademicYearLevel::create([
            'name' => trim($request->name),
            'is_active' => true,
        ]);

        return back()->with('success', 'Year level added.');
    }

    public function toggleYearLevel($id)
    {
        $yearLevel = AcademicYearLevel::findOrFail($id);
        $yearLevel->update([
            'is_active' => ! $yearLevel->is_active,
        ]);

        return back()->with('success', 'Year level visibility updated.');
    }

    public function subjects(Request $request)
    {
        $subjectMode = $request->query('mode', 'arrange');
        if ($subjectMode === 'raw') {
            $rawSubjects = RawSubject::orderBy('code')->get();
            return view('dashboards.settings-subjects', [
                'subjectMode' => 'raw',
                'rawSubjects' => $rawSubjects,
                'breadcrumb' => [['label' => 'Subject Settings', 'url' => route($this->settingsRoutePrefix() . '.subjects')]],
            ]);
        }

        $program = $request->query('program');
        $year = $request->query('year');
        $semester = $request->query('semester');
        $yearLevels = AcademicYearLevel::where('is_active', true)->orderBy('name')->pluck('name')->all();
        $semesters = AcademicSemester::where('is_active', true)->orderBy('name')->pluck('name')->all();
        $programs = config('fee_programs.programs', []);
        $displayLabels = config('fee_programs.display_labels', []);

        if ($program !== null && $program !== '' && $year !== null && $year !== '' && $semester !== null && $semester !== '') {
            return $this->subjectsTable($program, $year, $semester, $yearLevels, $semesters, $programs, $displayLabels);
        }

        if ($program !== null && $program !== '' && $year !== null && $year !== '') {
            return $this->subjectsSemesterFolders($program, $year, $semesters, $displayLabels);
        }

        if ($program !== null && $program !== '') {
            return $this->subjectsYearFolders($program, $yearLevels, $displayLabels);
        }

        return $this->subjectsProgramFolders($programs, $displayLabels);
    }

    private function subjectsProgramFolders(array $programs, array $displayLabels = []): \Illuminate\Contracts\View\View
    {
        return view('dashboards.settings-subjects', [
            'subjectMode' => 'arrange',
            'viewMode' => 'programs',
            'programs' => $programs,
            'displayLabels' => $displayLabels,
            'breadcrumb' => [['label' => 'Subjects', 'url' => route($this->settingsRoutePrefix() . '.subjects')]],
        ]);
    }

    private function subjectsYearFolders(string $program, array $yearLevels, array $displayLabels = []): \Illuminate\Contracts\View\View
    {
        $subjectsUrl = route($this->settingsRoutePrefix() . '.subjects');
        $programLabel = $displayLabels[$program] ?? $program;
        return view('dashboards.settings-subjects', [
            'subjectMode' => 'arrange',
            'viewMode' => 'years',
            'program' => $program,
            'programLabel' => $programLabel,
            'yearLevels' => $yearLevels,
            'displayLabels' => $displayLabels,
            'breadcrumb' => [
                ['label' => 'Subjects', 'url' => $subjectsUrl],
                ['label' => $programLabel, 'url' => $subjectsUrl . '?program=' . urlencode($program)],
            ],
        ]);
    }

    private function subjectsSemesterFolders(string $program, string $year, array $semesters, array $displayLabels = []): \Illuminate\Contracts\View\View
    {
        $subjectsUrl = route($this->settingsRoutePrefix() . '.subjects');
        $programLabel = $displayLabels[$program] ?? $program;
        return view('dashboards.settings-subjects', [
            'subjectMode' => 'arrange',
            'viewMode' => 'semesters',
            'program' => $program,
            'programLabel' => $programLabel,
            'year' => $year,
            'semesters' => $semesters,
            'displayLabels' => $displayLabels,
            'breadcrumb' => [
                ['label' => 'Subjects', 'url' => $subjectsUrl],
                ['label' => $programLabel, 'url' => $subjectsUrl . '?program=' . urlencode($program)],
                ['label' => $year, 'url' => $subjectsUrl . '?program=' . urlencode($program) . '&year=' . urlencode($year)],
            ],
        ]);
    }

    private function subjectsTable(string $program, string $year, string $semester, array $yearLevels, array $semesters, array $programs, array $displayLabels = []): \Illuminate\Contracts\View\View
    {
        $programModel = Program::where('program_name', $program)->first();
        $yearLevelModel = AcademicYearLevel::where('name', $year)->first();
        $subjects = collect();
        if ($programModel && $yearLevelModel) {
            $subjects = Subject::query()
                ->forProgramAndYear($programModel->id, $yearLevelModel->id)
                ->where('semester', $semester)
                ->orderBy('code')
                ->get();
        }

        $subjectsUrl = route($this->settingsRoutePrefix() . '.subjects');
        $programLabel = $displayLabels[$program] ?? $program;
        $rawSubjects = RawSubject::where('is_active', true)->orderBy('code')->get();
        return view('dashboards.settings-subjects', [
            'subjectMode' => 'arrange',
            'viewMode' => 'table',
            'program' => $program,
            'rawSubjects' => $rawSubjects,
            'programLabel' => $programLabel,
            'year' => $year,
            'semester' => $semester,
            'yearLevels' => $yearLevels,
            'semesters' => $semesters,
            'subjects' => $subjects,
            'programs' => $programs,
            'displayLabels' => $displayLabels,
            'breadcrumb' => [
                ['label' => 'Subjects', 'url' => $subjectsUrl],
                ['label' => $programLabel, 'url' => $subjectsUrl . '?program=' . urlencode($program)],
                ['label' => $year, 'url' => $subjectsUrl . '?program=' . urlencode($program) . '&year=' . urlencode($year)],
                ['label' => $semester, 'url' => $subjectsUrl . '?program=' . urlencode($program) . '&year=' . urlencode($year) . '&semester=' . urlencode($semester)],
            ],
        ]);
    }

    public function storeSubject(Request $request)
    {
        $request->validate([
            'raw_subject_id' => ['nullable', 'exists:raw_subjects,id'],
            'code' => ['required_without:raw_subject_id', 'nullable', 'string', 'max:50'],
            'title' => ['required_without:raw_subject_id', 'nullable', 'string', 'max:255'],
            'units' => ['required_without:raw_subject_id', 'nullable', 'numeric', 'min:0'],
            'prerequisites' => ['nullable', 'string', 'max:500'],
            'program' => ['required', 'string', 'max:255'],
            'major' => ['nullable', 'string', 'max:255'],
            'year_level' => ['required', 'string', 'max:100'],
            'semester' => ['required', 'string', 'max:100'],
        ]);

        $programModel = Program::where('program_name', trim($request->program))->firstOrFail();
        $yearLevelModel = AcademicYearLevel::where('name', trim($request->year_level))->firstOrFail();

        if ($request->filled('raw_subject_id')) {
            $raw = RawSubject::findOrFail($request->raw_subject_id);
            $code = $raw->code;
            $title = $raw->title;
            $units = (float) $raw->units;
            $prerequisites = $raw->prerequisites;
            $rawSubjectId = $raw->id;
        } else {
            $code = trim($request->code);
            $title = trim($request->title);
            $units = (float) $request->units;
            $prerequisites = $request->filled('prerequisites') ? trim($request->prerequisites) : null;
            $rawSubjectId = RawSubject::where('code', $code)->value('id');
        }

        Subject::create([
            'raw_subject_id' => $rawSubjectId,
            'code' => $code,
            'title' => $title,
            'units' => $units,
            'prerequisites' => $prerequisites,
            'program_id' => $programModel->id,
            'academic_year_level_id' => $yearLevelModel->id,
            'major' => $request->major ? trim($request->major) : null,
            'semester' => trim($request->semester),
            'is_active' => true,
        ]);

        $url = route($this->settingsRoutePrefix() . '.subjects');
        if ($request->filled('program') && $request->filled('year_level') && $request->filled('semester')) {
            $url .= '?program=' . urlencode($request->program) . '&year=' . urlencode($request->year_level) . '&semester=' . urlencode($request->semester);
        } elseif ($request->filled('program') && $request->filled('year_level')) {
            $url .= '?program=' . urlencode($request->program) . '&year=' . urlencode($request->year_level);
        }
        return redirect($url)->with('success', 'Subject added.');
    }

    /**
     * Push all subjects from this Subject Settings scope (program, year, semester) to the Dean Schedule by Program.
     * Creates or updates the COR scope for the same path and syncs subject IDs so the Dean's schedule form shows them.
     */
    public function pushSubjectsToDeanSchedule(Request $request)
    {
        $request->validate([
            'program' => ['required', 'string', 'max:255'],
            'year' => ['required', 'string', 'max:100'],
            'semester' => ['required', 'string', 'max:100'],
            'school_year' => ['nullable', 'string', 'max:100'],
        ]);

        $programModel = Program::where('program_name', trim($request->program))->first();
        $yearLevelModel = AcademicYearLevel::where('name', trim($request->year))->first();
        if (! $programModel || ! $yearLevelModel) {
            return back()->with('error', 'Invalid program or year level.');
        }

        $subjectIds = Subject::query()
            ->forProgramAndYear($programModel->id, $yearLevelModel->id)
            ->where('semester', trim($request->semester))
            ->where('is_active', true)
            ->orderBy('code')
            ->pluck('id')
            ->all();

        $schoolYear = $request->filled('school_year') && trim($request->school_year) !== ''
            ? trim($request->school_year)
            : (SchoolYear::query()->orderByDesc('start_year')->value('label'));

        if (! $schoolYear) {
            return back()->with('error', 'No school year defined. Add a school year in settings first.');
        }

        $corScope = CorScope::findForScope(
            $programModel->id,
            $yearLevelModel->id,
            trim($request->semester),
            $schoolYear,
            null
        );

        if (! $corScope) {
            $corScope = CorScope::create([
                'program_id' => $programModel->id,
                'academic_year_level_id' => $yearLevelModel->id,
                'semester' => trim($request->semester),
                'school_year' => $schoolYear,
                'major' => null,
                'created_by' => auth()->id(),
            ]);
        }

        $corScope->subjects()->sync($subjectIds);

        // Create ScopeScheduleSlot for each subject that does not have one, so Registrar Program Schedule and Dean Schedule by Program show the same list.
        foreach ($subjectIds as $subjectId) {
            $exists = ScopeScheduleSlot::query()
                ->where('program_id', $programModel->id)
                ->where('academic_year_level_id', $yearLevelModel->id)
                ->where('semester', trim($request->semester))
                ->where('subject_id', $subjectId)
                ->where(function ($q) use ($schoolYear) {
                    $q->where('school_year', $schoolYear)->orWhereNull('school_year');
                })
                ->exists();
            if (! $exists) {
                ScopeScheduleSlot::create([
                    'program_id' => $programModel->id,
                    'academic_year_level_id' => $yearLevelModel->id,
                    'semester' => trim($request->semester),
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

        $redirectUrl = route($this->settingsRoutePrefix() . '.subjects') . '?program=' . urlencode($request->program) . '&year=' . urlencode($request->year) . '&semester=' . urlencode($request->semester);
        return redirect($redirectUrl)->with('success', 'Pushed ' . count($subjectIds) . ' subject(s) to Dean Schedule by Program for ' . $programModel->program_name . ' — ' . $request->year . ' — ' . $request->semester . ' (school year ' . $schoolYear . '). They appear in Program Schedule and the Dean can set day, time, room, and professor.');
    }

    public function toggleSubject($id)
    {
        $subject = Subject::findOrFail($id);
        $subject->update([
            'is_active' => ! $subject->is_active,
        ]);

        return back()->with('success', 'Subject visibility updated.');
    }

    public function storeRawSubject(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:raw_subjects,code'],
            'title' => ['required', 'string', 'max:255'],
            'units' => ['required', 'numeric', 'min:0'],
            'prerequisites' => ['nullable', 'string', 'max:500'],
        ]);

        RawSubject::create([
            'code' => trim($request->code),
            'title' => trim($request->title),
            'units' => (float) $request->units,
            'prerequisites' => $request->filled('prerequisites') ? trim($request->prerequisites) : null,
            'is_active' => true,
        ]);

        return redirect()->route($this->settingsRoutePrefix() . '.subjects', ['mode' => 'raw'])->with('success', 'Raw subject added.');
    }

    public function toggleRawSubject($id)
    {
        $raw = RawSubject::findOrFail($id);
        $raw->update([
            'is_active' => ! $raw->is_active,
        ]);

        return redirect()->route($this->settingsRoutePrefix() . '.subjects', ['mode' => 'raw'])->with('success', 'Raw subject visibility updated.');
    }

    public function fees(Request $request)
    {
        $feesRouteName = $this->feesRouteName();

        // Registrar or Unifast: raw fees tab (all fee records in one list)
        if (in_array($feesRouteName, ['registrar.settings.fees', 'unifast.settings.fees', 'admin.settings.fees'], true) && $request->query('mode') === 'raw') {
            $rawFeesQuery = Fee::with('feeCategory')
                ->orderBy('fee_category_id')
                ->orderBy('program')
                ->orderBy('year_level');
            $rawFees = $feesRouteName === 'admin.settings.fees'
                ? $rawFeesQuery->paginate(20)->withQueryString()
                : $rawFeesQuery->get();
            $feeCategories = FeeCategory::orderBy('sort_order')->orderBy('name')->get();
            $programs = config('fee_programs.programs', []);
            $displayLabels = config('fee_programs.display_labels', []);
            $yearLevels = AcademicYearLevel::where('is_active', true)->orderBy('name')->pluck('name')->all();
            return view('dashboards.settings-fees', [
                'feeMode' => 'raw',
                'rawFees' => $rawFees,
                'feeCategories' => $feeCategories,
                'rawFeePrograms' => $programs,
                'rawFeeDisplayLabels' => $displayLabels,
                'rawFeeYearLevels' => $yearLevels,
                'feesRouteName' => $feesRouteName,
                'breadcrumb' => [['label' => 'Fees', 'url' => route($feesRouteName)]],
            ]);
        }

        $program = $request->query('program');
        $year = $request->query('year');
        $yearLevels = AcademicYearLevel::where('is_active', true)->orderBy('name')->pluck('name')->all();
        $programs = config('fee_programs.programs', []);
        $displayLabels = config('fee_programs.display_labels', []);

        if ($program !== null && $program !== '' && $year !== null && $year !== '') {
            return $this->feesTable($program, $year, $yearLevels, $displayLabels, $feesRouteName);
        }

        if ($program !== null && $program !== '') {
            return $this->feesYearFolders($program, $yearLevels, $displayLabels, $feesRouteName);
        }

        return $this->feesProgramFolders($programs, $displayLabels, $feesRouteName);
    }

    private function feesRouteName(): string
    {
        $name = request()->route()?->getName() ?? '';
        if (str_starts_with($name, 'unifast.')) {
            return 'unifast.settings.fees';
        }
        if (str_starts_with($name, 'staff.')) {
            return 'staff.settings.fees';
        }
        if (str_starts_with($name, 'admin.')) {
            return 'admin.settings.fees';
        }
        return 'registrar.settings.fees';
    }

    private function settingsRoutePrefix(): string
    {
        $name = request()->route()?->getName() ?? '';
        return str_starts_with($name, 'admin.') ? 'admin.settings' : 'registrar.settings';
    }

    private function feesProgramFolders(array $programs, array $displayLabels = [], string $feesRouteName = 'registrar.settings.fees'): \Illuminate\Contracts\View\View
    {
        return view('dashboards.settings-fees', [
            'feeMode' => 'arrange',
            'viewMode' => 'programs',
            'programs' => $programs,
            'displayLabels' => $displayLabels,
            'feesRouteName' => $feesRouteName,
            'breadcrumb' => [['label' => 'Fees', 'url' => route($feesRouteName)]],
        ]);
    }

    private function feesYearFolders(string $program, array $yearLevels, array $displayLabels = [], string $feesRouteName = 'registrar.settings.fees'): \Illuminate\Contracts\View\View
    {
        $feesUrl = route($feesRouteName);
        $programLabel = $displayLabels[$program] ?? $program;
        return view('dashboards.settings-fees', [
            'feeMode' => 'arrange',
            'viewMode' => 'years',
            'program' => $program,
            'programLabel' => $programLabel,
            'yearLevels' => $yearLevels,
            'displayLabels' => $displayLabels,
            'feesRouteName' => $feesRouteName,
            'breadcrumb' => [
                ['label' => 'Fees', 'url' => $feesUrl],
                ['label' => $programLabel, 'url' => $feesUrl . '?program=' . urlencode($program)],
            ],
        ]);
    }

    private function feesTable(string $program, string $year, array $yearLevels, array $displayLabels = [], string $feesRouteName = 'registrar.settings.fees'): \Illuminate\Contracts\View\View
    {
        $feeCategories = FeeCategory::orderBy('sort_order')->orderBy('name')->get();
        $programModel = Program::where('program_name', $program)->first();
        $yearLevelModel = AcademicYearLevel::where('name', $year)->first();
        $isUnifastTable = $feesRouteName === 'unifast.settings.fees';

        if ($isUnifastTable && $programModel && $yearLevelModel) {
            // Unifast: only fees in this scope, ordered by sort_order; categories not in scope can be added
            $feesInScope = Fee::feesForScope($programModel->id, $yearLevelModel->id);
            $rows = [];
            foreach ($feesInScope as $fee) {
                $cat = $fee->feeCategory;
                $rows[] = [
                    'category' => $cat,
                    'fee' => $fee,
                    'amount' => (float) $fee->amount,
                    'fee_id' => $fee->id,
                ];
            }
            $categoryIdsInScope = $feesInScope->pluck('fee_category_id')->all();
            $availableCategoriesForAdd = $feeCategories->filter(fn ($c) => ! in_array($c->id, $categoryIdsInScope, true))->values()->all();
            $rawFeesForDropdown = Fee::with('feeCategory')->orderBy('fee_category_id')->orderBy('program')->orderBy('year_level')->get();
        } else {
            // Registrar/Staff: all categories, with or without fee
            $resolvedFees = ($programModel && $yearLevelModel)
                ? Fee::feesForScope($programModel->id, $yearLevelModel->id)
                : Fee::resolvedFeesFor($program, $year, null);
            $feeByCategory = $resolvedFees->keyBy(fn ($f) => $f->feeCategory?->name ?? $f->name);
            $rows = [];
            foreach ($feeCategories as $cat) {
                $fee = $feeByCategory->get($cat->name);
                $rows[] = [
                    'category' => $cat,
                    'fee' => $fee,
                    'amount' => $fee ? (float) $fee->amount : 0,
                    'fee_id' => $fee?->id,
                ];
            }
            $availableCategoriesForAdd = [];
            $rawFeesForDropdown = $feesRouteName === 'registrar.settings.fees'
                ? Fee::with('feeCategory')->orderBy('fee_category_id')->orderBy('program')->orderBy('year_level')->get()
                : collect();
        }

        $total = collect($rows)->sum('amount');
        $feesUrl = route($feesRouteName);
        $programLabel = $displayLabels[$program] ?? $program;
        return view('dashboards.settings-fees', [
            'feeMode' => 'arrange',
            'viewMode' => 'table',
            'program' => $program,
            'programLabel' => $programLabel,
            'year' => $year,
            'yearLevels' => $yearLevels,
            'feeCategories' => $feeCategories,
            'rows' => $rows,
            'total' => $total,
            'displayLabels' => $displayLabels,
            'feesRouteName' => $feesRouteName,
            'isUnifastFeesTable' => $isUnifastTable,
            'availableCategoriesForAdd' => $availableCategoriesForAdd,
            'rawFeesForDropdown' => $rawFeesForDropdown ?? collect(),
            'breadcrumb' => [
                ['label' => 'Fees', 'url' => $feesUrl],
                ['label' => $programLabel, 'url' => $feesUrl . '?program=' . urlencode($program)],
                ['label' => $year, 'url' => $feesUrl . '?program=' . urlencode($program) . '&year=' . urlencode($year)],
            ],
        ]);
    }

    public function updateFeeTable(Request $request)
    {
        $request->validate([
            'program' => ['required', 'string', 'max:255'],
            'year_level' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::in(AcademicYearLevel::CANONICAL)],
            'fees' => ['required', 'array'],
            'fees.*.fee_category_id' => ['required', 'exists:fee_categories,id'],
            'fees.*.amount' => ['required', 'numeric', 'min:0'],
        ]);

        $program = trim($request->program);
        $yearLevel = $request->year_level;
        $programModel = Program::where('program_name', $program)->firstOrFail();
        $yearLevelModel = AcademicYearLevel::where('name', $yearLevel)->firstOrFail();

        $isUnifast = str_starts_with(request()->route()?->getName() ?? '', 'unifast.');
        foreach ($request->fees as $index => $row) {
            $cat = FeeCategory::find($row['fee_category_id']);
            if (! $cat) {
                continue;
            }
            $fee = Fee::updateOrCreate(
                [
                    'fee_category_id' => $cat->id,
                    'program_id' => $programModel->id,
                    'academic_year_level_id' => $yearLevelModel->id,
                ],
                [
                    'name' => $cat->name,
                    'category' => $cat->name,
                    'program' => $program,
                    'year_level' => $yearLevel,
                    'amount' => (float) $row['amount'],
                    'is_active' => true,
                ]
            );
            if ($isUnifast) {
                $fee->update(['sort_order' => $index]);
            }
        }

        $feesRouteName = $this->feesRouteName();
        $feesUrl = route($feesRouteName) . '?program=' . urlencode($program) . '&year=' . urlencode($yearLevel);
        return redirect($feesUrl)->with('success', 'Fees updated for ' . $program . ' / ' . $yearLevel . '.');
    }

    public function storeFee(Request $request)
    {
        $request->validate([
            'fee_category_id' => ['required', 'exists:fee_categories,id'],
            'year_level' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::in(AcademicYearLevel::CANONICAL)],
            'program' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $category = FeeCategory::findOrFail($request->fee_category_id);
        $program = $request->filled('program') ? trim($request->program) : null;
        $programModel = $program ? Program::where('program_name', $program)->first() : null;
        $yearLevelModel = AcademicYearLevel::where('name', $request->year_level)->first();
        $programId = $programModel?->id;
        $academicYearLevelId = $yearLevelModel?->id;

        $isUnifast = str_starts_with(request()->route()?->getName() ?? '', 'unifast.');
        $sortOrder = null;
        if ($isUnifast && $programId !== null && $academicYearLevelId !== null) {
            $maxOrder = Fee::query()
                ->forProgramAndYear($programId, $academicYearLevelId)
                ->max('sort_order');
            $sortOrder = $maxOrder === null ? 0 : (int) $maxOrder + 1;
        }

        Fee::updateOrCreate(
            [
                'fee_category_id' => $category->id,
                'program_id' => $programId,
                'academic_year_level_id' => $academicYearLevelId,
            ],
            [
                'name' => $category->name,
                'category' => $category->name,
                'program' => $program,
                'year_level' => $request->year_level,
                'amount' => $request->amount,
                'is_active' => true,
                'sort_order' => $sortOrder,
            ]
        );

        $scope = $program ? $program . ' / ' . $request->year_level : $request->year_level;
        return back()->with('success', 'Fee saved for ' . $scope . '.');
    }

    public function destroyFee($id)
    {
        $fee = Fee::findOrFail($id);
        $fee->delete();
        return back()->with('success', 'Fee removed.');
    }

    /** Registrar: add a new fee from Raw fees page (category from dropdown or new name, program, year level, amount). */
    public function storeRawFee(Request $request)
    {
        $request->validate([
            'fee_category_id' => ['nullable', 'exists:fee_categories,id'],
            'category_name' => ['nullable', 'string', 'max:255'],
            'program' => ['nullable', 'string', 'max:255'],
            'year_level' => ['nullable', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);
        if (! $request->filled('fee_category_id') && ! $request->filled('category_name')) {
            return back()->withErrors(['fee_category_id' => 'Select a category or type a new category name.'])->withInput();
        }

        $categoryName = null;
        if ($request->filled('fee_category_id')) {
            $category = FeeCategory::findOrFail($request->fee_category_id);
            $categoryName = $category->name;
        } else {
            $categoryName = trim($request->category_name);
            if ($categoryName === '') {
                return back()->withErrors(['category_name' => 'Category name is required when not selecting from list.'])->withInput();
            }
            $category = FeeCategory::firstOrCreate(
                ['name' => $categoryName],
                ['sort_order' => FeeCategory::max('sort_order') + 1]
            );
        }

        $program = $request->filled('program') ? trim($request->program) : null;
        $yearLevel = $request->filled('year_level') ? trim($request->year_level) : null;
        if ($yearLevel === '') {
            $yearLevel = null;
        }
        $programModel = $program ? Program::where('program_name', $program)->first() : null;
        $yearLevelModel = $yearLevel ? AcademicYearLevel::where('name', $yearLevel)->first() : null;
        $programId = $programModel?->id;
        $academicYearLevelId = $yearLevelModel?->id;

        Fee::updateOrCreate(
            [
                'fee_category_id' => $category->id,
                'program_id' => $programId,
                'academic_year_level_id' => $academicYearLevelId,
            ],
            [
                'name' => $categoryName,
                'category' => $categoryName,
                'program' => $program,
                'year_level' => $yearLevel,
                'amount' => (float) $request->amount,
                'is_active' => true,
            ]
        );

        $name = request()->route()?->getName() ?? '';
        $baseFeesRoute = str_starts_with($name, 'unifast.') ? 'unifast.settings.fees' : (str_starts_with($name, 'admin.') ? 'admin.settings.fees' : 'registrar.settings.fees');
        return redirect()->route($baseFeesRoute, ['mode' => 'raw'])->with('success', 'Fee added to raw list.');
    }

    /** Registrar or Unifast: copy a fee from raw list into current program/year (Arrange table). */
    public function copyFeeFromRaw(Request $request)
    {
        $request->validate([
            'fee_id' => ['required', 'exists:fees,id'],
            'program' => ['required', 'string', 'max:255'],
            'year_level' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::in(AcademicYearLevel::CANONICAL)],
        ]);

        $sourceFee = Fee::with('feeCategory')->findOrFail($request->fee_id);
        $program = trim($request->program);
        $yearLevel = $request->year_level;
        $programModel = Program::where('program_name', $program)->firstOrFail();
        $yearLevelModel = AcademicYearLevel::where('name', $yearLevel)->firstOrFail();

        $isUnifast = str_starts_with(request()->route()?->getName() ?? '', 'unifast.');
        Fee::updateOrCreate(
            [
                'fee_category_id' => $sourceFee->fee_category_id,
                'program_id' => $programModel->id,
                'academic_year_level_id' => $yearLevelModel->id,
            ],
            [
                'name' => $sourceFee->name ?? $sourceFee->feeCategory?->name,
                'category' => $sourceFee->category ?? $sourceFee->feeCategory?->name,
                'program' => $program,
                'year_level' => $yearLevel,
                'amount' => (float) $sourceFee->amount,
                'is_active' => true,
                'sort_order' => $isUnifast ? ((Fee::query()->forProgramAndYear($programModel->id, $yearLevelModel->id)->max('sort_order') ?? -1) + 1) : null,
            ]
        );

        $name = request()->route()?->getName() ?? '';
        $baseFeesRoute = $isUnifast ? 'unifast.settings.fees' : (str_starts_with($name, 'admin.') ? 'admin.settings.fees' : 'registrar.settings.fees');
        $feesUrl = route($baseFeesRoute) . '?program=' . urlencode($program) . '&year=' . urlencode($yearLevel);
        return redirect($feesUrl)->with('success', 'Fee added to this program/year.');
    }

    public function toggleFee($id)
    {
        $fee = Fee::findOrFail($id);
        $fee->update([
            'is_active' => ! $fee->is_active,
        ]);

        return back()->with('success', 'Fee visibility updated.');
    }

    public function blocks()
    {
        $blocks = Block::query()
            ->orderBy('program')
            ->orderBy('year_level')
            ->orderBy('semester')
            ->orderBy('code')
            ->get();

        $programs = Program::orderBy('program_name')->pluck('program_name');

        $yearLevels = AcademicYearLevel::where('is_active', true)->orderBy('name')->pluck('name');
        $semesters = AcademicSemester::where('is_active', true)->orderBy('name')->pluck('name');

        return view('dashboards.settings-blocks', compact('blocks', 'programs', 'yearLevels', 'semesters'));
    }

    public function storeBlock(Request $request)
    {
        $request->validate([
            'program' => ['required', 'string', 'max:255'],
            'year_level' => ['required', 'string', 'max:100', \Illuminate\Validation\Rule::in(AcademicYearLevel::CANONICAL)],
            'semester' => ['required', 'string', 'max:100', \Illuminate\Validation\Rule::in(AcademicSemester::CANONICAL)],
            'shift' => ['required', 'in:day,night'],
            'code' => ['nullable', 'string', 'max:50'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $programName = trim($request->program);
        $programModel = Program::where('program_name', $programName)->first();
        $yearLevel = trim($request->year_level);
        $semester = trim($request->semester);
        $shift = $request->shift;

        $blockAssignment = app(BlockAssignmentService::class);
        $userCode = trim((string) $request->code);

        // Use consistent format: PREFIX yearNum - section (e.g. BEED 1 - 1, BEED 3 - 1). Auto-suggest if empty or not matching.
        if ($userCode === '' || ! $blockAssignment->codeMatchesYearLevel($userCode, $yearLevel)) {
            $code = $blockAssignment->suggestNextBlockCode($programName, $yearLevel, $semester, $shift);
        } else {
            $code = $userCode;
        }

        if (Block::query()->where('code', $code)->exists()) {
            return back()->withErrors(['code' => 'A block with this code already exists. Use a unique block code or leave blank to auto-generate.'])->withInput();
        }

        $sectionName = preg_match('/\s\d+\s*-\s*([A-Z0-9]+)\s*$/i', $code, $m) ? $m[1] : $code;

        $cap = $request->capacity ?: 50;
        $attrs = [
            'program_id' => $programModel?->id,
            'program' => $programName,
            'year_level' => $yearLevel,
            'semester' => $semester,
            'shift' => $shift,
            'code' => $code,
            'name' => $code,
            'section_name' => $sectionName,
            'capacity' => $cap,
            'max_capacity' => $cap,
            'max_students' => $cap,
            'current_size' => 0,
            'is_active' => true,
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('blocks', 'school_year_label')) {
            $attrs['school_year_label'] = \App\Services\AcademicCalendarService::getSelectedSchoolYearLabel()
                ?? SchoolYear::query()->orderByDesc('start_year')->value('label');
        }
        Block::create($attrs);

        return back()->with('success', 'Block added with code: ' . $code . '.');
    }

    public function toggleBlock($id)
    {
        $block = Block::findOrFail($id);
        $block->update([
            'is_active' => ! $block->is_active,
        ]);

        return back()->with('success', 'Block visibility updated.');
    }

    public function professors(Request $request)
    {
        $scopeService = app(SchedulingScopeService::class);
        $user = $request->user();
        $allowedScopes = $scopeService->allowedScopesForCreator($user);
        $professorsQuery = User::query()
            ->whereNotNull('faculty_type')
            ->when($user->role === 'dean', fn ($q) => $scopeService->scopeProfessorsForViewer($q, $user))
            ->orderBy('name');
        if (request()->routeIs('admin.settings.*')) {
            $professors = $professorsQuery->paginate(15)->withQueryString();
            return view('dashboards.settings-professors', ['professors' => $professors, 'allowedScopes' => $allowedScopes, 'isDean' => false]);
        }
        $professors = $professorsQuery->get(['id', 'name', 'email', 'gender', 'faculty_type', 'department_scope', 'created_by_role']);
        $viewName = $user->role === 'dean' ? 'dashboards.dean.settings-professors' : 'dashboards.registrar.settings-professors';
        return view($viewName, [
            'professors' => $professors,
            'allowedScopes' => $allowedScopes,
        ]);
    }

    public function storeProfessor(Request $request)
    {
        $scopeService = app(SchedulingScopeService::class);
        $user = $request->user();
        $allowedScopes = $scopeService->allowedScopesForCreator($user);
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'gender' => ['required', 'string', 'in:Male,Female'],
            'department_scope' => ['required', 'string', 'in:' . implode(',', $allowedScopes)],
            'faculty_type' => ['nullable', 'string', 'in:permanent,cos,part-time'],
        ]);
        if (!in_array($request->department_scope, $allowedScopes, true)) {
            return back()->withErrors(['department_scope' => 'You are not allowed to assign this scope.'])->withInput();
        }
        // Professor is a record for scheduling/reports only, not a login account
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make(Str::random(32)),
            'role' => 'staff',
            'gender' => $request->gender,
            'faculty_type' => $request->faculty_type ?: 'cos',
            'department_scope' => $request->department_scope,
            'created_by_role' => $user->role,
            'created_by_user_id' => $user->id,
        ]);
        return back()->with('success', 'Professor added.');
    }

    public function rooms(Request $request)
    {
        $scopeService = app(SchedulingScopeService::class);
        $user = $request->user();
        $allowedScopes = $scopeService->allowedScopesForCreator($user);
        $roomsQuery = Room::query()
            ->when($user->role === 'dean', fn ($q) => $scopeService->scopeRoomsForViewer($q, $user))
            ->orderBy('name');
        if (request()->routeIs('admin.settings.*')) {
            $rooms = $roomsQuery->paginate(15)->withQueryString();
            return view('dashboards.settings-rooms', ['rooms' => $rooms, 'allowedScopes' => $allowedScopes, 'isDean' => false]);
        }
        $rooms = $roomsQuery->get();
        $viewName = $user->role === 'dean' ? 'dashboards.dean.settings-rooms' : 'dashboards.registrar.settings-rooms';
        return view($viewName, [
            'rooms' => $rooms,
            'allowedScopes' => $allowedScopes,
        ]);
    }

    public function storeRoom(Request $request)
    {
        $scopeService = app(SchedulingScopeService::class);
        $user = $request->user();
        $allowedScopes = $scopeService->allowedScopesForCreator($user);
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:rooms,code'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'building' => ['nullable', 'string', 'max:255'],
            'department_scope' => ['required', 'string', 'in:' . implode(',', $allowedScopes)],
        ]);
        if (!in_array($request->department_scope, $allowedScopes, true)) {
            return back()->withErrors(['department_scope' => 'You are not allowed to assign this scope.'])->withInput();
        }
        Room::create([
            'name' => $request->name,
            'code' => $request->code,
            'capacity' => (int) $request->capacity,
            'building' => $request->building,
            'department_scope' => $request->department_scope,
            'created_by_role' => $user->role,
            'created_by_user_id' => $user->id,
            'is_active' => true,
        ]);
        return back()->with('success', 'Room added.');
    }
}
