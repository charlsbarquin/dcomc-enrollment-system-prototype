<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor — {{ $professor->name }} — Faculty Availability</title>
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
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Faculty Availability — {{ $professor->name }}</h1>
                            <p class="text-white/90 text-sm font-data">{{ $professor->email }} · {{ $professor->faculty_type ? ucfirst(str_replace('-', ' ', $professor->faculty_type)) : '—' }} · Max Units: {{ $professor->max_units ?? ($professor->faculty_type === 'permanent' ? 24 : '—') }}</p>
                        </div>
                        <a href="{{ route('dean.faculty-availability') }}" class="inline-flex items-center px-5 py-2.5 rounded-xl text-base font-bold text-[#1E40AF] bg-white hover:bg-gray-100 no-underline transition-colors shrink-0 font-heading">← Back to Faculty Availability</a>
                    </div>
                </section>

                <div class="bg-white shadow-2xl rounded-xl overflow-hidden border border-gray-200 mb-6">
                    <div class="bg-[#1E40AF] px-6 py-4">
                        <h2 class="font-heading text-lg font-bold text-white">Assigned Subjects (Regular Schedule)</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm font-data">
                            <thead class="bg-[#1E40AF]">
                                <tr>
                                    <th class="p-3 text-left font-heading font-bold text-white">Code</th>
                                    <th class="p-3 text-left font-heading font-bold text-white">Year Level — Program</th>
                                    <th class="p-3 text-left font-heading font-bold text-white">Block</th>
                                    <th class="p-3 text-center font-heading font-bold text-white">Units</th>
                                    <th class="p-3 text-left font-heading font-bold text-white">Days</th>
                                    <th class="p-3 text-left font-heading font-bold text-white">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($regularSchedules as $s)
                                    @php
                                        $block = $s->block;
                                        $prog = $block?->program;
                                        $programName = $prog ? $prog->program_name : ($block->program ?? '—');
                                        $yearLevel = $block->year_level ?? '—';
                                        $dayName = $dayNames[$s->day_of_week] ?? 'Day ' . $s->day_of_week;
                                        $start = $s->start_time ? \Carbon\Carbon::parse($s->start_time)->format('H:i') : '—';
                                        $end = $s->end_time ? \Carbon\Carbon::parse($s->end_time)->format('H:i') : '—';
                                    @endphp
                                    <tr class="border-t border-gray-100 hover:bg-blue-50/50 transition-colors">
                                        <td class="p-3 font-medium">{{ $s->subject->code ?? '—' }}</td>
                                        <td class="p-3">{{ $yearLevel }} — {{ $programName }}</td>
                                        <td class="p-3">{{ $block->code ?? $block->name ?? '—' }}</td>
                                        <td class="p-3 text-center">{{ $s->subject->units ?? '—' }}</td>
                                        <td class="p-3">{{ $dayName }}</td>
                                        <td class="p-3">{{ $start }}–{{ $end }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="p-6 text-gray-500 text-center font-data">No regular schedule assigned.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($regularSchedules->isNotEmpty())
                        <p class="p-4 text-sm text-gray-600 font-data font-medium border-t border-gray-100">Total units (regular): {{ $totalRegularUnits }}</p>
                    @endif
                </div>

                <div class="bg-white shadow-2xl rounded-xl overflow-hidden border border-gray-200">
                    <div class="bg-[#1E40AF] px-6 py-4">
                        <h2 class="font-heading text-lg font-bold text-white">Overload Schedule</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm font-data">
                            <thead class="bg-[#1E40AF]">
                                <tr>
                                    <th class="p-3 text-left font-heading font-bold text-white">Code</th>
                                    <th class="p-3 text-left font-heading font-bold text-white">Year Level — Program</th>
                                    <th class="p-3 text-left font-heading font-bold text-white">Block</th>
                                    <th class="p-3 text-center font-heading font-bold text-white">Units</th>
                                    <th class="p-3 text-left font-heading font-bold text-white">Days</th>
                                    <th class="p-3 text-left font-heading font-bold text-white">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($overloadSchedules as $s)
                                    @php
                                        $block = $s->block;
                                        $prog = $block?->program;
                                        $programName = $prog ? $prog->program_name : ($block->program ?? '—');
                                        $yearLevel = $block->year_level ?? '—';
                                        $dayName = $dayNames[$s->day_of_week] ?? 'Day ' . $s->day_of_week;
                                        $start = $s->start_time ? \Carbon\Carbon::parse($s->start_time)->format('H:i') : '—';
                                        $end = $s->end_time ? \Carbon\Carbon::parse($s->end_time)->format('H:i') : '—';
                                    @endphp
                                    <tr class="border-t border-gray-100 hover:bg-blue-50/50 transition-colors">
                                        <td class="p-3 font-medium">{{ $s->subject->code ?? '—' }}</td>
                                        <td class="p-3">{{ $yearLevel }} — {{ $programName }}</td>
                                        <td class="p-3">{{ $block->code ?? $block->name ?? '—' }}</td>
                                        <td class="p-3 text-center">{{ $s->subject->units ?? '—' }}</td>
                                        <td class="p-3">{{ $dayName }}</td>
                                        <td class="p-3">{{ $start }}–{{ $end }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="p-6 text-gray-500 text-center font-data">No overload schedule.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($overloadSchedules->isNotEmpty())
                        <p class="p-4 text-sm text-gray-600 font-data font-medium border-t border-gray-100">Total units (overload): {{ $totalOverloadUnits }}</p>
                    @endif
                </div>
            </div>
        </div>
    </main>
</body>
</html>
