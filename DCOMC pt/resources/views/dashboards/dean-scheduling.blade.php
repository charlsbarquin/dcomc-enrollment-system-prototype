<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dean Scheduling - DCOMC</title>
    @include('layouts.partials.offline-assets')
</head>
<body class="bg-gray-100 flex h-screen">

    @include('dashboards.partials.dean-sidebar')

    <main class="flex-1 p-8 overflow-y-auto">
    <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Dean Scheduling</h1>
                <p class="text-xs text-gray-500">Department / scope: {{ Auth::user()->department?->name ?? $scope ?? 'ALL' }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('dean.room-utilization') }}" class="px-4 py-2 bg-yellow-700 text-white rounded text-sm hover:bg-yellow-800 transition">Room Utilization</a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded mb-4">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded shadow p-4">
                <h2 class="font-semibold text-gray-800 mb-3">Add Subject</h2>
                <form method="POST" action="{{ route('dean.scheduling.subjects.store') }}" class="grid grid-cols-2 gap-2">
                    @csrf
                    <input type="text" name="code" placeholder="Subject Code" class="border rounded p-2" required>
                    <input type="text" name="title" placeholder="Subject Title" class="border rounded p-2" required>
                    <input type="number" name="units" placeholder="Units" class="border rounded p-2" min="1" max="6" required>
                    <input type="text" name="program" placeholder="Program" class="border rounded p-2" required>
                    <input type="text" name="major" placeholder="Major (optional)" class="border rounded p-2">
                    <input type="text" name="year_level" placeholder="Year Level" class="border rounded p-2" required>
                    <input type="text" name="semester" placeholder="Semester" class="border rounded p-2" required>
                    <button class="col-span-2 bg-yellow-700 text-white rounded p-2 font-semibold">Save Subject</button>
                </form>
            </div>
            <div class="bg-white rounded shadow p-4">
                <h2 class="font-semibold text-gray-800 mb-3">Add Room</h2>
                <form method="POST" action="{{ route('dean.scheduling.rooms.store') }}" class="grid grid-cols-2 gap-2">
                    @csrf
                    <input type="text" name="room_code" placeholder="Room Code" class="border rounded p-2" required>
                    <input type="text" name="room_name" placeholder="Room Name" class="border rounded p-2" required>
                    <input type="number" name="room_capacity" placeholder="Capacity" class="border rounded p-2" min="1" max="500" required>
                    <input type="text" name="room_building" placeholder="Building (optional)" class="border rounded p-2">
                    <button class="col-span-2 bg-yellow-700 text-white rounded p-2 font-semibold">Save Room</button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded shadow p-4 mb-6">
            <h2 class="font-semibold text-gray-800 mb-3">Assign Schedule</h2>
            <form method="POST" action="{{ route('dean.scheduling.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                @csrf
                <select name="block_id" class="border rounded p-2" required>
                    <option value="">Select Block</option>
                    @foreach($blocks as $block)
                        <option value="{{ $block->id }}">{{ $block->code ?? $block->name }} ({{ $block->year_level }} {{ $block->semester }})</option>
                    @endforeach
                </select>
                <select name="subject_id" class="border rounded p-2" required>
                    <option value="">Select Subject (course code, description, units)</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->code }} – {{ $subject->title }} ({{ (int)($subject->units ?? 0) }} units)</option>
                    @endforeach
                </select>
                <select name="room_id" class="border rounded p-2" required>
                    <option value="">Select Room</option>
                </select>
                <button type="button" id="checkRoomsBtn" class="border border-yellow-700 text-yellow-700 rounded p-2 font-semibold hover:bg-yellow-50">
                    Show Available Rooms
                </button>
                <select id="allRoomsFallback" class="border rounded p-2 hidden">
                    <option value="">Select Room</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}">{{ $room->name }} ({{ $room->code ?? 'N/A' }})</option>
                    @endforeach
                </select>
                <select name="professor_id" class="border rounded p-2" required id="professor_id_select">
                    <option value="">Select Professor</option>
                    @foreach($professors as $professor)
                        <option value="{{ $professor->id }}" {{ (int)($preselectProfessorId ?? 0) === (int)$professor->id ? 'selected' : '' }}>
                            {{ $professor->name }}
                            – {{ strtoupper($professor->faculty_type ?? 'N/A') }}
                            [{{ (int) ($professor->assigned_units ?? 0) }}/{{ (int) ($professor->max_units ?? 0) }}]
                        </option>
                    @endforeach
                </select>
                <select name="day_of_week" class="border rounded p-2" required>
                    <option value="">Day (M, T, W, Th, F, Sat, Sun)</option>
                    <option value="1">M (Monday)</option>
                    <option value="2">T (Tuesday)</option>
                    <option value="3">W (Wednesday)</option>
                    <option value="4">Th (Thursday)</option>
                    <option value="5">F (Friday)</option>
                    <option value="6">Sat (Saturday)</option>
                    <option value="7">Sun (Sunday)</option>
                </select>
                <div class="grid grid-cols-2 gap-2">
                    <input type="time" name="start_time" class="border rounded p-2" required>
                    <input type="time" name="end_time" class="border rounded p-2" required>
                </div>
                <input type="text" name="school_year" placeholder="School Year (optional)" class="border rounded p-2">
                <input type="text" name="semester" placeholder="Semester (optional)" class="border rounded p-2">
                <button class="bg-yellow-700 text-white rounded p-2 font-semibold">Save Schedule</button>
            </form>
            <p class="text-xs text-gray-500 mt-2">Room list is filtered by selected day and time to prevent occupied-room conflicts.</p>
        </div>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="p-3 text-left">Block</th>
                        <th class="p-3 text-left">Subject</th>
                        <th class="p-3 text-left">Professor</th>
                        <th class="p-3 text-left">Room</th>
                        <th class="p-3 text-left">Day</th>
                        <th class="p-3 text-left">Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $schedule)
                        <tr class="border-t">
                            <td class="p-3">{{ $schedule->block?->code ?? $schedule->block?->name ?? 'N/A' }}</td>
                            <td class="p-3">{{ $schedule->subject?->code ?? '' }} {{ $schedule->subject?->title ?? 'N/A' }}</td>
                            <td class="p-3">{{ $schedule->professor?->name ?? 'N/A' }}</td>
                            <td class="p-3">{{ $schedule->room?->name ?? 'N/A' }}</td>
                            <td class="p-3">
                                {{ [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'][$schedule->day_of_week] ?? $schedule->day_of_week }}
                            </td>
                            <td class="p-3">{{ $schedule->start_time }} - {{ $schedule->end_time }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-6 text-center text-gray-500">No schedules assigned yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <script>
        const roomSelect = document.querySelector('select[name="room_id"]');
        const daySelect = document.querySelector('select[name="day_of_week"]');
        const startInput = document.querySelector('input[name="start_time"]');
        const endInput = document.querySelector('input[name="end_time"]');
        const checkRoomsBtn = document.getElementById('checkRoomsBtn');
        const fallbackSelect = document.getElementById('allRoomsFallback');

        function fillRooms(options) {
            roomSelect.innerHTML = '<option value="">Select Room</option>';
            options.forEach((opt) => {
                const option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.label;
                roomSelect.appendChild(option);
            });
        }

        function fallbackRooms() {
            const options = [];
            Array.from(fallbackSelect.options).forEach((opt) => {
                if (opt.value) options.push({ value: opt.value, label: opt.textContent });
            });
            fillRooms(options);
        }

        async function loadAvailableRooms() {
            const day = daySelect.value;
            const start = startInput.value;
            const end = endInput.value;
            const syInput = document.querySelector('input[name="school_year"]');
            const semInput = document.querySelector('input[name="semester"]');
            const schoolYear = syInput ? syInput.value : '';
            const semester = semInput ? semInput.value : '';
            if (!day || !start || !end) {
                fallbackRooms();
                return;
            }

            const params = new URLSearchParams({
                day_of_week: day,
                start_time: start,
                end_time: end,
            });
            if (schoolYear) params.set('school_year', schoolYear);
            if (semester) params.set('semester', semester);

            try {
                const response = await fetch(`{{ route('dean.scheduling.available-rooms') }}?${params.toString()}`);
                if (!response.ok) throw new Error('Failed room lookup');
                const data = await response.json();
                const options = (data.rooms || []).map((room) => ({
                    value: room.id,
                    label: `${room.name} (${room.code || 'N/A'})`,
                }));
                fillRooms(options);
            } catch (error) {
                fallbackRooms();
            }
        }

        checkRoomsBtn.addEventListener('click', loadAvailableRooms);
        fallbackRooms();
    </script>
    </div>
    </main>
</body>
</html>

