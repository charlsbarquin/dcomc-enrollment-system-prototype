<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EnrollmentForm;
use App\Models\FormResponse;
use App\Models\Assessment;
use App\Models\AcademicSemester;
use App\Models\AcademicYearLevel;
use App\Models\Major;
use App\Models\Program;
use App\Models\SchoolYear;
use App\Models\StudentBlockAssignment;
use App\Models\StudentCorRecord;
use App\Services\AcademicCalendarService;
use App\Services\BlockAssignmentService;
use App\Services\EnrollmentApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RegistrarController extends Controller
{
    public function dashboard()
    {
        $base = FormResponse::query()->forSelectedSchoolYear();

        $qaCounts = [
            'needs_correction' => (clone $base)->where('process_status', 'needs_correction')->count(),
            'approved' => (clone $base)->where('process_status', 'approved')->count(),
            'scheduled' => (clone $base)->where('process_status', 'scheduled')->count(),
            'completed' => (clone $base)->where('process_status', 'completed')->count(),
        ];

        $needsCorrection = FormResponse::query()->forSelectedSchoolYear()->with('user')->where('process_status', 'needs_correction')->latest()->limit(8)->get();
        $approvedUnscheduled = FormResponse::query()->forSelectedSchoolYear()->with('user')->where('process_status', 'approved')->latest()->limit(8)->get();
        $scheduledPending = FormResponse::query()->forSelectedSchoolYear()->with('user')->where('process_status', 'scheduled')->latest()->limit(8)->get();

        return view('dashboards.registrar', compact('qaCounts', 'needsCorrection', 'approvedUnscheduled', 'scheduledPending'));
    }

    // ==========================================
    // SEPARATED REGISTRATION VIEWS
    // ==========================================

    public function manualRegistration()
    {
        $savedForms = EnrollmentForm::forSelectedSchoolYear()->orderBy('created_at', 'desc')->get();
        $globalEnrollmentActive = Cache::get('global_enrollment_active', false);
        $yearLevels = AcademicYearLevel::where('is_active', true)->orderBy('name')->pluck('name')->values();
        $semesters = AcademicSemester::where('is_active', true)->orderBy('name')->pluck('name')->values();
        $schoolYears = SchoolYear::orderByDesc('start_year')->pluck('label')->values();
        $defaultSchoolYear = $schoolYears->first(); // current school year (latest)
        $majorsByProgram = Major::majorsByProgram();
        $programOptions = Program::orderBy('program_name')->pluck('program_name')->values()->merge(collect(array_keys($majorsByProgram)))->unique()->values();
        $totalResponsesCount = FormResponse::forSelectedSchoolYear()->count();

        return view('dashboards.manual-registration', compact('savedForms', 'globalEnrollmentActive', 'yearLevels', 'semesters', 'schoolYears', 'defaultSchoolYear', 'majorsByProgram', 'programOptions', 'totalResponsesCount'));
    }

    /**
     * Store a manually registered student: save details and create a student account (User with role=student).
     * Optionally assigns the student to a block via BlockAssignmentService.
     */
    public function storeManualRegistration(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['required', 'string', 'in:Male,Female'],
            'civil_status' => ['nullable', 'string', 'max:50'],
            'citizenship' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date'],
            'place_of_birth' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'course' => ['required', 'string', 'max:255'],
            'major' => ['nullable', 'string', 'max:255'],
            'year_level' => ['required', 'string', 'max:100'],
            'semester' => ['required', 'string', 'max:100'],
            'school_year' => ['nullable', 'string', 'max:50'],
            'shift' => ['required', 'string', 'in:day,night'],
            'student_type' => ['nullable', 'string', 'max:50', Rule::in(['Regular', 'Transferee', 'Returnee', 'Irregular'])],
            'house_number' => ['nullable', 'string', 'max:100'],
            'street' => ['nullable', 'string', 'max:255'],
            'purok_zone' => ['nullable', 'string', 'max:100'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'father_occupation' => ['nullable', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'mother_occupation' => ['nullable', 'string', 'max:255'],
            'monthly_income' => ['nullable', 'string', 'max:100'],
            'dswd_household_no' => ['nullable', 'string', 'max:100'],
            'num_family_members' => ['nullable', 'integer', 'min:0', 'max:99'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'high_school' => ['nullable', 'string', 'max:255'],
            'hs_graduation_date' => ['nullable', 'string', 'max:50'],
            'lrn' => ['nullable', 'string', 'max:50'],
            'registration_remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $email = !empty(trim($validated['email'] ?? ''))
            ? trim($validated['email'])
            : $this->generateStudentEmail($this->generateNextSchoolId(), $validated['first_name'], $validated['last_name']);
        $defaultPassword = config('app.manual_registration_default_password', 'Student123');
        $schoolId = $email;

        $user = User::create([
            'name' => trim($validated['last_name'] . ', ' . $validated['first_name'] . ($validated['middle_name'] ?? '' ? ' ' . $validated['middle_name'] : '')),
            'email' => $email,
            'password' => Hash::make($defaultPassword),
            'role' => User::ROLE_STUDENT,
            'school_id' => $schoolId,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'gender' => $validated['gender'],
            'civil_status' => $validated['civil_status'] ?? null,
            'citizenship' => $validated['citizenship'] ?? 'Filipino',
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'place_of_birth' => $validated['place_of_birth'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'course' => $validated['course'],
            'major' => $validated['major'] ?? null,
            'year_level' => $validated['year_level'],
            'semester' => $validated['semester'],
            'school_year' => $validated['school_year'] ?? SchoolYear::orderByDesc('start_year')->value('label'),
            'shift' => $validated['shift'],
            'student_type' => $validated['student_type'] ?? 'Regular',
            'student_status' => 'Enrolled',
            'units_enrolled' => 0,
            'profile_completed' => true,
            'house_number' => $validated['house_number'] ?? null,
            'street' => $validated['street'] ?? null,
            'purok_zone' => $validated['purok_zone'] ?? null,
            'barangay' => $validated['barangay'] ?? null,
            'municipality' => $validated['municipality'] ?? null,
            'province' => $validated['province'] ?? null,
            'zip_code' => $validated['zip_code'] ?? null,
            'father_name' => $validated['father_name'] ?? null,
            'father_occupation' => $validated['father_occupation'] ?? null,
            'mother_name' => $validated['mother_name'] ?? null,
            'mother_occupation' => $validated['mother_occupation'] ?? null,
            'monthly_income' => $validated['monthly_income'] ?? null,
            'dswd_household_no' => $validated['dswd_household_no'] ?? null,
            'num_family_members' => isset($validated['num_family_members']) ? (int) $validated['num_family_members'] : null,
            'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
            'high_school' => $validated['high_school'] ?? null,
            'hs_graduation_date' => $validated['hs_graduation_date'] ?? null,
            'registration_remarks' => $validated['registration_remarks'] ?? null,
            'created_by_role' => Auth::check() ? auth()->user()->effectiveRole() : null,
            'created_by_user_id' => Auth::id(),
        ]);

        $blockAssignment = app(BlockAssignmentService::class);
        $blockAssignment->assignStudentToBlock($user, $validated['year_level'], $validated['semester'], null);

        $registrationManualRoute = (Auth::check() && auth()->user()->effectiveRole() === 'staff')
            ? 'staff.registration.manual'
            : 'registrar.registration.manual';
        return redirect()
            ->route($registrationManualRoute)
            ->with('success', 'Student registered and enrolled successfully. Student ID / School ID: ' . $user->school_id . ' | Status: Enrolled | Default password: ' . $defaultPassword);
    }

    private function generateNextSchoolId(): string
    {
        $year = (string) now()->year;
        $prefix = 'DCOMC-' . $year . '-';
        $max = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->where('school_id', 'like', $prefix . '%')
            ->get(['school_id'])
            ->max(fn ($u) => (int) preg_replace('/^DCOMC-\d+-/', '', $u->school_id ?? '0'));
        $next = ($max ?? 0) + 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function generateStudentEmail(string $schoolId, string $firstName, string $lastName): string
    {
        $base = str_replace([' ', '-'], '', strtolower($lastName . '.' . $firstName));
        $base = preg_replace('/[^a-z0-9]/', '', $base) ?: 'student';
        $candidate = $base . '@dcomc.edu.ph';
        $exists = User::where('email', $candidate)->exists();
        if (! $exists) {
            return $candidate;
        }
        $suffix = preg_replace('/^DCOMC-\d+-/', '', $schoolId);

        return $base . $suffix . '@dcomc.edu.ph';
    }

    /**
     * Batch import students from CSV. CSV must have a header row.
     * Required column: "student_id" or "school_id" (used as login / School ID).
     * Optional columns: first_name, last_name, middle_name, name, course, year_level, semester, school_year, gender, phone, student_type, etc.
     * student_type must be one of: Regular, Transferee, Returnee, Irregular. Default: Regular.
     * All imported accounts get password: Student12345
     */
    public function importManualRegistration(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file = $request->file('csv_file');
        $defaultPassword = 'Student12345';
        $defaultSchoolYear = SchoolYear::orderByDesc('start_year')->value('label');
        $created = 0;
        $skipped = [];
        $errors = [];

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return back()->withErrors(['csv_file' => 'Could not read the uploaded file.']);
        }

        $headerRow = fgetcsv($handle);
        if ($headerRow === false || empty(array_filter($headerRow))) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'CSV must have a header row.']);
        }

        $headers = array_map(function ($h) {
            return strtolower(trim(str_replace(["\xEF\xBB\xBF", "\r", "\n"], '', $h)));
        }, $headerRow);
        $studentIdCol = null;
        foreach (['student_id', 'school_id', 'student id', 'school id'] as $key) {
            $idx = array_search($key, $headers, true);
            if ($idx !== false) {
                $studentIdCol = $idx;
                break;
            }
        }
        if ($studentIdCol === null) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'CSV must contain a column named "student_id" or "school_id" (used as login / School ID).']);
        }

        $rowNum = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            $values = array_pad($row, count($headers), '');
            $assoc = array_combine($headers, $values);
            if ($assoc === false) {
                continue;
            }
            $studentId = trim($assoc[$headers[$studentIdCol]] ?? '');
            if ($studentId === '') {
                $skipped[] = "Row {$rowNum}: empty Student ID / School ID.";
                continue;
            }
            if (User::where('email', $studentId)->exists()) {
                $skipped[] = "Row {$rowNum}: Student ID \"{$studentId}\" already exists.";
                continue;
            }

            $firstName = trim($assoc['first_name'] ?? $assoc['firstname'] ?? '');
            $lastName = trim($assoc['last_name'] ?? $assoc['lastname'] ?? '');
            $middleName = trim($assoc['middle_name'] ?? $assoc['middlename'] ?? '');
            $name = trim($assoc['name'] ?? '');
            if ($name === '' && ($firstName !== '' || $lastName !== '')) {
                $name = trim($lastName . ', ' . $firstName . ($middleName !== '' ? ' ' . $middleName : ''));
            }
            if ($name === '') {
                $name = $studentId;
            }

            $course = trim($assoc['course'] ?? $assoc['program'] ?? '');
            $yearLevel = trim($assoc['year_level'] ?? $assoc['yearlevel'] ?? '1st Year');
            $semester = trim($assoc['semester'] ?? '1st Semester');
            $schoolYear = trim($assoc['school_year'] ?? $assoc['schoolyear'] ?? $defaultSchoolYear);
            $gender = trim($assoc['gender'] ?? '');
            if ($gender !== '' && ! in_array($gender, ['Male', 'Female'], true)) {
                $gender = '';
            }
            $shift = trim($assoc['shift'] ?? 'day');
            if (! in_array($shift, ['day', 'night'], true)) {
                $shift = 'day';
            }
            $allowedStudentTypes = ['Regular', 'Transferee', 'Returnee', 'Irregular'];
            $studentTypeRaw = trim($assoc['student_type'] ?? 'Regular');
            $studentType = in_array($studentTypeRaw, $allowedStudentTypes, true) ? $studentTypeRaw : 'Regular';

            try {
                $user = User::create([
                    'name' => $name,
                    'email' => $studentId,
                    'password' => Hash::make($defaultPassword),
                    'role' => User::ROLE_STUDENT,
                    'school_id' => $studentId,
                    'first_name' => $firstName ?: null,
                    'last_name' => $lastName ?: null,
                    'middle_name' => $middleName ?: null,
                    'gender' => $gender ?: null,
                    'phone' => trim($assoc['phone'] ?? $assoc['contact'] ?? '') ?: null,
                    'course' => $course ?: null,
                    'major' => trim($assoc['major'] ?? '') ?: null,
                    'year_level' => $yearLevel,
                    'semester' => $semester,
                    'school_year' => $schoolYear,
                    'shift' => $shift,
                    'student_type' => $studentType,
                    'student_status' => 'Enrolled',
                    'units_enrolled' => 0,
                    'profile_completed' => false,
                    'municipality' => trim($assoc['municipality'] ?? $assoc['address'] ?? '') ?: null,
                    'barangay' => trim($assoc['barangay'] ?? '') ?: null,
                    'province' => trim($assoc['province'] ?? '') ?: null,
                    'created_by_role' => Auth::check() ? auth()->user()->effectiveRole() : null,
                    'created_by_user_id' => Auth::id(),
                ]);
                $blockAssignment = app(BlockAssignmentService::class);
                $blockAssignment->assignStudentToBlock($user, $yearLevel, $semester, null);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = "Row {$rowNum}: " . $e->getMessage();
            }
        }
        fclose($handle);

        $registrationManualRoute = (Auth::check() && auth()->user()->effectiveRole() === 'staff')
            ? 'staff.registration.manual'
            : 'registrar.registration.manual';

        $message = "Batch import completed. {$created} student account(s) created. Login: use Student ID / School ID column; Password for all: {$defaultPassword}.";
        if (count($skipped) > 0) {
            $message .= ' Skipped: ' . implode(' ', array_slice($skipped, 0, 5)) . (count($skipped) > 5 ? ' ...' : '');
        }
        if (count($errors) > 0) {
            $message .= ' Errors: ' . implode(' ', array_slice($errors, 0, 3)) . (count($errors) > 3 ? ' ...' : '');
        }

        return redirect()
            ->route($registrationManualRoute)
            ->with('success', $message);
    }

    /**
     * Download a CSV template for batch import: headers, one example row, and blank rows.
     */
    public function downloadImportTemplate()
    {
        $headers = [
            'student_id',
            'first_name',
            'last_name',
            'middle_name',
            'name',
            'course',
            'year_level',
            'semester',
            'school_year',
            'gender',
            'phone',
            'major',
            'municipality',
            'barangay',
            'province',
            'shift',
            'student_type',
        ];
        $exampleRow = [
            '2024-0001',
            'Juan',
            'Dela Cruz',
            'Santos',
            '',
            'BSIT',
            '1st Year',
            '1st Semester',
            '2024-2025',
            'Male',
            '09171234567',
            '',
            'Daraga',
            'Tagas',
            'Albay',
            'day',
            'Regular',
        ];
        $blankRow = array_fill(0, count($headers), '');

        return response()->streamDownload(function () use ($headers, $exampleRow, $blankRow) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, $exampleRow);
            for ($i = 0; $i < 10; $i++) {
                fputcsv($out, $blankRow);
            }
            fclose($out);
        }, 'DCOMC_student_import_template.csv', [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="DCOMC_student_import_template.csv"',
        ]);
    }

    public function formBuilder()
    {
        $savedForms = EnrollmentForm::forSelectedSchoolYear()->orderBy('created_at', 'desc')->get();
        $globalEnrollmentActive = Cache::get('global_enrollment_active', false);
        $totalResponsesCount = FormResponse::forSelectedSchoolYear()->count();
        
        return view('dashboards.form-library', compact('savedForms', 'globalEnrollmentActive', 'totalResponsesCount'));
    }

    public function createForm()
    {
        $yearLevels = AcademicYearLevel::where('is_active', true)->orderBy('name')->pluck('name')->values();
        $semesters = AcademicSemester::where('is_active', true)->orderBy('name')->pluck('name')->values();
        $form = (object)[
            'id' => null,
            'title' => 'Untitled Form',
            'description' => '',
            'questions' => null,
            'incoming_year_level' => request('incoming_year_level'),
            'incoming_semester' => request('incoming_semester'),
        ];
        $globalEnrollmentActive = Cache::get('global_enrollment_active', false);
        $totalResponsesCount = FormResponse::forSelectedSchoolYear()->count();
        
        return view('dashboards.form-builder', compact('form', 'globalEnrollmentActive', 'yearLevels', 'semesters', 'totalResponsesCount'));
    }

    public function editForm($id)
    {
        $yearLevels = AcademicYearLevel::where('is_active', true)->orderBy('name')->pluck('name')->values();
        $semesters = AcademicSemester::where('is_active', true)->orderBy('name')->pluck('name')->values();
        $form = EnrollmentForm::forSelectedSchoolYear()->findOrFail($id);
        $globalEnrollmentActive = Cache::get('global_enrollment_active', false);
        $totalResponsesCount = FormResponse::forSelectedSchoolYear()->count();
        
        return view('dashboards.form-builder', compact('form', 'globalEnrollmentActive', 'yearLevels', 'semesters', 'totalResponsesCount'));
    }

    public function responses(Request $request)
    {
        // Remove orphan responses (user was deleted; user_id set to null by FK) so counts and lists stay correct.
        FormResponse::whereNull('user_id')->delete();

        $filters = [
            'student_number' => trim($request->string('student_number')->toString()),
            'program' => trim($request->string('program')->toString()),
            'school_year' => trim($request->string('school_year')->toString()),
            'first_name' => trim($request->string('first_name')->toString()),
            'last_name' => trim($request->string('last_name')->toString()),
            'year' => trim($request->string('year')->toString()),
            'semester' => trim($request->string('semester')->toString()),
            'status' => trim($request->string('status')->toString()),
            'folder' => trim($request->string('folder')->toString()),
        ];

        $hasTableFilters = collect([
            'student_number' => $filters['student_number'],
            'program' => $filters['program'],
            'school_year' => $filters['school_year'],
            'first_name' => $filters['first_name'],
            'last_name' => $filters['last_name'],
            'year' => $filters['year'],
            'semester' => $filters['semester'],
            'status' => $filters['status'],
        ])->contains(fn ($value) => $value !== '');
        $showTableResults = $hasTableFilters;
        $invalidStudentNumber = $filters['student_number'] !== '' && !ctype_digit($filters['student_number']);

        $activeFormFolders = EnrollmentForm::query()
            ->forSelectedSchoolYear()
            ->where('is_active', true)
            ->when($filters['folder'] !== '', fn ($q) => $q->where('title', 'like', '%'.$filters['folder'].'%'))
            ->when($showTableResults, function ($q) use ($filters, $invalidStudentNumber) {
                $q->whereHas('formResponses.user', function ($userQuery) use ($filters) {
                    if ($filters['student_number'] !== '') {
                        $userQuery->where('id', $filters['student_number']);
                    }

                    if ($filters['first_name'] !== '') {
                        $userQuery->where('first_name', 'like', '%'.$filters['first_name'].'%');
                    }

                    if ($filters['last_name'] !== '') {
                        $userQuery->where('last_name', 'like', '%'.$filters['last_name'].'%');
                    }

                    if ($filters['program'] !== '') {
                        $userQuery->where('course', 'like', '%'.$filters['program'].'%');
                    }

                    if ($filters['year'] !== '') {
                        $userQuery->where('year_level', 'like', '%'.$filters['year'].'%');
                    }

                    if ($filters['semester'] !== '') {
                        $userQuery->where('semester', $filters['semester']);
                    }
                });
                if ($invalidStudentNumber) {
                    $q->whereRaw('1 = 0');
                }
            })
            ->with(['formResponses' => fn ($q) => $q->whereNotNull('user_id')->with(['user', 'enrollmentForm'])])
            ->withCount(['formResponses as form_responses_count' => fn ($q) => $q->whereNotNull('user_id')])
            ->latest()
            ->get();

        $filteredResponses = collect();
        if ($showTableResults) {
            $filteredResponses = FormResponse::query()
                ->forSelectedSchoolYear()
                ->with(['user.block', 'enrollmentForm', 'preferredBlock', 'assignedBlock'])
                ->whereHas('enrollmentForm', fn ($q) => $q->where('is_active', true))
                ->when($filters['folder'] !== '', fn ($q) => $q->whereHas('enrollmentForm', fn ($formQuery) => $formQuery->where('title', 'like', '%'.$filters['folder'].'%')))
                ->when($filters['school_year'] !== '', fn ($q) => $q->whereHas('enrollmentForm', fn ($formQuery) => $formQuery->where('assigned_year', $filters['school_year'])))
                ->when($filters['status'] !== '', function ($q) use ($filters) {
                    if ($filters['status'] === 'Pending') {
                        $q->where(function ($statusQuery) {
                            $statusQuery->whereNull('approval_status')
                                ->orWhere('approval_status', 'pending');
                        });
                    } elseif ($filters['status'] === 'Enrolled') {
                        $q->where('approval_status', 'approved');
                    } elseif ($filters['status'] === 'Rejected') {
                        $q->where('approval_status', 'rejected');
                    }
                })
                ->whereHas('user', function ($userQuery) use ($filters) {
                    if ($filters['student_number'] !== '') {
                        $userQuery->where('id', $filters['student_number']);
                    }
                    if ($filters['first_name'] !== '') {
                        $userQuery->where('first_name', 'like', '%'.$filters['first_name'].'%');
                    }
                    if ($filters['last_name'] !== '') {
                        $userQuery->where('last_name', 'like', '%'.$filters['last_name'].'%');
                    }
                    if ($filters['program'] !== '') {
                        $userQuery->where('course', 'like', '%'.$filters['program'].'%');
                    }
                    if ($filters['year'] !== '') {
                        $userQuery->where('year_level', $filters['year']);
                    }
                    if ($filters['semester'] !== '') {
                        $userQuery->where('semester', $filters['semester']);
                    }
                })
                ->latest()
                ->get();

            if ($invalidStudentNumber) {
                $filteredResponses = collect();
            }
        }

        $globalEnrollmentActive = Cache::get('global_enrollment_active', false);
        $yearLevels = AcademicYearLevel::where('is_active', true)->orderBy('name')->pluck('name')->values();
        $semesters = AcademicSemester::where('is_active', true)->orderBy('name')->pluck('name')->values();
        $schoolYears = SchoolYear::orderByDesc('start_year')->pluck('label')->values();
        $totalResponsesCount = $activeFormFolders->sum('form_responses_count');
        
        return view('dashboards.form-responses', compact('activeFormFolders', 'filteredResponses', 'showTableResults', 'globalEnrollmentActive', 'yearLevels', 'semesters', 'schoolYears', 'totalResponsesCount', 'filters'));
    }

    public function responseFolder($id)
    {
        $folder = EnrollmentForm::forSelectedSchoolYear()->where('is_active', true)->findOrFail($id);

        // Remove orphan responses (user was deleted; user_id is null) so only valid data is shown.
        FormResponse::where('enrollment_form_id', $folder->id)->whereNull('user_id')->delete();

        $responses = FormResponse::with(['user', 'enrollmentForm', 'preferredBlock', 'assignedBlock'])
            ->where('enrollment_form_id', $folder->id)
            ->whereNotNull('user_id')
            ->latest()
            ->get();

        $activeFormFolders = EnrollmentForm::forSelectedSchoolYear()->where('is_active', true)
            ->withCount(['formResponses as form_responses_count' => fn ($q) => $q->whereNotNull('user_id')])
            ->latest()
            ->get();

        $globalEnrollmentActive = Cache::get('global_enrollment_active', false);
        $yearLevels = AcademicYearLevel::where('is_active', true)->orderBy('name')->pluck('name')->values();
        $semesters = AcademicSemester::where('is_active', true)->orderBy('name')->pluck('name')->values();
        $totalResponsesCount = $activeFormFolders->sum('form_responses_count');

        return view('dashboards.form-response-folder', compact(
            'folder',
            'responses',
            'activeFormFolders',
            'globalEnrollmentActive',
            'yearLevels',
            'semesters',
            'totalResponsesCount'
        ));
    }

    // ==========================================
    // API ROUTES FOR FORM BUILDER
    // ==========================================

    public function getForm($id)
    {
        $form = EnrollmentForm::forSelectedSchoolYear()->findOrFail($id);
        return response()->json([
            'success' => true,
            'form' => $form
        ]);
    }

    public function saveForm(Request $request)
    {
        $yearLevels = AcademicYearLevel::where('is_active', true)->pluck('name')->all();
        $semesters = AcademicSemester::where('is_active', true)->pluck('name')->all();

        $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'questions' => 'nullable|array',
            'form_id' => 'nullable|exists:enrollment_forms,id',
            'incoming_year_level' => ['required', 'string', Rule::in($yearLevels)],
            'incoming_semester' => ['required', 'string', Rule::in($semesters)],
            'lock_course_major' => 'nullable|boolean',
        ]);

        $selectedSchoolYearId = AcademicCalendarService::getSelectedSchoolYearId();

        if ($request->form_id) {
            $form = EnrollmentForm::forSelectedSchoolYear()->findOrFail($request->form_id);
            $form->update([
                'title' => $request->title,
                'description' => $request->description,
                'questions' => $request->questions,
                'incoming_year_level' => $request->incoming_year_level,
                'incoming_semester' => $request->incoming_semester,
            ]);
            $message = 'Draft updated successfully!';
        } else {
            $form = EnrollmentForm::create([
                'title' => $request->title,
                'description' => $request->description,
                'questions' => $request->questions,
                'is_active' => false, // Saved as draft by default
                'incoming_year_level' => $request->incoming_year_level,
                'incoming_semester' => $request->incoming_semester,
                'school_year_id' => $selectedSchoolYearId,
                'lock_course_major' => $request->boolean('lock_course_major', true),
            ]);
            $message = 'Draft created successfully!';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'form_id' => $form->id
        ]);
    }

    // Delete a form
    public function deleteForm($id)
    {
        $form = EnrollmentForm::forSelectedSchoolYear()->findOrFail($id);
        $form->delete();

        return response()->json([
            'success' => true,
            'message' => 'Form deleted successfully!'
        ]);
    }

    // ==========================================
    // ENROLLMENT TOGGLES & DEPLOYMENT
    // ==========================================

    // Master Switch for Students
    public function toggleGlobalEnrollment(Request $request)
    {
        if (Auth::check() && auth()->user()->effectiveRole() === 'staff') {
            abort(403, 'Only registrar or admin can enable or disable the enrollment portal.');
        }
        $isActive = $request->boolean('is_active');
        Cache::forever('global_enrollment_active', $isActive);

        return response()->json([
            'success' => true,
            'message' => 'Global Enrollment is now ' . ($isActive ? 'OPEN' : 'CLOSED') . ' for students.'
        ]);
    }

    // Deploy a specific form to a target Year & Semester
    public function deployForm(Request $request)
    {
        $yearLevels = AcademicYearLevel::where('is_active', true)->pluck('name')->all();
        $semesters = AcademicSemester::where('is_active', true)->pluck('name')->all();

        $request->validate([
            'form_id' => 'required|exists:enrollment_forms,id',
            'assigned_year' => ['required', 'string', Rule::in($yearLevels)],
            'assigned_semester' => ['required', 'string', Rule::in($semesters)]
        ]);

        // 1. Find any other form currently assigned to this specific Year & Sem and deactivate it (within selected school year)
        EnrollmentForm::forSelectedSchoolYear()
            ->where('assigned_year', $request->assigned_year)
            ->where('assigned_semester', $request->assigned_semester)
            ->update(['is_active' => false]);

        // 2. Activate the newly selected form and assign it to the target
        $form = EnrollmentForm::forSelectedSchoolYear()->findOrFail($request->form_id);
        if (empty($form->incoming_year_level) || empty($form->incoming_semester)) {
            return response()->json([
                'success' => false,
                'message' => 'Set the incoming year level and semester on the form before deployment.',
            ], 422);
        }
        if (! in_array($form->incoming_year_level, $yearLevels, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Incoming year level must be an active year level. Update it in Settings > Year Levels.',
            ], 422);
        }
        if (! in_array($form->incoming_semester, $semesters, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Incoming semester must be an active semester. Update it in Settings > Semesters.',
            ], 422);
        }
        $form->update([
            'is_active' => true,
            'assigned_year' => $request->assigned_year,
            'assigned_semester' => $request->assigned_semester
        ]);

        return response()->json([
            'success' => true,
            'message' => "Form deployed: {$request->assigned_year} - {$request->assigned_semester} cohort will enroll to {$form->incoming_year_level} - {$form->incoming_semester}."
        ]);
    }

    // ==========================================
    // STUDENTS DATA
    // ==========================================

    public function students(\Illuminate\Http\Request $request)
    {
        $yearLevels = AcademicYearLevel::where('is_active', true)->orderBy('name')->pluck('name')->values();
        $semesters = AcademicSemester::where('is_active', true)->orderBy('name')->pluck('name')->values();
        $schoolYears = SchoolYear::orderByDesc('start_year')->pluck('label')->values();
        $currentSchoolYear = AcademicCalendarService::getSelectedSchoolYearLabel() ?? SchoolYear::orderByDesc('start_year')->value('label');

        $query = User::where('role', 'student')->with(['formResponses' => function ($q) {
            $q->forSelectedSchoolYear();
        }, 'formResponses.enrollmentForm']);
        if ($currentSchoolYear !== null && $currentSchoolYear !== '') {
            $activeLabel = AcademicCalendarService::getActiveSchoolYearLabel();
            $query->where(function ($q) use ($currentSchoolYear, $activeLabel) {
                $q->where('school_year', $currentSchoolYear);
                if ($activeLabel === $currentSchoolYear) {
                    $q->orWhereNull('school_year')->orWhere('school_year', '');
                }
            });
        }

        if ($request->filled('student_number')) {
            $query->where('id', $request->student_number);
        }

        if ($request->filled('first_name')) {
            $query->where('first_name', 'like', '%' . $request->first_name . '%');
        }

        if ($request->filled('last_name')) {
            $query->where('last_name', 'like', '%' . $request->last_name . '%');
        }

        if ($request->filled('program')) {
            $query->where('course', 'like', '%' . $request->program . '%');
        }

        if ($request->filled('year_level')) {
            $query->where('year_level', $request->year_level);
        }

        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        $students = $query->get();

        return view('dashboards.registrar-students', compact('students', 'yearLevels', 'semesters', 'schoolYears', 'currentSchoolYear'));
    }

    /**
     * List students whose Student Type is Irregular (or Shifter / status_color yellow).
     * They can be assigned to multiple blocks (variable year levels); no single static block.
     */
    public function irregularities(Request $request)
    {
        $selectedLabel = AcademicCalendarService::getSelectedSchoolYearLabel();
        $query = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->where(function ($q) {
                $q->whereIn('student_type', ['Irregular', 'Shifter'])
                    ->orWhere('status_color', 'yellow');
            })
            ->with(['block', 'blockAssignments.block'])
            ->orderBy('name');
        if ($selectedLabel !== null && $selectedLabel !== '') {
            $activeLabel = AcademicCalendarService::getActiveSchoolYearLabel();
            $query->where(function ($q) use ($selectedLabel, $activeLabel) {
                $q->where('school_year', $selectedLabel);
                if ($activeLabel === $selectedLabel) {
                    $q->orWhereNull('school_year')->orWhere('school_year', '');
                }
            });
        }

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($qry) use ($q) {
                $qry->where('name', 'like', '%' . $q . '%')
                    ->orWhere('email', 'like', '%' . $q . '%')
                    ->orWhere('school_id', 'like', '%' . $q . '%');
            });
        }

        $students = $query->paginate(50)->withQueryString();
        $blocksQuery = \App\Models\Block::query()->where('is_active', true)->orderBy('program')->orderBy('year_level')->orderBy('semester')->orderBy('code');
        if ($selectedLabel !== null && $selectedLabel !== '') {
            $blocksQuery->where('school_year_label', $selectedLabel);
        }
        $blocks = $blocksQuery->get(['id', 'program_id', 'code', 'program', 'year_level', 'semester']);

        $tab = $request->input('tab', 'irregular');
        $createScheduleData = null;
        if ($tab === 'create-schedule') {
            $templateQuery = \App\Models\ScheduleTemplate::query()->orderBy('id');
            if ($selectedLabel !== null && $selectedLabel !== '') {
                $templateQuery->where(function ($q) use ($selectedLabel) {
                    $q->where('school_year', $selectedLabel)->orWhereNull('school_year')->orWhere('school_year', '');
                });
            }
            $template = $templateQuery->first();
            if (! $template) {
                $template = \App\Models\ScheduleTemplate::create([
                    'title' => 'Irregular Schedule',
                    'template' => ['subject_ids' => [], 'fees' => [], 'slots' => []],
                    'school_year' => $selectedLabel,
                ]);
            }
            $createScheduleData = app(\App\Http\Controllers\RegistrarScheduleController::class)->getScheduleWorkspaceData($template);
        }

        return view('dashboards.registrar-irregularities', compact('students', 'blocks', 'tab', 'createScheduleData'));
    }

    /**
     * View an irregular student's deployed schedule (create_schedule COR). Allows removing a subject;
     * if that was the only subject for a block, the student is removed from that block in Explorer.
     */
    public function irregularSchedule(User $user): View|RedirectResponse
    {
        if ($user->role !== User::ROLE_STUDENT) {
            return redirect()->route('registrar.irregularities')->with('error', 'Not a student.');
        }
        $selectedLabel = AcademicCalendarService::getSelectedSchoolYearLabel();
        $recordsQuery = StudentCorRecord::query()
            ->where('student_id', $user->id)
            ->where('cor_source', StudentCorRecord::COR_SOURCE_CREATE_SCHEDULE)
            ->with(['subject', 'block'])
            ->orderBy('days_snapshot');
        if ($selectedLabel !== null && $selectedLabel !== '') {
            $recordsQuery->where('school_year', $selectedLabel);
        }
        $records = $recordsQuery
            ->orderBy('start_time_snapshot')
            ->get();
        return view('dashboards.registrar-irregular-schedule', compact('user', 'records'));
    }

    /**
     * Remove one subject (COR record) from an irregular student's schedule. If no other records
     * remain for that block_id, remove the student's block assignment so they disappear from that block in Explorer.
     */
    public function removeIrregularScheduleSubject(StudentCorRecord $record): RedirectResponse
    {
        if ($record->cor_source !== StudentCorRecord::COR_SOURCE_CREATE_SCHEDULE) {
            return back()->with('error', 'Cannot remove this record.');
        }
        $studentId = $record->student_id;
        $blockId = $record->block_id;
        DB::transaction(function () use ($record, $studentId, $blockId) {
            $record->delete();
            if ($blockId && ! StudentCorRecord::query()
                ->where('student_id', $studentId)
                ->where('cor_source', StudentCorRecord::COR_SOURCE_CREATE_SCHEDULE)
                ->where('block_id', $blockId)
                ->exists()) {
                StudentBlockAssignment::query()
                    ->where('user_id', $studentId)
                    ->where('block_id', $blockId)
                    ->delete();
                \App\Models\Block::where('id', $blockId)->decrement('current_size');
            }
        });
        $scheduleRoute = (Auth::check() && auth()->user()->effectiveRole() === 'staff')
            ? 'staff.irregularities.schedule'
            : 'registrar.irregularities.schedule';
        return redirect()->route($scheduleRoute, $studentId)->with('success', 'Subject removed from schedule. If that was the only subject for that block, the student was removed from that block in Students Explorer.');
    }

    public function approveResponse($id, EnrollmentApprovalService $enrollmentApprovalService)
    {
        $response = FormResponse::query()->forSelectedSchoolYear()->with(['user', 'enrollmentForm'])->findOrFail($id);

        $result = $enrollmentApprovalService->approve($response);

        if (! ($result['success'] ?? false)) {
            return back()->withErrors(['error' => $result['message'] ?? 'Enrollment approval failed.']);
        }

        return back()->with('success', 'Enrollment approved and student promoted to the incoming year/semester.');
    }

    public function rejectResponse($id)
    {
        $response = FormResponse::query()->forSelectedSchoolYear()->findOrFail($id);

        $response->update([
            'approval_status' => 'rejected',
            'process_status' => 'rejected',
            'process_notes' => 'Enrollment rejected by '.Auth::user()->name,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'reviewed_by_role' => Auth::check() ? auth()->user()->effectiveRole() : null,
        ]);

        return back()->with('success', 'Enrollment application rejected.');
    }
}