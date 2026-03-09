<?php

namespace App\Http\Controllers;

use App\Models\AcademicSemester;
use App\Models\AcademicYearLevel;
use App\Models\Assessment;
use App\Models\Department;
use App\Models\FormResponse;
use App\Models\Major;
use App\Models\Program;
use App\Models\SchoolYear;
use App\Models\User;
use App\Services\AcademicCalendarService;
use App\Services\BlockAssignmentService;
use App\Services\EnrollmentApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminAccountController extends Controller
{
    // READ: Show the accounts page with optional filters (layout like reference filter bar)
    public function index(Request $request)
    {
        $filters = [
            'student_number' => trim($request->string('student_number')->toString()),
            'program' => trim($request->string('program')->toString()),
            'school_year' => trim($request->string('school_year')->toString()),
            'year_level' => trim($request->string('year_level')->toString()),
            'semester' => trim($request->string('semester')->toString()),
            'first_name' => trim($request->string('first_name')->toString()),
            'last_name' => trim($request->string('last_name')->toString()),
            'status' => trim($request->string('status')->toString()), // role
            'folder' => trim($request->string('folder')->toString()), // email/search
        ];

        $query = User::with('department');

        if ($filters['student_number'] !== '') {
            $query->where('school_id', 'like', '%' . $filters['student_number'] . '%');
        }
        if ($filters['program'] !== '') {
            $query->where('course', 'like', '%' . $filters['program'] . '%');
        }
        if ($filters['school_year'] !== '') {
            $query->where('school_year', $filters['school_year']);
        }
        if ($filters['year_level'] !== '') {
            $query->where('year_level', $filters['year_level']);
        }
        if ($filters['semester'] !== '') {
            $query->where('semester', $filters['semester']);
        }
        if ($filters['first_name'] !== '') {
            $query->where('first_name', 'like', '%' . $filters['first_name'] . '%');
        }
        if ($filters['last_name'] !== '') {
            $query->where('last_name', 'like', '%' . $filters['last_name'] . '%');
        }
        if ($filters['status'] !== '' && in_array($filters['status'], User::roles(), true)) {
            $query->where('role', $filters['status']);
        }
        if ($filters['folder'] !== '') {
            $query->where(function ($q) use ($filters) {
                $q->where('email', 'like', '%' . $filters['folder'] . '%')
                    ->orWhere('name', 'like', '%' . $filters['folder'] . '%');
            });
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();
        $departments = Department::orderBy('name')->get();

        $schoolYears = SchoolYear::orderByDesc('start_year')->pluck('label')->values();
        $yearLevels = AcademicYearLevel::where('is_active', true)->orderBy('name')->pluck('name')->values();
        $semesters = AcademicSemester::where('is_active', true)->orderBy('name')->pluck('name')->values();
        $programOptions = Program::orderBy('program_name')->pluck('program_name')->merge(
            User::whereNotNull('course')->distinct()->pluck('course')
        )->filter()->unique()->sort()->values();

        return view('dashboards.admin-accounts', compact('users', 'departments', 'filters', 'schoolYears', 'yearLevels', 'semesters', 'programOptions'));
    }

    // Show create-student form (same fields as manual registration / detailed)
    public function createStudent()
    {
        $yearLevels = AcademicYearLevel::where('is_active', true)->orderBy('name')->pluck('name')->values();
        $semesters = AcademicSemester::where('is_active', true)->orderBy('name')->pluck('name')->values();
        $schoolYears = SchoolYear::orderByDesc('start_year')->pluck('label')->values();
        $defaultSchoolYear = $schoolYears->first();
        $majorsByProgram = Major::majorsByProgram();
        $programOptions = Program::orderBy('program_name')->pluck('program_name')->values()->merge(collect(array_keys($majorsByProgram)))->unique()->values();

        return view('dashboards.admin-create-student', compact('yearLevels', 'semesters', 'schoolYears', 'defaultSchoolYear', 'majorsByProgram', 'programOptions'));
    }

    // Store student account (same validation and logic as manual registration)
    public function storeStudent(Request $request)
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

        $email = ! empty(trim($validated['email'] ?? ''))
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

        return redirect()
            ->route('admin.accounts')
            ->with('success', 'Student registered successfully. Student ID / School ID: ' . $user->school_id . ' | Default password: ' . $defaultPassword);
    }

    // CREATE: Save a new DCOMC account (dean, registrar, staff, unifast, admin only)
    public function storeDcomc(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:admin,registrar,staff,unifast,dean',
            'department_id' => 'nullable|integer|exists:departments,id',
            'faculty_type' => 'nullable|in:permanent,cos,part-time',
            'program_scope' => 'nullable|string|max:255',
            'max_units' => 'nullable|integer|min:1|max:50',
            'accounting_access' => 'nullable|boolean',
        ];
        if ($request->input('role') === 'dean') {
            $rules['department_id'] = 'required|integer|exists:departments,id';
        }
        $request->validate($rules);

        $role = $request->input('role');
        $facultyType = $request->input('faculty_type');
        $maxUnits = $request->filled('max_units')
            ? (int) $request->input('max_units')
            : ($facultyType === 'permanent' ? 24 : null);

        $attrs = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role,
            'department_id' => $role === 'dean' && $request->filled('department_id') ? (int) $request->department_id : null,
            'faculty_type' => $role === 'dean' ? $facultyType : null,
            'program_scope' => $role === 'dean' ? $request->input('program_scope') : null,
            'max_units' => $role === 'dean' ? $maxUnits : null,
            'assigned_units' => 0,
            'accounting_access' => $request->boolean('accounting_access'),
        ];

        User::create($attrs);

        return back()->with('success', 'DCOMC account created successfully!');
    }

    // CREATE: Save a new user to the database (legacy; prefer storeStudent / storeDcomc)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:' . implode(',', User::roles()),
            'department_id' => 'nullable|integer|exists:departments,id',
            'faculty_type' => 'nullable|in:permanent,cos,part-time',
            'program_scope' => 'nullable|string|max:255',
            'max_units' => 'nullable|integer|min:1|max:50',
            'accounting_access' => 'nullable|boolean',
        ]);

        $facultyType = $request->input('faculty_type');
        $maxUnits = $request->filled('max_units')
            ? (int) $request->input('max_units')
            : ($facultyType === 'permanent' ? 24 : null);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'department_id' => $request->filled('department_id') ? (int) $request->department_id : null,
            'faculty_type' => $facultyType,
            'program_scope' => $request->input('program_scope'),
            'max_units' => $maxUnits,
            'assigned_units' => 0,
            'accounting_access' => $request->boolean('accounting_access'),
        ]);

        return back()->with('success', 'Account created successfully!');
    }

    // UPDATE: Save changes to an existing user
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|string|in:' . implode(',', User::roles()),
            'department_id' => 'nullable|integer|exists:departments,id',
            'faculty_type' => 'nullable|in:permanent,cos,part-time',
            'program_scope' => 'nullable|string|max:255',
            'max_units' => 'nullable|integer|min:1|max:50',
            'accounting_access' => 'nullable|boolean',
        ]);

        if ($user->id === Auth::id() && $request->role !== $user->role) {
            return back()->withErrors([
                'role' => 'You cannot change your own role while logged in. Ask another authorized account to do this.',
            ]);
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;
        $user->department_id = $request->filled('department_id') ? (int) $request->department_id : null;
        $user->faculty_type = $request->input('faculty_type');
        $user->program_scope = $request->input('program_scope');
        $user->max_units = $request->filled('max_units')
            ? (int) $request->input('max_units')
            : ($user->faculty_type === 'permanent' ? 24 : $user->max_units);
        $user->accounting_access = $request->boolean('accounting_access');

        // Only update password if they typed a new one
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return back()->with('success', 'Account updated successfully!');
    }

    // DELETE: Remove a user entirely
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->withErrors([
                'account' => 'You cannot delete your own currently logged-in account.',
            ]);
        }

        // Remove enrollment form responses so no orphan records remain (e.g. "Unknown" on registrar Responses).
        FormResponse::where('user_id', $user->id)->delete();

        $user->delete();

        return back()->with('success', 'Account deleted successfully!');
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
        if (! User::where('email', $candidate)->exists()) {
            return $candidate;
        }
        $suffix = preg_replace('/^DCOMC-\d+-/', '', $schoolId);

        return $base . $suffix . '@dcomc.edu.ph';
    }

    public function studentStatus(Request $request)
    {
        // #region agent log
        file_put_contents(base_path('debug-3083bc.log'), json_encode([
            'sessionId' => '3083bc',
            'id' => 'log_' . time(),
            'timestamp' => time() * 1000,
            'location' => 'AdminAccountController.php:studentStatus',
            'message' => 'studentStatus accessed',
            'data' => [
                'route' => request()->route()->getName(),
            ],
            'hypothesisId' => 'H-A'
        ]) . "\n", FILE_APPEND);
        // #endregion
        $filters = [
            'student' => trim($request->string('student')->toString()),
            'level' => trim($request->string('level')->toString()),
            'program' => trim($request->string('program')->toString()),
            'block' => trim($request->string('block')->toString()),
            'shift' => trim($request->string('shift')->toString()),
            'process_status' => trim($request->string('process_status')->toString()),
        ];

        $selectedId = AcademicCalendarService::getSelectedSchoolYearId();
        $activeId = AcademicCalendarService::getActiveSchoolYearId();
        $schoolYearIds = array_values(array_unique(array_filter([$selectedId, $activeId])));

        $formResponseScope = function ($q) use ($schoolYearIds) {
            $q->whereHas('enrollmentForm', function ($ef) use ($schoolYearIds) {
                if (count($schoolYearIds) > 0) {
                    $ef->whereIn('school_year_id', $schoolYearIds);
                } else {
                    $ef->whereNull('school_year_id');
                }
            });
        };

        $students = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->with(['block', 'formResponses' => fn ($q) => $q->whereHas('enrollmentForm', function ($ef) use ($schoolYearIds) {
                if (count($schoolYearIds) > 0) {
                    $ef->whereIn('school_year_id', $schoolYearIds);
                } else {
                    $ef->whereNull('school_year_id');
                }
            })->with('enrollmentForm')])
            ->whereHas('formResponses', $formResponseScope)
            ->when($filters['student'], function ($query, $value) {
                $query->where(function ($inner) use ($value) {
                    $inner->where('name', 'like', "%{$value}%")
                        ->orWhere('first_name', 'like', "%{$value}%")
                        ->orWhere('last_name', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%")
                        ->orWhere('school_id', 'like', "%{$value}%");
                });
            })
            ->when($filters['level'], function ($q, $v) {
                $q->where(function ($inner) use ($v) {
                    $inner->where('year_level', $v)
                        ->orWhereHas('block', fn ($b) => $b->where('year_level', $v));
                });
            })
            ->when($filters['program'], function ($q, $v) {
                $q->where(function ($inner) use ($v) {
                    $inner->where('course', $v)
                        ->orWhereHas('block', fn ($b) => $b->where('program', $v));
                });
            })
            ->when($filters['shift'], fn ($q, $v) => $q->where('shift', $v))
            ->when($filters['block'], fn ($q, $v) => $q->where('block_id', $v))
            ->when($filters['process_status'], function ($q, $v) use ($schoolYearIds) {
                $q->whereHas('formResponses', function ($fr) use ($v, $schoolYearIds) {
                    $fr->where('process_status', $v)->whereHas('enrollmentForm', function ($ef) use ($schoolYearIds) {
                        if (count($schoolYearIds) > 0) {
                            $ef->whereIn('school_year_id', $schoolYearIds);
                        } else {
                            $ef->whereNull('school_year_id');
                        }
                    });
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // Program/Course: from programs table + users + blocks + majors so dropdown is complete
        $availableCourses = collect()
            ->merge(\App\Models\Program::query()->orderBy('program_name')->pluck('program_name'))
            ->merge(User::query()->where('role', User::ROLE_STUDENT)->whereNotNull('course')->distinct()->pluck('course'))
            ->merge(\App\Models\Block::query()->whereNotNull('program')->distinct()->pluck('program'))
            ->merge(\App\Models\Major::query()->where('is_active', true)->distinct()->pluck('program'))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $yearLevels = \App\Models\AcademicYearLevel::where('is_active', true)->orderBy('name')->pluck('name');
        $semesters = \App\Models\AcademicSemester::where('is_active', true)->orderBy('name')->pluck('name');
        $majorsByProgram = \App\Models\Major::majorsByProgram();

        // Same blocks as Block Management so dropdown is never empty when blocks exist
        $blocks = \App\Models\Block::query()
            ->orderBy('program')
            ->orderBy('year_level')
            ->orderBy('semester')
            ->orderBy('code')
            ->get();

        $schoolYears = \App\Models\SchoolYear::orderByDesc('start_year')->pluck('label')->values();

        return view('dashboards.admin-student-status', compact('students', 'filters', 'availableCourses', 'yearLevels', 'semesters', 'majorsByProgram', 'blocks', 'schoolYears'));
    }

    /**
     * School year label (e.g. 2024-2025) for a given date based on when they enrolled.
     * June–May: June 2024 -> 2024-2025; Jan 2024 -> 2023-2024.
     */
    public static function schoolYearLabelForDate(?\Carbon\Carbon $date): ?string
    {
        if (! $date) {
            return null;
        }
        $y = (int) $date->format('Y');
        $m = (int) $date->format('n');
        if ($m >= 6) {
            return $y . '-' . ($y + 1);
        }
        return ($y - 1) . '-' . $y;
    }

    public function updateStudentStatusRecord(Request $request, $id)
    {
        $student = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->findOrFail($id);

        $yearLevelOptions = \App\Models\AcademicYearLevel::where('is_active', true)->pluck('name')->all();
        $semesterOptions = \App\Models\AcademicSemester::where('is_active', true)->pluck('name')->all();
        $programOptions = collect()
            ->merge(\App\Models\Program::query()->orderBy('program_name')->pluck('program_name'))
            ->merge(User::query()->where('role', User::ROLE_STUDENT)->whereNotNull('course')->distinct()->pluck('course'))
            ->merge(\App\Models\Block::query()->whereNotNull('program')->distinct()->pluck('program'))
            ->merge(\App\Models\Major::query()->where('is_active', true)->distinct()->pluck('program'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        // #region agent log
        file_put_contents(base_path('debug-3083bc.log'), json_encode([
            'sessionId' => '3083bc',
            'id' => 'log_' . time() . '_before_validate',
            'timestamp' => time() * 1000,
            'location' => 'AdminAccountController.php:updateStudentStatusRecord',
            'message' => 'Validating request data',
            'data' => [
                'request_all' => $request->all(),
                'yearLevelOptions' => $yearLevelOptions,
                'semesterOptions' => $semesterOptions,
            ],
            'hypothesisId' => 'H-B'
        ]) . "\n", FILE_APPEND);
        // #endregion

        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $student->id,
                'school_id' => 'nullable|string|max:255',
                'course' => array_filter(['required', 'string', 'max:255', count($programOptions) > 0 ? \Illuminate\Validation\Rule::in($programOptions) : null]),
                'major' => 'nullable|string|max:255',
                'year_level' => ['required', 'string', 'max:255', \Illuminate\Validation\Rule::in($yearLevelOptions)],
                'semester' => ['required', 'string', 'max:255', \Illuminate\Validation\Rule::in($semesterOptions)],
                'school_year' => ['nullable', 'string', 'max:50'],
                'block_id' => 'nullable|exists:blocks,id',
                'shift' => 'nullable|in:day,night',
                'student_type' => 'nullable|in:Student,Freshman,Regular,Shifter,Transferee,Returnee,Irregular',
                'previous_program' => 'nullable|string|max:255',
                'student_status' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:255',
                'gender' => 'nullable|in:Male,Female',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // #region agent log
            file_put_contents(base_path('debug-3083bc.log'), json_encode([
                'sessionId' => '3083bc',
                'id' => 'log_' . time() . '_validation_error',
                'timestamp' => time() * 1000,
                'location' => 'AdminAccountController.php:updateStudentStatusRecord',
                'message' => 'Validation failed',
                'data' => [
                    'errors' => $e->errors()
                ],
                'hypothesisId' => 'H-B'
            ]) . "\n", FILE_APPEND);
            // #endregion
            throw $e;
        }

        $program = $validated['course'] ?? $student->course;
        $isSecondary = stripos($program, 'Bachelor of Secondary Education') !== false;
        $isElementary = stripos($program, 'Bachelor of Elementary Education') !== false;

        if ($isSecondary && empty($validated['major'])) {
            return back()
                ->withErrors(['major' => 'Major is required for secondary education students.'])
                ->withInput();
        }

        if ($isElementary) {
            $validated['major'] = null;
        }

        // When a block is assigned, sync course/year_level/semester from block and enforce capacity
        if (! empty($validated['block_id'])) {
            $block = \App\Models\Block::find($validated['block_id']);
            if ($block) {
                $currentCount = $block->currentCountForSchoolYear($validated['school_year'] ?? null);
                if ($student->block_id != $block->id && $currentCount >= $block->effectiveMaxCapacity()) {
                    return back()
                        ->withErrors(['block_id' => 'Selected block has reached maximum capacity (' . $block->effectiveMaxCapacity() . '). Choose another block or increase capacity in Settings.'])
                        ->withInput();
                }
                $validated['course'] = $block->program ?? $validated['course'];
                $validated['year_level'] = $block->year_level ?? $validated['year_level'];
                $validated['semester'] = $block->semester ?? $validated['semester'];
            }
        }

        $validated['name'] = trim(($validated['first_name'] ?? '') . ' ' . ($validated['middle_name'] ?? '') . ' ' . ($validated['last_name'] ?? ''));
        if (array_key_exists('school_year', $validated)) {
            $validated['school_year'] = $validated['school_year'] ?: null;
        }
        $validated['status_color'] = match ($validated['student_type'] ?? null) {
            'Irregular' => 'yellow',
            'Returnee' => 'blue',
            'Transferee' => 'green',
            default => null,
        };
        if (($validated['student_type'] ?? null) !== 'Irregular') {
            $validated['previous_program'] = null;
        }

        $student->update($validated);

        $redirectRoute = request()->routeIs('registrar.*')
            ? (request('redirect') === 'students-explorer' ? 'registrar.students-explorer' : 'registrar.student-status')
            : 'admin.student-status';

        return redirect()->route($redirectRoute)->with('success', 'Student record updated successfully.');
    }

    public function enrollApplication($id, EnrollmentApprovalService $enrollmentApprovalService)
    {
        $application = FormResponse::query()
            ->forSelectedSchoolYear()
            ->with(['user', 'enrollmentForm', 'preferredBlock', 'assignedBlock'])
            ->findOrFail($id);

        $result = $enrollmentApprovalService->approve($application);

        if (! ($result['success'] ?? false)) {
            return back()->withErrors(['error' => $result['message'] ?? 'Enrollment approval failed.']);
        }

        return back()->with('success', 'Application enrolled. Student has been promoted to the destination year/semester.');
    }

    public function rejectApplication($id)
    {
        $application = FormResponse::query()->forSelectedSchoolYear()->findOrFail($id);

        $application->update([
            'approval_status' => 'rejected',
            'process_status' => 'rejected',
            'process_notes' => 'Enrollment rejected by '.Auth::user()->name,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Application rejected.');
    }

    public function deleteApplication($id)
    {
        $application = FormResponse::query()->forSelectedSchoolYear()->findOrFail($id);
        $application->delete();

        return back()->with('success', 'Application deleted. Student can re-open and re-submit enrollment form.');
    }

    public function markNeedsCorrection($id)
    {
        $application = FormResponse::query()->forSelectedSchoolYear()->findOrFail($id);

        $application->update([
            'approval_status' => 'pending',
            'process_status' => 'needs_correction',
            'process_notes' => 'Needs correction: Please coordinate with registrar.',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Application marked as needs correction.');
    }
}