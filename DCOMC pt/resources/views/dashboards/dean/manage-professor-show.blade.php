<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $professor->name }} | Manage Professor</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">
    @include('dashboards.partials.dean-sidebar')

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full max-w-5xl mx-auto">
                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">{{ $professor->name }}</h1>
                            <p class="text-white/90 text-sm font-data">{{ $professor->email }} · {{ strtoupper($professor->faculty_type ?? '—') }} · Gender: {{ $professor->gender ?? '—' }}</p>
                        </div>
                        <a href="{{ route('dean.manage-professor.index') }}" class="inline-flex items-center px-5 py-2.5 rounded-xl text-base font-bold text-[#1E40AF] bg-white hover:bg-gray-100 no-underline transition-colors shrink-0 font-heading">← Manage Professor</a>
                    </div>
                </section>

                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-data shadow-sm">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm font-data">
                        @foreach($errors->all() as $e) <p>{{ $e }}</p> @endforeach
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-white shadow-2xl rounded-xl border border-gray-200 p-6">
                        <h2 class="font-heading text-lg font-bold text-gray-800 mb-3">Professor profile</h2>
                        <p class="font-medium text-gray-800 font-data">{{ $professor->name }}</p>
                        <p class="text-sm text-gray-600 font-data">{{ $professor->email }}</p>
                        <p class="text-sm mt-2 font-data">Employment: <strong>{{ strtoupper($professor->faculty_type ?? '—') }}</strong></p>
                        <p class="text-sm font-data">Gender: <strong>{{ $professor->gender ?? '—' }}</strong></p>
                        <a href="{{ route('dean.scheduling') }}?professor_id={{ $professor->id }}" class="mt-4 inline-flex items-center px-4 py-2.5 rounded-lg bg-[#1E40AF] text-white text-sm font-medium no-underline hover:bg-[#1D3A8A] font-data">Dean Scheduling</a>
                    </div>
                    <div class="bg-white shadow-2xl rounded-xl border border-gray-200 p-6">
                        <h2 class="font-heading text-lg font-bold text-gray-800 mb-3">Workload</h2>
                        <p class="text-sm font-data">Total units assigned: <strong>{{ $total_units }}</strong> / Max units: <strong>{{ $max_units ?: '—' }}</strong></p>
                        <div class="mt-2 flex items-center gap-2">
                            @if($unit_overload)
                                <span class="inline-block w-3 h-3 rounded-full bg-red-500" title="Unit overload"></span>
                                <span class="text-red-600 text-sm font-medium font-data">Unit overload</span>
                            @else
                                <span class="inline-block w-3 h-3 rounded-full bg-green-500"></span>
                                <span class="text-green-700 text-sm font-data">Units OK</span>
                            @endif
                            @if($time_overload)
                                <span class="ml-3 inline-block w-3 h-3 rounded-full bg-red-500" title="Time overload"></span>
                                <span class="text-red-600 text-sm font-medium font-data">Time overload (after 5 PM)</span>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('dean.manage-professor.max-units', $professor) }}" class="mt-4 flex items-end gap-2">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1 font-heading">Set unit limit</label>
                                <input type="number" name="max_units" value="{{ $max_units }}" min="0" max="99" class="w-24 border border-gray-300 rounded px-2 py-1.5 text-sm font-data">
                            </div>
                            <button type="submit" class="px-4 py-2 rounded-lg bg-[#1E40AF] text-white text-sm font-medium hover:bg-[#1D3A8A] font-data">Update</button>
                        </form>
                    </div>
                </div>

                <div class="bg-white shadow-2xl rounded-xl overflow-hidden border border-gray-200 mb-6">
                    <div class="bg-[#1E40AF] px-6 py-4">
                        <h2 class="font-heading text-lg font-bold text-white">Assigned subjects</h2>
                    </div>
                    @if($assigned_subjects->isEmpty())
                        <div class="p-6">
                            <p class="text-sm text-gray-500 font-data">No subjects assigned yet. Use <strong>Assign subjects</strong> from Manage Professor.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm font-data">
                                <thead class="bg-[#1E40AF]">
                                    <tr>
                                        <th class="p-3 text-left font-heading font-bold text-white">Course code</th>
                                        <th class="p-3 text-left font-heading font-bold text-white">Description</th>
                                        <th class="p-3 text-right font-heading font-bold text-white">Units</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($assigned_subjects as $a)
                                        @php $disp = $a->rawSubject ?? $a->subject?->rawSubject ?? $a->subject; @endphp
                                        <tr class="border-t border-gray-100 hover:bg-blue-50/50 transition-colors">
                                            <td class="p-3">{{ $disp->code ?? '—' }}</td>
                                            <td class="p-3">{{ $disp->title ?? '—' }}</td>
                                            <td class="p-3 text-right">{{ $disp->units ?? 0 }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-blue-50">
                                    <tr>
                                        <td class="p-3 font-semibold font-data" colspan="2">Total units (assigned subjects)</td>
                                        <td class="p-3 text-right font-semibold font-data">{{ $assigned_subjects->sum(function ($a) { $d = $a->rawSubject ?? $a->subject?->rawSubject ?? $a->subject; return (int)($d->units ?? 0); }) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="bg-white shadow-2xl rounded-xl overflow-hidden border border-gray-200">
                    <div class="bg-[#1E40AF] px-6 py-4">
                        <h2 class="font-heading text-lg font-bold text-white">Current schedule grid</h2>
                    </div>
                    @if($schedules->isEmpty())
                        <div class="p-6">
                            <p class="text-sm text-gray-500 font-data">No schedule slots yet. Assign from <a href="{{ route('dean.scheduling') }}?professor_id={{ $professor->id }}" class="text-[#1E40AF] font-medium no-underline hover:underline font-data">Dean Scheduling</a>.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm font-data">
                                <thead class="bg-[#1E40AF]">
                                    <tr>
                                        <th class="p-3 text-left font-heading font-bold text-white">Course</th>
                                        <th class="p-3 text-left font-heading font-bold text-white">YR – Program & Block</th>
                                        <th class="p-3 text-right font-heading font-bold text-white">Units</th>
                                        <th class="p-3 text-left font-heading font-bold text-white">Day</th>
                                        <th class="p-3 text-left font-heading font-bold text-white">Time</th>
                                        <th class="p-3 text-left font-heading font-bold text-white">Room</th>
                                        <th class="p-3 text-center font-heading font-bold text-white">Overload</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($schedules as $s)
                                        <tr class="border-t border-gray-100 hover:bg-blue-50/50 transition-colors">
                                            <td class="p-3">{{ $s->subject->code ?? '—' }} {{ $s->subject->title ?? '' }}</td>
                                            <td class="p-3">{{ $s->block->year_level ?? '—' }}-{{ $s->block->program ?? $s->block->name ?? '—' }}</td>
                                            <td class="p-3 text-right">{{ $s->subject->units ?? 0 }}</td>
                                            <td class="p-3">{{ $day_names[$s->day_of_week] ?? $s->day_of_week }}</td>
                                            <td class="p-3">{{ substr((string)$s->start_time, 0, 5) }} – {{ substr((string)$s->end_time, 0, 5) }}</td>
                                            <td class="p-3">{{ $s->room->name ?? $s->room->code ?? '—' }}</td>
                                            <td class="p-3 text-center">@if($s->is_overload)<span class="text-red-600 font-semibold">OVERLOAD</span>@else—@endif</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            <tfoot class="bg-blue-50">
                                <tr>
                                    <td class="p-2 font-semibold" colspan="2">Total units (schedule)</td>
                                    <td class="p-2 text-right font-semibold">{{ $schedules->sum(fn ($s) => (int)($s->subject->units ?? 0)) }}</td>
                                    <td class="p-2" colspan="4"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </main>
</body>
</html>
