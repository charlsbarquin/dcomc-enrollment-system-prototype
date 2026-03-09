<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Irregular COR Archive — {{ $date }} - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">
    @php
        $isStaff = request()->routeIs('staff.*');
        $indexRoute = $isStaff ? route('staff.irregular-cor-archive.index') : route('registrar.irregular-cor-archive.index');
    @endphp
    @include('dashboards.partials.role-sidebar')

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full max-w-5xl mx-auto">
                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Irregular COR — {{ $date }}</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Deployed by {{ $deployer ? $deployer->name : 'Unknown' }}. {{ $records->count() }} record(s).</p>
                        </div>
                        <a href="{{ $indexRoute }}" class="btn-back-hero shrink-0 whitespace-nowrap">← Irregular COR Archive</a>
                    </div>
                </section>

                <div class="rounded-xl overflow-hidden border border-gray-200 shadow-2xl bg-white">
                    <div class="bg-[#1E40AF] px-6 py-4">
                        <h2 class="font-heading text-lg font-bold text-white">Records</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm font-data" role="grid" aria-label="Irregular COR records">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Student</th>
                                    <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Subject</th>
                                    <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Days</th>
                                    <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Time</th>
                                    <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Room</th>
                                    <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Professor</th>
                                    <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Block</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach($records as $rec)
                                    <tr class="hover:bg-blue-50/50 transition-colors font-data">
                                        <td class="py-4 px-4 text-gray-800">{{ $rec->student ? $rec->student->name : '—' }}</td>
                                        <td class="py-4 px-4 text-gray-700">{{ $rec->subject ? ($rec->subject->code . ' — ' . $rec->subject->title) : '—' }}</td>
                                        <td class="py-4 px-4 text-gray-700">{{ $rec->days_snapshot ?? '—' }}</td>
                                        <td class="py-4 px-4 text-gray-700">{{ $rec->start_time_snapshot && $rec->end_time_snapshot ? ($rec->start_time_snapshot->format('H:i') . ' - ' . $rec->end_time_snapshot->format('H:i')) : '—' }}</td>
                                        <td class="py-4 px-4 text-gray-700">{{ $rec->room_name_snapshot ?? '—' }}</td>
                                        <td class="py-4 px-4 text-gray-700">{{ $rec->professor_name_snapshot ?? '—' }}</td>
                                        <td class="py-4 px-4 text-gray-700">{{ $rec->block ? ($rec->block->code ?? $rec->block->name) : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
