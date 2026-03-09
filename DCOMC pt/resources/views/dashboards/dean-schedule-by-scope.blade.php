<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Schedule by Program - Dean</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">

    @include('dashboards.partials.dean-sidebar')

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full max-w-6xl mx-auto">
            <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Schedule by Program</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">In your department. Open Program → Year → Semester to edit time, day, room, and professor. You can assign only professors and rooms from your department. This COR Archive is for transferee, returnee, and regular students; shifter/irregular students use a separate COR from the registrar’s Create Schedule.</p>
                </div>
                <a href="{{ route('cor.archive.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-base font-bold text-[#1E40AF] bg-white hover:bg-gray-100 no-underline transition-colors shrink-0 font-heading">COR Archive</a>
                    </div>
                </section>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-data shadow-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm font-data">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-data">
                    <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            @if(!empty($breadcrumb) && ($viewMode ?? '') !== 'table')
                <nav class="flex items-center gap-2 text-sm text-gray-600 mb-4 font-data" aria-label="Breadcrumb">
                    @foreach($breadcrumb as $i => $item)
                        @if($i > 0)<span class="text-gray-400">/</span>@endif
                        @if(!empty($item['url']))<a href="{{ $item['url'] }}" class="text-[#1E40AF] hover:underline no-underline">{{ $item['label'] }}</a>@else<span>{{ $item['label'] }}</span>@endif
                    @endforeach
                </nav>
            @endif

            @if(($viewMode ?? 'programs') === 'programs')
                <div class="bg-white shadow-2xl rounded-xl border border-gray-200 p-6">
                    <h2 class="font-heading text-lg font-bold text-gray-800 mb-4">Programs (your department)</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @forelse($programs ?? [] as $prog)
                            @php
                                $programModel = \App\Models\Program::where('program_name', $prog)->first();
                                if ($programModel && !empty($programModel->code)) {
                                    $label = $programModel->code;
                                } else {
                                    $label = ($displayLabels ?? [])[$prog] ?? $prog;
                                }
                            @endphp
                            <a href="{{ route('dean.schedule.by-scope') }}?program={{ urlencode($prog) }}" class="folder-card-dcomc flex items-center gap-3 p-4 no-underline text-gray-800">
                                <div class="folder-preview-dcomc w-16 shrink-0 rounded-lg flex items-center justify-center"><span class="text-2xl text-[#1E40AF]/70">📁</span></div>
                                <span class="font-medium font-data">{{ $label }}</span>
                            </a>
                        @empty
                            <p class="text-sm text-gray-500 col-span-2 font-data">No programs in your department. Contact the administrator.</p>
                        @endforelse
                    </div>
                </div>
            @endif

            @if(($viewMode ?? '') === 'years')
                <div class="bg-white shadow-2xl rounded-xl border border-gray-200 p-6">
                    <h2 class="font-heading text-lg font-bold text-gray-800 mb-4">Year levels</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach($yearLevels ?? [] as $yr)
                            <a href="{{ route('dean.schedule.by-scope') }}?program={{ urlencode($program) }}&year={{ urlencode($yr) }}" class="folder-card-dcomc flex items-center gap-3 p-4 no-underline text-gray-800">
                                <div class="folder-preview-dcomc w-16 shrink-0 rounded-lg flex items-center justify-center"><span class="text-2xl text-[#1E40AF]/70">📁</span></div>
                                <span class="font-medium font-data">{{ $yr }}</span>
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
                            <a href="{{ route('dean.schedule.by-scope') }}?program={{ urlencode($program) }}&year={{ urlencode($year) }}&semester={{ urlencode($sem) }}" class="folder-card-dcomc flex items-center gap-3 p-4 no-underline text-gray-800">
                                <div class="folder-preview-dcomc w-16 shrink-0 rounded-lg flex items-center justify-center"><span class="text-2xl text-[#1E40AF]/70">📁</span></div>
                                <span class="font-medium font-data">{{ $sem }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(($viewMode ?? '') === 'table')
                <div class="bg-white shadow-2xl rounded-xl border border-gray-200 overflow-hidden mb-6">
                    <div class="bg-[#1E40AF] px-6 py-4">
                        <h2 class="font-heading text-lg font-bold text-white">Schedule by Program</h2>
                        <p class="text-white/90 text-sm font-data mt-1">Edit day, time, room, and professor for each subject. Then click Save schedule.</p>
                    </div>
                    {{-- Sticky scope indicator: Program — Year — Semester (and School year dropdown) so current scope is always visible --}}
                    <div class="sticky top-0 z-10 flex flex-wrap items-center gap-3 px-6 py-3 bg-gray-50 border-b border-gray-200 shadow-sm">
                        <span class="text-sm font-medium text-gray-500 font-data">Current scope:</span>
                        <div class="flex items-center gap-2 flex-wrap">
                            <a href="{{ route('dean.schedule.by-scope') }}?program={{ urlencode($program) }}" class="text-[#1E40AF] font-semibold hover:underline no-underline font-data">{{ $programLabel ?? $program }}</a>
                            <span class="text-gray-400 font-data">—</span>
                            <a href="{{ route('dean.schedule.by-scope') }}?program={{ urlencode($program) }}&year={{ urlencode($year) }}" class="text-[#1E40AF] font-semibold hover:underline no-underline font-data">{{ $year }}</a>
                            <span class="text-gray-400 font-data">—</span>
                            <span class="font-semibold text-gray-800 font-data">{{ $semester ?? '' }}</span>
                            @if(!empty($school_year))
                                <span class="text-gray-400 font-data">·</span>
                                <span class="text-gray-700 font-data">{{ $school_year }}</span>
                            @endif
                        </div>
                        @if(!empty($schoolYears))
                            <div class="flex items-center gap-2 ml-auto">
                                <label class="text-sm font-medium text-gray-600 font-heading whitespace-nowrap">School year:</label>
                                <select id="school-year-select" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white font-data focus:ring-2 focus:ring-[#1E40AF] focus:border-[#1E40AF] min-w-[120px]">
                                    @foreach($schoolYears as $sy)
                                        <option value="{{ $sy }}" {{ ($school_year ?? '') == $sy ? 'selected' : '' }}>{{ $sy }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <script>
                                document.getElementById('school-year-select').addEventListener('change', function() {
                                    var url = new URL(window.location.href);
                                    url.searchParams.set('school_year', this.value);
                                    window.location.href = url.toString();
                                });
                            </script>
                        @endif
                    </div>
                    <div class="p-6">
                    <form method="POST" action="{{ route('dean.schedule.slots.save') }}" id="schedule-slots-form">
                        @csrf
                        <input type="hidden" name="program_id" value="{{ $program_id }}">
                        <input type="hidden" name="academic_year_level_id" value="{{ $academic_year_level_id }}">
                        <input type="hidden" name="semester" value="{{ $semester }}">
                        <input type="hidden" name="school_year" value="{{ $school_year ?? '' }}">
                        <input type="hidden" name="year" value="{{ $year ?? '' }}">

                        @if($subjects->isEmpty())
                            <p class="text-sm text-gray-500 font-data">No subjects for this program, year, and semester. Add subjects in Subject Settings (Registrar) first.</p>
                        @else
                            @php $days = $scheduleSlotScriptData['days']; @endphp
                            <div class="overflow-x-auto -mx-6">
                                <table class="w-full text-sm font-data min-w-[900px]" id="schedule-slots-table">
                                    <thead class="bg-[#1E40AF]">
                                        <tr>
                                            <th class="p-3 text-left font-heading font-bold text-white whitespace-nowrap">Code</th>
                                            <th class="p-3 text-left font-heading font-bold text-white min-w-[140px]">Title</th>
                                            <th class="p-3 text-center font-heading font-bold text-white w-16">Units</th>
                                            <th class="p-3 text-left font-heading font-bold text-white w-28">Day</th>
                                            <th class="p-3 text-left font-heading font-bold text-white w-24">Start</th>
                                            <th class="p-3 text-left font-heading font-bold text-white w-24">End</th>
                                            <th class="p-3 text-left font-heading font-bold text-white min-w-[100px]">Room</th>
                                            <th class="p-3 text-left font-heading font-bold text-white min-w-[140px]">Professor</th>
                                            <th class="p-3 text-right font-heading font-bold text-white min-w-[160px]">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="schedule-slots-tbody" class="divide-y divide-gray-100">
                                        @forelse($slotRows as $row)
                                            @php
                                                $subject = $row['subject'];
                                                $slot = $row['slot'];
                                                $idx = $row['slot_index'];
                                                $isFirstSlot = $loop->index === 0 || ($slotRows[$loop->index - 1]['subject']->id ?? null) !== $subject->id;
                                                $isExtraSlot = !$isFirstSlot;
                                                $subjectProfessors = $professorsPerSubject[$subject->id] ?? collect();
                                            @endphp
                                            <tr class="align-top schedule-slot-row hover:bg-blue-50/50 transition-colors" data-subject-id="{{ $subject->id }}">
                                                <td class="p-3 {{ $isExtraSlot ? 'text-gray-400' : 'font-medium text-gray-800' }}">{{ $isFirstSlot ? $subject->code : '—' }}</td>
                                                <td class="p-3 {{ $isExtraSlot ? 'text-gray-400' : 'text-gray-800' }}">{{ $isFirstSlot ? $subject->title : '—' }}</td>
                                                <td class="p-3 text-center {{ $isExtraSlot ? 'text-gray-400' : 'text-gray-800' }}">{{ $isFirstSlot ? $subject->units : '—' }}</td>
                                                <td class="p-3">
                                                    <input type="hidden" name="slots[{{ $idx }}][subject_id]" value="{{ $subject->id }}">
                                                    <select name="slots[{{ $idx }}][day_of_week]" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm day-select focus:ring-2 focus:ring-[#1E40AF] focus:border-[#1E40AF] min-w-[100px]">
                                                        <option value="" {{ !$slot || $slot->day_of_week === null ? 'selected' : '' }}>—</option>
                                                        @foreach($days as $d => $label)
                                                            <option value="{{ $d }}" {{ ($slot && $slot->day_of_week == $d) ? 'selected' : '' }}>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="p-3">
                                                    <input type="time" name="slots[{{ $idx }}][start_time]" value="{{ $slot ? \Carbon\Carbon::parse($slot->start_time)->format('H:i') : '08:00' }}" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-[#1E40AF] focus:border-[#1E40AF]">
                                                </td>
                                                <td class="p-3">
                                                    <input type="time" name="slots[{{ $idx }}][end_time]" value="{{ $slot ? \Carbon\Carbon::parse($slot->end_time)->format('H:i') : '09:00' }}" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-[#1E40AF] focus:border-[#1E40AF]">
                                                </td>
                                                <td class="p-3">
                                                    <select name="slots[{{ $idx }}][room_id]" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm room-select focus:ring-2 focus:ring-[#1E40AF] focus:border-[#1E40AF] min-w-[90px]" data-slot-index="{{ $idx }}" data-day="{{ $slot ? $slot->day_of_week : 1 }}" data-start="{{ $slot && $slot->start_time ? \Carbon\Carbon::parse($slot->start_time)->format('H:i') : '08:00' }}" data-end="{{ $slot && $slot->end_time ? \Carbon\Carbon::parse($slot->end_time)->format('H:i') : '09:00' }}">
                                                        <option value="">—</option>
                                                        @foreach(($row['available_room_ids'] ?? []) as $rid)
                                                            @php $room = $rooms->firstWhere('id', $rid); @endphp
                                                            @if($room)
                                                                <option value="{{ $room->id }}" {{ ($slot && $slot->room_id == $room->id) ? 'selected' : '' }}>{{ $room->code ?? $room->name }}</option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                    @if(empty($row['available_room_ids']))
                                                        <span class="text-xs text-amber-600 block mt-1">No rooms at this day/time</span>
                                                    @endif
                                                </td>
                                                <td class="p-3">
                                                    @if($isFirstSlot)
                                                        <select name="slots[{{ $idx }}][professor_id]" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-[#1E40AF] focus:border-[#1E40AF] min-w-[120px]">
                                                            <option value="">—</option>
                                                            @foreach($subjectProfessors as $prof)
                                                                <option value="{{ $prof->id }}" {{ ($slot && $slot->professor_id == $prof->id) ? 'selected' : '' }}>{{ $prof->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        @if($subjectProfessors->isEmpty())
                                                            <span class="text-xs text-amber-600 block mt-1">Assign in Manage Professor first</span>
                                                        @endif
                                                    @else
                                                        <input type="hidden" name="slots[{{ $idx }}][professor_id]" value="">
                                                        <span class="text-sm text-gray-500">Same as first day</span>
                                                    @endif
                                                </td>
                                                <td class="p-3 text-right">
                                                    @if($isFirstSlot)
                                                        <div class="flex flex-wrap gap-2 items-center justify-end">
                                                            <button type="button" class="add-day-btn px-3 py-2 rounded-lg bg-[#1E40AF] text-white text-xs font-medium hover:bg-[#1D3A8A] no-underline" data-subject-id="{{ $subject->id }}" title="Add another day for this subject">+ Add day</button>
                                                            <button type="button" class="remove-subject-btn px-3 py-2 rounded-lg border border-red-300 text-red-700 text-xs font-medium hover:bg-red-50 no-underline" data-subject-id="{{ $subject->id }}" title="Remove this subject from schedule">Remove</button>
                                                        </div>
                                                    @else
                                                        <button type="button" class="remove-day-btn px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg text-xs font-medium no-underline">Remove day</button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="9" class="p-8 text-center text-gray-500 text-sm font-data">No subjects in schedule. Add a subject below.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                                <div class="mt-3 flex flex-wrap items-center gap-2 border-t pt-3">
                                    <span class="text-sm font-medium text-gray-700">Add subject:</span>
                                    <select id="add-subject-select" class="border border-gray-300 rounded px-2 py-1.5 text-sm bg-white min-w-[200px]">
                                        <option value="">— Select subject —</option>
                                        @foreach($availableSubjectsForAdd ?? [] as $s)
                                            <option value="{{ $s->id }}" data-code="{{ $s->code }}" data-title="{{ $s->title }}" data-units="{{ $s->units ?? 0 }}">{{ $s->code }} — {{ Str::limit($s->title, 40) }} ({{ $s->units ?? 0 }} u)</option>
                                        @endforeach
                                    </select>
                                    <button type="button" id="add-subject-btn" class="px-3 py-2 rounded-lg bg-[#1E40AF] hover:bg-[#1D3A8A] text-white text-sm font-medium no-underline">Add row</button>
                                </div>
                                <script>
                                (function() {
                                    var data = @json($scheduleSlotScriptData);
                                    var nextSlotIndex = data.nextSlotIndex;
                                    var days = data.days;
                                    var rooms = data.rooms;
                                    var roomOccupancy = data.roomOccupancy || [];
                                    var professorOccupancy = data.professorOccupancy || [];
                                    // #region agent log (disabled for offline deployment)
                                    // fetch('http://127.0.0.1:7344/ingest/...') ...
                                    // #endregion
                                    var professorsBySubject = data.professorsBySubject;
                                    var subjects = data.subjects;

                                    function timesOverlap(startA, endA, startB, endB) {
                                        var sA = (startA || '08:00').toString().replace(/^(\d{1,2}):(\d{2})$/, function(_, h, m) { return (parseInt(h,10)*60)+parseInt(m,10); });
                                        var eA = (endA || '09:00').toString().replace(/^(\d{1,2}):(\d{2})$/, function(_, h, m) { return (parseInt(h,10)*60)+parseInt(m,10); });
                                        var sB = (startB || '08:00').toString().replace(/^(\d{1,2}):(\d{2})$/, function(_, h, m) { return (parseInt(h,10)*60)+parseInt(m,10); });
                                        var eB = (endB || '09:00').toString().replace(/^(\d{1,2}):(\d{2})$/, function(_, h, m) { return (parseInt(h,10)*60)+parseInt(m,10); });
                                        return sA < eB && eA > sB;
                                    }
                                    function isRoomOccupied(roomId, day, start, end) {
                                        day = parseInt(day, 10);
                                        for (var i = 0; i < roomOccupancy.length; i++) {
                                            var o = roomOccupancy[i];
                                            if (o.room_id !== roomId || (o.day_of_week !== day)) continue;
                                            if (timesOverlap(start, end, o.start_time, o.end_time)) return true;
                                        }
                                        return false;
                                    }
                                    function getAvailableRooms(day, start, end) {
                                        return rooms.filter(function(r) { return !isRoomOccupied(r.id, day, start, end); });
                                    }
                                    function isRoomOccupiedWithList(roomId, day, start, end, occupancyList) {
                                        day = parseInt(day, 10);
                                        for (var i = 0; i < occupancyList.length; i++) {
                                            var o = occupancyList[i];
                                            if (o.room_id != roomId || (o.day_of_week != day)) continue;
                                            if (timesOverlap(start, end, o.start_time, o.end_time)) return true;
                                        }
                                        return false;
                                    }
                                    function getAvailableRoomsWithOccupancy(day, start, end, occupancyList) {
                                        return rooms.filter(function(r) { return !isRoomOccupiedWithList(r.id, day, start, end, occupancyList); });
                                    }
                                    function getOccupancyFromOtherRows(excludeRow) {
                                        var tbody = document.getElementById('schedule-slots-tbody');
                                        if (!tbody) return [];
                                        var list = [];
                                        var rows = tbody.querySelectorAll('tr.schedule-slot-row');
                                        rows.forEach(function(tr) {
                                            if (tr === excludeRow) return;
                                            var daySel = tr.querySelector('select[name*="[day_of_week]"]');
                                            var startInp = tr.querySelector('input[name*="[start_time]"]');
                                            var endInp = tr.querySelector('input[name*="[end_time]"]');
                                            var roomSel = tr.querySelector('select[name*="[room_id]"]');
                                            if (!daySel || !startInp || !endInp || !roomSel || !roomSel.value) return;
                                            var start = (startInp.value || '08:00').substring(0, 5);
                                            var end = (endInp.value || '09:00').substring(0, 5);
                                            if (end <= start) return;
                                            list.push({
                                                room_id: parseInt(roomSel.value, 10),
                                                day_of_week: parseInt(daySel.value, 10),
                                                start_time: start,
                                                end_time: end
                                            });
                                        });
                                        return list;
                                    }
                                    function getProfessorOccupancyFromOtherRows(excludeRow) {
                                        var tbody = document.getElementById('schedule-slots-tbody');
                                        if (!tbody) return [];
                                        var list = [];
                                        var rows = tbody.querySelectorAll('tr.schedule-slot-row');
                                        rows.forEach(function(tr) {
                                            if (tr === excludeRow) return;
                                            var daySel = tr.querySelector('select[name*="[day_of_week]"]');
                                            var startInp = tr.querySelector('input[name*="[start_time]"]');
                                            var endInp = tr.querySelector('input[name*="[end_time]"]');
                                            var profSel = tr.querySelector('select[name*="[professor_id]"]');
                                            if (!daySel || !startInp || !endInp) return;
                                            var profId = profSel ? profSel.value : (tr.querySelector('input[name*="[professor_id]"]') || {}).value;
                                            if (!profId) return;
                                            var start = (startInp.value || '08:00').substring(0, 5);
                                            var end = (endInp.value || '09:00').substring(0, 5);
                                            if (end <= start) return;
                                            list.push({
                                                professor_id: parseInt(profId, 10),
                                                day_of_week: parseInt(daySel.value, 10),
                                                start_time: start,
                                                end_time: end
                                            });
                                        });
                                        return list;
                                    }
                                    function isProfessorOccupied(profId, day, start, end, occupancyList) {
                                        day = parseInt(day, 10);
                                        if (!day || day < 1 || day > 7) return false;
                                        for (var i = 0; i < occupancyList.length; i++) {
                                            var o = occupancyList[i];
                                            if (parseInt(o.professor_id, 10) !== parseInt(profId, 10) || parseInt(o.day_of_week, 10) !== day) continue;
                                            if (timesOverlap(start, end, o.start_time || '08:00', o.end_time || '09:00')) return true;
                                        }
                                        return false;
                                    }
                                    function updateRoomDropdownForRow(row) {
                                        var daySel = row.querySelector('select[name*="[day_of_week]"]');
                                        var startInp = row.querySelector('input[name*="[start_time]"]');
                                        var endInp = row.querySelector('input[name*="[end_time]"]');
                                        var roomSel = row.querySelector('select[name*="[room_id]"]');
                                        if (!daySel || !startInp || !endInp || !roomSel) return;
                                        var day = daySel.value;
                                        var start = (startInp.value || '08:00').substring(0, 5);
                                        var end = (endInp.value || '09:00').substring(0, 5);
                                        if (end <= start) return;
                                        // When day is not selected, do not show rooms (avoids showing occupied rooms as available).
                                        var availableRooms = [];
                                        if (day && day !== '0') {
                                            var otherOccupancy = getOccupancyFromOtherRows(row);
                                            var mergedOccupancy = roomOccupancy.concat(otherOccupancy);
                                            // #region agent log (disabled for offline deployment)
                                            var occForDay = mergedOccupancy.filter(function(o){ return String(o.day_of_week) === String(day); });
                                            // fetch('http://127.0.0.1:7344/ingest/...') ...
                                            // #endregion
                                            availableRooms = getAvailableRoomsWithOccupancy(day, start, end, mergedOccupancy);
                                            // #region agent log (disabled for offline deployment)
                                            // fetch('http://127.0.0.1:7344/ingest/...') ...
                                            // #endregion
                                        }
                                        var currentVal = roomSel.value;
                                        roomSel.innerHTML = '<option value="">—</option>';
                                        availableRooms.forEach(function(r) {
                                            var opt = document.createElement('option');
                                            opt.value = r.id;
                                            opt.textContent = (r.name || '').replace(/</g, '&lt;');
                                            if (String(r.id) === String(currentVal)) opt.selected = true;
                                            roomSel.appendChild(opt);
                                        });
                                        if (!roomSel.value && availableRooms.length) roomSel.selectedIndex = 0;
                                    }

                                    function refreshAllRowDropdowns() {
                                        var tbody = document.getElementById('schedule-slots-tbody');
                                        if (!tbody) return;
                                        tbody.querySelectorAll('tr.schedule-slot-row').forEach(function(r) {
                                            updateRoomDropdownForRow(r);
                                            updateProfessorDropdownForRow(r);
                                        });
                                    }
                                    function validateScheduleBeforeSubmit() {
                                        refreshAllRowDropdowns();
                                        var tbody = document.getElementById('schedule-slots-tbody');
                                        if (!tbody) return { valid: true };
                                        var rows = tbody.querySelectorAll('tr.schedule-slot-row');
                                        for (var i = 0; i < rows.length; i++) {
                                            var row = rows[i];
                                            var daySel = row.querySelector('select[name*="[day_of_week]"]');
                                            var startInp = row.querySelector('input[name*="[start_time]"]');
                                            var endInp = row.querySelector('input[name*="[end_time]"]');
                                            var roomSel = row.querySelector('select[name*="[room_id]"]');
                                            var profSel = row.querySelector('select[name*="[professor_id]"]');
                                            if (!daySel || !startInp || !endInp) continue;
                                            var day = daySel.value;
                                            var dayNum = parseInt(day, 10);
                                            var start = (startInp.value || '08:00').substring(0, 5);
                                            var end = (endInp.value || '09:00').substring(0, 5);
                                            if (!day || dayNum < 1 || dayNum > 7 || end <= start) continue;
                                            var otherRoomOcc = getOccupancyFromOtherRows(row);
                                            var mergedRoomOcc = (roomOccupancy || []).concat(otherRoomOcc);
                                            var roomId = roomSel ? parseInt(roomSel.value, 10) : 0;
                                            if (roomId && roomSel) {
                                                var availableRooms = getAvailableRoomsWithOccupancy(day, start, end, mergedRoomOcc);
                                                if (availableRooms.every(function(r) { return r.id !== roomId; })) {
                                                    return { valid: false, message: 'A selected room is already in use at that day/time (including COR Archive or another row). Please change room or time.' };
                                                }
                                            }
                                            var otherProfOcc = getProfessorOccupancyFromOtherRows(row);
                                            var mergedProfOcc = (professorOccupancy || []).concat(otherProfOcc);
                                            var profId = profSel ? profSel.value : (row.querySelector('input[name*="[professor_id]"]') || {}).value;
                                            if (profId) {
                                                profId = parseInt(profId, 10);
                                                if (isProfessorOccupied(profId, day, start, end, mergedProfOcc)) {
                                                    return { valid: false, message: 'A selected professor is already assigned at that day/time (including COR Archive or another row). Please change professor or time.' };
                                                }
                                            }
                                        }
                                        return { valid: true };
                                    }

                                    function updateProfessorDropdownForRow(row) {
                                        var daySel = row.querySelector('select[name*="[day_of_week]"]');
                                        var startInp = row.querySelector('input[name*="[start_time]"]');
                                        var endInp = row.querySelector('input[name*="[end_time]"]');
                                        var profSel = row.querySelector('select[name*="[professor_id]"]');
                                        if (!daySel || !startInp || !endInp || !profSel) return;
                                        var day = daySel.value;
                                        var dayNum = parseInt(day, 10);
                                        var start = (startInp.value || '08:00').substring(0,5);
                                        var end = (endInp.value || '09:00').substring(0,5);
                                        var subjId = row.getAttribute('data-subject-id');
                                        var profs = (professorsBySubject && professorsBySubject[subjId]) ? professorsBySubject[subjId] : [];
                                        var otherProfOccupancy = getProfessorOccupancyFromOtherRows(row);
                                        var mergedProfOccupancy = (professorOccupancy || []).concat(otherProfOccupancy);
                                        var current = profSel.value;
                                        profSel.innerHTML = '<option value="">—</option>';
                                        profs.forEach(function(p) {
                                            var t = (p.faculty_type || '').toLowerCase();
                                            var include = false;
                                            if (t === 'cos' || t === 'cos' ) {
                                                include = true;
                                            } else if (t === 'part_time' || t === 'part-time' || t === 'parttime') {
                                                include = (dayNum === 6 || dayNum === 7);
                                            } else if (t === 'permanent' || t === 'permanent') {
                                                include = (dayNum >= 1 && dayNum <= 5);
                                            } else {
                                                include = true;
                                            }
                                            if (include && dayNum >= 1 && dayNum <= 7 && isProfessorOccupied(p.id, day, start, end, mergedProfOccupancy)) include = false; // exclude if already booked at this day/time
                                            if (include) {
                                                var opt = document.createElement('option');
                                                opt.value = p.id;
                                                opt.textContent = p.name + (p.faculty_type ? (' — ' + p.faculty_type) : '');
                                                if (String(p.id) === String(current)) opt.selected = true;
                                                profSel.appendChild(opt);
                                            }
                                        });
                                    }

                                    function getSubjectById(id) {
                                        return subjects.find(function(s) { return s.id == id; });
                                    }

                                    function getFirstSlotValuesForSubject(subjectId) {
                                        var firstRow = document.querySelector('tr[data-subject-id="' + subjectId + '"]');
                                        if (!firstRow) return null;
                                        var start = firstRow.querySelector('input[name*="[start_time]"]');
                                        var end = firstRow.querySelector('input[name*="[end_time]"]');
                                        var room = firstRow.querySelector('select[name*="[room_id]"]');
                                        var prof = firstRow.querySelector('select[name*="[professor_id]"]');
                                        var daySel = firstRow.querySelector('select[name*="[day_of_week]"]');
                                        return {
                                            start_time: start ? start.value : '08:00',
                                            end_time: end ? end.value : '09:00',
                                            room_id: room ? room.value : '',
                                            professor_id: prof ? prof.value : '',
                                            day_of_week: daySel ? daySel.value : '1'
                                        };
                                    }

                                        // professor data from server
                                        var professorsBySubject = data.professorsBySubject || {};

                                    function addDayRow(subjectId) {
                                        var subject = getSubjectById(subjectId);
                                        if (!subject) return;
                                        var subjectIdNum = subject.id;
                                        var first = getFirstSlotValuesForSubject(subjectIdNum);
                                        var defaultDay = '2';
                                        var defaultStart = '08:00';
                                        var defaultEnd = '09:00';
                                        var defaultRoom = '';
                                        if (first) {
                                            defaultStart = first.start_time;
                                            defaultEnd = first.end_time;
                                            defaultRoom = first.room_id;
                                            var firstDay = parseInt(first.day_of_week, 10) || 1;
                                            defaultDay = String(Math.min(7, firstDay + 1));
                                        }
                                        var otherOcc = getOccupancyFromOtherRows(null);
                                        var mergedOcc = (roomOccupancy || []).concat(otherOcc);
                                        var availableRooms = getAvailableRoomsWithOccupancy(defaultDay, defaultStart, defaultEnd, mergedOcc);
                                        var roomOpts = availableRooms.map(function(r) { return '<option value="' + r.id + '"' + (r.id == defaultRoom ? ' selected' : '') + '>' + (r.name || '').replace(/</g,'&lt;') + '</option>'; }).join('');
                                        var dayOpts = '<option value="">—</option>' + Object.keys(days).map(function(d) { return '<option value="' + d + '"' + (d === defaultDay ? ' selected' : '') + '>' + days[d] + '</option>'; }).join('');
                                        var idx = nextSlotIndex++;
                                        var tr = document.createElement('tr');
                                        tr.className = 'align-top schedule-slot-row hover:bg-blue-50/50 transition-colors';
                                        tr.setAttribute('data-subject-id', subjectIdNum);
                                        tr.innerHTML =
                                            '<td class="p-3 text-gray-400">—</td>' +
                                            '<td class="p-3 text-gray-400">—</td>' +
                                            '<td class="p-3 text-center text-gray-400">—</td>' +
                                            '<td class="p-3"><input type="hidden" name="slots[' + idx + '][subject_id]" value="' + subjectIdNum + '">' +
                                            '<select name="slots[' + idx + '][day_of_week]" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm min-w-[100px]">' + dayOpts + '</select></td>' +
                                            '<td class="p-3"><input type="time" name="slots[' + idx + '][start_time]" value="' + defaultStart + '" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm"></td>' +
                                            '<td class="p-3"><input type="time" name="slots[' + idx + '][end_time]" value="' + defaultEnd + '" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm"></td>' +
                                            '<td class="p-3"><select name="slots[' + idx + '][room_id]" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm"><option value="">—</option>' + roomOpts + '</select></td>' +
                                            '<td class="p-3"><input type="hidden" name="slots[' + idx + '][professor_id]" value=""><span class="text-sm text-gray-500">Same as first day</span></td>' +
                                            '<td class="p-3 text-right"><button type="button" class="remove-day-btn px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg text-xs font-medium no-underline">Remove day</button></td>';
                                        var tbody = document.getElementById('schedule-slots-tbody');
                                        var subjectRows = Array.from(tbody.querySelectorAll('tr[data-subject-id="' + subjectIdNum + '"]'));
                                        var lastForSubject = subjectRows[subjectRows.length - 1];
                                        if (lastForSubject && lastForSubject.nextSibling) {
                                            tbody.insertBefore(tr, lastForSubject.nextSibling);
                                        } else {
                                            tbody.appendChild(tr);
                                        }
                                        tr.querySelector('.remove-day-btn').addEventListener('click', function() { tr.remove(); });
                                        // Filter room dropdown by occupancy (including other rows) so same room at same time is not offered
                                        updateRoomDropdownForRow(tr);
                                    }

                                    function removeSubjectRows(subjectId) {
                                        var tbody = document.getElementById('schedule-slots-tbody');
                                        if (!tbody) return;
                                        tbody.querySelectorAll('tr[data-subject-id="' + subjectId + '"]').forEach(function(tr) { tr.remove(); });
                                        var emptyRow = tbody.querySelector('tr td[colspan="9"]');
                                        if (tbody.querySelectorAll('tr.schedule-slot-row').length === 0 && emptyRow) {
                                            emptyRow.closest('tr').style.display = '';
                                        }
                                    }
                                    function addSubjectRow(subjectId) {
                                        var subject = getSubjectById(subjectId);
                                        if (!subject) return;
                                        var subjectIdNum = subject.id;
                                        var profs = (professorsBySubject && professorsBySubject[subjectIdNum]) ? professorsBySubject[subjectIdNum] : [];
                                        var dayOpts = '<option value="">—</option>' + Object.keys(days).filter(function(d){ return d !== ''; }).map(function(d) { return '<option value="' + d + '"' + (d === '1' ? ' selected' : '') + '>' + days[d] + '</option>'; }).join('');
                                        var roomOpts = rooms.map(function(r) { return '<option value="' + r.id + '">' + (r.name || '').replace(/</g,'&lt;') + '</option>'; }).join('');
                                        var profOpts = '<option value="">—</option>' + profs.map(function(p) { return '<option value="' + p.id + '">' + (p.name || '').replace(/</g,'&lt;') + '</option>'; }).join('');
                                        var idx = nextSlotIndex++;
                                        var tr = document.createElement('tr');
                                        tr.className = 'align-top schedule-slot-row hover:bg-blue-50/50 transition-colors';
                                        tr.setAttribute('data-subject-id', subjectIdNum);
                                        tr.innerHTML =
                                            '<td class="p-3 font-medium text-gray-800">' + (subject.code || '').replace(/</g,'&lt;') + '</td>' +
                                            '<td class="p-3 text-gray-800">' + (subject.title || '').replace(/</g,'&lt;') + '</td>' +
                                            '<td class="p-3 text-center text-gray-800">' + (subject.units || 0) + '</td>' +
                                            '<td class="p-3"><input type="hidden" name="slots[' + idx + '][subject_id]" value="' + subjectIdNum + '">' +
                                            '<select name="slots[' + idx + '][day_of_week]" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm min-w-[100px]">' + dayOpts + '</select></td>' +
                                            '<td class="p-3"><input type="time" name="slots[' + idx + '][start_time]" value="08:00" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm"></td>' +
                                            '<td class="p-3"><input type="time" name="slots[' + idx + '][end_time]" value="09:00" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm"></td>' +
                                            '<td class="p-3"><select name="slots[' + idx + '][room_id]" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm"><option value="">—</option>' + roomOpts + '</select></td>' +
                                            '<td class="p-3"><select name="slots[' + idx + '][professor_id]" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm">' + profOpts + '</select></td>' +
                                            '<td class="p-3 text-right"><div class="flex flex-wrap gap-2 justify-end"><button type="button" class="remove-subject-btn px-3 py-2 rounded-lg border border-red-300 text-red-700 text-xs font-medium hover:bg-red-50 no-underline" data-subject-id="' + subjectIdNum + '">Remove</button></div></td>';
                                        var tbody = document.getElementById('schedule-slots-tbody');
                                        var placeholder = tbody.querySelector('tr td[colspan="9"]');
                                        if (placeholder && placeholder.closest('tr')) {
                                            placeholder.closest('tr').style.display = 'none';
                                            tbody.insertBefore(tr, placeholder.closest('tr'));
                                        } else {
                                            tbody.appendChild(tr);
                                        }
                                        tr.querySelector('.remove-subject-btn').addEventListener('click', function() { removeSubjectRows(subjectIdNum); });
                                        var sel = document.getElementById('add-subject-select');
                                        if (sel) {
                                            var opt = sel.querySelector('option[value="' + subjectIdNum + '"]');
                                            if (opt) opt.remove();
                                            sel.value = '';
                                        }
                                        // Filter room and professor dropdowns by current day/time so already-scheduled rooms and professors are excluded
                                        updateRoomDropdownForRow(tr);
                                        updateProfessorDropdownForRow(tr);
                                    }
                                    var formEl = document.getElementById('schedule-slots-form');
                                    if (formEl) {
                                        formEl.addEventListener('submit', function(e) {
                                            var result = validateScheduleBeforeSubmit();
                                            if (!result.valid) {
                                                e.preventDefault();
                                                alert(result.message);
                                                return false;
                                            }
                                        });
                                    }
                                    document.getElementById('schedule-slots-tbody').addEventListener('click', function(e) {
                                        var btn = e.target.closest('.add-day-btn');
                                        if (btn) {
                                            e.preventDefault();
                                            addDayRow(btn.getAttribute('data-subject-id'));
                                        }
                                        var removeDayBtn = e.target.closest('.remove-day-btn');
                                        if (removeDayBtn) {
                                            e.preventDefault();
                                            removeDayBtn.closest('tr').remove();
                                        }
                                        var removeSubjBtn = e.target.closest('.remove-subject-btn');
                                        if (removeSubjBtn) {
                                            e.preventDefault();
                                            removeSubjectRows(removeSubjBtn.getAttribute('data-subject-id'));
                                        }
                                    });
                                    document.getElementById('add-subject-btn') && document.getElementById('add-subject-btn').addEventListener('click', function() {
                                        var sel = document.getElementById('add-subject-select');
                                        if (!sel || !sel.value) return;
                                        addSubjectRow(sel.value);
                                    });
                                    var tableEl = document.getElementById('schedule-slots-table');
                                    if (tableEl) {
                                        tableEl.addEventListener('change', function(e) {
                                            if (e.target.matches('select[name*="[day_of_week]"]') || e.target.matches('select[name*="[room_id]"]') || e.target.matches('select[name*="[professor_id]"]')) {
                                                refreshAllRowDropdowns();
                                            }
                                        });
                                        tableEl.addEventListener('change', function(e) {
                                            if (!e.target.matches('select[name*="[professor_id]"]')) return;
                                            var profSel = e.target;
                                            var row = profSel.closest('tr.schedule-slot-row');
                                            if (!row) return;
                                            var daySel = row.querySelector('select[name*="[day_of_week]"]');
                                            var startInp = row.querySelector('input[name*="[start_time]"]');
                                            var endInp = row.querySelector('input[name*="[end_time]"]');
                                            var subjId = row.getAttribute('data-subject-id');
                                            var profId = profSel.value;
                                            var profList = (professorsBySubject && professorsBySubject[subjId]) ? professorsBySubject[subjId] : [];
                                            var prof = profList.find(function(p){ return String(p.id) === String(profId); });
                                            if (!prof) return;
                                            var start = (startInp ? startInp.value : '08:00') || '08:00';
                                            var end = (endInp ? endInp.value : '09:00') || '09:00';
                                            var day = parseInt(daySel ? daySel.value : 1, 10);
                                            // if permanent and outside standard hours, confirm overload
                                            if ((prof.faculty_type || '').toLowerCase() === 'permanent') {
                                                var s = (start || '08:00').substring(0, 5);
                                                var e = (end || '09:00').substring(0, 5);
                                                if (s < '08:00' || e > '17:00') {
                                                    var ok = confirm('Are you sure you want to add this professor past 5pm schedule? It will be an overload.');
                                                    if (!ok) {
                                                        profSel.value = '';
                                                    }
                                                }
                                            }
                                        });
                                        tableEl.addEventListener('input', function(e) {
                                            if (e.target.matches('input[name*="[start_time]"], input[name*="[end_time]"]')) {
                                                refreshAllRowDropdowns();
                                            }
                                        });
                                    }
                                    document.getElementById('schedule-slots-tbody').addEventListener('click', function(e) {
                                        var removeDayBtn = e.target.closest('.remove-day-btn');
                                        if (removeDayBtn) {
                                            setTimeout(function() { refreshAllRowDropdowns(); }, 0);
                                        }
                                        var removeSubjBtn = e.target.closest('.remove-subject-btn');
                                        if (removeSubjBtn) {
                                            setTimeout(function() { refreshAllRowDropdowns(); }, 0);
                                        }
                                    }, true);
                                    // initialize professor dropdowns on page load
                                    document.querySelectorAll('tr.schedule-slot-row').forEach(function(r){ updateProfessorDropdownForRow(r); });
                                })();
                                </script>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2 items-center">
                                <button type="submit" form="schedule-slots-form" class="px-5 py-2.5 rounded-lg bg-[#1E40AF] hover:bg-[#1D3A8A] text-white text-sm font-semibold no-underline">Save schedule</button>
                                <a href="{{ route('dean.schedule.by-scope') }}?program={{ urlencode($program) }}&year={{ urlencode($year) }}" class="px-5 py-2.5 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-semibold no-underline">Back to semesters</a>
                            </div>
                            <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                                <p class="text-sm font-semibold text-gray-800 mb-2 font-heading">Deploy and Archive</p>
                                <p class="text-sm text-gray-700 mb-3 font-data">Select a target block below to archive the current schedule for that block (archive-only). For a full deploy that creates per-student records, use the Deploy action in the COR Archive block folder.</p>
                                @if(isset($blocks) && $blocks->isNotEmpty())
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <label class="text-sm text-gray-700 font-data">Target block:</label>
                                        <select id="fetch-block-select" class="border border-gray-300 rounded-lg px-3 py-2 text-sm font-data">
                                            @foreach($blocks as $b)
                                                <option value="{{ $b->id }}" data-shift="{{ $b->shift ?? '' }}">{{ $b->code ?? $b->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" id="fetch-archive-btn" class="px-4 py-2 rounded-lg bg-[#1E40AF] hover:bg-[#1D3A8A] text-white text-sm font-semibold no-underline">Fetch (Archive)</button>
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 font-data">No blocks available in this scope. Open COR Archive to manage blocks.</p>
                                @endif
                                <div class="mt-3">
                                    <a href="{{ route('cor.archive.show', ['programId' => $program_id, 'yearLevel' => $year, 'semester' => $semester]) }}{{ $school_year ? ('?school_year=' . urlencode($school_year)) : '' }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-[#1E40AF] hover:bg-[#1D3A8A] text-white text-sm font-semibold no-underline">Open COR Archive for this scope</a>
                                </div>
                            </div>
                        @endif
                    </form>

                    <!-- Hidden form for Fetch (Archive) to avoid nested forms inside the schedule save form -->
                    <form id="fetch-cor-form" method="POST" action="{{ route('dean.schedule.fetch-cor') }}" style="display:none;">
                        @csrf
                        <input type="hidden" name="program_id" id="fetch_program_id" value="{{ $program_id }}">
                        <input type="hidden" name="academic_year_level_id" id="fetch_academic_year_level_id" value="{{ $academic_year_level_id }}">
                        <input type="hidden" name="semester" id="fetch_semester" value="{{ $semester }}">
                        <input type="hidden" name="school_year" id="fetch_school_year" value="{{ $school_year ?? '' }}">
                        <input type="hidden" name="block_id" id="fetch_block_id" value="">
                        <input type="hidden" name="shift" id="fetch_shift" value="">
                    </form>
                    <script>
                        (function() {
                            var btn = document.getElementById('fetch-archive-btn');
                            if (!btn) return;
                            btn.addEventListener('click', function() {
                                var sel = document.getElementById('fetch-block-select');
                                if (!sel) return;
                                var blockId = sel.value;
                                var shift = sel.options[sel.selectedIndex]?.dataset?.shift || '';
                                document.getElementById('fetch_block_id').value = blockId;
                                document.getElementById('fetch_shift').value = shift;
                                document.getElementById('fetch-cor-form').submit();
                            });
                        })();
                    </script>
                </div>
            @endif
        </div>
    </main>
</body>
</html>
