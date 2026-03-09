<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teaching Load Report | Manage Professor</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">
    @include('dashboards.partials.dean-sidebar')

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full max-w-[1400px] mx-auto">
                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Faculty Teaching Load Report</h1>
                            <p class="text-white/90 text-sm font-data">Filter by employment type and gender. Overload (units or time) is marked per professor.</p>
                        </div>
                        <a href="{{ route('dean.manage-professor.index') }}" class="inline-flex items-center px-5 py-2.5 rounded-xl text-base font-bold text-[#1E40AF] bg-white hover:bg-gray-100 no-underline transition-colors shrink-0 font-heading">Manage Professor</a>
                    </div>
                </section>

                {{-- Filters: Employment type + Gender --}}
                <div class="bg-white shadow-2xl rounded-xl border border-gray-200 p-4 mb-6">
                    <p class="text-xs font-semibold text-gray-600 mb-2 font-heading">Filter by employment type & gender</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($employment_types as $empKey => $empLabel)
                            @foreach($genders as $genKey => $genLabel)
                                <a href="{{ route('dean.manage-professor.teaching-load', ['employment' => $empKey, 'gender' => $genKey, 'semester' => $semester, 'school_year' => $school_year]) }}"
                                   class="px-3 py-1.5 rounded-lg text-sm font-medium transition no-underline font-data
                                        @if($filter_employment === $empKey && $filter_gender === $genKey)
                                            bg-[#1E40AF] text-white hover:bg-[#1D3A8A]
                                        @else
                                            bg-gray-100 text-gray-700 hover:bg-gray-200
                                        @endif">
                                    {{ strtoupper($empKey === 'part-time' ? 'ADJUNCT' : $empKey) }}_{{ strtoupper($genKey) }}
                                </a>
                            @endforeach
                        @endforeach
                        <a href="{{ route('dean.manage-professor.teaching-load', ['semester' => $semester, 'school_year' => $school_year]) }}"
                           class="px-3 py-1.5 rounded-lg text-sm font-medium bg-gray-200 text-gray-700 hover:bg-gray-300 no-underline font-data">All</a>
                    </div>
                </div>

                <div class="bg-white shadow-2xl rounded-xl overflow-hidden border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm font-data min-w-[900px]">
                            <thead class="bg-[#1E40AF]">
                                <tr>
                                    <th class="p-3 text-left font-heading font-bold text-white whitespace-nowrap">NAME OF INSTRUCTOR</th>
                                    <th class="p-3 text-left font-heading font-bold text-white">COURSE CODE</th>
                                    <th class="p-3 text-left font-heading font-bold text-white">COURSE DESCRIPTION</th>
                                    <th class="p-3 text-left font-heading font-bold text-white">YR – PROGRAM & BLOCK</th>
                                    <th class="p-3 text-right font-heading font-bold text-white">UNITS</th>
                                    <th class="p-3 text-left font-heading font-bold text-white">SCHEDULE</th>
                                    <th class="p-3 text-right font-heading font-bold text-white">UNITS (Total)</th>
                                    <th class="p-3 text-center font-heading font-bold text-white">OVERLOAD</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $row)
                                    <tr class="border-t border-gray-100 hover:bg-blue-50/50 transition-colors">
                                        <td class="p-3 font-medium">{{ $row->professor_name }}</td>
                                        <td class="p-3">{{ $row->course_code }}</td>
                                        <td class="p-3">{{ $row->course_description }}</td>
                                        <td class="p-3">{{ $row->yr_program_block }}</td>
                                        <td class="p-3 text-right">{{ $row->units }}</td>
                                        <td class="p-3">{{ $row->schedule }}</td>
                                        <td class="p-3 text-right">{{ $row->total_units !== null ? $row->total_units : '—' }}</td>
                                        <td class="p-3 text-center">
                                            @if($row->show_overload_label)
                                                <span class="text-red-600 font-semibold">OVERLOAD</span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="p-6 text-center text-gray-500 font-data">No teaching load data. Clear filters or assign schedules.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <p class="mt-3 text-xs text-gray-500 font-data">OVERLOAD = unit overload and/or time overload (schedule beyond 5:00 PM for permanent faculty).</p>
            </div>
        </div>
    </main>
</body>
</html>
