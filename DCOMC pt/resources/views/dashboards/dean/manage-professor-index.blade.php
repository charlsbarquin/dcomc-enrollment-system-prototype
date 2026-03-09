<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Professor | Dean</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
    <style>
        /* Custom searchable subject dropdown in Assign subjects modal */
        .subject-dropdown-wrap { position: relative; flex: 1; min-width: 0; }
        .subject-dropdown-input { width: 100%; background: #fff; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-size: 14px; color: #111827; outline: none; box-sizing: border-box; }
        .subject-dropdown-input:hover { border-color: #9ca3af; }
        .subject-dropdown-input:focus { border-color: #1E40AF; box-shadow: 0 0 0 2px rgba(30,64,175,0.2); }
        .subject-dropdown-input::placeholder { color: #6b7280; }
        .subject-dropdown-panel { position: absolute; left: 0; right: 0; top: 100%; margin-top: 4px; background: #fff; border: 1px solid #d1d5db; border-radius: 6px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); z-index: 100; max-height: 280px; display: none; flex-direction: column; }
        .subject-dropdown-panel.open { display: flex; }
        .subject-dropdown-list { overflow-y: auto; max-height: 280px; }
        .subject-dropdown-option { padding: 8px 12px; cursor: pointer; font-size: 14px; border-bottom: 1px solid #f3f4f6; }
        .subject-dropdown-option:hover { background: #eff6ff; }
        .subject-dropdown-option:last-child { border-bottom: none; }
        .subject-dropdown-list .subject-dropdown-option[style] { border-bottom: none; }
    </style>
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">
    @include('dashboards.partials.dean-sidebar')

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full max-w-7xl mx-auto">
                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Manage Professor</h1>
                            <p class="text-white/90 text-sm font-data">View workload, units, and overload indicators. View profile or assign subjects (popup) per professor.</p>
                        </div>
                        <a href="{{ route('dean.manage-professor.teaching-load') }}" class="inline-flex items-center px-5 py-2.5 rounded-xl text-base font-bold text-[#1E40AF] bg-white hover:bg-gray-100 no-underline transition-colors shrink-0 font-heading">Teaching Load Report</a>
                    </div>
                </section>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-data shadow-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm font-data">{{ session('error') }}</div>
            @endif

            @if(isset($department) && $department)
            <div class="bg-white shadow-2xl rounded-xl border border-gray-200 px-6 py-5 mb-6">
                <p class="text-sm font-heading font-bold text-gray-700 mb-2">Schedule by Program — professor eligibility</p>
                <form method="POST" action="{{ route('dean.manage-professor.toggle-all-subjects') }}" class="flex flex-wrap items-center gap-3">
                    @csrf
                    <label class="inline-flex items-center gap-2 cursor-pointer font-data">
                        <input type="checkbox" name="all_professors_all_subjects" value="1" {{ ($all_professors_all_subjects ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-[#1E40AF] focus:ring-[#1E40AF]">
                        <span class="text-sm text-gray-800">Set all teachers to handle all subjects</span>
                    </label>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-[#1E40AF] text-white text-sm font-medium hover:bg-[#1D3A8A] font-data">Save</button>
                    <span class="text-xs text-gray-500 w-full">When ON, every professor in your department can be chosen for any subject in Schedule by Program. When OFF, only professors assigned to each subject (via Assign subjects) appear.</span>
                </form>
            </div>
            @endif

            <div class="bg-white shadow-2xl rounded-xl overflow-hidden border border-gray-200 mb-6">
                    <div class="bg-[#1E40AF] px-6 py-4">
                        <h2 class="font-heading text-lg font-bold text-white">Professors</h2>
                    </div>
                    <table class="w-full text-sm font-data">
                        <thead class="bg-[#1E40AF]">
                            <tr>
                                <th class="p-3 text-left font-heading font-bold text-white">Professor</th>
                                <th class="p-3 text-left font-heading font-bold text-white">Employment</th>
                                <th class="p-3 text-left font-heading font-bold text-white">Gender</th>
                                <th class="p-3 text-right font-heading font-bold text-white">Total Units</th>
                                <th class="p-3 text-right font-heading font-bold text-white">Max Units</th>
                                <th class="p-3 text-center font-heading font-bold text-white">Schedule limit</th>
                                <th class="p-3 text-center font-heading font-bold text-white">Status</th>
                                <th class="p-3 text-left font-heading font-bold text-white">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($professors as $idx => $p)
                                @php
                                    $emp = strtolower($p->employment_type ?? 'cos');
                                    if ($emp === 'part-time') $emp = 'part-time';
                                @endphp
                                <tr class="professor-row border-t border-gray-100 hover:bg-blue-50/50 transition-colors" data-employment="{{ $emp }}" data-gender="{{ $p->gender ?? '' }}" data-professor-id="{{ $p->id }}">
                                <td class="p-3 font-medium">{{ $p->name }}</td>
                                <td class="p-3">{{ strtoupper($p->employment_type ?? '—') }}</td>
                                <td class="p-3">{{ $p->gender }}</td>
                                <td class="p-3 text-right">{{ $p->total_units }}</td>
                                <td class="p-3 text-right"><span class="prof-max-units" data-professor-id="{{ $p->id }}">{{ $p->max_units ?: '—' }}</span></td>
                                <td class="p-3 text-center">
                                    <span class="prof-schedule-limit text-sm" data-professor-id="{{ $p->id }}">{{ $p->schedule_selection_limit ?? '∞' }}</span>
                                </td>
                                <td class="p-3 text-center">
                                    @if($p->indicator === 'red')
                                        <span class="inline-block w-3 h-3 rounded-full bg-red-500" title="Exceeded / Overload"></span>
                                        <span class="text-red-600 text-xs ml-1">Overload</span>
                                    @elseif($p->indicator === 'yellow')
                                        <span class="inline-block w-3 h-3 rounded-full bg-yellow-500" title="Near limit"></span>
                                        <span class="text-yellow-700 text-xs ml-1">Near limit</span>
                                    @else
                                        <span class="inline-block w-3 h-3 rounded-full bg-green-500" title="Within limits"></span>
                                        <span class="text-green-700 text-xs ml-1">OK</span>
                                    @endif
                                </td>
                                <td class="p-3">
                                    <button type="button" onclick="openViewProfileModal({{ $p->id }})" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-[#1E40AF] text-white text-sm font-medium no-underline hover:bg-[#1D3A8A] font-data">View profile</button>
                                    <span class="mx-1 text-gray-400">|</span>
                                    <button type="button" onclick="openAssignSubjectsModal({{ $p->id }})" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-[#1E40AF] text-white text-sm font-medium no-underline hover:bg-[#1D3A8A] font-data">Assign subjects</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-6 text-center text-gray-500">No professors in your scope.</td>
                            </tr>
                        @endforelse
                        @if($professors->isNotEmpty())
                            <tr id="no-match-row" class="hidden" style="display: none;">
                                <td colspan="8" class="p-6 text-center text-gray-500">No professors match this filter.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-gray-500 mb-4">Green: within limits · Yellow: near limit · Red: exceeded / overload (units or time)</p>

            {{-- Tabs: Permanent, COS, Part-time. Dropdown: Male / Female (filter table) --}}
            @if($professors->isNotEmpty())
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm px-4 py-3 flex flex-wrap items-center gap-3">
                    <span class="text-sm font-medium text-gray-600">Filter:</span>
                    <div class="flex items-center gap-1 border border-gray-300 rounded overflow-hidden">
                        <button type="button" data-employment="permanent" class="filter-tab px-4 py-2 text-sm font-medium bg-blue-50 border-r border-gray-300 text-[#1E40AF] font-data">Permanent</button>
                        <button type="button" data-employment="cos" class="filter-tab px-4 py-2 text-sm font-medium bg-gray-100 text-gray-600 hover:bg-gray-200 border-r border-gray-300">COS</button>
                        <button type="button" data-employment="part-time" class="filter-tab px-4 py-2 text-sm font-medium bg-gray-100 text-gray-600 hover:bg-gray-200">Part-time</button>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <span>Gender:</span>
                        <select id="gender-filter" class="border border-gray-300 rounded px-3 py-2 text-sm">
                            <option value="">All</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </label>
                </div>
            @endif
        </div>

        {{-- View Profile popup: professor overview + COR archive (normal schedule + overload) --}}
        <div id="view-profile-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" style="display: none;" onclick="if(event.target===this) closeViewProfileModal()">
            <div class="bg-white rounded-lg shadow-xl w-[90vw] max-w-5xl min-h-[70vh] max-h-[92vh] overflow-hidden flex flex-col" onclick="event.stopPropagation()">
                <div class="p-6 border-b border-gray-200 flex-shrink-0">
                    <h3 class="text-xl font-semibold text-gray-800">View Profile</h3>
                    <div id="view-profile-overview" class="mt-3 text-sm text-gray-600 space-y-1"></div>
                </div>
                <div class="p-6 overflow-y-auto flex-1 min-h-0">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Normal Schedule (from COR Archive)</h4>
                    <div id="view-profile-normal-table" class="mb-6"></div>
                    <h4 class="text-sm font-semibold text-red-700 mb-2">Overload (5pm onwards)</h4>
                    <div id="view-profile-overload-table" class="mb-4"></div>
                </div>
                <div class="p-6 border-t border-gray-200 flex-shrink-0 flex justify-end">
                    <button type="button" onclick="closeViewProfileModal()" class="px-5 py-2.5 bg-gray-200 rounded-lg font-medium hover:bg-gray-300">Close</button>
                </div>
            </div>
        </div>

        {{-- Assign subjects popup (single modal, loaded by professor id) --}}
        <div id="assign-subjects-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" style="display: none;" onclick="if(event.target===this) closeAssignSubjectsModal()">
            <div class="bg-white rounded-lg shadow-xl w-[90vw] max-w-5xl min-h-[70vh] max-h-[92vh] overflow-hidden flex flex-col" onclick="event.stopPropagation()">
                <div class="p-6 border-b border-gray-200 flex-shrink-0">
                    <h3 class="text-xl font-semibold text-gray-800">Assign subjects</h3>
                    <div id="assign-modal-professor-details" class="mt-3 text-sm text-gray-600 space-y-1"></div>
                    <div class="mt-2 flex items-center gap-3">
                        <p id="assign-modal-units" class="font-medium text-gray-800 mr-4"></p>
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-gray-600">Max units</label>
                            <input id="assign-modal-max-units" type="number" min="0" max="99" class="w-20 border border-gray-300 rounded px-2 py-1 text-sm">
                            <button id="assign-modal-save-max-units" type="button" class="px-3 py-1 bg-[#1E40AF] text-white rounded text-sm font-medium hover:bg-[#1D3A8A]">Save</button>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-gray-600">Schedule limit</label>
                            <input id="assign-modal-schedule-limit" type="number" min="0" max="255" class="w-20 border border-gray-300 rounded px-2 py-1 text-sm">
                            <button id="assign-modal-save-schedule-limit" type="button" class="px-3 py-1 bg-[#1E40AF] text-white rounded text-sm font-medium hover:bg-[#1D3A8A]">Save</button>
                        </div>
                    </div>
                    <p id="assign-modal-total-units" class="mt-1 text-sm text-[#1E40AF] font-semibold font-data"></p>
                </div>
                <div class="p-6 overflow-y-auto flex-1 min-h-0">
                    <div id="assign-modal-assigned-subjects" class="mb-4"></div>
                    <div id="assign-modal-schedule-grid" class="mb-4"></div>
                    <div id="assign-modal-overload-grid" class="mb-4"></div>
                    <p class="text-sm font-medium text-gray-700 mb-3">Subjects (course code, description, units)</p>
                    <div id="assign-subjects-rows" class="space-y-3"></div>
                    <button type="button" id="assign-add-row-btn" class="mt-4 flex items-center gap-2 px-4 py-2.5 border-2 border-[#1E40AF] text-[#1E40AF] rounded-lg text-sm font-medium hover:bg-blue-50 font-data">
                        <span class="text-xl leading-none">+</span> Add subject
                    </button>
                    <div id="assign-max-units-warning" class="hidden mt-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm font-medium"></div>
                </div>
                <div class="p-6 border-t border-gray-200 flex-shrink-0 flex justify-end gap-3">
                    <button type="button" onclick="closeAssignSubjectsModal()" class="px-5 py-2.5 bg-gray-200 rounded-lg font-medium hover:bg-gray-300">Cancel</button>
                    <button type="button" id="assign-subjects-submit-btn" class="px-5 py-2.5 bg-[#1E40AF] text-white rounded-lg font-medium hover:bg-[#1D3A8A] font-data">Save assignments</button>
                </div>
            </div>
        </div>

        {{-- Overload status popup (one per professor with overload) --}}
        @foreach($professors as $p)
            @if($p->unit_overload || $p->time_overload)
                <div id="overload-modal-{{ $p->id }}" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" style="display: none;" onclick="if(event.target===this) closeOverloadModal({{ $p->id }})">
                    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-5">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Overload status – {{ $p->name }}</h3>
                        <div class="space-y-2 text-sm">
                            @if($p->unit_overload)
                                <p class="text-red-700 font-medium">Unit overload: Assigned {{ $p->total_units }} units (max {{ $p->max_units }}).</p>
                            @endif
                            @if($p->time_overload && !empty($p->time_overload_slots))
                                <p class="text-red-700 font-medium mt-2">Time overload (beyond 5:00 PM):</p>
                                <ul class="list-disc pl-5 text-gray-700">
                                    @foreach($p->time_overload_slots as $slot)
                                        <li>{{ $slot->course }} – {{ $slot->schedule }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button type="button" onclick="closeOverloadModal({{ $p->id }})" class="px-4 py-2 bg-gray-200 rounded font-medium hover:bg-gray-300">Close</button>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </main>

    <script>
        var currentEmployment = 'permanent';
        var currentGender = '';

        function applyFilter() {
            var employment = currentEmployment;
            var gender = currentGender;
            var rows = document.querySelectorAll('.professor-row');
            var visibleCount = 0;
            rows.forEach(function(tr) {
                var rowEmp = (tr.getAttribute('data-employment') || '').toLowerCase();
                var rowGender = (tr.getAttribute('data-gender') || '').trim();
                var matchEmp = rowEmp === employment;
                var matchGender = !gender || rowGender === gender;
                var show = matchEmp && matchGender;
                tr.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });
            var noMatchRow = document.getElementById('no-match-row');
            if (noMatchRow) {
                noMatchRow.style.display = visibleCount === 0 ? '' : 'none';
            }
        }

        document.querySelectorAll('.filter-tab').forEach(function(btn) {
            btn.addEventListener('click', function() {
                currentEmployment = this.getAttribute('data-employment') || 'permanent';
                document.querySelectorAll('.filter-tab').forEach(function(b) {
                    b.classList.remove('bg-blue-50', 'text-[#1E40AF]');
                    b.classList.add('bg-gray-100', 'text-gray-600');
                });
                this.classList.remove('bg-gray-100', 'text-gray-600');
                this.classList.add('bg-blue-50', 'text-[#1E40AF]');
                applyFilter();
            });
        });

        document.getElementById('gender-filter') && document.getElementById('gender-filter').addEventListener('change', function() {
            currentGender = this.value || '';
            applyFilter();
        });

        applyFilter();

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        var assignModalProfessorId = null;
        var assignModalSubjects = [];
        var assignModalMaxUnits = 0;
        var assignModalLastData = null;

        function openViewProfileModal(professorId) {
            var modal = document.getElementById('view-profile-modal');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            document.getElementById('view-profile-overview').innerHTML = '<p class="text-gray-500">Loading...</p>';
            document.getElementById('view-profile-normal-table').innerHTML = '';
            document.getElementById('view-profile-overload-table').innerHTML = '';
            var url = '{{ route("dean.manage-professor.view-profile-data", ["professor" => ":id"]) }}'.replace(':id', String(professorId));
            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var p = data.professor || {};
                    document.getElementById('view-profile-overview').innerHTML =
                        '<p><strong>' + escapeHtml(p.name || '') + '</strong></p>' +
                        '<p>Email: ' + escapeHtml(p.email || '—') + '</p>' +
                        '<p>Employment: ' + escapeHtml(p.employment_type || '—') + ' &nbsp; Gender: ' + escapeHtml(p.gender || '—') + '</p>';
                    var normal = data.normal_schedule || [];
                    var overload = data.overload_schedule || [];
                    if (normal.length === 0) {
                        document.getElementById('view-profile-normal-table').innerHTML = '<p class="text-sm text-gray-500">No normal schedule in COR Archive. Deploy a schedule from Schedule by Program to populate.</p>';
                    } else {
                        var html = '<div class="overflow-x-auto"><table class="w-full text-sm border border-gray-200"><thead class="bg-gray-100"><tr><th class="p-2 text-left border-b">Course Code</th><th class="p-2 text-left border-b">Course Description</th><th class="p-2 text-left border-b">YR – Program & Block</th><th class="p-2 text-right border-b">Units</th><th class="p-2 text-left border-b">Schedule</th></tr></thead><tbody>';
                        normal.forEach(function(row) {
                            html += '<tr class="border-b"><td class="p-2">' + escapeHtml(row.course_code) + '</td><td class="p-2">' + escapeHtml(row.course_description) + '</td><td class="p-2">' + escapeHtml(row.yr_program_block) + '</td><td class="p-2 text-right">' + escapeHtml(String(row.units)) + '</td><td class="p-2">' + escapeHtml(row.schedule) + '</td></tr>';
                        });
                        html += '</tbody><tfoot class="bg-blue-50"><tr><td class="p-2 font-semibold" colspan="3">Total units</td><td class="p-2 text-right font-semibold">' + (data.normal_units_total || 0) + '</td><td class="p-2"></td></tr></tfoot></table></div>';
                        document.getElementById('view-profile-normal-table').innerHTML = html;
                    }
                    if (overload.length === 0) {
                        document.getElementById('view-profile-overload-table').innerHTML = '<p class="text-sm text-gray-500">No overload schedule (5pm onwards) in COR Archive.</p>';
                    } else {
                        var html = '<div class="overflow-x-auto"><table class="w-full text-sm border border-red-200"><thead class="bg-red-50"><tr><th class="p-2 text-left border-b">Course Code</th><th class="p-2 text-left border-b">Course Description</th><th class="p-2 text-left border-b">YR – Program & Block</th><th class="p-2 text-right border-b">Units</th><th class="p-2 text-left border-b">Schedule</th></tr></thead><tbody>';
                        overload.forEach(function(row) {
                            html += '<tr class="border-b"><td class="p-2">' + escapeHtml(row.course_code) + '</td><td class="p-2">' + escapeHtml(row.course_description) + '</td><td class="p-2">' + escapeHtml(row.yr_program_block) + '</td><td class="p-2 text-right">' + escapeHtml(String(row.units)) + '</td><td class="p-2">' + escapeHtml(row.schedule) + '</td></tr>';
                        });
                        html += '</tbody><tfoot class="bg-red-50"><tr><td class="p-2 font-semibold" colspan="3">Overload units total</td><td class="p-2 text-right font-semibold">' + (data.overload_units_total || 0) + '</td><td class="p-2"></td></tr></tfoot></table></div>';
                        document.getElementById('view-profile-overload-table').innerHTML = html;
                    }
                })
                .catch(function() {
                    document.getElementById('view-profile-overview').innerHTML = '<p class="text-red-600">Could not load profile.</p>';
                });
        }
        function closeViewProfileModal() {
            document.getElementById('view-profile-modal').classList.add('hidden');
            document.getElementById('view-profile-modal').style.display = 'none';
        }

        function openAssignSubjectsModal(professorId) {
            assignModalProfessorId = professorId;
            var modal = document.getElementById('assign-subjects-modal');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            document.getElementById('assign-subjects-rows').innerHTML = '';
            document.getElementById('assign-max-units-warning').classList.add('hidden');
            var assignmentsDataUrl = '{{ route("dean.manage-professor.assignments-data", ["professor" => ":id"]) }}'.replace(':id', String(professorId));
            fetch(assignmentsDataUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function(r) { return r.json(); }).then(function(data) {
                var p = data.professor;
                assignModalMaxUnits = p.max_units || 0;
                document.getElementById('assign-modal-professor-details').innerHTML =
                    '<p><strong>' + escapeHtml(p.name || '') + '</strong></p>' +
                    '<p>Email: ' + escapeHtml(p.email || '—') + '</p>' +
                    '<p>Employment: ' + (p.employment_type || '—').toUpperCase() + ' &nbsp; Gender: ' + escapeHtml(p.gender || '—') + '</p>';
                document.getElementById('assign-modal-units').textContent =
                    'Assigned units: ' + (p.assigned_units || 0) + ' / Max units: ' + (p.max_units || '—');
                // fill modal inputs for max units and schedule limit
                try {
                    var maxInp = document.getElementById('assign-modal-max-units');
                    var limitInp = document.getElementById('assign-modal-schedule-limit');
                    if (maxInp) maxInp.value = (p.max_units !== undefined && p.max_units !== null) ? p.max_units : '';
                    if (limitInp) limitInp.value = (p.schedule_selection_limit !== undefined && p.schedule_selection_limit !== null) ? p.schedule_selection_limit : '';
                } catch (e) {}
                // store last data for later UI updates
                assignModalLastData = data;
                document.getElementById('assign-modal-total-units').textContent = '';
                assignModalSubjects = data.subjects || [];
                // Render assigned subjects table in modal
                var assignedEl = document.getElementById('assign-modal-assigned-subjects');
                if (assignedEl) {
                    var as = data.assignments || [];
                    if (!as || as.length === 0) {
                        assignedEl.innerHTML = '<p class="text-sm text-gray-500">No assigned subjects.</p>';
                    } else {
                        var html = '<div class="overflow-x-auto"><table class="w-full text-sm border border-gray-200"><thead class="bg-gray-100"><tr><th class="p-2 text-left border-b">Course code</th><th class="p-2 text-left border-b">Description</th><th class="p-2 text-right border-b">Units</th></tr></thead><tbody>';
                        as.forEach(function(a) {
                            var code = a.code || '—';
                            var title = a.title || '—';
                            var units = a.units || 0;
                            html += '<tr class="border-b"><td class="p-2">' + escapeHtml(code) + '</td><td class="p-2">' + escapeHtml(title) + '</td><td class="p-2 text-right">' + escapeHtml(String(units)) + '</td></tr>';
                        });
                        html += '</tbody></table></div>';
                        assignedEl.innerHTML = html;
                    }
                }

                // Render schedule grid
                var scheduleEl = document.getElementById('assign-modal-schedule-grid');
                if (scheduleEl) {
                    var schedules = data.schedules || [];
                    if (!schedules || schedules.length === 0) {
                        scheduleEl.innerHTML = '<p class="text-sm text-gray-500">No schedule slots yet.</p>';
                    } else {
                        var html = '<div class="overflow-x-auto"><table class="w-full text-sm border border-gray-200"><thead class="bg-gray-100"><tr><th class="p-2 text-left border-b">Course</th><th class="p-2 text-left border-b">YR – Program & Block</th><th class="p-2 text-right border-b">Units</th><th class="p-2 text-left border-b">Day</th><th class="p-2 text-left border-b">Time</th><th class="p-2 text-left border-b">Room</th><th class="p-2 text-center border-b">Overload</th></tr></thead><tbody>';
                        schedules.forEach(function(s) {
                            html += '<tr class="border-b"><td class="p-2">' + escapeHtml((s.course_code || '') + ' ' + (s.course_title || '')) + '</td>' +
                                '<td class="p-2">' + escapeHtml(s.yr_program_block || '—') + '</td>' +
                                '<td class="p-2 text-right">' + escapeHtml(String(s.units || 0)) + '</td>' +
                                '<td class="p-2">' + escapeHtml(s.day || '') + '</td>' +
                                '<td class="p-2">' + escapeHtml((s.start_time || '') + ' – ' + (s.end_time || '')) + '</td>' +
                                '<td class="p-2">' + escapeHtml(s.room || '') + '</td>' +
                                '<td class="p-2 text-center">' + (s.is_overload ? '<span class="text-red-600 font-semibold">OVERLOAD</span>' : '—') + '</td></tr>';
                        });
                        html += '</tbody><tfoot class="bg-blue-50"><tr><td class="p-2 font-semibold" colspan="2">Total units (schedule)</td><td class="p-2 text-right font-semibold">' + (data.schedule_units || 0) + '</td><td class="p-2" colspan="4"></td></tr></tfoot></table></div>';
                        scheduleEl.innerHTML = html;
                    }
                }

                // Render overload grid
                var overloadEl = document.getElementById('assign-modal-overload-grid');
                if (overloadEl) {
                    var ov = data.overload_schedules || [];
                    if (!ov || ov.length === 0) {
                        overloadEl.innerHTML = '<p class="text-sm text-gray-500">No overload slots.</p>';
                    } else {
                        var html = '<div class="overflow-x-auto"><table class="w-full text-sm border border-red-200"><thead class="bg-red-50"><tr><th class="p-2 text-left border-b">Course</th><th class="p-2 text-left border-b">YR – Program & Block</th><th class="p-2 text-right border-b">Units</th><th class="p-2 text-left border-b">Day</th><th class="p-2 text-left border-b">Time</th><th class="p-2 text-left border-b">Room</th></tr></thead><tbody>';
                        ov.forEach(function(s) {
                            html += '<tr class="border-b"><td class="p-2">' + escapeHtml((s.course_code || '') + ' ' + (s.course_title || '')) + '</td>' +
                                '<td class="p-2">' + escapeHtml(s.yr_program_block || '—') + '</td>' +
                                '<td class="p-2 text-right">' + escapeHtml(String(s.units || 0)) + '</td>' +
                                '<td class="p-2">' + escapeHtml(s.day || '') + '</td>' +
                                '<td class="p-2">' + escapeHtml((s.start_time || '') + ' – ' + (s.end_time || '')) + '</td>' +
                                '<td class="p-2">' + escapeHtml(s.room || '') + '</td></tr>';
                        });
                        html += '</tbody><tfoot class="bg-red-50"><tr><td class="p-2 font-semibold" colspan="2">Overload units total</td><td class="p-2 text-right font-semibold">' + (ov.reduce(function(acc, x){ return acc + (x.units || 0); }, 0) || 0) + '</td><td class="p-2" colspan="3"></td></tr></tfoot></table></div>';
                        overloadEl.innerHTML = html;
                    }
                }
                // update unit summary with schedule and totals
                try {
                    var sumEl = document.getElementById('assign-modal-units');
                    var totEl = document.getElementById('assign-modal-total-units');
                    if (sumEl) {
                        var assigned = (p.assigned_units || 0);
                        var scheduleUnits = (data.schedule_units || 0);
                        var max = (p.max_units || '—');
                        sumEl.textContent = 'Assigned units: ' + assigned + ' · Schedule units: ' + scheduleUnits + ' / Max units: ' + max;
                    }
                    if (totEl) {
                        totEl.textContent = 'Overall total (assigned + schedule): ' + (data.total_units || 0);
                    }
                } catch (e) {}
                var rowsEl = document.getElementById('assign-subjects-rows');
                rowsEl.innerHTML = '';
                (data.assignments || []).forEach(function(a) {
                    addAssignRow(a.subject_id, a.units, assignModalSubjects);
                });
                addAssignRow('', 0, assignModalSubjects);
                document.getElementById('assign-add-row-btn').onclick = function() { addAssignRow('', 0, assignModalSubjects); updateAssignUnitsWarning(); updateAssignTotalUnits(); };
                document.getElementById('assign-subjects-submit-btn').onclick = function() { submitAssignments(); };
                updateAssignUnitsWarning();
                updateAssignTotalUnits();
            }).catch(function() {
                document.getElementById('assign-modal-professor-details').textContent = 'Could not load professor.';
            });
        }

        function subjectLabel(s) {
            return (s.code || '') + ' – ' + (s.title || '') + ' (' + (s.units || 0) + ' units)';
        }

        function addAssignRow(selectedId, selectedUnits, subjects) {
            var container = document.getElementById('assign-subjects-rows');
            subjects = subjects || [];
            var selected = selectedId ? subjects.find(function(s) { return String(s.id) === String(selectedId); }) : null;
            var inputPlaceholder = 'Select subject (course code, description, units)';
            var initialText = selected ? subjectLabel(selected) : '';

            var div = document.createElement('div');
            div.className = 'flex items-center gap-2 assign-subject-row';
            div.dataset.units = selectedUnits || 0;
            var wrap = document.createElement('div');
            wrap.className = 'subject-dropdown-wrap';
            wrap.innerHTML =
                '<input type="text" class="subject-dropdown-input" placeholder="' + escapeHtml(inputPlaceholder) + '" value="' + escapeHtml(initialText) + '" autocomplete="off">' +
                '<div class="subject-dropdown-panel">' +
                '<div class="subject-dropdown-list"></div>' +
                '</div>' +
                '<input type="hidden" class="assign-subject-value" value="' + (selectedId || '') + '">';
            var textInput = wrap.querySelector('.subject-dropdown-input');
            var panel = wrap.querySelector('.subject-dropdown-panel');
            var listEl = wrap.querySelector('.subject-dropdown-list');
            var valueInput = wrap.querySelector('.assign-subject-value');

            function renderList(filter) {
                var q = (filter || '').toLowerCase().trim();
                var items = q ? subjects.filter(function(s) {
                    var label = subjectLabel(s);
                    return label.toLowerCase().indexOf(q) >= 0;
                }) : subjects;
                listEl.innerHTML = '';
                if (items.length === 0) {
                    listEl.innerHTML = '<div class="subject-dropdown-option" style="cursor:default;color:#6b7280;">No matches. Keep typing to search.</div>';
                    return;
                }
                items.forEach(function(s) {
                    var opt = document.createElement('div');
                    opt.className = 'subject-dropdown-option';
                    opt.textContent = subjectLabel(s);
                    opt.dataset.id = s.id;
                    opt.dataset.units = s.units || 0;
                    opt.dataset.label = subjectLabel(s);
                    opt.onclick = function() {
                        valueInput.value = opt.dataset.id;
                        div.dataset.units = opt.dataset.units;
                        textInput.value = opt.dataset.label;
                        panel.classList.remove('open');
                        updateAssignUnitsWarning();
                        updateAssignTotalUnits();
                    };
                    listEl.appendChild(opt);
                });
            }

            function openAndFilter() {
                panel.classList.add('open');
                renderList(textInput.value);
            }

            textInput.addEventListener('focus', function(e) {
                e.stopPropagation();
                openAndFilter();
                if (valueInput.value) textInput.select();
            });
            textInput.addEventListener('input', function() {
                openAndFilter();
            });
            textInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    panel.classList.remove('open');
                    if (valueInput.value) {
                        var s = subjects.find(function(x) { return String(x.id) === valueInput.value; });
                        if (s) textInput.value = subjectLabel(s);
                    }
                }
            });
            textInput.addEventListener('click', function(e) { e.stopPropagation(); });
            listEl.addEventListener('click', function(e) { e.stopPropagation(); });

            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'assign-remove-row px-2 py-1 text-red-600 hover:bg-red-50 rounded text-sm';
            removeBtn.textContent = 'Remove';
            removeBtn.onclick = function() {
                if (container.querySelectorAll('.assign-subject-value').length <= 1) return;
                div.remove();
                updateAssignUnitsWarning();
                updateAssignTotalUnits();
            };

            div.appendChild(wrap);
            div.appendChild(removeBtn);

            function closePanel(e) {
                if (!wrap.contains(e.target)) {
                    panel.classList.remove('open');
                    document.removeEventListener('click', closePanel);
                }
            }
            setTimeout(function() { document.addEventListener('click', closePanel); }, 0);

            container.appendChild(div);
        }

        function updateAssignTotalUnits() {
            var total = 0;
            document.querySelectorAll('#assign-subjects-rows .assign-subject-value').forEach(function(inp) {
                if (inp.value) {
                    var row = inp.closest('.assign-subject-row');
                    if (row) total += parseInt(row.dataset.units || '0', 10);
                }
            });
            var el = document.getElementById('assign-modal-total-units');
            if (el) el.textContent = 'Total units selected: ' + total + (assignModalMaxUnits > 0 ? ' / ' + assignModalMaxUnits + ' max' : '');
        }

        function updateAssignUnitsWarning() {
            var total = 0;
            document.querySelectorAll('#assign-subjects-rows .assign-subject-value').forEach(function(inp) {
                if (inp.value) {
                    var row = inp.closest('.assign-subject-row');
                    if (row) total += parseInt(row.dataset.units || '0', 10);
                }
            });
            var warningEl = document.getElementById('assign-max-units-warning');
            if (assignModalMaxUnits > 0 && total > assignModalMaxUnits) {
                warningEl.textContent = 'This professor has maximum units. (Total: ' + total + ' / Max: ' + assignModalMaxUnits + ')';
                warningEl.classList.remove('hidden');
            } else {
                warningEl.classList.add('hidden');
            }
        }

        function submitAssignments() {
            if (!assignModalProfessorId) return;
            var subjectIds = [];
            document.querySelectorAll('#assign-subjects-rows .assign-subject-value').forEach(function(inp) {
                if (inp.value) subjectIds.push(inp.value);
            });
            var total = 0;
            document.querySelectorAll('#assign-subjects-rows .assign-subject-value').forEach(function(inp) {
                if (inp.value) {
                    var row = inp.closest('.assign-subject-row');
                    if (row) total += parseInt(row.dataset.units || '0', 10);
                }
            });
            if (assignModalMaxUnits > 0 && total > assignModalMaxUnits) {
                alert('This professor has maximum units. Reduce assigned subjects or increase max units.');
                return;
            }
            var formData = new FormData();
            subjectIds.forEach(function(id) { formData.append('subject_ids[]', id); });
            var storeAssignmentsUrl = '{{ route("dean.manage-professor.assignments.store", ["professor" => ":id"]) }}'.replace(':id', String(assignModalProfessorId));
            fetch(storeAssignmentsUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            }).then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); }).then(function(result) {
                if (result.ok && result.data.success) {
                    closeAssignSubjectsModal();
                    window.location.reload();
                } else {
                    alert(result.data.message || 'Could not save.');
                }
            }).catch(function() { alert('Could not save.'); });
        }

        function closeAssignSubjectsModal() {
            assignModalProfessorId = null;
            document.getElementById('assign-subjects-modal').classList.add('hidden');
            document.getElementById('assign-subjects-modal').style.display = 'none';
        }

        // Save max units from modal
        async function saveModalMaxUnits() {
            if (!assignModalProfessorId) return;
            var inp = document.getElementById('assign-modal-max-units');
            if (!inp) return;
            var val = inp.value === '' ? null : parseInt(inp.value, 10);
            var url = '{{ route("dean.manage-professor.max-units", ["professor" => ":id"]) }}'.replace(':id', String(assignModalProfessorId));
            var form = new FormData();
            form.append('max_units', val === null ? '' : String(val));
            try {
                var res = await fetch(url, { method: 'PATCH', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }, body: form });
                if (res.ok) {
                    var data = await res.json().catch(() => ({}));
                    // update table cell
                    var row = document.querySelector('.professor-row[data-professor-id="' + String(assignModalProfessorId) + '"]');
                    if (row) {
                        var cell = row.querySelector('.prof-max-units');
                        if (cell) cell.textContent = (val === null ? '—' : String(val));
                    }
                    assignModalMaxUnits = val || 0;
                    // update modal summary
                    var sumEl = document.getElementById('assign-modal-units');
                    try { if (sumEl) { var assigned = (assignModalLastData && assignModalLastData.professor && assignModalLastData.professor.assigned_units) ? assignModalLastData.professor.assigned_units : 0; var scheduleUnits = (assignModalLastData && assignModalLastData.schedule_units) ? assignModalLastData.schedule_units : 0; sumEl.textContent = 'Assigned units: ' + assigned + ' · Schedule units: ' + scheduleUnits + ' / Max units: ' + (val === null ? '—' : String(val)); } } catch(e){}
                    alert('Max units updated.');
                } else {
                    alert('Could not update max units.');
                }
            } catch (e) { alert('Could not update max units.'); }
        }

        // Save schedule selection limit from modal
        async function saveModalScheduleLimit() {
            if (!assignModalProfessorId) return;
            var inp = document.getElementById('assign-modal-schedule-limit');
            if (!inp) return;
            var val = inp.value === '' ? null : parseInt(inp.value, 10);
            var url = '{{ route("dean.manage-professor.schedule-selection-limit", ["professor" => ":id"]) }}'.replace(':id', String(assignModalProfessorId));
            var form = new FormData();
            form.append('schedule_selection_limit', val === null ? '' : String(val));
            try {
                var res = await fetch(url, { method: 'PATCH', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }, body: form });
                if (res.ok) {
                    var data = await res.json().catch(()=>({}));
                    // update table text
                    var row = document.querySelector('.professor-row[data-professor-id="' + String(assignModalProfessorId) + '"]');
                    if (row) {
                        var el = row.querySelector('.prof-schedule-limit');
                        if (el) el.textContent = (val === null ? '∞' : String(val));
                    }
                    alert('Schedule selection limit updated.');
                } else {
                    alert('Could not update schedule selection limit.');
                }
            } catch (e) { alert('Could not update schedule selection limit.'); }
        }

        document.getElementById('assign-modal-save-max-units') && document.getElementById('assign-modal-save-max-units').addEventListener('click', saveModalMaxUnits);
        document.getElementById('assign-modal-save-schedule-limit') && document.getElementById('assign-modal-save-schedule-limit').addEventListener('click', saveModalScheduleLimit);

        function openOverloadModal(professorId) {
            var el = document.getElementById('overload-modal-' + professorId);
            if (el) { el.classList.remove('hidden'); el.style.display = 'flex'; }
        }
        function closeOverloadModal(professorId) {
            var el = document.getElementById('overload-modal-' + professorId);
            if (el) { el.classList.add('hidden'); el.style.display = 'none'; }
        }
    </script>
</body>
</html>
