<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Accounts - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
</head>
<body class="dashboard-wrap bg-[#F1F5F9]">
    @php
        $isRegistrarView = auth()->user()?->role === 'registrar';
        $dashboardUrl = $isRegistrarView ? route('registrar.dashboard') : route('admin.dashboard');
        $studentStatusUrl = $isRegistrarView ? route('registrar.student-status') : route('admin.student-status');
        $accountsUrl = route('admin.accounts');
        $accountsStoreDcomcUrl = route('admin.accounts.store.dcomc');
        $accountsUpdateRouteTemplate = route('admin.accounts.update', ['id' => '__ID__']);
    @endphp

    @include('dashboards.partials.admin-sidebar')
    @include('dashboards.partials.admin-loading-bar')

    <main class="dashboard-main flex flex-col overflow-hidden">
        <section class="hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mx-4 mt-4 mb-4">
            <div class="d-flex flex-column flex-md-row align-items-start justify-content-between gap-4">
                <div>
                    <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Account Management</h1>
                    <p class="text-white/90 text-sm sm:text-base font-data mb-0">Manage user accounts, roles, and DCOMC/Student creation.</p>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 shrink-0">
                @if(!$isRegistrarView && session('role_switch.active'))
                    @php
                        $mirrorLabel = strtoupper(session('role_switch.as_role') ?? '');
                        if ($mirrorLabel === 'DEAN' && !empty(session('role_switch.department_id'))) {
                            $mirrorDept = \App\Models\Department::find(session('role_switch.department_id'));
                            $mirrorLabel .= $mirrorDept ? ' (' . $mirrorDept->name . ')' : '';
                        }
                    @endphp
                    <span class="badge bg-warning text-dark">Mirroring: {{ $mirrorLabel }}</span>
                    <form method="POST" action="{{ route('admin.role-switch.stop') }}" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-warning btn-sm">Switch Back</button>
                    </form>
                @elseif(!$isRegistrarView)
                    @php $deanDepts = \App\Models\Department::whereIn('name', [\App\Models\Department::NAME_EDUCATION, \App\Models\Department::NAME_ENTREPRENEURSHIP])->orderBy('name')->get(); @endphp
                    <form method="POST" action="{{ route('admin.role-switch.start') }}" class="d-inline-flex flex-wrap align-items-center gap-2">
                        @csrf
                        <select name="role" id="accRoleSelect" class="form-select form-select-sm" style="width: auto;">
                            <option value="" selected>Select role to mirror...</option>
                            <option value="student">Student</option>
                            <option value="registrar">Registrar</option>
                            <option value="staff">Staff</option>
                            <option value="unifast">UniFAST</option>
                            <option value="dean">Dean</option>
                        </select>
                        <div id="accDeanChoice" class="d-none d-flex align-items-center gap-2">
                            <label for="accDeanDept" class="small text-white/90 mb-0">as</label>
                            <select name="department_id" id="accDeanDept" class="form-select form-select-sm" style="width: auto;">
                                <option value="">— Educ / Entrep —</option>
                                @foreach($deanDepts as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-light btn-sm">Switch to Dean</button>
                        </div>
                    </form>
                    <script>
                        (function() {
                            var sel = document.getElementById('accRoleSelect');
                            if (!sel) return;
                            sel.addEventListener('change', function() {
                                var v = this.value, deanChoice = document.getElementById('accDeanChoice');
                                if (v === 'dean') { deanChoice.classList.remove('d-none'); deanChoice.classList.add('d-flex'); document.getElementById('accDeanDept').required = true; }
                                else if (v) { deanChoice.classList.add('d-none'); document.getElementById('accDeanDept').required = false; document.getElementById('accDeanDept').value = ''; this.form.submit(); }
                                else { deanChoice.classList.add('d-none'); }
                            });
                        })();
                    </script>
                @endif
                    <a href="{{ route('admin.accounts.create.student') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold bg-[#1E40AF] text-white hover:bg-[#1D3A8A] no-underline transition-colors font-data">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        Create Student Account
                    </a>
                    <button type="button" onclick="openModal('dcomcModal')" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold bg-[#059669] text-white hover:bg-[#047857] no-underline transition-colors font-data">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Create DCOMC Account
                    </button>
                </div>
            </div>
        </section>

        <div class="p-4 flex-grow-1 overflow-auto">
            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <ul class="list-disc pl-5 text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-2xl rounded-xl border border-gray-200 overflow-hidden mb-4 card-dcomc-top">
                <form method="GET" action="{{ $accountsUrl }}" class="p-5">
                    <h2 class="font-heading h6 fw-bold text-gray-800 mb-4">Search &amp; Filter</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label for="student_number" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Student Number</label>
                            <input type="text" id="student_number" name="student_number" value="{{ old('student_number', $filters['student_number'] ?? '') }}" placeholder="School ID..." class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus">
                        </div>
                        <div>
                            <label for="program" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Program</label>
                            <input type="text" id="program" name="program" value="{{ old('program', $filters['program'] ?? '') }}" placeholder="Program / course..." class="w-full border border-gray-300 rounded bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" list="program-list">
                            @if(isset($programOptions) && $programOptions->isNotEmpty())
                                <datalist id="program-list">
                                    @foreach($programOptions as $p)
                                        <option value="{{ $p }}">{{ $p }}</option>
                                    @endforeach
                                </datalist>
                            @endif
                        </div>
                        <div>
                            <label for="school_year" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">School Year</label>
                            <select id="school_year" name="school_year" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus">
                                <option value="">All</option>
                                @foreach($schoolYears ?? [] as $sy)
                                    <option value="{{ $sy }}" {{ ($filters['school_year'] ?? '') === $sy ? 'selected' : '' }}>{{ $sy }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="year_level" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Year Level</label>
                            <select id="year_level" name="year_level" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus">
                                <option value="">All</option>
                                @foreach($yearLevels ?? [] as $yl)
                                    <option value="{{ $yl }}" {{ ($filters['year_level'] ?? '') === $yl ? 'selected' : '' }}>{{ $yl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 flex-wrap">
                        <div>
                            <label for="semester" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Semester</label>
                            <select id="semester" name="semester" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus">
                                <option value="">All</option>
                                @foreach($semesters ?? [] as $sem)
                                    <option value="{{ $sem }}" {{ ($filters['semester'] ?? '') === $sem ? 'selected' : '' }}>{{ $sem }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="first_name" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $filters['first_name'] ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus">
                        </div>
                        <div>
                            <label for="last_name" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $filters['last_name'] ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus">
                        </div>
                        <div>
                            <label for="status" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Status</label>
                            <select id="status" name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus">
                                <option value="">All</option>
                                @foreach(\App\Models\User::roles() as $r)
                                    <option value="{{ $r }}" {{ ($filters['status'] ?? '') === $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="folder" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Search</label>
                            <input type="text" id="folder" name="folder" value="{{ old('folder', $filters['folder'] ?? '') }}" placeholder="Search name or email..." class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus">
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-4 pt-2">
                        <a href="{{ $accountsUrl }}" class="admin-btn-secondary no-underline">Reset</a>
                        <button type="submit" class="admin-btn-primary">Apply Filters</button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden">
                <div class="bg-[#1E40AF] px-5 py-3">
                    <h2 class="font-heading text-base font-bold text-white">User accounts</h2>
                </div>
                @php $hasFilters = isset($filters) && collect($filters)->contains(fn ($v) => $v !== ''); @endphp
                @if($users->isEmpty())
                    @include('dashboards.partials.admin-empty-state', [
                        'title' => 'No accounts to display',
                        'text' => $hasFilters ? 'No accounts match your filters.' : 'No user accounts yet.',
                        'actionUrl' => $hasFilters ? $accountsUrl : null,
                        'actionLabel' => $hasFilters ? 'Clear filters' : null,
                    ])
                @else
                <div class="px-4 py-2 border-b border-gray-200 text-sm text-gray-600 font-data">
                    @if(method_exists($users, 'total'))
                    Showing {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} of {{ $users->total() }} account(s)
                    @else
                    Showing {{ $users->count() }} account(s)
                    @endif
                </div>
                <div class="overflow-x-auto admin-table-wrap">
                <table class="w-full text-left border-collapse font-data">
                    <thead class="table-header-dcomc">
                        <tr>
                            <th class="p-4 border-0 text-white font-heading font-semibold">Name</th>
                            <th class="p-4 border-0 text-white font-heading font-semibold">Email</th>
                            <th class="p-4 border-0 text-white font-heading font-semibold">Role</th>
                            <th class="p-4 border-0 text-white font-heading font-semibold">Faculty Type / Load</th>
                            <th class="p-4 border-0 text-white font-heading font-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @foreach($users as $user)
                        <tr class="hover:bg-blue-50/50 transition-colors border-b border-gray-100">
                            <td class="p-4">{{ $user->name }}</td>
                            <td class="p-4">{{ $user->email }}</td>
                            <td class="p-4 font-bold uppercase text-xs text-blue-700">{{ $user->role }}</td>
                            <td class="p-4 text-xs text-gray-700">
                                <p>{{ $user->faculty_type ? strtoupper($user->faculty_type) : 'N/A' }}</p>
                                <p>Department: {{ $user->department?->name ?? '—' }}</p>
                                <p>Scope: {{ $user->program_scope ?: 'ALL PROGRAMS' }}</p>
                                <p>Units: {{ (int) ($user->assigned_units ?? 0) }} / {{ (int) ($user->max_units ?? 0) }}</p>
                                <p>Accounting: {{ $user->accounting_access ? 'Yes' : 'No' }}</p>
                            </td>
                            <td class="p-4 text-center space-x-2">
                                <button type="button" onclick="openEditModal({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ addslashes($user->email) }}', '{{ $user->role }}', '{{ $user->faculty_type ?? '' }}', '{{ addslashes($user->program_scope ?? '') }}', '{{ $user->max_units ?? '' }}', '{{ $user->accounting_access ? 1 : 0 }}', '{{ $user->department_id ?? '' }}')" class="admin-btn-primary text-sm py-1.5 px-3">Edit</button>
                                <form action="{{ route('admin.accounts.destroy', $user->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this account?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="admin-btn-danger text-sm py-1.5 px-3">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
                @if(method_exists($users, 'links'))
                <div class="admin-pagination px-4 py-3 border-t border-gray-200">
                    {{ $users->withQueryString()->links() }}
                </div>
                @endif
                @endif
            </div>
        </div>
    </main>

    <div id="dcomcModal" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl border-t-[10px] border-t-[#1E40AF] w-full max-w-md max-h-[90vh] overflow-hidden flex flex-col">
            <div class="bg-[#1E40AF] px-6 py-4 shrink-0">
                <h3 class="font-heading text-lg font-bold text-white">Create DCOMC Account</h3>
                <p class="text-white/90 text-sm font-data mt-0.5">For dean, registrar, staff, UniFAST, or admin. Students use Create Student Account.</p>
            </div>
            <form action="{{ $accountsStoreDcomcUrl }}" method="POST" id="dcomcForm" class="flex flex-col flex-1 min-h-0 overflow-y-auto">
                @csrf
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block font-heading text-xs font-bold text-gray-600 uppercase tracking-wide mb-1.5">Full Name</label>
                        <input type="text" name="name" placeholder="Full Name" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus">
                    </div>
                    <div>
                        <label class="block font-heading text-xs font-bold text-gray-600 uppercase tracking-wide mb-1.5">Email Address</label>
                        <input type="email" name="email" placeholder="Email Address" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus">
                    </div>
                    <div>
                        <label class="block font-heading text-xs font-bold text-gray-600 uppercase tracking-wide mb-1.5">Password</label>
                        <input type="password" name="password" placeholder="Min 6 characters" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus">
                    </div>
                    <div>
                        <label for="dcomcRole" class="block font-heading text-xs font-bold text-gray-600 uppercase tracking-wide mb-1.5">Role</label>
                        <select name="role" id="dcomcRole" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus">
                            <option value="" disabled selected>Select a role…</option>
                            <option value="admin">Admin</option>
                            <option value="registrar">Registrar</option>
                            <option value="staff">Staff</option>
                            <option value="unifast">UniFAST</option>
                            <option value="dean">Dean</option>
                        </select>
                    </div>
                    <div id="dcomcDeanOnly" class="hidden space-y-4 pt-2 border-t border-gray-100">
                        <div>
                            <label for="dcomcDepartmentId" class="block font-heading text-xs font-bold text-gray-600 uppercase tracking-wide mb-1.5">Department <span class="text-red-600 normal-case">(required for Dean)</span></label>
                            <select name="department_id" id="dcomcDepartmentId" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus">
                                <option value="">— Select Department —</option>
                                @foreach($departments ?? [] as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-heading text-xs font-bold text-gray-600 uppercase tracking-wide mb-1.5">Faculty Type</label>
                            <select name="faculty_type" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus">
                                <option value="">— Optional —</option>
                                <option value="permanent">Permanent</option>
                                <option value="cos">COS</option>
                                <option value="part-time">Part-time</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-heading text-xs font-bold text-gray-600 uppercase tracking-wide mb-1.5">Program Scope (optional)</label>
                            <input type="text" name="program_scope" placeholder="Program scope" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus">
                        </div>
                        <div>
                            <label class="block font-heading text-xs font-bold text-gray-600 uppercase tracking-wide mb-1.5">Max Units (optional)</label>
                            <input type="number" name="max_units" placeholder="1–50" min="1" max="50" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus">
                        </div>
                    </div>
                    <label class="flex items-center gap-2.5 text-sm font-data text-gray-700 cursor-pointer pt-1">
                        <input type="checkbox" name="accounting_access" value="1" class="rounded border-gray-300 text-[#1E40AF] focus:ring-[#1E40AF]">
                        Enable accounting access
                    </label>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-2 shrink-0 bg-gray-50/50">
                    <button type="button" onclick="closeModal('dcomcModal')" class="admin-btn-secondary">Cancel</button>
                    <button type="submit" class="admin-btn-success">Create account</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center">
        <div class="bg-white p-6 rounded shadow-lg w-96">
            <h3 class="text-xl font-bold mb-4">Edit Account</h3>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <input type="text" id="editName" name="name" required class="w-full border p-2 mb-3 rounded">
                <input type="email" id="editEmail" name="email" required class="w-full border p-2 mb-3 rounded">
                <input type="password" name="password" placeholder="New Password (Leave blank to keep current)" class="w-full border p-2 mb-3 rounded">
                <select id="editRole" name="role" required class="w-full border p-2 mb-4 rounded">
                    <option value="student">Student</option>
                    <option value="admin">Admin</option>
                    <option value="registrar">Registrar</option>
                    <option value="staff">Staff</option>
                    <option value="unifast">UniFAST</option>
                    <option value="dean">Dean</option>
                </select>
                <label class="block text-sm text-gray-600 mb-1">Department (required for Dean)</label>
                <select id="editDepartmentId" name="department_id" class="w-full border p-2 mb-3 rounded">
                    <option value="">— None —</option>
                    @foreach($departments ?? [] as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
                <select id="editFacultyType" name="faculty_type" class="w-full border p-2 mb-3 rounded">
                    <option value="">Faculty Type (optional)</option>
                    <option value="permanent">Permanent</option>
                    <option value="cos">COS</option>
                    <option value="part-time">Part-time</option>
                </select>
                <input type="text" id="editProgramScope" name="program_scope" placeholder="Program Scope (optional)" class="w-full border p-2 mb-3 rounded">
                <input type="number" id="editMaxUnits" name="max_units" placeholder="Max Units (optional)" min="1" max="50" class="w-full border p-2 mb-3 rounded">
                <label class="flex items-center gap-2 text-sm mb-4">
                    <input type="checkbox" id="editAccountingAccess" name="accounting_access" value="1">
                    Enable accounting access
                </label>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('editModal')" class="admin-btn-secondary">Cancel</button>
                    <button type="submit" class="admin-btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
            if (id === 'dcomcModal') toggleDcomcDeanFields();
        }
        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }
        function toggleDcomcDeanFields() {
            var roleEl = document.getElementById('dcomcRole');
            var deanOnly = document.getElementById('dcomcDeanOnly');
            var deptEl = document.getElementById('dcomcDepartmentId');
            if (!deanOnly || !roleEl) return;
            if (roleEl.value === 'dean') {
                deanOnly.classList.remove('hidden');
                if (deptEl) deptEl.removeAttribute('disabled');
            } else {
                deanOnly.classList.add('hidden');
                if (deptEl) { deptEl.value = ''; deptEl.setAttribute('disabled', 'disabled'); }
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            var dcomcRole = document.getElementById('dcomcRole');
            if (dcomcRole) dcomcRole.addEventListener('change', toggleDcomcDeanFields);
        });
        function openEditModal(id, name, email, role, facultyType, programScope, maxUnits, accountingAccess, departmentId) {
            document.getElementById('editForm').action = '{{ $accountsUpdateRouteTemplate }}'.replace('__ID__', id);
            document.getElementById('editName').value = name;
            document.getElementById('editEmail').value = email;
            document.getElementById('editRole').value = role;
            document.getElementById('editFacultyType').value = facultyType || '';
            document.getElementById('editProgramScope').value = programScope || '';
            document.getElementById('editMaxUnits').value = maxUnits || '';
            document.getElementById('editAccountingAccess').checked = String(accountingAccess) === '1';
            var deptEl = document.getElementById('editDepartmentId');
            if (deptEl) deptEl.value = departmentId || '';
            openModal('editModal');
        }
    </script>
</body>
</html>