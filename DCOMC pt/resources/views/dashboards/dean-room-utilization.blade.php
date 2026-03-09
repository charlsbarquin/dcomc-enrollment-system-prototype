<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dean Room Utilization - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">

    @include('dashboards.partials.dean-sidebar')

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full max-w-7xl mx-auto">
                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Room Utilization</h1>
                    <p class="text-white/90 text-sm font-data">Department / scope: {{ Auth::user()->department?->name ?? $scope ?? 'ALL' }}</p>
                    <p class="text-white/80 text-xs mt-1 font-data">View room availability. Rooms occupied at a given day and time do not appear in the Schedule by Program room dropdown.</p>
                </section>

                <div class="bg-white shadow-2xl rounded-xl overflow-hidden border border-gray-200">
                    <div class="bg-[#1E40AF] px-6 py-4">
                        <h2 class="font-heading text-lg font-bold text-white">Rooms</h2>
                    </div>
                    <table class="w-full text-sm font-data">
                        <thead class="bg-[#1E40AF]">
                            <tr>
                                <th class="p-3 text-left font-heading font-bold text-white">Room name</th>
                                <th class="p-3 text-left font-heading font-bold text-white">Room code</th>
                                <th class="p-3 text-left font-heading font-bold text-white">Status of slots</th>
                                <th class="p-3 text-left w-28 font-heading font-bold text-white">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rooms as $room)
                                <tr class="border-t border-gray-100 hover:bg-blue-50/50 transition-colors">
                            <td class="p-3 font-medium text-gray-800">{{ $room->name }}</td>
                            <td class="p-3 text-gray-700">{{ $room->code }}</td>
                            <td class="p-3">
                                @if($room->schedule_count > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">Occupied ({{ $room->schedule_count }} slot{{ $room->schedule_count !== 1 ? 's' : '' }})</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Available</span>
                                @endif
                            </td>
                            <td class="p-3">
                                <button type="button" onclick="openRoomDetails({{ $room->id }})" class="inline-flex items-center px-4 py-2 rounded-lg bg-[#1E40AF] text-white text-sm font-medium no-underline hover:bg-[#1D3A8A] font-data">View</button>
                            </td>
                        </tr>
                        <tr id="room-detail-{{ $room->id }}" class="hidden border-t bg-amber-50/50">
                            <td colspan="4" class="p-4">
                                <p class="text-xs font-semibold text-gray-600 uppercase mb-2">Room availability (when this room is occupied)</p>
                                @if(count($room->occupancy_slots) > 0)
                                    <ul class="space-y-1.5 text-sm">
                                        @foreach($room->occupancy_slots as $occ)
                                            <li class="text-gray-800">{{ $occ->line }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-sm text-gray-500">No slots assigned. This room is available at all times.</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-6 text-center text-gray-500">No room utilization data.</td></tr>
                    @endforelse
                </tbody>
            </table>
                </div>
            </div>
        </div>
    </main>

    <script>
    function openRoomDetails(roomId) {
        var row = document.getElementById('room-detail-' + roomId);
        if (!row) return;
        row.classList.toggle('hidden');
    }
    </script>
</body>
</html>
