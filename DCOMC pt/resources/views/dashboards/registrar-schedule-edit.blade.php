<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Schedule - {{ $template->title }} - DCOMC</title>
    @include('layouts.partials.offline-assets')
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    @include('dashboards.partials.registrar-sidebar')

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="bg-white flex justify-center items-end border-b border-gray-200 pt-4 relative shrink-0">
            <div class="flex space-x-8 text-sm font-medium text-gray-600 px-6">
                <a href="{{ route('registrar.irregularities', ['tab' => 'create-schedule']) }}" class="pb-3 px-4 border-b-4 border-transparent hover:text-blue-700 rounded-t-md">← Create Schedule</a>
                <span class="pb-3 px-4 border-b-4 text-blue-700 border-blue-700 rounded-t-md">Edit: {{ $template->title }}</span>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8">
            <div class="max-w-6xl mx-auto">

                @if(session('success'))
                    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                        <ul class="list-disc pl-5 text-sm">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('registrar.schedule.templates.update', $template->id) }}" id="schedule-edit-form">
                    @csrf
                    @method('PATCH')

                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Schedule title</label>
                        <input type="text" name="title" required value="{{ old('title', $template->title) }}" class="w-full max-w-md border border-gray-300 rounded px-3 py-2">
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mb-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Schedule table</h2>
                        <p class="text-sm text-gray-500 mb-4">Each row is independent. <strong>Program</strong> is required first and filters the subject list. <strong>Subject</strong> is required next and filters the Schedule slot dropdown. Slots come from COR Archive deployed/saved schedules only, regardless of year level. Slot format: [Day] [Time] | [Room] | [Professor] | [Block]. Save when done.</p>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm" id="schedule-slots-table">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-2 text-left">Program</th>
                                        <th class="p-2 text-left">Code</th>
                                        <th class="p-2 text-left">Title</th>
                                        <th class="p-2 text-center">Units</th>
                                        <th class="p-2 text-left">Schedule slot (Day, Time, Room, Professor, Block)</th>
                                        <th class="p-2 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="schedule-slots-tbody">
                                    @forelse($slotRows as $row)
                                        @php
                                            $subject = $row['subject'];
                                            $slot = $row['slot'];
                                            $idx = $row['slot_index'];
                                            $rowProgramId = $row['program_id'] ?? null;
                                            $subjectSlots = $row['slot_options'] ?? [];
                                            $slotValue = json_encode([
                                                'day_of_week' => (int)($slot['day_of_week'] ?? 0),
                                                'start_time' => isset($slot['start_time']) ? $slot['start_time'] : '',
                                                'end_time' => isset($slot['end_time']) ? $slot['end_time'] : '',
                                                'room_id' => $slot['room_id'] ?? null,
                                                'professor_id' => $slot['professor_id'] ?? null,
                                                'block_id' => $slot['block_id'] ?? null,
                                                'is_overload' => !empty($slot['is_overload']),
                                            ]);
                                        @endphp
                                        <tr class="border-t align-top schedule-slot-row" data-subject-id="{{ $subject->id }}" data-row-index="{{ $idx }}">
                                            <td class="p-2">
                                                <select name="slots[{{ $idx }}][program_id]" class="row-program-select w-full min-w-[140px] border border-gray-300 rounded px-2 py-1.5 text-sm" data-row-index="{{ $idx }}" data-subject-id="{{ $subject->id }}" required>
                                                    <option value="">— Program —</option>
                                                    @foreach($programs as $p)
                                                        <option value="{{ $p->id }}" {{ ($rowProgramId && (int)$p->id === (int)$rowProgramId) ? 'selected' : '' }}>{{ $p->program_name ?? $p->code ?? $p->id }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="p-2 font-medium">{{ $subject->code ?? '—' }}</td>
                                            <td class="p-2">{{ $subject->title ?? '—' }}</td>
                                            <td class="p-2 text-center">{{ $subject->units ?? 0 }}</td>
                                            <td class="p-2">
                                                <input type="hidden" name="slots[{{ $idx }}][subject_id]" value="{{ $subject->id }}">
                                                <select name="slots[{{ $idx }}][slot_data]" class="row-slot-select w-full min-w-[280px] border border-gray-300 rounded px-2 py-1.5 text-sm" data-row-index="{{ $idx }}" {{ empty($subjectSlots) ? 'disabled' : '' }}>
                                                    <option value="">— Select slot from COR Archive —</option>
                                                    @foreach($subjectSlots as $opt)
                                                        @php
                                                            $optValue = json_encode([
                                                                'day_of_week' => $opt['day_of_week'],
                                                                'start_time' => $opt['start_time'],
                                                                'end_time' => $opt['end_time'],
                                                                'room_id' => $opt['room_id'],
                                                                'professor_id' => $opt['professor_id'],
                                                                'block_id' => $opt['block_id'] ?? null,
                                                                'is_overload' => !empty($opt['is_overload'] ?? false),
                                                            ]);
                                                            $matchDay = (int) ($slot['day_of_week'] ?? 0) === (int) ($opt['day_of_week'] ?? 0);
                                                            $matchStart = ($opt['start_time'] ?? '') === ($slot['start_time'] ?? '');
                                                            $matchEnd = ($opt['end_time'] ?? '') === ($slot['end_time'] ?? '');
                                                            $matchRoom = (string) ($opt['room_id'] ?? '') === (string) ($slot['room_id'] ?? '');
                                                            $matchProf = (string) ($opt['professor_id'] ?? '') === (string) ($slot['professor_id'] ?? '');
                                                            $matchBlock = (string) ($opt['block_id'] ?? '') === (string) ($slot['block_id'] ?? '');
                                                            $matchOverload = !empty($opt['is_overload']) === !empty($slot['is_overload']);
                                                            $selected = $matchDay && $matchStart && $matchEnd && $matchRoom && $matchProf && $matchBlock && $matchOverload;
                                                        @endphp
                                                        <option value="{{ e($optValue) }}" {{ $selected ? 'selected' : '' }}>{{ $opt['label'] ?? '' }}</option>
                                                    @endforeach
                                                </select>
                                                @if(empty($subjectSlots))
                                                    <span class="text-xs text-amber-600 block mt-1">No Deployed Schedule Found</span>
                                                @endif
                                            </td>
                                            <td class="p-2">
                                                <button type="button" class="remove-subject-btn px-2 py-1 text-red-600 hover:bg-red-50 rounded text-xs border border-red-200" data-subject-id="{{ $subject->id }}">Remove</button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr id="schedule-empty-row"><td colspan="6" class="p-4 text-center text-gray-500 text-sm">No subjects in schedule. Select a program first, then search and select a subject, then Add row.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 border-t pt-3" style="position:sticky; bottom:0; background:linear-gradient(#ffffff,#ffffff); z-index:30;">
                            <p class="text-xs text-gray-600 mb-2">Add a row: choose <strong>Program</strong> first, then <strong>Subject</strong>, then click Add row. Schedule slot appears in the new row.</p>
                            <div class="flex flex-wrap items-end gap-4">
                                <div class="flex flex-col gap-1">
                                    <label for="add-row-program" class="text-sm font-medium text-gray-700">1. Program <span class="text-red-500">*</span></label>
                                    <select id="add-row-program" name="add_row_program" class="border border-gray-300 rounded px-3 py-2 text-sm bg-white min-w-[220px] cursor-pointer" required aria-label="Program (required first)">
                                        <option value="">— Select program first —</option>
                                        @foreach($programs ?? [] as $p)
                                            <option value="{{ $p->id }}">{{ $p->program_name ?? $p->code ?? $p->id }}</option>
                                        @endforeach
                                    </select>
                                    @if(empty($programs) || $programs->isEmpty())
                                        <span class="text-xs text-amber-600">No programs in system. Add programs first.</span>
                                    @endif
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label for="add-subject-search" class="text-sm font-medium text-gray-700">2. Subject <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="text" id="add-subject-search" placeholder="Select program first, then type to search..." class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white min-w-[280px]" autocomplete="off" role="combobox" aria-expanded="false" aria-haspopup="listbox" aria-controls="add-subject-results" aria-label="Subject (required; filtered by program)" disabled>
                                        <input type="hidden" id="add-subject-id" value="">
                                        <div id="add-subject-results" class="hidden absolute left-0 top-full mt-1 z-50 bg-white border border-gray-300 rounded shadow-lg max-h-56 overflow-y-auto w-full min-w-[280px]" role="listbox"></div>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="text-sm font-medium text-gray-700 opacity-0 select-none">3.</span>
                                    <button type="button" id="add-subject-btn" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap" disabled title="Select program first, then select a subject">Add row</button>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Side-by-side Add / Remove transfer UI --}}
                        <div class="mt-6 border-t pt-4">
                            <h3 class="text-sm font-semibold text-gray-800 mb-3">Manage subjects for a program (side-by-side)</h3>
                            <p class="text-xs text-gray-500 mb-3">Select a program to load available subjects on the left and scheduled subjects on the right. Use the buttons to move subjects between lists. Adding will append rows to the schedule table; removing will remove schedule rows.</p>
                            <div class="flex gap-4 items-start">
                                <div class="flex-1">
                                    <label class="text-xs font-medium text-gray-700">Available subjects</label>
                                    <select id="available-subjects-list" multiple size="8" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm bg-white"></select>
                                </div>
                                <div class="flex flex-col items-center justify-center gap-2 mt-6">
                                    <button type="button" id="add-selected-btn" class="px-3 py-1 bg-blue-600 text-white rounded text-sm">Add →</button>
                                    <button type="button" id="remove-selected-btn" class="px-3 py-1 bg-red-100 text-red-800 rounded text-sm">← Remove</button>
                                </div>
                                <div class="flex-1">
                                    <label class="text-xs font-medium text-gray-700">Scheduled subjects (in this schedule)</label>
                                    <select id="scheduled-subjects-list" multiple size="8" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm bg-white"></select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white px-6 py-2 rounded font-semibold">Save changes</button>
                        <a href="{{ route('registrar.irregularities', ['tab' => 'create-schedule']) }}" class="text-gray-600 hover:text-gray-800 font-medium">Cancel</a>
                    </div>
                </form>

                {{-- Deploy to selected students --}}
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mt-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-2">Deploy to students</h2>
                    <p class="text-xs text-gray-500 mb-4">Add irregular students below; only students in the table receive this schedule (they will see it in View COR with a yellow indicator).</p>
                    <form method="POST" action="{{ route('registrar.schedule.templates.deploy', $template->id) }}" id="deploy-form" onsubmit="return confirm('Deploy this schedule to the selected students? They will see it in View COR.');">
                        @csrf
                        <div class="relative flex flex-wrap gap-2 mb-4">
                            <input type="text" id="student-search" placeholder="Type or select irregular student (name, email, or student ID)..." class="border border-gray-300 rounded px-3 py-2 text-sm w-72" autocomplete="off" role="combobox" aria-expanded="false" aria-haspopup="listbox" aria-controls="student-search-results">
                            <span class="text-xs text-gray-500 self-center">Dropdown lists only irregular-type students. Click a name to add to the table.</span>
                            <div id="student-search-results" class="hidden absolute left-0 top-full mt-1 z-50 bg-white border border-gray-300 rounded shadow-lg max-h-48 overflow-y-auto w-72" role="listbox"></div>
                        </div>
                        <table class="w-full text-sm border border-gray-200 mb-4">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-2 text-left">Name</th>
                                    <th class="p-2 w-20">Remove</th>
                                </tr>
                            </thead>
                            <tbody id="deploy-students-tbody">
                                <tr id="deploy-students-empty"><td colspan="2" class="p-4 text-gray-500 text-center">No students added. Use the dropdown above to add irregular students.</td></tr>
                            </tbody>
                        </table>
                        <div id="deploy-student-ids-container"></div>
                        <div class="flex flex-wrap items-center gap-4">
                            <button type="submit" id="deploy-btn" class="px-4 py-2 rounded font-semibold bg-green-600 hover:bg-green-700 text-white disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                Deploy to selected students
                            </button>
                        </div>
                    </form>
                    @if($template->is_active)
                        <form method="POST" action="{{ route('registrar.schedule.templates.undeploy', $template->id) }}" class="inline mt-2" onsubmit="return confirm('Undeploy? Listed students will no longer see this schedule in View COR.');">
                            @csrf
                            <button type="submit" class="px-4 py-2 rounded font-semibold bg-red-100 text-red-800 border border-red-300 hover:bg-red-200">Undeploy</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </main>

    <script>
    (function() {
        var data = @json($scheduleScriptData);
        var nextSlotIndex = data.nextSlotIndex || 0;
        var programs = data.programs || [];
        var subjectsForProgramUrl = data.subjectsForProgramUrl || '';
        var slotsForScopeUrl = data.slotsForScopeUrl || '';
        var availableSubjectsForAdd = [];
        var token = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function escapeHtml(s) {
            if (!s) return '';
            return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }
        function subjectDisplayText(s) {
            return (s.code || '') + ' — ' + (s.title ? s.title.substring(0, 45) : '') + ' (' + (s.units || 0) + ' u)';
        }

        function removeSubjectRows(subjectId) {
            var tbody = document.getElementById('schedule-slots-tbody');
            if (!tbody) return;
            tbody.querySelectorAll('tr[data-subject-id="' + subjectId + '"]').forEach(function(tr) { tr.remove(); });
            var emptyRow = document.getElementById('schedule-empty-row');
            if (tbody.querySelectorAll('tr.schedule-slot-row').length === 0 && emptyRow) {
                emptyRow.style.display = '';
            }
        }

        function buildSlotOptionsHtml(slotOpts) {
            var html = '<option value="">— Select slot from COR Archive —</option>';
            for (var i = 0; i < (slotOpts || []).length; i++) {
                var o = slotOpts[i];
                var val = JSON.stringify({
                    day_of_week: o.day_of_week,
                    start_time: o.start_time,
                    end_time: o.end_time,
                    room_id: o.room_id,
                    professor_id: o.professor_id,
                    block_id: o.block_id || null,
                    is_overload: o.is_overload || false
                });
                html += '<option value="' + escapeHtml(val) + '">' + escapeHtml(o.label || '') + '</option>';
            }
            return html;
        }

        function addSubjectRow(programId, subject, slotOpts) {
            if (!subject || !programId) return;
            var subjectIdNum = subject.id;
            var slotOptionsHtml = buildSlotOptionsHtml(slotOpts || []);
            var hasSlots = (slotOpts || []).length > 0;
            var programName = (programs.find(function(p) { return p.id == programId; }) || {}).name || programId;
            var idx = nextSlotIndex++;
            var tr = document.createElement('tr');
            tr.className = 'border-t align-top schedule-slot-row';
            tr.setAttribute('data-subject-id', subjectIdNum);
            tr.setAttribute('data-row-index', idx);
            tr.innerHTML =
                '<td class="p-2"><select name="slots[' + idx + '][program_id]" class="row-program-select w-full min-w-[140px] border border-gray-300 rounded px-2 py-1.5 text-sm" data-row-index="' + idx + '" data-subject-id="' + subjectIdNum + '" required><option value="">— Program —</option>' +
                programs.map(function(p) { return '<option value="' + p.id + '"' + (p.id == programId ? ' selected' : '') + '>' + escapeHtml(p.name) + '</option>'; }).join('') +
                '</select></td>' +
                '<td class="p-2 font-medium">' + escapeHtml(subject.code || '') + '</td>' +
                '<td class="p-2">' + escapeHtml(subject.title || '') + '</td>' +
                '<td class="p-2 text-center">' + (subject.units || 0) + '</td>' +
                '<td class="p-2">' +
                '<input type="hidden" name="slots[' + idx + '][subject_id]" value="' + subjectIdNum + '">' +
                '<select name="slots[' + idx + '][slot_data]" class="row-slot-select w-full min-w-[280px] border border-gray-300 rounded px-2 py-1.5 text-sm" data-row-index="' + idx + '"' + (hasSlots ? '' : ' disabled') + '>' + slotOptionsHtml + '</select>' +
                                                (!hasSlots ? '<span class="text-xs text-amber-600 block mt-1">No Deployed Schedule Found</span>' : '') +
                '</td>' +
                '<td class="p-2"><button type="button" class="remove-subject-btn px-2 py-1 text-red-600 hover:bg-red-50 rounded text-xs border border-red-200" data-subject-id="' + subjectIdNum + '">Remove</button></td>';
            var tbody = document.getElementById('schedule-slots-tbody');
            var emptyRow = document.getElementById('schedule-empty-row');
            if (emptyRow && emptyRow.closest('tbody') === tbody) {
                emptyRow.style.display = 'none';
                tbody.insertBefore(tr, emptyRow);
            } else {
                tbody.appendChild(tr);
            }
            tr.querySelector('.remove-subject-btn').addEventListener('click', function() { removeSubjectRows(subjectIdNum); });
            tr.querySelector('.row-program-select').addEventListener('change', function() { onRowProgramChange(tr); });
        }

        function onRowProgramChange(tr) {
            var programId = tr.querySelector('.row-program-select').value;
            var subjectId = tr.querySelector('input[name*="[subject_id]"]').value;
            var slotSelect = tr.querySelector('.row-slot-select');
            var prevMsg = tr.querySelector('.text-amber-600');
            if (prevMsg) prevMsg.remove();
            slotSelect.innerHTML = '<option value="">— Select slot from COR Archive —</option>';
            slotSelect.disabled = true;
            if (!programId || !subjectId) return;
            fetch(slotsForScopeUrl + '?program_id=' + encodeURIComponent(programId) + '&subject_id=' + encodeURIComponent(subjectId), { headers: { 'Accept': 'application/json' } })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    var opts = res.slots || [];
                    slotSelect.innerHTML = buildSlotOptionsHtml(opts);
                    slotSelect.disabled = opts.length === 0;
                    if (opts.length === 0) {
                        var span = document.createElement('span');
                        span.className = 'text-xs text-amber-600 block mt-1';
                        span.textContent = 'No Deployed Schedule Found';
                        slotSelect.parentNode.appendChild(span);
                    }
                })
                .catch(function() {
                    slotSelect.innerHTML = '<option value="">— Select slot from COR Archive —</option>';
                    slotSelect.disabled = true;
                });
        }

        document.getElementById('schedule-slots-tbody').addEventListener('click', function(e) {
            var removeSubjBtn = e.target.closest('.remove-subject-btn');
            if (removeSubjBtn) {
                e.preventDefault();
                removeSubjectRows(removeSubjBtn.getAttribute('data-subject-id'));
            }
        });

        function updateAddRowButtonState() {
            var programId = document.getElementById('add-row-program').value;
            var subjectId = (document.getElementById('add-subject-id') || {}).value || '';
            var btn = document.getElementById('add-subject-btn');
            if (btn) btn.disabled = !programId || !subjectId;
        }

        document.getElementById('add-row-program').addEventListener('change', function() {
            var programId = this.value;
            var searchEl = document.getElementById('add-subject-search');
            var resultsEl = document.getElementById('add-subject-results');
            document.getElementById('add-subject-id').value = '';
            updateAddRowButtonState();
            if (!programId) {
                searchEl.disabled = true;
                searchEl.placeholder = 'Select program first';
                searchEl.value = '';
                availableSubjectsForAdd = [];
                resultsEl.classList.add('hidden');
                return;
            }
            searchEl.disabled = false;
            searchEl.placeholder = 'Type to search subjects...';
            searchEl.value = '';
            fetch(subjectsForProgramUrl + '?program_id=' + encodeURIComponent(programId), { headers: { 'Accept': 'application/json' } })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    availableSubjectsForAdd = res.subjects || [];
                    if (availableSubjectsForAdd.length === 0) {
                        searchEl.placeholder = 'No subjects in COR Archive for this program';
                    } else {
                        searchEl.placeholder = 'Type to search subjects...';
                    }
                })
                .catch(function() { availableSubjectsForAdd = []; });
        });

        function filterSubjectsForAdd(query) {
            var q = (query || '').toLowerCase().trim();
            if (!q) return availableSubjectsForAdd.slice();
            return availableSubjectsForAdd.filter(function(s) {
                var code = (s.code || '').toLowerCase();
                var title = (s.title || '').toLowerCase();
                return code.indexOf(q) !== -1 || title.indexOf(q) !== -1;
            });
        }
        function showSubjectResults(list) {
            var resultsEl = document.getElementById('add-subject-results');
            var searchEl = document.getElementById('add-subject-search');
            resultsEl.innerHTML = '';
            if (!list || list.length === 0) {
                resultsEl.innerHTML = '<div class="p-3 text-gray-500 text-sm">No matching subjects.</div>';
            } else {
                list.forEach(function(s) {
                    var div = document.createElement('div');
                    div.className = 'p-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100 add-subject-item';
                    div.setAttribute('data-id', String(s.id));
                    div.setAttribute('data-code', s.code || '');
                    div.setAttribute('data-title', s.title || '');
                    div.setAttribute('data-units', s.units || 0);
                    div.setAttribute('role', 'option');
                    div.textContent = subjectDisplayText(s);
                    resultsEl.appendChild(div);
                });
            }
            resultsEl.classList.remove('hidden');
            searchEl.setAttribute('aria-expanded', 'true');
        }
        function openSubjectDropdown() {
            var programId = document.getElementById('add-row-program').value;
            if (!programId) return;
            var q = (document.getElementById('add-subject-search') || {}).value || '';
            showSubjectResults(filterSubjectsForAdd(q));
        }
        document.getElementById('add-subject-search').addEventListener('focus', function() {
            if (document.getElementById('add-row-program').value) openSubjectDropdown();
        });
        document.getElementById('add-subject-search').addEventListener('input', function() {
            openSubjectDropdown();
        });
        document.getElementById('add-subject-results').addEventListener('mousedown', function(e) {
            var item = e.target && e.target.closest && e.target.closest('.add-subject-item');
            if (!item) return;
            e.preventDefault();
            e.stopPropagation();
            var id = item.getAttribute('data-id');
            if (!id) return;
            document.getElementById('add-subject-id').value = id;
            document.getElementById('add-subject-search').value = item.textContent;
            document.getElementById('add-subject-results').classList.add('hidden');
            document.getElementById('add-subject-results').innerHTML = '';
            document.getElementById('add-subject-search').setAttribute('aria-expanded', 'false');
            updateAddRowButtonState();
        });
        document.getElementById('add-subject-search').addEventListener('blur', function(e) {
            var resultsEl = document.getElementById('add-subject-results');
            var related = e.relatedTarget;
            if (related && resultsEl && resultsEl.contains(related)) return;
            setTimeout(function() {
                document.getElementById('add-subject-results').classList.add('hidden');
                document.getElementById('add-subject-search').setAttribute('aria-expanded', 'false');
            }, 200);
        });
        document.getElementById('add-subject-btn') && document.getElementById('add-subject-btn').addEventListener('click', function() {
            var programId = document.getElementById('add-row-program').value;
            var idEl = document.getElementById('add-subject-id');
            var subjectId = idEl && idEl.value ? idEl.value : '';
            if (!programId || !subjectId) return;
            var subject = availableSubjectsForAdd.find(function(s) { return String(s.id) === String(subjectId); });
            if (!subject) return;
            fetch(slotsForScopeUrl + '?program_id=' + encodeURIComponent(programId) + '&subject_id=' + encodeURIComponent(subjectId), { headers: { 'Accept': 'application/json' } })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    addSubjectRow(programId, subject, res.slots || []);
                    idEl.value = '';
                    document.getElementById('add-subject-search').value = '';
                    updateAddRowButtonState();
                })
                .catch(function() {
                    addSubjectRow(programId, subject, []);
                    idEl.value = '';
                    document.getElementById('add-subject-search').value = '';
                    updateAddRowButtonState();
                });
        });

        document.querySelectorAll('.row-program-select').forEach(function(sel) {
            var tr = sel.closest('tr');
            if (tr) sel.addEventListener('change', function() { onRowProgramChange(tr); });
        });

        // expose helpers to global for registrar-side transfer UI
        try {
            window._registrar_addSubjectRow = addSubjectRow;
            window._registrar_removeSubjectRows = removeSubjectRows;
            window._registrar_availableSubjectsForAdd = function() { return availableSubjectsForAdd; };
            window._registrar_programs = programs;
            window._registrar_slotsForScopeUrl = slotsForScopeUrl;
            window._registrar_subjectsForProgramUrl = subjectsForProgramUrl;
        } catch (e) {
            // noop
        }
    })();

    var deployStudentIds = [];
    var studentSearchTimeout = null;
    var studentsSearchUrl = '{{ route("registrar.schedule.templates.students-search") }}';

    function refreshDeployInputs() {
        var container = document.getElementById('deploy-student-ids-container');
        if (!container) return;
        container.innerHTML = '';
        deployStudentIds.forEach(function(id) {
            var inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'student_ids[]';
            inp.value = id;
            container.appendChild(inp);
        });
        var btn = document.getElementById('deploy-btn');
        if (btn) btn.disabled = deployStudentIds.length === 0;
        var empty = document.getElementById('deploy-students-empty');
        if (empty) empty.style.display = deployStudentIds.length === 0 ? 'table-row' : 'none';
    }

    function addDeployStudent(student) {
        if (deployStudentIds.indexOf(student.id) !== -1) return;
        deployStudentIds.push(student.id);
        var tbody = document.getElementById('deploy-students-tbody');
        if (!tbody) return;
        var empty = document.getElementById('deploy-students-empty');
        if (empty) empty.style.display = 'none';
        var tr = document.createElement('tr');
        tr.className = 'deploy-student-row border-t border-gray-100';
        tr.setAttribute('data-student-id', student.id);
        tr.innerHTML = '<td class="p-2">' + (student.name || '').replace(/</g,'&lt;') + '</td><td class="p-2"><button type="button" class="remove-deploy-student text-red-600 hover:text-red-800 text-sm font-semibold" data-id="' + student.id + '">Remove</button></td>';
        tbody.appendChild(tr);
        tr.querySelector('.remove-deploy-student').addEventListener('click', function() {
            deployStudentIds = deployStudentIds.filter(function(id) { return id !== student.id; });
            tr.remove();
            if (tbody.querySelectorAll('.deploy-student-row').length === 0 && document.getElementById('deploy-students-empty')) {
                document.getElementById('deploy-students-empty').style.display = 'table-row';
            }
            refreshDeployInputs();
        });
        refreshDeployInputs();
    }

    var lastStudentResults = [];
    function showStudentResults(list) {
        var resultsEl = document.getElementById('student-search-results');
        lastStudentResults = list || [];
        resultsEl.innerHTML = '';
        if (list.length === 0) {
            resultsEl.innerHTML = '<div class="p-3 text-gray-500 text-sm">No irregular students found.</div>';
        } else {
            list.forEach(function(s) {
                var div = document.createElement('div');
                div.className = 'p-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100 student-search-item';
                div.setAttribute('data-id', String(s.id));
                div.setAttribute('data-name', s.name || '');
                div.setAttribute('role', 'option');
                div.textContent = s.name || '';
                resultsEl.appendChild(div);
            });
        }
        resultsEl.classList.remove('hidden');
        document.getElementById('student-search').setAttribute('aria-expanded', 'true');
    }
    document.getElementById('student-search-results').addEventListener('mousedown', function(e) {
        var item = e.target && e.target.closest && e.target.closest('.student-search-item');
        if (!item) return;
        e.preventDefault();
        e.stopPropagation();
        var id = item.getAttribute('data-id');
        if (!id) return;
        var student = lastStudentResults.filter(function(s) { return String(s.id) === String(id); })[0];
        if (!student) return;
        addDeployStudent({ id: student.id, name: student.name, email: student.email, student_id: student.student_id });
        var resultsEl = document.getElementById('student-search-results');
        resultsEl.classList.add('hidden');
        resultsEl.innerHTML = '';
        document.getElementById('student-search').value = '';
        document.getElementById('student-search').setAttribute('aria-expanded', 'false');
    });

    function fetchIrregularStudents(q) {
        var url = studentsSearchUrl + (q !== undefined && q !== '' ? '?q=' + encodeURIComponent(q) : '');
        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) { showStudentResults(data.students || []); })
            .catch(function() { document.getElementById('student-search-results').classList.add('hidden'); });
    }

    var searchInput = document.getElementById('student-search');
    searchInput.addEventListener('focus', function() {
        clearTimeout(studentSearchTimeout);
        studentSearchTimeout = setTimeout(function() { fetchIrregularStudents(this.value.trim()); }.bind(this), 100);
    });
    searchInput.addEventListener('input', function() {
        var q = this.value.trim();
        var resultsEl = document.getElementById('student-search-results');
        clearTimeout(studentSearchTimeout);
        studentSearchTimeout = setTimeout(function() {
            fetchIrregularStudents(q);
        }, 300);
    });
    searchInput.addEventListener('blur', function(e) {
        var resultsEl = document.getElementById('student-search-results');
        var related = e.relatedTarget;
        if (related && resultsEl && resultsEl.contains(related)) return;
        setTimeout(function() {
            document.getElementById('student-search-results').classList.add('hidden');
            searchInput.setAttribute('aria-expanded', 'false');
        }, 250);
    });

    document.getElementById('deploy-form').addEventListener('submit', function() {
        refreshDeployInputs();
    });
    </script>

    <script>
    (function() {
        try {
            var availEl = document.getElementById('available-subjects-list');
            var schedEl = document.getElementById('scheduled-subjects-list');
            var programSel = document.getElementById('add-row-program');
            var addBtn = document.getElementById('add-selected-btn');
            var removeBtn = document.getElementById('remove-selected-btn');
            var slotsForScopeUrl = window._registrar_slotsForScopeUrl || '';

            function getAvailableSubjects() { return (window._registrar_availableSubjectsForAdd && window._registrar_availableSubjectsForAdd()) || []; }

            function getScheduledSubjectsForProgram(programId) {
                var rows = Array.prototype.slice.call(document.querySelectorAll('#schedule-slots-tbody tr.schedule-slot-row'));
                var out = [];
                rows.forEach(function(tr) {
                    var psel = tr.querySelector('.row-program-select');
                    if (!psel) return;
                    if (String(psel.value) !== String(programId)) return;
                    var subjectId = tr.getAttribute('data-subject-id');
                    var code = tr.querySelector('td:nth-child(2)') && tr.querySelector('td:nth-child(2)').textContent.trim();
                    var title = tr.querySelector('td:nth-child(3)') && tr.querySelector('td:nth-child(3)').textContent.trim();
                    if (subjectId) out.push({ id: subjectId, text: (code || '') + ' — ' + (title || '') });
                });
                return out;
            }

            function renderAvailable(programId) {
                if (!availEl) return;
                availEl.innerHTML = '';
                var avail = getAvailableSubjects().slice();
                var scheduled = getScheduledSubjectsForProgram(programId).map(function(s){ return String(s.id); });
                avail.forEach(function(s) {
                    if (String(s.id) === '') return;
                    if (scheduled.indexOf(String(s.id)) !== -1) return; // skip already scheduled for this program
                    var opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = (s.code || '') + ' — ' + (s.title ? s.title.substring(0,60) : '') + ' (' + (s.units || 0) + 'u)';
                    availEl.appendChild(opt);
                });
            }

            function renderScheduled(programId) {
                if (!schedEl) return;
                schedEl.innerHTML = '';
                var scheduled = getScheduledSubjectsForProgram(programId);
                scheduled.forEach(function(s) {
                    var opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.text;
                    schedEl.appendChild(opt);
                });
            }

            function refreshListsForProgram(programId) {
                renderAvailable(programId);
                renderScheduled(programId);
            }

            if (programSel) {
                programSel.addEventListener('change', function() {
                    var pid = this.value;
                    // ensure availableSubjectsForAdd is populated by existing handler
                    setTimeout(function() { refreshListsForProgram(pid); }, 250);
                });
            }

            if (addBtn) addBtn.addEventListener('click', function() {
                var pid = (programSel && programSel.value) || '';
                if (!pid) return alert('Select a program first');
                if (!availEl) return;
                var selected = Array.prototype.slice.call(availEl.selectedOptions || []);
                if (selected.length === 0) return;
                selected.forEach(function(opt) {
                    var sid = opt.value;
                    var subject = (getAvailableSubjects() || []).find(function(x){ return String(x.id) === String(sid); });
                    if (!subject) return;
                    fetch(slotsForScopeUrl + '?program_id=' + encodeURIComponent(pid) + '&subject_id=' + encodeURIComponent(sid), { headers: { 'Accept': 'application/json' } })
                        .then(function(r){ return r.json(); })
                        .then(function(res){
                            try { window._registrar_addSubjectRow(pid, subject, res.slots || []); } catch(e) { console.error(e); }
                            // refresh lists
                            refreshListsForProgram(pid);
                        })
                        .catch(function(){
                            try { window._registrar_addSubjectRow(pid, subject, []); } catch(e) { console.error(e); }
                            refreshListsForProgram(pid);
                        });
                });
            });

            if (removeBtn) removeBtn.addEventListener('click', function() {
                var pid = (programSel && programSel.value) || '';
                if (!pid) return alert('Select a program first');
                if (!schedEl) return;
                var selected = Array.prototype.slice.call(schedEl.selectedOptions || []);
                if (selected.length === 0) return;
                selected.forEach(function(opt) {
                    var sid = opt.value;
                    try { window._registrar_removeSubjectRows(sid); } catch(e) { console.error(e); }
                });
                // give DOM a moment then refresh
                setTimeout(function(){ refreshListsForProgram(pid); }, 120);
            });

            // initial render for current program selection
            if (programSel && programSel.value) setTimeout(function(){ refreshListsForProgram(programSel.value); }, 300);
        } catch (e) {
            console.error('Transfer UI init error', e);
        }
    })();
    </script>
</body>
</html>
