<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Program Schedule - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">
    @php
        $isStaff = request()->routeIs('staff.*');
        $programScheduleIndexRoute = $isStaff ? 'staff.program-schedule.index' : 'registrar.program-schedule.index';
        $programScheduleSaveRoute = $isStaff ? 'staff.program-schedule.save' : 'registrar.program-schedule.save';
        $dashRoute = $isStaff ? 'staff.dashboard' : 'registrar.dashboard';
    @endphp
    @include('dashboards.partials.role-sidebar')

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full max-w-5xl mx-auto">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-data shadow-sm">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm font-data shadow-sm">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-data">
                        <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif

                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Program Schedule</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Monitor subjects in the schedule (same data as the Dean's Schedule by Program). Day, time, room, and professor are set by the Dean and shown here read-only.</p>
                        </div>
                        <a href="{{ route($dashRoute) }}" class="btn-back-hero shrink-0 whitespace-nowrap">← Back to Dashboard</a>
                    </div>
                </section>

                @if(!empty($breadcrumb))
                    <nav class="flex items-center gap-2 text-sm text-gray-600 mb-4 font-data" aria-label="Breadcrumb">
                        @foreach($breadcrumb as $i => $item)
                            @if($i > 0)<span class="text-gray-400">/</span>@endif
                            <a href="{{ $item['url'] }}" class="text-[#1E40AF] hover:underline">{{ $item['label'] }}</a>
                        @endforeach
                    </nav>
                @endif

                @if(($viewMode ?? 'programs') === 'programs')
                    <div class="bg-white shadow-2xl rounded-xl border border-gray-200 p-6">
                        <h2 class="font-heading text-lg font-bold text-gray-800 mb-4">Programs</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @forelse($programs ?? [] as $prog)
                                @php $label = ($displayLabels ?? [])[$prog->program_name ?? ''] ?? $prog->code ?? $prog->program_name ?? 'Program'; @endphp
                                <a href="{{ route($programScheduleIndexRoute) }}?program={{ urlencode($prog->program_name ?? '') }}" class="folder-card-dcomc flex items-center gap-3 p-4">
                                    <div class="folder-preview-dcomc w-14 shrink-0 rounded-lg"><span class="text-xl text-[#1E40AF]/70">📁</span></div>
                                    <span class="font-medium text-gray-800 font-data">{{ $label }}</span>
                                </a>
                            @empty
                                <p class="text-sm text-gray-500 col-span-2 font-data">No programs defined.</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                @if(($viewMode ?? '') === 'years')
                    <div class="bg-white shadow-2xl rounded-xl border border-gray-200 p-6">
                        <h2 class="font-heading text-lg font-bold text-gray-800 mb-4">Year levels</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @foreach($yearLevels ?? [] as $yr)
                                <a href="{{ route($programScheduleIndexRoute) }}?program={{ urlencode($program) }}&year={{ urlencode($yr) }}{{ !empty($school_year) ? '&school_year=' . urlencode($school_year) : '' }}" class="folder-card-dcomc flex items-center gap-3 p-4">
                                    <div class="folder-preview-dcomc w-12 shrink-0 rounded-lg"><span class="text-lg text-[#1E40AF]/70">📁</span></div>
                                    <span class="font-medium text-gray-800 font-data">{{ $yr }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(($viewMode ?? '') === 'semesters')
                    <div class="bg-white shadow-2xl rounded-xl border border-gray-200 p-6">
                        <h2 class="font-heading text-lg font-bold text-gray-800 mb-4">Semesters</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($semesters ?? [] as $sem)
                                <a href="{{ route($programScheduleIndexRoute) }}?program={{ urlencode($program) }}&year={{ urlencode($year) }}&semester={{ urlencode($sem) }}{{ isset($school_year) && $school_year ? '&school_year=' . urlencode($school_year) : '' }}" class="folder-card-dcomc flex items-center gap-3 p-4">
                                    <div class="folder-preview-dcomc w-14 shrink-0 rounded-lg"><span class="text-xl text-[#1E40AF]/70">📁</span></div>
                                    <span class="font-medium text-gray-800 font-data">{{ $sem }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(($viewMode ?? '') === 'table')
                    <div class="bg-white shadow-2xl rounded-xl border border-gray-200 p-6 mb-6">
                        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                            <h2 class="font-heading text-lg font-bold text-gray-800">Schedule — {{ $programLabel ?? $program }} — {{ $year }} — {{ $semester ?? '' }}{{ !empty($school_year) ? ' — ' . $school_year : '' }}</h2>
                            @if(!empty($schoolYears))
                                <div class="flex items-center gap-2">
                                    <label for="school-year-select" class="text-sm font-medium text-gray-700 font-data">School year:</label>
                                    <select id="school-year-select" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white font-data input-dcomc-focus">
                                        @foreach($schoolYears as $sy)
                                            <option value="{{ $sy }}" {{ ($school_year ?? '') == $sy ? 'selected' : '' }}>{{ $sy }}</option>
                                        @endforeach
                                    </select>
                                    <script>
                                        document.getElementById('school-year-select').addEventListener('change', function() {
                                            var url = new URL(window.location.href);
                                            url.searchParams.set('school_year', this.value);
                                            window.location.href = url.toString();
                                        });
                                    </script>
                                </div>
                            @endif
                        </div>
                        <form method="POST" action="{{ route($programScheduleSaveRoute) }}" id="program-schedule-form">
                            @csrf
                            <input type="hidden" name="program_id" value="{{ $program_id }}">
                            <input type="hidden" name="academic_year_level_id" value="{{ $academic_year_level_id }}">
                            <input type="hidden" name="semester" value="{{ $semester }}">
                            <input type="hidden" name="school_year" value="{{ $school_year ?? '' }}">

                            <div class="overflow-x-auto rounded-xl border border-gray-200">
                                <table class="w-full text-sm font-data" id="program-schedule-table">
                                    <thead class="bg-gray-50 border-b border-gray-200">
                                        <tr>
                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Code</th>
                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Title</th>
                                            <th class="py-3 px-4 text-center font-heading font-bold text-gray-700">Units</th>
                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Day</th>
                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Start–End</th>
                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Room</th>
                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Professor</th>
                                            <th class="py-3 px-4 text-right font-heading font-bold text-gray-700">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="program-schedule-tbody" class="divide-y divide-gray-100 bg-white">
                                        @forelse($rows ?? [] as $row)
                                            @php
                                                $subject = $row['subject'];
                                                $slot = $row['slot'] ?? null;
                                            @endphp
                                            <tr class="schedule-row hover:bg-blue-50/50 transition-colors" data-subject-id="{{ $subject->id }}" data-code="{{ $subject->code ?? '' }}" data-title="{{ e($subject->title ?? '') }}" data-units="{{ $subject->units ?? 0 }}">
                                                <td class="py-3 px-4 font-medium">{{ $subject->code }}</td>
                                                <td class="py-3 px-4">{{ $subject->title }}</td>
                                                <td class="py-3 px-4 text-center">{{ $subject->units ?? 0 }}</td>
                                                <td class="py-3 px-4 text-gray-600">{{ $slot && $slot->day_of_week !== null ? \App\Models\ScopeScheduleSlot::dayName((int) $slot->day_of_week) : '—' }}</td>
                                                <td class="py-3 px-4 text-gray-600">@if($slot && $slot->start_time && $slot->end_time){{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}@else — @endif</td>
                                                <td class="py-3 px-4 text-gray-600">{{ $slot && $slot->room ? ($slot->room->code ?? $slot->room->name) : '—' }}</td>
                                                <td class="py-3 px-4 text-gray-600">{{ $slot && $slot->professor ? $slot->professor->name : '—' }}</td>
                                                <td class="py-3 px-4 text-right">
                                                    <button type="button" class="remove-subject-btn btn-secondary py-1.5 px-3 text-xs" data-subject-id="{{ $subject->id }}">Remove</button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr id="empty-row"><td colspan="8" class="py-8 px-4 text-center text-gray-500 text-sm font-data">No subjects in schedule. Add a subject below or push from Settings → Subjects.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                                <div id="subject-ids-container" style="display:none;">
                                    @foreach($rows ?? [] as $row)
                                        <input type="hidden" name="subject_ids[]" value="{{ $row['subject']->id }}" data-subject-id="{{ $row['subject']->id }}">
                                    @endforeach
                                </div>
                                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-200 pt-4 px-1">
                                    <span class="text-sm font-medium text-gray-700 font-data">Add subject:</span>
                                    <select id="add-subject-select" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white font-data input-dcomc-focus min-w-[200px]">
                                        <option value="">— Select subject —</option>
                                        @foreach($availableSubjectsForAdd ?? [] as $s)
                                            <option value="{{ $s->id }}" data-code="{{ $s->code }}" data-title="{{ $s->title }}" data-units="{{ $s->units ?? 0 }}">{{ $s->code }} — {{ Str::limit($s->title, 40) }} ({{ $s->units ?? 0 }} u)</option>
                                        @endforeach
                                    </select>
                                    <button type="button" id="add-subject-btn" class="btn-primary">Add</button>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2 items-center">
                                <button type="submit" class="btn-primary">Save</button>
                                <a href="{{ route($programScheduleIndexRoute) }}?program={{ urlencode($program) }}&year={{ urlencode($year) }}" class="btn-secondary">Back to semesters</a>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </main>
    <script>
    (function() {
        var form = document.getElementById('program-schedule-form');
        if (!form) return;
        var tbody = document.getElementById('program-schedule-tbody');
        var container = document.getElementById('subject-ids-container');
        var addSelect = document.getElementById('add-subject-select');
        var addBtn = document.getElementById('add-subject-btn');
        function removeSubject(subjectId) {
            subjectId = String(subjectId);
            tbody.querySelectorAll('tr[data-subject-id="' + subjectId + '"]').forEach(function(tr) { tr.remove(); });
            if (container) {
                container.querySelectorAll('input[data-subject-id="' + subjectId + '"]').forEach(function(inp) { inp.remove(); });
            }
            var emptyRow = document.getElementById('empty-row');
            if (tbody.querySelectorAll('tr.schedule-row').length === 0 && emptyRow) {
                emptyRow.style.display = '';
            }
        }
        function addSubject(subjectId, code, title, units) {
            subjectId = String(subjectId);
            code = code || ''; title = title || ''; units = units || 0;
            var emptyRow = document.getElementById('empty-row');
            if (emptyRow) emptyRow.style.display = 'none';
            var tr = document.createElement('tr');
            tr.className = 'border-t schedule-row';
            tr.setAttribute('data-subject-id', subjectId);
            tr.setAttribute('data-code', code);
            tr.setAttribute('data-title', title);
            tr.setAttribute('data-units', units);
            tr.innerHTML = '<td class="py-3 px-4 font-medium">' + (code).replace(/</g,'&lt;') + '</td><td class="py-3 px-4">' + (title).replace(/</g,'&lt;') + '</td><td class="py-3 px-4 text-center">' + units + '</td><td class="py-3 px-4 text-gray-600">—</td><td class="py-3 px-4 text-gray-600">—</td><td class="py-3 px-4 text-gray-600">—</td><td class="py-3 px-4 text-gray-600">—</td><td class="py-3 px-4 text-right"><button type="button" class="remove-subject-btn btn-secondary py-1.5 px-3 text-xs" data-subject-id="' + subjectId + '">Remove</button></td>';
            if (emptyRow && emptyRow.parentNode) {
                tbody.insertBefore(tr, emptyRow);
            } else {
                tbody.appendChild(tr);
            }
            if (container) {
                var hid = document.createElement('input');
                hid.type = 'hidden';
                hid.name = 'subject_ids[]';
                hid.value = subjectId;
                hid.setAttribute('data-subject-id', subjectId);
                container.appendChild(hid);
            }
            tr.querySelector('.remove-subject-btn').addEventListener('click', function() {
                removeSubject(subjectId);
                if (addSelect) {
                    var opt = document.createElement('option');
                    opt.value = subjectId;
                    opt.setAttribute('data-code', code);
                    opt.setAttribute('data-title', title);
                    opt.setAttribute('data-units', units);
                    opt.textContent = (code + ' — ' + (title.length > 40 ? title.substring(0, 40) + '...' : title) + ' (' + units + ' u)').replace(/</g,'&lt;');
                    addSelect.appendChild(opt);
                }
            });
            var opt = addSelect && addSelect.querySelector('option[value="' + subjectId + '"]');
            if (opt) opt.remove();
            if (addSelect) addSelect.value = '';
        }
        tbody.addEventListener('click', function(e) {
            var btn = e.target.closest('.remove-subject-btn');
            if (btn) {
                e.preventDefault();
                var subjectId = btn.getAttribute('data-subject-id');
                var row = btn.closest('tr.schedule-row');
                var code = row ? row.getAttribute('data-code') || '' : '';
                var title = row ? row.getAttribute('data-title') || '' : '';
                var units = row ? row.getAttribute('data-units') || 0 : 0;
                removeSubject(subjectId);
                if (addSelect) {
                    var opt = document.createElement('option');
                    opt.value = subjectId;
                    opt.setAttribute('data-code', code);
                    opt.setAttribute('data-title', title);
                    opt.setAttribute('data-units', units);
                    opt.textContent = (code + ' — ' + (title.length > 40 ? title.substring(0, 40) + '...' : title) + ' (' + units + ' u)').replace(/</g,'&lt;');
                    addSelect.appendChild(opt);
                }
            }
        });
        if (addBtn && addSelect) {
            addBtn.addEventListener('click', function() {
                if (!addSelect.value) return;
                var opt = addSelect.options[addSelect.selectedIndex];
                addSubject(addSelect.value, opt.dataset.code, opt.dataset.title, opt.dataset.units);
            });
        }
    })();
    </script>
</body>
</html>
