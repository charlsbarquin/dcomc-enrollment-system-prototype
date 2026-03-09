<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Status Records - DCOMC</title>
    @include('layouts.partials.offline-assets')
    <style>
        .font-heading { font-family: 'Figtree', sans-serif; }
        .font-data { font-family: 'Roboto', sans-serif; }
        .hero-gradient { background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 50%, #60A5FA 100%); }
        .forms-canvas { background: #f3f4f6; }
        .input-dcomc-focus:focus { outline: none; border-color: #1E40AF; box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.2); }
        .pill-status-green { background: #059669; color: #fff; }
        .pill-status-orange { background: #d97706; color: #fff; }
        .pill-status-red { background: #dc2626; color: #fff; }
    </style>
</head>
@php
    $isRegistrarView = request()->routeIs('registrar.*');
    $isStaffView = request()->routeIs('staff.*');
    $updateRouteTemplate = $isRegistrarView
        ? route('registrar.student-status.update-record', ['id' => '__ID__'])
        : ($isStaffView ? route('staff.student-status.update-record', ['id' => '__ID__']) : route('admin.student-status.update-record', ['id' => '__ID__']));
    $statusRoute = $isRegistrarView ? route('registrar.student-status') : ($isStaffView ? route('staff.student-status') : route('admin.student-status'));
    $reportsRoute = $isRegistrarView ? route('registrar.reports') : ($isStaffView ? route('staff.reports') : route('admin.reports'));
@endphp
<body class="dashboard-wrap bg-[#F1F5F9] min-h-screen flex text-gray-800 font-data">
    @if(request()->routeIs('admin.*'))
    @include('dashboards.partials.admin-loading-bar')
    @endif

    @include('dashboards.partials.role-sidebar')

    <main class="flex-1 flex flex-col min-w-0" role="main" tabindex="-1">
        <div class="w-full p-6 md:p-8 forms-canvas">
            <div class="w-full">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-data shadow-sm">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm font-data">
                        <ul class="list-disc pl-5 mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif

                {{-- Hero Banner --}}
                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Student Status Records</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Track and manage student academic standings, promotion history, and enrollment eligibility.</p>
                        </div>
                        <a href="{{ $reportsRoute }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-base font-bold text-[#1E40AF] bg-white hover:bg-gray-100 no-underline transition-colors shrink-0 font-heading">Generate Report</a>
                    </div>
                </section>

                {{-- Status Filter Card --}}
                <form method="GET" action="{{ $statusRoute }}" class="bg-white shadow-2xl rounded-xl border border-gray-200 px-6 py-5 mb-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label for="filter-level" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Year Level</label>
                            <select name="level" id="filter-level" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus">
                                <option value="">All Levels</option>
                                @foreach($yearLevels ?? [] as $level)
                                    <option value="{{ $level }}" {{ ($filters['level'] ?? '') === $level ? 'selected' : '' }}>{{ $level }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="filter-semester" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Semester</label>
                            <select name="semester" id="filter-semester" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus">
                                <option value="">All</option>
                                @foreach($semesters ?? [] as $sem)
                                    <option value="{{ $sem }}" {{ ($filters['semester'] ?? '') === $sem ? 'selected' : '' }}>{{ $sem }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="filter-status" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Status</label>
                            <select name="process_status" id="filter-status" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus">
                                <option value="">All statuses</option>
                                <option value="pending" {{ ($filters['process_status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="needs_correction" {{ ($filters['process_status'] ?? '') === 'needs_correction' ? 'selected' : '' }}>Needs Correction</option>
                                <option value="approved" {{ ($filters['process_status'] ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="scheduled" {{ ($filters['process_status'] ?? '') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                <option value="completed" {{ ($filters['process_status'] ?? '') === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="rejected" {{ ($filters['process_status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>
                        <div>
                            <label for="filter-student" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Search</label>
                            <input type="text" name="student" id="filter-student" value="{{ $filters['student'] ?? '' }}" placeholder="Name, email, or ID" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus">
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 mt-4">
                        <button type="submit" class="px-4 py-2.5 rounded-lg text-sm font-semibold bg-[#1E40AF] text-white hover:bg-[#1D3A8A] font-data">Apply</button>
                        <a href="{{ $statusRoute }}" class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 no-underline font-data">Reset</a>
                    </div>
                </form>

                {{-- Detailed Status Table --}}
                <div class="bg-white shadow-2xl rounded-xl overflow-hidden border border-gray-200">
                    <div class="bg-[#1E40AF] px-6 py-4">
                        <h2 class="font-heading text-lg font-bold text-white">Status Records</h2>
                    </div>
                    <div class="overflow-x-auto admin-table-wrap">
                        <table class="w-full text-sm font-data" role="grid" aria-label="Student status records">
                            <thead class="table-header-dcomc">
                                <tr>
                                    <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-white border-0">Student ID</th>
                                    <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-white border-0">Full Name</th>
                                    <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-white border-0">Year Level</th>
                                    <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-white border-0">Academic Status</th>
                                    <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-white border-0">Remarks</th>
                                    <th scope="col" class="py-3 px-4 text-right font-heading font-bold text-white border-0">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse($students ?? collect() as $student)
                                    @php
                                        $latestResponse = $student->formResponses->sortByDesc('created_at')->first();
                                        $remarks = $latestResponse->process_status ?? $latestResponse->process_notes ?? '—';
                                        $statusColor = $student->status_color ?? '';
                                        $studentType = $student->student_type ?? 'N/A';
                                        if ($statusColor === 'green' || in_array(strtolower($studentType), ['regular', 'transferee', 'promoted'], true)) {
                                            $pillClass = 'pill-status-green';
                                            $statusLabel = $studentType ?: 'Regular / Promoted';
                                        } elseif ($statusColor === 'yellow' || in_array(strtolower($studentType), ['irregular', 'shifter'], true)) {
                                            $pillClass = 'pill-status-orange';
                                            $statusLabel = $studentType ?: 'Irregular / Warning';
                                        } elseif ($statusColor === 'red' || in_array(strtolower($studentType), ['dismissed', 'probation'], true)) {
                                            $pillClass = 'pill-status-red';
                                            $statusLabel = $studentType ?: 'Dismissed / Probation';
                                        } else {
                                            $pillClass = 'bg-gray-100 text-gray-800';
                                            $statusLabel = $studentType;
                                        }
                                    @endphp
                                    <tr class="hover:bg-blue-50/50 transition-colors">
                                        <td class="py-4 px-4 font-data text-gray-700">{{ $student->school_id ?? '—' }}</td>
                                        <td class="py-4 px-4 font-heading font-medium text-gray-900">{{ $student->name ?? '—' }}</td>
                                        <td class="py-4 px-4 font-data text-gray-700">{{ $student->resolved_year_level ?? $student->year_level ?? 'N/A' }}</td>
                                        <td class="py-4 px-4">
                                            <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold {{ $pillClass }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td class="py-4 px-4 font-data text-gray-600">{{ $remarks }}</td>
                                        <td class="py-4 px-4 text-right">
                                            @php
                                                $enrollmentSchoolYear = \App\Http\Controllers\AdminAccountController::schoolYearLabelForDate($student->created_at);
                                                $studentPayload = [
                                                    'id' => $student->id,
                                                    'school_id' => $student->school_id,
                                                    'first_name' => $student->first_name,
                                                    'middle_name' => $student->middle_name,
                                                    'last_name' => $student->last_name,
                                                    'email' => $student->email,
                                                    'course' => $student->course,
                                                    'major' => $student->major,
                                                    'year_level' => $student->year_level,
                                                    'semester' => $student->semester,
                                                    'school_year' => $student->school_year,
                                                    'enrollment_school_year' => $enrollmentSchoolYear,
                                                    'block_id' => $student->block_id,
                                                    'shift' => $student->shift,
                                                    'student_type' => $student->student_type,
                                                    'previous_program' => $student->previous_program,
                                                    'phone' => $student->phone,
                                                    'gender' => $student->gender,
                                                ];
                                            @endphp
                                            <button type="button" data-student='@json($studentPayload)' onclick="openStudentEdit(this)" class="text-[#1E40AF] hover:text-[#1D3A8A] font-semibold font-data">View / Edit</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="p-0">
                                            @include('dashboards.partials.admin-empty-state', [
                                                'title' => 'No student records found',
                                                'text' => 'Ensure an active school year is set and students have submitted an enrollment form for that year.',
                                            ])
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(isset($students) && $students->isNotEmpty())
                    <div class="px-6 py-3 border-t border-gray-200 flex justify-end font-data text-sm text-gray-600">
                        Showing {{ $students->count() }} student(s)
                        @if(method_exists($students, 'links'))
                        <div class="admin-pagination mt-2">{{ $students->links() }}</div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </main>

    {{-- Edit Student Record modal --}}
    <div id="studentEditModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden border border-gray-200">
            <div class="px-6 py-4 bg-[#1E40AF] text-white flex justify-between items-center">
                <h3 class="font-heading font-semibold text-lg">Edit Student Record</h3>
                <button type="button" onclick="closeStudentEdit()" class="text-white hover:text-white/80 text-2xl leading-none">&times;</button>
            </div>
            <form id="studentEditForm" method="POST" action="" class="p-6 overflow-y-auto max-h-[calc(90vh-130px)]">
                @csrf
                @method('PATCH')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div><label class="block mb-1 font-semibold font-heading">School ID</label><input id="student_school_id" name="school_id" class="w-full border border-gray-300 rounded-lg p-2 font-data input-dcomc-focus"></div>
                    <div><label class="block mb-1 font-semibold font-heading">Email</label><input id="student_email" name="email" class="w-full border border-gray-300 rounded-lg p-2 font-data input-dcomc-focus" required></div>
                    <div><label class="block mb-1 font-semibold font-heading">First Name</label><input id="student_first_name" name="first_name" class="w-full border border-gray-300 rounded-lg p-2 font-data input-dcomc-focus" required></div>
                    <div><label class="block mb-1 font-semibold font-heading">Middle Name</label><input id="student_middle_name" name="middle_name" class="w-full border border-gray-300 rounded-lg p-2 font-data input-dcomc-focus"></div>
                    <div><label class="block mb-1 font-semibold font-heading">Last Name</label><input id="student_last_name" name="last_name" class="w-full border border-gray-300 rounded-lg p-2 font-data input-dcomc-focus" required></div>
                    <div><label class="block mb-1 font-semibold font-heading">Phone</label><input id="student_phone" name="phone" class="w-full border border-gray-300 rounded-lg p-2 font-data input-dcomc-focus"></div>
                    <div>
                        <label class="block mb-1 font-semibold font-heading">Program / Course</label>
                        <select id="student_course" name="course" class="w-full border border-gray-300 rounded-lg p-2 bg-white font-data input-dcomc-focus" required>
                            <option value="">— Select —</option>
                            @foreach($availableCourses ?? [] as $course)
                                <option value="{{ $course }}">{{ $course }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="student_major_wrap" class="hidden">
                        <label class="block mb-1 font-semibold font-heading">Major</label>
                        <select id="student_major" name="major" class="w-full border border-gray-300 rounded-lg p-2 bg-white font-data input-dcomc-focus">
                            <option value="">— None —</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold font-heading">Year Level</label>
                        <select id="student_year_level" name="year_level" class="w-full border border-gray-300 rounded-lg p-2 bg-white font-data input-dcomc-focus" required>
                            <option value="">— Select —</option>
                            @foreach($yearLevels ?? [] as $level)
                                <option value="{{ $level }}">{{ $level }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold font-heading">Semester</label>
                        <select id="student_semester" name="semester" class="w-full border border-gray-300 rounded-lg p-2 bg-white font-data input-dcomc-focus" required>
                            <option value="">— Select —</option>
                            @foreach($semesters ?? [] as $sem)
                                <option value="{{ $sem }}">{{ $sem }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold font-heading">School Year</label>
                        <select id="student_school_year" name="school_year" class="w-full border border-gray-300 rounded-lg p-2 bg-white font-data input-dcomc-focus">
                            <option value="">— Select —</option>
                            @foreach($schoolYears ?? collect() as $sy)
                                <option value="{{ $sy }}">{{ $sy }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold font-heading">Block</label>
                        <select id="student_block_id" name="block_id" class="w-full border border-gray-300 rounded-lg p-2 bg-white font-data input-dcomc-focus">
                            <option value="">Unassigned</option>
                            @foreach($blocks ?? [] as $block)
                                <option value="{{ $block->id }}">{{ $block->code ?? $block->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold font-heading">Shift</label>
                        <select id="student_shift" name="shift" class="w-full border border-gray-300 rounded-lg p-2 bg-white font-data input-dcomc-focus">
                            <option value="">N/A</option>
                            <option value="day">Day</option>
                            <option value="night">Night</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold font-heading">Student Type</label>
                        <select id="student_type" name="student_type" class="w-full border border-gray-300 rounded-lg p-2 bg-white font-data input-dcomc-focus">
                            <option value="">N/A</option>
                            <option value="Freshman">Freshman</option>
                            <option value="Regular">Regular</option>
                            <option value="Transferee">Transferee</option>
                            <option value="Returnee">Returnee</option>
                            <option value="Irregular">Irregular</option>
                        </select>
                    </div>
                    <div id="student_previous_program_wrap" class="hidden md:col-span-2">
                        <label class="block mb-1 font-semibold font-heading">Previous program (before transfer)</label>
                        <input type="text" id="student_previous_program" name="previous_program" class="w-full border border-gray-300 rounded-lg p-2 font-data input-dcomc-focus">
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold font-heading">Gender</label>
                        <select id="student_gender" name="gender" class="w-full border border-gray-300 rounded-lg p-2 bg-white font-data input-dcomc-focus">
                            <option value="">N/A</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" onclick="closeStudentEdit()" class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 font-semibold font-data">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-[#1E40AF] text-white font-semibold font-data">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const studentUpdateRouteTemplate = @js($updateRouteTemplate);
        const majorsByProgram = @json($majorsByProgram ?? []);

        function isSecondaryEducation(program) {
            if (!program) return false;
            return /secondary\s*education/i.test(program);
        }

        function updateMajorDropdown(program, selectedMajor) {
            const wrap = document.getElementById('student_major_wrap');
            const select = document.getElementById('student_major');
            const valueToKeep = selectedMajor !== undefined ? selectedMajor : select.value;
            select.innerHTML = '<option value="">— None —</option>';
            if (isSecondaryEducation(program)) {
                wrap.classList.remove('hidden');
                const majors = majorsByProgram[program] || [];
                majors.forEach(function(m) {
                    const opt = document.createElement('option');
                    opt.value = m;
                    opt.textContent = m;
                    if (m === valueToKeep) opt.selected = true;
                    select.appendChild(opt);
                });
            } else {
                wrap.classList.add('hidden');
                select.value = '';
            }
        }

        document.getElementById('student_course').addEventListener('change', function() {
            updateMajorDropdown(this.value);
        });

        function togglePreviousProgramVisibility(isIrregular) {
            const wrap = document.getElementById('student_previous_program_wrap');
            wrap.classList.toggle('hidden', !isIrregular);
            if (!isIrregular) document.getElementById('student_previous_program').value = '';
        }

        document.getElementById('student_type').addEventListener('change', function() {
            togglePreviousProgramVisibility(this.value === 'Irregular');
        });

        function closeStudentEdit() {
            document.getElementById('studentEditModal').classList.add('hidden');
            document.getElementById('studentEditModal').classList.remove('flex');
        }

        function openStudentEdit(button) {
            const raw = button.getAttribute('data-student');
            if (!raw) return;
            let student;
            try { student = JSON.parse(raw); } catch (e) { return; }

            document.getElementById('studentEditForm').action = studentUpdateRouteTemplate.replace('__ID__', student.id);
            document.getElementById('student_school_id').value = student.school_id || '';
            document.getElementById('student_email').value = student.email || '';
            document.getElementById('student_first_name').value = student.first_name || '';
            document.getElementById('student_middle_name').value = student.middle_name || '';
            document.getElementById('student_last_name').value = student.last_name || '';
            document.getElementById('student_phone').value = student.phone || '';
            document.getElementById('student_course').value = student.course || '';
            document.getElementById('student_year_level').value = student.year_level || '';
            document.getElementById('student_semester').value = student.semester || '';
            const schoolYearSelect = document.getElementById('student_school_year');
            const schoolYearValue = student.school_year || student.enrollment_school_year || '';
            if (schoolYearSelect && schoolYearValue && !Array.from(schoolYearSelect.options).some(function(o) { return o.value === schoolYearValue; })) {
                const opt = document.createElement('option');
                opt.value = schoolYearValue;
                opt.textContent = schoolYearValue + ' (enrollment)';
                schoolYearSelect.appendChild(opt);
            }
            if (schoolYearSelect) schoolYearSelect.value = schoolYearValue || '';
            document.getElementById('student_block_id').value = student.block_id || '';
            document.getElementById('student_shift').value = student.shift || '';
            document.getElementById('student_type').value = student.student_type || '';
            document.getElementById('student_gender').value = student.gender || '';
            document.getElementById('student_previous_program').value = student.previous_program || '';
            togglePreviousProgramVisibility(student.student_type === 'Irregular');
            updateMajorDropdown(student.course || '', student.major || '');

            document.getElementById('studentEditModal').classList.remove('hidden');
            document.getElementById('studentEditModal').classList.add('flex');
        }
    </script>
</body>
</html>
