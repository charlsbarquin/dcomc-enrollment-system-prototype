{{-- Single Create Schedule workspace: Deploy to students at top, then schedule table. --}}
@if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
        <ul class="list-disc pl-5 text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

{{-- Deploy to students (top): Program sets curriculum for Schedule table; Year level filters search; add students then Deploy. --}}
<div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-2">Deploy to students</h2>
    <p class="text-xs text-gray-500 mb-4">Set <strong>Program</strong> to define the curriculum for the Schedule table below (subjects will be limited to that program). Use <strong>Year level</strong> to show only irregulars from that year in the search. Add students to the table, then click Deploy. Deployed schedules are saved to <strong>Irregular COR Archive</strong>.</p>
    <form method="POST" action="{{ route('registrar.schedule.templates.deploy', $template->id) }}" id="deploy-form" onsubmit="return confirm('Deploy this schedule to the selected students? It will be saved to Irregular COR Archive.');">
        @csrf
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <div class="flex flex-col gap-1">
                <label for="deploy-program" class="text-xs font-medium text-gray-700">Program (curriculum for schedule)</label>
                <select id="deploy-program" class="border border-gray-300 rounded px-3 py-2 text-sm bg-white min-w-[180px]">
                    <option value="">— Select program —</option>
                    @foreach($programs ?? [] as $p)
                        <option value="{{ $p->id }}">{{ $p->program_name ?? $p->code ?? $p->id }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label for="deploy-year-level" class="text-xs font-medium text-gray-700">Year level</label>
                <select id="deploy-year-level" class="border border-gray-300 rounded px-3 py-2 text-sm bg-white min-w-[120px]">
                    <option value="">All years</option>
                    @foreach($yearLevels ?? [] as $yl)
                        <option value="{{ $yl }}">{{ $yl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-1 relative">
                <label for="student-search" class="text-xs font-medium text-gray-700">Search student</label>
                <input type="text" id="student-search" placeholder="Type to search (name, email, or student ID)..." class="border border-gray-300 rounded px-3 py-2 text-sm w-72" autocomplete="off">
                <div id="student-search-results" class="hidden absolute left-0 top-full mt-1 z-50 bg-white border border-gray-300 rounded shadow-lg max-h-48 overflow-y-auto w-72"></div>
            </div>
        </div>
        <table class="w-full text-sm border border-gray-200 mb-4">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 text-left">Name</th>
                    <th class="p-2 text-left w-32">Conflict</th>
                    <th class="p-2 w-20">Remove</th>
                </tr>
            </thead>
            <tbody id="deploy-students-tbody">
                <tr id="deploy-students-empty"><td colspan="3" class="p-4 text-gray-500 text-center">No students added. Use the search above to add irregular students.</td></tr>
            </tbody>
        </table>
        <div id="deploy-student-ids-container"></div>
        <div class="flex flex-wrap items-center gap-4">
            <button type="submit" id="deploy-btn" class="px-4 py-2 rounded font-semibold bg-green-600 hover:bg-green-700 text-white disabled:opacity-50 disabled:cursor-not-allowed" disabled>Deploy to selected students</button>
        </div>
    </form>
    @if($template->is_active ?? false)
        <form method="POST" action="{{ route('registrar.schedule.templates.undeploy', $template->id) }}" class="inline mt-2" onsubmit="return confirm('Undeploy? Listed students will no longer see this schedule in View COR.');">
            @csrf
            <button type="submit" class="px-4 py-2 rounded font-semibold bg-red-100 text-red-800 border border-red-300 hover:bg-red-200">Undeploy</button>
        </form>
    @endif
</div>

<form method="POST" action="{{ route('registrar.schedule.templates.update', $template->id) }}" id="schedule-edit-form">
    @csrf
    @method('PATCH')
    <input type="hidden" name="title" value="{{ old('title', $template->title ?? 'Irregular Schedule') }}">

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Schedule table</h2>
        <p class="text-sm text-gray-500 mb-4">The <strong>Program</strong> selected in <strong>Deploy to students</strong> above sets the curriculum here: when you choose a program there, the Add row subject list is limited to that program. You can still use <strong>Limit subjects by program</strong> below to change or turn it off to pick from all programs. Each row is independent. Slots come from COR Archive deployed/saved schedules only. Slot format: [Day] [Time] | [Room] | [Professor] | [Block]. Save when done.</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="schedule-slots-table">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 text-left">Program | Year level</th>
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
                        @endphp
                        @php
                            $rowYearLevel = $row['year_level'] ?? null;
                        @endphp
                        <tr class="border-t align-top schedule-slot-row" data-subject-id="{{ $subject->id }}" data-row-index="{{ $idx }}">
                            <td class="p-2">
                                <div class="flex flex-wrap items-center gap-1">
                                    <select name="slots[{{ $idx }}][program_id]" class="row-program-select border border-gray-300 rounded px-2 py-1.5 text-sm flex-1 min-w-0" data-row-index="{{ $idx }}" data-subject-id="{{ $subject->id }}" required>
                                        <option value="">— Program —</option>
                                        @foreach($programs as $p)
                                            <option value="{{ $p->id }}" {{ ($rowProgramId && (int)$p->id === (int)$rowProgramId) ? 'selected' : '' }}>{{ $p->program_name ?? $p->code ?? $p->id }}</option>
                                        @endforeach
                                    </select>
                                    <span class="text-gray-500 text-sm shrink-0">|</span>
                                    <select name="slots[{{ $idx }}][year_level]" class="row-year-level-select border border-gray-300 rounded px-2 py-1.5 text-sm flex-1 min-w-0" data-row-index="{{ $idx }}">
                                        <option value="">— Year —</option>
                                        @foreach($yearLevels ?? [] as $yl)
                                            <option value="{{ $yl }}" {{ ($rowYearLevel !== null && $rowYearLevel !== '' && $yl === $rowYearLevel) ? 'selected' : '' }}>{{ $yl }}</option>
                                        @endforeach
                                    </select>
                                </div>
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
                        <tr id="schedule-empty-row"><td colspan="6" class="p-4 text-center text-gray-500 text-sm">No subjects in schedule. Select Program | Year level above (Deploy to students), then search and select a subject, then Add row.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3 border-t pt-3">
            <p class="text-xs text-gray-600 mb-2">Add a row: optionally limit by program, then choose <strong>Subject</strong>, then click Add row. Schedule slot appears in the new row; Program column shows the subject’s program.</p>
            <div class="flex flex-wrap items-end gap-4">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-gray-700 flex items-center gap-2">
                        <input type="checkbox" id="limit-by-program" name="limit_by_program" value="1" checked class="rounded border-gray-300">
                        Limit subjects by program
                    </label>
                    <select id="add-row-program" name="add_row_program" class="border border-gray-300 rounded px-3 py-2 text-sm bg-white min-w-[220px]" required>
                        <option value="">— Select program first —</option>
                        @foreach($programs ?? [] as $p)
                            <option value="{{ $p->id }}">{{ $p->program_name ?? $p->code ?? $p->id }}</option>
                        @endforeach
                    </select>
                    <span id="add-row-program-hint" class="text-xs text-gray-500 hidden">All programs — subject list shows all programs.</span>
                </div>
                <div class="flex flex-col gap-1">
                    <label for="add-subject-search" class="text-sm font-medium text-gray-700">Subject <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="text" id="add-subject-search" placeholder="Select program first, then type to search..." class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white min-w-[280px]" autocomplete="off" disabled>
                        <input type="hidden" id="add-subject-id" value="">
                        <div id="add-subject-results" class="hidden absolute left-0 top-full mt-1 z-50 bg-white border border-gray-300 rounded shadow-lg max-h-56 overflow-y-auto w-full min-w-[280px]"></div>
                    </div>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-sm font-medium text-gray-700 opacity-0 select-none">.</span>
                    <button type="button" id="add-subject-btn" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap" disabled>Add row</button>
                </div>
            </div>
        </div>
        <details class="mt-6 border-t pt-4 group" id="manage-subjects-details">
            <summary class="list-none cursor-pointer flex items-center justify-between gap-2 py-1 -mx-1 rounded hover:bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-800">Manage subjects for a program (side-by-side)</h3>
                <span class="text-gray-400 text-xs select-none group-open:rotate-180 transition-transform inline-block" aria-hidden="true">▾</span>
            </summary>
            <p class="text-xs text-gray-500 mb-3 mt-2">Select a program to load available and scheduled subjects. Use Add → / ← Remove to move subjects.</p>
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
                    <label class="text-xs font-medium text-gray-700">Scheduled subjects</label>
                    <select id="scheduled-subjects-list" multiple size="8" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm bg-white"></select>
                </div>
            </div>
        </details>
    </div>

    <div class="flex flex-wrap items-center gap-4 mb-6">
        <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white px-6 py-2 rounded font-semibold">Save changes</button>
    </div>
</form>

<div id="conflict-modal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg max-w-lg w-full p-4">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-semibold text-gray-800">Conflicting subjects</h3>
            <button type="button" id="conflict-modal-close" class="text-gray-500 hover:text-gray-700 text-lg leading-none">&times;</button>
        </div>
        <p id="conflict-modal-student" class="text-xs text-gray-500 mb-3"></p>
        <div id="conflict-modal-list" class="max-h-64 overflow-y-auto text-sm text-gray-800"></div>
    </div>
</div>

@php
    $availableSubjectsForAddJson = isset($availableSubjectsForAdd) ? $availableSubjectsForAdd->map(function ($s) {
        return ['id' => $s->id, 'code' => $s->code ?? '', 'title' => $s->title ?? '', 'units' => (int)($s->units ?? 0)];
    })->values()->all() : [];
@endphp
<script>
(function() {
    var data = @json($scheduleScriptData ?? []);
    var nextSlotIndex = data.nextSlotIndex || 0;
    var programs = data.programs || [];
    var yearLevels = @json($yearLevels ?? []);
    var subjectsForProgramUrl = data.subjectsForProgramUrl || '';
    var subjectsForAllProgramsUrl = data.subjectsForAllProgramsUrl || '';
    var templateSemester = (data.templateSemester != null && data.templateSemester !== '') ? String(data.templateSemester) : '';
    var slotsForScopeUrl = data.slotsForScopeUrl || '';
    var conflictsUrl = data.conflictsUrl || '';
    var availableSubjectsForAdd = @json($availableSubjectsForAddJson);
    var token = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function escapeHtml(s) { if (!s) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function buildSlotOptionsHtml(slotOpts) {
        var html = '<option value="">— Select slot from COR Archive —</option>';
        for (var i = 0; i < (slotOpts || []).length; i++) {
            var o = slotOpts[i];
            var val = JSON.stringify({ day_of_week: o.day_of_week, start_time: o.start_time, end_time: o.end_time, room_id: o.room_id, professor_id: o.professor_id, block_id: o.block_id || null, is_overload: o.is_overload || false });
            html += '<option value="' + escapeHtml(val) + '">' + escapeHtml(o.label || '') + '</option>';
        }
        return html;
    }
    function removeSubjectRows(subjectId) {
        var tbody = document.getElementById('schedule-slots-tbody');
        if (!tbody) return;
        tbody.querySelectorAll('tr[data-subject-id="' + subjectId + '"]').forEach(function(tr) { tr.remove(); });
        var emptyRow = document.getElementById('schedule-empty-row');
        if (tbody.querySelectorAll('tr.schedule-slot-row').length === 0 && emptyRow) emptyRow.style.display = '';
        if (window._registrar_refreshConflicts) {
            try { window._registrar_refreshConflicts(); } catch (e) {}
        }
    }
    function refetchSlotsForRow(tr) {
        var programId = (tr.querySelector('.row-program-select') || {}).value || '';
        var yearLevel = (tr.querySelector('.row-year-level-select') || {}).value || '';
        var subjectId = (tr.querySelector('input[name*="[subject_id]"]') || {}).value || '';
        var slotSelect = tr.querySelector('.row-slot-select');
        var prevMsg = tr.querySelector('.text-amber-600');
        if (prevMsg) prevMsg.remove();
        if (!slotSelect) return;
        slotSelect.innerHTML = '<option value="">— Select slot from COR Archive —</option>';
        slotSelect.disabled = true;
        if (!programId || !subjectId) return;
        var url = slotsForScopeUrl + '?program_id=' + encodeURIComponent(programId) + '&subject_id=' + encodeURIComponent(subjectId);
        if (yearLevel) url += '&year_level=' + encodeURIComponent(yearLevel);
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                var opts = res.slots || [];
                slotSelect.innerHTML = buildSlotOptionsHtml(opts);
                slotSelect.disabled = opts.length === 0;
                if (opts.length === 0) { var span = document.createElement('span'); span.className = 'text-xs text-amber-600 block mt-1'; span.textContent = 'No Deployed Schedule Found'; slotSelect.parentNode.appendChild(span); }
            })
            .catch(function() { slotSelect.innerHTML = '<option value="">— Select slot from COR Archive —</option>'; slotSelect.disabled = true; });
    }
    function addSubjectRow(programId, subject, slotOpts, initialYearLevel) {
        if (!subject || !programId) return;
        var subjectIdNum = subject.id;
        var slotOptionsHtml = buildSlotOptionsHtml(slotOpts || []);
        var hasSlots = (slotOpts || []).length > 0;
        var idx = nextSlotIndex++;
        var ylOpts = (yearLevels || []).map(function(yl) { return '<option value="' + escapeHtml(yl) + '"' + (yl === (initialYearLevel || '') ? ' selected' : '') + '>' + escapeHtml(yl) + '</option>'; }).join('');
        var yearLevelSelectHtml = '<select name="slots[' + idx + '][year_level]" class="row-year-level-select border border-gray-300 rounded px-2 py-1.5 text-sm flex-1 min-w-0" data-row-index="' + idx + '"><option value="">— Year —</option>' + ylOpts + '</select>';
        var tr = document.createElement('tr');
        tr.className = 'border-t align-top schedule-slot-row';
        tr.setAttribute('data-subject-id', subjectIdNum);
        tr.setAttribute('data-row-index', idx);
        tr.innerHTML = '<td class="p-2"><div class="flex flex-wrap items-center gap-1"><select name="slots[' + idx + '][program_id]" class="row-program-select border border-gray-300 rounded px-2 py-1.5 text-sm flex-1 min-w-0" data-row-index="' + idx + '" data-subject-id="' + subjectIdNum + '" required><option value="">— Program —</option>' +
            programs.map(function(p) { return '<option value="' + p.id + '"' + (p.id == programId ? ' selected' : '') + '>' + escapeHtml(p.name) + '</option>'; }).join('') + '</select><span class="text-gray-500 text-sm shrink-0">|</span>' + yearLevelSelectHtml + '</div></td>' +
            '<td class="p-2 font-medium">' + escapeHtml(subject.code || '') + '</td><td class="p-2">' + escapeHtml(subject.title || '') + '</td><td class="p-2 text-center">' + (subject.units || 0) + '</td>' +
            '<td class="p-2"><input type="hidden" name="slots[' + idx + '][subject_id]" value="' + subjectIdNum + '"><select name="slots[' + idx + '][slot_data]" class="row-slot-select w-full min-w-[280px] border border-gray-300 rounded px-2 py-1.5 text-sm" data-row-index="' + idx + '"' + (hasSlots ? '' : ' disabled') + '>' + slotOptionsHtml + '</select>' + (!hasSlots ? '<span class="text-xs text-amber-600 block mt-1">No Deployed Schedule Found</span>' : '') + '</td>' +
            '<td class="p-2"><button type="button" class="remove-subject-btn px-2 py-1 text-red-600 hover:bg-red-50 rounded text-xs border border-red-200" data-subject-id="' + subjectIdNum + '">Remove</button></td>';
        var tbody = document.getElementById('schedule-slots-tbody');
        var emptyRow = document.getElementById('schedule-empty-row');
        if (emptyRow && emptyRow.closest('tbody') === tbody) { emptyRow.style.display = 'none'; tbody.insertBefore(tr, emptyRow); } else { tbody.appendChild(tr); }
        tr.querySelector('.remove-subject-btn').addEventListener('click', function() { removeSubjectRows(subjectIdNum); });
        tr.querySelector('.row-program-select').addEventListener('change', function() { refetchSlotsForRow(tr); });
        tr.querySelector('.row-year-level-select').addEventListener('change', function() { refetchSlotsForRow(tr); });
        if (window._registrar_refreshConflicts) {
            try { window._registrar_refreshConflicts(); } catch (e) {}
        }
    }
    document.getElementById('schedule-slots-tbody').addEventListener('change', function(e) {
        var tr = (e.target.closest && e.target.closest('tr.schedule-slot-row')) || null;
        if (tr && (e.target.classList.contains('row-program-select') || e.target.classList.contains('row-year-level-select'))) {
            refetchSlotsForRow(tr);
        }
    });
    document.getElementById('schedule-slots-tbody').addEventListener('click', function(e) {
        var removeSubjBtn = e.target.closest('.remove-subject-btn');
        if (removeSubjBtn) { e.preventDefault(); removeSubjectRows(removeSubjBtn.getAttribute('data-subject-id')); }
    });
    function isLimitByProgram() { return document.getElementById('limit-by-program').checked; }
    function updateAddRowButtonState() {
        var programId = document.getElementById('add-row-program').value;
        var subjectId = (document.getElementById('add-subject-id') || {}).value || '';
        var limit = isLimitByProgram();
        var btn = document.getElementById('add-subject-btn');
        if (btn) btn.disabled = limit ? (!programId || !subjectId) : !subjectId;
    }
    function applyLimitByProgramUI() {
        var limit = isLimitByProgram();
        var programSelect = document.getElementById('add-row-program');
        var programHint = document.getElementById('add-row-program-hint');
        var searchEl = document.getElementById('add-subject-search');
        if (limit) {
            programSelect.disabled = false;
            programSelect.removeAttribute('aria-disabled');
            programSelect.required = true;
            if (programHint) programHint.classList.add('hidden');
            if (!programSelect.value) { searchEl.disabled = true; searchEl.placeholder = 'Select program first, then type to search...'; } else { searchEl.disabled = false; searchEl.placeholder = 'Type to search subjects...'; }
        } else {
            programSelect.disabled = true;
            programSelect.required = false;
            programSelect.value = '';
            if (programHint) programHint.classList.remove('hidden');
            searchEl.disabled = false;
            searchEl.placeholder = 'Type to search subjects (all programs)...';
        }
        document.getElementById('add-subject-id').value = '';
        availableSubjectsForAdd = [];
        document.getElementById('add-subject-results').classList.add('hidden');
        updateAddRowButtonState();
    }
    document.getElementById('limit-by-program').addEventListener('change', applyLimitByProgramUI);
    // Deploy Program dropdown sets the curriculum for the Schedule table: sync to Add row program and limit by program
    var deployProgramEl = document.getElementById('deploy-program');
    if (deployProgramEl) {
        deployProgramEl.addEventListener('change', function() {
            var programId = this.value;
            var addRowProgram = document.getElementById('add-row-program');
            var limitByProgram = document.getElementById('limit-by-program');
            if (addRowProgram) addRowProgram.value = programId || '';
            if (limitByProgram) limitByProgram.checked = !!programId;
            applyLimitByProgramUI();
            if (programId) {
                fetch(subjectsForProgramUrl + '?program_id=' + encodeURIComponent(programId), { headers: { 'Accept': 'application/json' } })
                    .then(function(r) { return r.json(); })
                    .then(function(res) { availableSubjectsForAdd = res.subjects || []; updateAddRowButtonState(); })
                    .catch(function() { availableSubjectsForAdd = []; });
            }
        });
    }
    document.getElementById('add-row-program').addEventListener('change', function() {
        if (!isLimitByProgram()) return;
        var programId = this.value;
        var searchEl = document.getElementById('add-subject-search');
        document.getElementById('add-subject-id').value = '';
        updateAddRowButtonState();
        if (!programId) { searchEl.disabled = true; searchEl.placeholder = 'Select program first'; searchEl.value = ''; availableSubjectsForAdd = []; document.getElementById('add-subject-results').classList.add('hidden'); return; }
        searchEl.disabled = false;
        searchEl.placeholder = 'Type to search subjects...';
        fetch(subjectsForProgramUrl + '?program_id=' + encodeURIComponent(programId), { headers: { 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(res) { availableSubjectsForAdd = res.subjects || []; updateAddRowButtonState(); })
            .catch(function() { availableSubjectsForAdd = []; });
    });
    function loadSubjectResults(list, showProgramName) {
        var resultsEl = document.getElementById('add-subject-results');
        resultsEl.innerHTML = '';
        list.forEach(function(s) {
            var div = document.createElement('div');
            div.className = 'p-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100';
            div.setAttribute('data-id', s.id);
            div.setAttribute('data-code', s.code || '');
            div.setAttribute('data-title', s.title || '');
            div.setAttribute('data-units', s.units || 0);
            if (s.program_id != null) div.setAttribute('data-program-id', s.program_id);
            if (s.program_name != null) div.setAttribute('data-program-name', s.program_name || '');
            var label = (s.code || '') + ' — ' + (s.title || '') + ' (' + (s.units || 0) + ' u)';
            if (showProgramName && (s.program_name || s.program_id)) label += ' [' + (s.program_name || 'Program ' + s.program_id) + ']';
            div.textContent = label;
            resultsEl.appendChild(div);
        });
        if (list.length === 0) resultsEl.innerHTML = '<div class="p-3 text-gray-500 text-sm">No subjects found.</div>';
        resultsEl.classList.remove('hidden');
    }
    function fetchSubjectResultsForAddRow(q) {
        if (isLimitByProgram()) {
            var programId = document.getElementById('add-row-program').value;
            if (!programId) return Promise.resolve();
            var url = subjectsForProgramUrl + '?program_id=' + encodeURIComponent(programId) + (q ? '&q=' + encodeURIComponent(q) : '');
            return fetch(url, { headers: { 'Accept': 'application/json' } }).then(function(r) { return r.json(); }).then(function(res) { availableSubjectsForAdd = res.subjects || []; loadSubjectResults(res.subjects || [], false); });
        } else {
            var url = subjectsForAllProgramsUrl + (templateSemester ? '?semester=' + encodeURIComponent(templateSemester) : '');
            return fetch(url, { headers: { 'Accept': 'application/json' } }).then(function(r) { return r.json(); }).then(function(res) {
                var list = res.subjects || [];
                if (q) { var ql = q.toLowerCase(); list = list.filter(function(s) { return ((s.code || '').toLowerCase().indexOf(ql) >= 0) || ((s.title || '').toLowerCase().indexOf(ql) >= 0); }); }
                availableSubjectsForAdd = list;
                loadSubjectResults(list, true);
            });
        }
    }
    document.getElementById('add-subject-search').addEventListener('focus', function() {
        if (isLimitByProgram() && !document.getElementById('add-row-program').value) return;
        fetchSubjectResultsForAddRow(this.value.trim());
    });
    document.getElementById('add-subject-search').addEventListener('input', function() {
        if (isLimitByProgram() && !document.getElementById('add-row-program').value) return;
        fetchSubjectResultsForAddRow(this.value.trim());
    });
    document.getElementById('add-subject-results').addEventListener('mousedown', function(e) {
        var div = e.target.closest('[data-id]');
        if (!div) return;
        e.preventDefault();
        var id = div.getAttribute('data-id');
        var subject = { id: id, code: div.getAttribute('data-code'), title: div.getAttribute('data-title'), units: parseInt(div.getAttribute('data-units') || 0, 10) };
        var pid = div.getAttribute('data-program-id');
        if (pid != null && pid !== '') subject.program_id = pid;
        var pname = div.getAttribute('data-program-name');
        if (pname != null) subject.program_name = pname;
        document.getElementById('add-subject-id').value = id;
        document.getElementById('add-subject-search').value = (subject.code || '') + ' — ' + (subject.title || '');
        document.getElementById('add-subject-results').classList.add('hidden');
        updateAddRowButtonState();
    });
    document.getElementById('add-subject-btn').addEventListener('click', function() {
        var programId = document.getElementById('add-row-program').value;
        var subjectId = document.getElementById('add-subject-id').value;
        var subject = availableSubjectsForAdd.find(function(s) { return String(s.id) === String(subjectId); }) || { id: subjectId, code: '', title: '', units: 0 };
        if (!subjectId) return;
        var effectiveProgramId = isLimitByProgram() ? programId : (subject.program_id != null ? String(subject.program_id) : null);
        if (!effectiveProgramId) { alert('Could not determine program for this subject.'); return; }
        var deployYearLevel = (document.getElementById('deploy-year-level') || {}).value || '';
        var slotUrl = slotsForScopeUrl + '?program_id=' + encodeURIComponent(effectiveProgramId) + '&subject_id=' + encodeURIComponent(subjectId);
        if (deployYearLevel) slotUrl += '&year_level=' + encodeURIComponent(deployYearLevel);
        fetch(slotUrl, { headers: { 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(res) { addSubjectRow(effectiveProgramId, subject, res.slots || [], deployYearLevel); })
            .catch(function() { addSubjectRow(effectiveProgramId, subject, [], deployYearLevel); });
        document.getElementById('add-subject-id').value = '';
        document.getElementById('add-subject-search').value = '';
        updateAddRowButtonState();
    });
    window._registrar_addSubjectRow = addSubjectRow;
    window._registrar_removeSubjectRows = removeSubjectRows;
    window._registrar_availableSubjectsForAdd = function() { return availableSubjectsForAdd; };
    window._registrar_programs = programs;
    window._registrar_slotsForScopeUrl = slotsForScopeUrl;
    window._registrar_subjectsForProgramUrl = subjectsForProgramUrl;
    window._registrar_conflictsUrl = conflictsUrl;
    window._csrfToken = token;
})();
</script>
<script>
(function() {
    var deployStudentIds = [];
    var studentsSearchUrl = '{{ route("registrar.schedule.templates.students-search") }}';
    var conflictsUrl = window._registrar_conflictsUrl || '';
    var csrfToken = window._csrfToken || '';
    var conflictsByStudent = {};
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
    function collectCurrentSubjectIds() {
        var tbody = document.getElementById('schedule-slots-tbody');
        if (!tbody) return [];
        var ids = [];
        tbody.querySelectorAll('tr.schedule-slot-row').forEach(function(tr) {
            var sid = tr.getAttribute('data-subject-id');
            if (sid) ids.push(parseInt(sid, 10));
        });
        var seen = {};
        return ids.filter(function(id) {
            if (!id) return false;
            if (seen[id]) return false;
            seen[id] = true;
            return true;
        });
    }
    function applyConflictHighlights() {
        var tbody = document.getElementById('deploy-students-tbody');
        if (!tbody) return;
        tbody.querySelectorAll('tr.deploy-student-row').forEach(function(tr) {
            tr.classList.remove('bg-red-50');
            tr.removeAttribute('data-has-conflict');
            var conflictCell = tr.querySelector('.conflict-cell');
            if (conflictCell) conflictCell.innerHTML = '';
        });
        Object.keys(conflictsByStudent).forEach(function(studentId) {
            var row = document.querySelector('tr.deploy-student-row[data-student-id="' + studentId + '"]');
            if (!row) return;
            var items = conflictsByStudent[studentId] || [];
            if (!items.length) return;
            row.classList.add('bg-red-50');
            row.setAttribute('data-has-conflict', '1');
            var conflictCell = row.querySelector('.conflict-cell');
            if (conflictCell) {
                conflictCell.innerHTML = '<button type="button" class="conflict-btn text-xs text-red-700 underline" data-student-id="' + studentId + '">Conflict</button>';
            }
        });
    }
    function refreshConflicts() {
        if (!conflictsUrl || deployStudentIds.length === 0) {
            conflictsByStudent = {};
            applyConflictHighlights();
            return;
        }
        var subjectIds = collectCurrentSubjectIds();
        if (!subjectIds.length) {
            conflictsByStudent = {};
            applyConflictHighlights();
            return;
        }
        fetch(conflictsUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ student_ids: deployStudentIds, subject_ids: subjectIds })
        })
            .then(function(r) { return r.ok ? r.json() : Promise.reject(); })
            .then(function(res) {
                conflictsByStudent = res.conflicts_by_student || {};
                applyConflictHighlights();
            })
            .catch(function() {
                conflictsByStudent = {};
                applyConflictHighlights();
            });
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
        tr.innerHTML = '<td class="p-2">' + (student.name || '').replace(/</g,'&lt;') + '</td>' +
            '<td class="p-2 conflict-cell text-xs text-red-700"></td>' +
            '<td class="p-2"><button type="button" class="remove-deploy-student text-red-600 hover:text-red-800 text-sm font-semibold" data-id="' + student.id + '">Remove</button></td>';
        tbody.appendChild(tr);
        tr.querySelector('.remove-deploy-student').addEventListener('click', function() {
            deployStudentIds = deployStudentIds.filter(function(id) { return id !== student.id; });
            tr.remove();
            if (tbody.querySelectorAll('.deploy-student-row').length === 0 && document.getElementById('deploy-students-empty')) document.getElementById('deploy-students-empty').style.display = 'table-row';
            refreshDeployInputs();
        });
        refreshDeployInputs();
        refreshConflicts();
    }
    var lastStudentResults = [];
    function showStudentResults(list) {
        var resultsEl = document.getElementById('student-search-results');
        lastStudentResults = list || [];
        resultsEl.innerHTML = '';
        if (list.length === 0) resultsEl.innerHTML = '<div class="p-3 text-gray-500 text-sm">No irregular students found.</div>';
        else list.forEach(function(s) {
            var div = document.createElement('div');
            div.className = 'p-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100 student-search-item';
            div.setAttribute('data-id', String(s.id));
            div.setAttribute('data-name', s.name || '');
            div.textContent = s.name || '';
            resultsEl.appendChild(div);
        });
        resultsEl.classList.remove('hidden');
    }
    document.getElementById('student-search-results').addEventListener('mousedown', function(e) {
        var item = e.target.closest('.student-search-item');
        if (!item) return;
        e.preventDefault();
        var id = item.getAttribute('data-id');
        if (!id) return;
        var student = lastStudentResults.find(function(s) { return String(s.id) === String(id); });
        if (!student) return;
        addDeployStudent({ id: student.id, name: student.name, email: student.email, student_id: student.student_id });
        document.getElementById('student-search-results').classList.add('hidden');
        document.getElementById('student-search-results').innerHTML = '';
        document.getElementById('student-search').value = '';
    });
    function buildStudentsSearchUrl() {
        var q = (document.getElementById('student-search') || {}).value || '';
        var yl = (document.getElementById('deploy-year-level') || {}).value || '';
        var params = [];
        if (q.trim()) params.push('q=' + encodeURIComponent(q.trim()));
        if (yl) params.push('year_level=' + encodeURIComponent(yl));
        return studentsSearchUrl + (params.length ? '?' + params.join('&') : '');
    }
    document.getElementById('deploy-year-level').addEventListener('change', function() {
        fetch(buildStudentsSearchUrl(), { headers: { 'Accept': 'application/json' } }).then(function(r) { return r.json(); }).then(function(data) { showStudentResults(data.students || []); });
    });
    document.getElementById('student-search').addEventListener('focus', function() {
        fetch(buildStudentsSearchUrl(), { headers: { 'Accept': 'application/json' } }).then(function(r) { return r.json(); }).then(function(data) { showStudentResults(data.students || []); });
    });
    document.getElementById('student-search').addEventListener('input', function() {
        fetch(buildStudentsSearchUrl(), { headers: { 'Accept': 'application/json' } }).then(function(r) { return r.json(); }).then(function(data) { showStudentResults(data.students || []); });
    });
    document.getElementById('deploy-form').addEventListener('submit', function() { refreshDeployInputs(); });
    document.getElementById('deploy-students-tbody').addEventListener('click', function(e) {
        var btn = e.target.closest('.conflict-btn');
        if (!btn) return;
        var studentId = btn.getAttribute('data-student-id');
        var row = document.querySelector('tr.deploy-student-row[data-student-id="' + studentId + '"]');
        var items = conflictsByStudent[studentId] || [];
        var modal = document.getElementById('conflict-modal');
        var modalStudent = document.getElementById('conflict-modal-student');
        var modalList = document.getElementById('conflict-modal-list');
        if (!modal || !modalStudent || !modalList) return;
        var nameCell = row ? row.querySelector('td:first-child') : null;
        var studentName = nameCell ? nameCell.textContent.trim() : ('Student #' + studentId);
        modalStudent.textContent = 'Conflicting subjects for ' + studentName + ':';
        modalList.innerHTML = '';
        if (!items.length) {
            modalList.innerHTML = '<p class="text-sm text-gray-600">No conflicts found.</p>';
        } else {
            var ul = document.createElement('ul');
            ul.className = 'list-disc pl-5 text-sm';
            items.forEach(function(it) {
                var li = document.createElement('li');
                li.textContent = (it.subject_code || it.subject_title || ('Subject #' + it.subject_id)) + ' — ' + (it.reason || '');
                ul.appendChild(li);
            });
            modalList.appendChild(ul);
        }
        modal.classList.remove('hidden');
    });
    (function setupModal() {
        var modal = document.getElementById('conflict-modal');
        if (!modal) return;
        var closeBtn = document.getElementById('conflict-modal-close');
        function hide() { modal.classList.add('hidden'); }
        if (closeBtn) closeBtn.addEventListener('click', hide);
        modal.addEventListener('click', function(e) {
            if (e.target === modal) hide();
        });
    })();
    window._registrar_refreshConflicts = refreshConflicts;
})();
</script>
<script>
(function() {
    var programSel = document.getElementById('add-row-program');
    var availEl = document.getElementById('available-subjects-list');
    var schedEl = document.getElementById('scheduled-subjects-list');
    var addBtn = document.getElementById('add-selected-btn');
    var removeBtn = document.getElementById('remove-selected-btn');
    var slotsForScopeUrl = window._registrar_slotsForScopeUrl || '';
    function getAvailableSubjects() { return (window._registrar_availableSubjectsForAdd && window._registrar_availableSubjectsForAdd()) || []; }
    function getScheduledSubjectsForProgram(programId) {
        var rows = document.querySelectorAll('#schedule-slots-tbody tr.schedule-slot-row');
        var out = [];
        rows.forEach(function(tr) {
            var psel = tr.querySelector('.row-program-select');
            if (!psel || String(psel.value) !== String(programId)) return;
            var subjectId = tr.getAttribute('data-subject-id');
            var code = tr.querySelector('td:nth-child(2)') && tr.querySelector('td:nth-child(2)').textContent.trim();
            var title = tr.querySelector('td:nth-child(3)') && tr.querySelector('td:nth-child(3)').textContent.trim();
            if (subjectId) out.push({ id: subjectId, text: (code || '') + ' — ' + (title || '') });
        });
        return out;
    }
    function refreshListsForProgram(programId) {
        if (!availEl || !schedEl) return;
        availEl.innerHTML = '';
        schedEl.innerHTML = '';
        var scheduled = getScheduledSubjectsForProgram(programId).map(function(s){ return String(s.id); });
        getAvailableSubjects().forEach(function(s) {
            if (scheduled.indexOf(String(s.id)) !== -1) return;
            var opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = (s.code || '') + ' — ' + (s.title ? s.title.substring(0,60) : '') + ' (' + (s.units || 0) + 'u)';
            availEl.appendChild(opt);
        });
        getScheduledSubjectsForProgram(programId).forEach(function(s) {
            var opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.text;
            schedEl.appendChild(opt);
        });
    }
    if (programSel) programSel.addEventListener('change', function() { setTimeout(function() { refreshListsForProgram(programSel.value); }, 250); });
    if (addBtn) addBtn.addEventListener('click', function() {
        var pid = (programSel && programSel.value) || '';
        if (!pid) return alert('Select a program first');
        var selected = Array.prototype.slice.call(availEl.selectedOptions || []);
        selected.forEach(function(opt) {
            var sid = opt.value;
            var subject = getAvailableSubjects().find(function(x){ return String(x.id) === String(sid); });
            if (!subject) return;
            fetch(slotsForScopeUrl + '?program_id=' + encodeURIComponent(pid) + '&subject_id=' + encodeURIComponent(sid), { headers: { 'Accept': 'application/json' } })
                .then(function(r){ return r.json(); })
                .then(function(res){ window._registrar_addSubjectRow(pid, subject, res.slots || []); refreshListsForProgram(pid); })
                .catch(function(){ window._registrar_addSubjectRow(pid, subject, []); refreshListsForProgram(pid); });
        });
    });
    if (removeBtn) removeBtn.addEventListener('click', function() {
        var pid = (programSel && programSel.value) || '';
        if (!pid) return alert('Select a program first');
        var selected = Array.prototype.slice.call(schedEl.selectedOptions || []);
        selected.forEach(function(opt) { try { window._registrar_removeSubjectRows(opt.value); } catch(e) {} });
        setTimeout(function(){ refreshListsForProgram(pid); }, 120);
    });
    if (programSel && programSel.value) setTimeout(function(){ refreshListsForProgram(programSel.value); }, 300);
})();
</script>
