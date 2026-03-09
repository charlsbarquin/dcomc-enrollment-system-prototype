<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Institutional Analytics - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @if(request()->routeIs('registrar.*') || request()->routeIs('admin.*') || request()->routeIs('staff.*') || request()->routeIs('unifast.*') || request()->routeIs('dean.*'))
    <style>
        .font-heading { font-family: 'Figtree', sans-serif; }
        .font-data { font-family: 'Roboto', sans-serif; }
        .hero-gradient { background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 50%, #60A5FA 100%); }
        .table-header-dcomc { background: #1E40AF; color: #fff; }
        .table-header-dcomc th { padding: 0.75rem 1rem; font-family: 'Figtree', sans-serif; font-weight: 600; }
        .admin-table-wrap tbody tr:hover { background: rgba(239, 246, 255, 0.5); }
        .admin-table-wrap tbody td { font-family: 'Roboto', sans-serif; }
    </style>
    @endif
</head>
<body class="h-screen overflow-hidden text-gray-800 font-data">
    @if(request()->routeIs('admin.*'))
    @include('dashboards.partials.admin-loading-bar')
    @endif

    <div class="w-full h-screen flex overflow-hidden">
    @include('dashboards.partials.role-sidebar')

    <main id="main-content" class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden" role="main" tabindex="-1">
        @php
            $isRegistrar = request()->routeIs('registrar.*');
            $isStaff = request()->routeIs('staff.*');
            $isUnifast = request()->routeIs('unifast.*');
            $isDean = request()->routeIs('dean.*');
            $analyticsRoute = $isDean ? 'dean.analytics' : ($isUnifast ? 'unifast.analytics' : ($isStaff ? 'staff.analytics' : ($isRegistrar ? 'registrar.analytics' : 'admin.analytics')));
            $dashboardRoute = $isDean ? 'dean.dashboard' : ($isUnifast ? 'unifast.dashboard' : ($isStaff ? 'staff.dashboard' : ($isRegistrar ? 'registrar.dashboard' : 'admin.dashboard')));
            $exportRoute = $isDean ? 'dean.analytics.export' : ($isUnifast ? 'unifast.analytics.export' : ($isStaff ? 'staff.analytics.export' : ($isRegistrar ? 'registrar.analytics.export' : 'admin.analytics.export')));
            $printRoute = $isDean ? 'dean.analytics.print' : ($isUnifast ? 'unifast.analytics.print' : ($isStaff ? 'staff.analytics.print' : ($isRegistrar ? 'registrar.analytics.print' : 'admin.analytics.print')));
        @endphp

        {{-- Master filter bar at top (AY + Semester) --}}
        <div class="bg-white border-b border-gray-200 px-6 py-4 shrink-0 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <form method="GET" action="{{ route($analyticsRoute) }}" class="flex flex-wrap items-center gap-6">
                    <p class="text-sm font-semibold text-gray-700 mb-0 {{ (request()->routeIs('registrar.*') || request()->routeIs('admin.*') || request()->routeIs('unifast.*') || request()->routeIs('dean.*')) ? 'font-heading' : '' }}">Currently Viewing Data for:</p>
                    <div class="flex flex-wrap items-end gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label for="analytics-academic-year" class="text-xs font-medium text-gray-500 uppercase tracking-wide">Academic Year</label>
                            <select name="academic_year" id="analytics-academic-year" class="border border-gray-300 rounded-lg px-4 py-2.5 text-sm bg-white min-w-[180px] shadow-sm focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:border-[#1E40AF] transition-colors cursor-pointer text-gray-800" onchange="this.form.submit()">
                                <option value="">All / Session default</option>
                                @foreach($academicYears as $year)
                                    <option value="{{ $year }}" {{ ($filters['academic_year'] ?? '') === $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="analytics-semester" class="text-xs font-medium text-gray-500 uppercase tracking-wide">Semester</label>
                            <select name="semester" id="analytics-semester" class="border border-gray-300 rounded-lg px-4 py-2.5 text-sm bg-white min-w-[140px] shadow-sm focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:border-[#1E40AF] transition-colors cursor-pointer text-gray-800" onchange="this.form.submit()">
                                <option value="">All</option>
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester }}" {{ ($filters['semester'] ?? '') === $semester ? 'selected' : '' }}>{{ $semester }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-bold bg-[#1E40AF] text-white hover:bg-[#1D3A8A] focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:ring-offset-2 transition-colors shrink-0 font-heading border-0 cursor-pointer no-underline">Apply</button>
                    </div>
                </form>
                <a href="{{ route($dashboardRoute) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors shrink-0 no-underline">← Dashboard</a>
            </div>
        </div>

        <div id="analytics-content-scroll" class="{{ (request()->routeIs('registrar.*') || request()->routeIs('admin.*') || request()->routeIs('unifast.*') || request()->routeIs('dean.*')) ? 'flex-1 w-full flex flex-col min-h-0 overflow-y-auto bg-gray-50 pt-2 px-8 pb-8 space-y-6' : 'flex-1 overflow-y-auto p-6 flex flex-col gap-6' }}">
            {{-- Hero banner — DCOMC blue gradient --}}
            <section class="hero-gradient rounded-2xl shadow-2xl p-6 sm:p-8 text-white">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div>
                        <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Institutional Analytics</h1>
                        <p class="text-white/90 text-sm sm:text-base font-data">Comprehensive data insights for enrollment and campus performance. Use filters below to narrow by academic year, semester, course, and status.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <a href="{{ route($printRoute, array_filter($filters)) }}" target="_blank" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-[#1E40AF] hover:bg-[#1D3A8A] text-white text-sm font-bold transition-colors no-underline font-heading" aria-label="Print report">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2h-2m-4-1v8"/></svg>
                            Print Report
                        </a>
                        <a href="{{ route($exportRoute, array_filter($filters)) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-[#1E40AF] hover:bg-[#1D3A8A] text-white text-sm font-bold transition-colors no-underline font-heading" aria-label="Export CSV">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Export CSV
                        </a>
                    </div>
                </div>
            </section>

            {{-- Additional filters — Google Forms–inspired white card with 10px blue top border --}}
            <form method="GET" action="{{ route($analyticsRoute) }}" class="bg-white rounded-xl border border-gray-200 shadow-2xl overflow-hidden border-t-[10px] border-t-[#1E40AF]">
                <input type="hidden" name="academic_year" value="{{ $filters['academic_year'] ?? '' }}">
                <input type="hidden" name="semester" value="{{ $filters['semester'] ?? '' }}">
                <div class="p-5 flex flex-wrap items-end gap-4">
                    <div class="flex flex-col gap-1">
                        <label for="analytics-course" class="text-xs font-heading font-bold text-gray-600 uppercase tracking-wide">Course</label>
                        <select name="course" id="analytics-course" class="border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white min-w-[140px] font-data focus:ring-2 focus:ring-[#1E40AF] focus:border-[#1E40AF]" onchange="this.form.submit()">
                            <option value="">All Courses</option>
                            @foreach($availableCourses as $course)
                                <option value="{{ $course }}" {{ ($filters['course'] ?? '') === $course ? 'selected' : '' }}>{{ $course }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label for="analytics-gender" class="text-xs font-heading font-bold text-gray-600 uppercase tracking-wide">Gender</label>
                        <select name="gender" id="analytics-gender" class="border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white min-w-[120px] font-data focus:ring-2 focus:ring-[#1E40AF] focus:border-[#1E40AF]" onchange="this.form.submit()">
                            <option value="">All Gender</option>
                            <option value="Male" {{ ($filters['gender'] ?? '') === 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ ($filters['gender'] ?? '') === 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label for="analytics-status" class="text-xs font-heading font-bold text-gray-600 uppercase tracking-wide">Status</label>
                        <select name="status" id="analytics-status" class="border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white min-w-[120px] font-data focus:ring-2 focus:ring-[#1E40AF] focus:border-[#1E40AF]" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label for="analytics-from" class="text-xs font-heading font-bold text-gray-600 uppercase tracking-wide">From</label>
                        <input type="date" name="from_date" id="analytics-from" value="{{ $filters['from_date'] ?? '' }}" class="border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data focus:ring-2 focus:ring-[#1E40AF] focus:border-[#1E40AF]">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label for="analytics-to" class="text-xs font-heading font-bold text-gray-600 uppercase tracking-wide">To</label>
                        <input type="date" name="to_date" id="analytics-to" value="{{ $filters['to_date'] ?? '' }}" class="border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data focus:ring-2 focus:ring-[#1E40AF] focus:border-[#1E40AF]">
                    </div>
                    <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-semibold bg-[#1E40AF] text-white hover:bg-[#1D3A8A] transition-colors font-data no-underline border-0 cursor-pointer">Apply Filters</button>
                </div>
            </form>

            {{-- KPI cards (4 columns) — white floating cards with DCOMC blue accent --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 border-t-[10px] border-t-[#1E40AF] shadow-2xl p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-heading font-bold uppercase tracking-wide text-gray-500 mb-1">Total Enrolled</p>
                            <p class="text-3xl font-bold text-[#1E40AF] font-data tabular-nums">{{ $totalStudents }}</p>
                        </div>
                        <div class="w-14 h-14 rounded-xl bg-[#1E40AF]/10 flex items-center justify-center shrink-0">
                            <svg class="w-8 h-8 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 border-t-[10px] border-t-[#1E40AF] shadow-2xl p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-heading font-bold uppercase tracking-wide text-gray-500 mb-1">Growth</p>
                            @php
                                $growthPct = null;
                                if ($trendRows->count() >= 2) {
                                    $last = (int) $trendRows->last()->count;
                                    $prev = (int) $trendRows->get($trendRows->count() - 2)->count;
                                    $growthPct = $prev > 0 ? round((($last - $prev) / $prev) * 100) : ($last > 0 ? 100 : 0);
                                }
                            @endphp
                            <p class="text-3xl font-bold font-data tabular-nums {{ $growthPct !== null ? ($growthPct >= 0 ? 'text-emerald-600' : 'text-red-600') : 'text-gray-400' }}">
                                @if($growthPct !== null)
                                    {{ $growthPct >= 0 ? '+' : '' }}{{ $growthPct }}%
                                @else
                                    —%
                                @endif
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5 font-data">{{ $growthPct !== null ? 'vs previous period' : 'Need 2+ months of submissions' }}</p>
                        </div>
                        <div class="w-14 h-14 rounded-xl bg-[#1E40AF]/10 flex items-center justify-center shrink-0">
                            <svg class="w-8 h-8 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 border-t-[10px] border-t-[#1E40AF] shadow-2xl p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-heading font-bold uppercase tracking-wide text-gray-500 mb-1">Pending</p>
                            <p class="text-3xl font-bold text-[#F97316] font-data tabular-nums">{{ $pendingCount }}</p>
                            <p class="text-xs text-gray-500 mt-0.5 font-data">applications awaiting review</p>
                        </div>
                        <div class="w-14 h-14 rounded-xl bg-[#F97316]/10 flex items-center justify-center shrink-0">
                            <svg class="w-8 h-8 text-[#F97316]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 border-t-[10px] border-t-[#1E40AF] shadow-2xl p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-heading font-bold uppercase tracking-wide text-gray-500 mb-1">Demographics</p>
                            @php
                                $maleRow = $genderBreakdown->firstWhere('gender', 'Male');
                                $femaleRow = $genderBreakdown->firstWhere('gender', 'Female');
                                $maleCount = $maleRow ? (int) $maleRow->count : 0;
                                $femaleCount = $femaleRow ? (int) $femaleRow->count : 0;
                            @endphp
                            <p class="text-2xl sm:text-3xl font-bold text-[#1E40AF] font-data tabular-nums">M: {{ $maleCount }} / F: {{ $femaleCount }}</p>
                        </div>
                        <div class="w-14 h-14 rounded-xl bg-[#1E40AF]/10 flex items-center justify-center shrink-0">
                            <svg class="w-8 h-8 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Two chart containers side by side --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-2xl overflow-hidden border-t-[10px] border-t-[#1E40AF] flex flex-col">
                    <div class="px-6 py-4 border-b border-gray-100 flex flex-col xl:flex-row xl:items-center justify-between gap-4 shrink-0">
                        <h2 class="font-heading text-lg font-bold text-gray-800">Institutional Overview</h2>
                        <div class="flex items-center gap-2">
                            <button type="button" class="px-4 py-1.5 text-xs font-bold font-heading rounded-lg transition-colors bg-[#1E40AF] text-white border border-[#1E40AF]" id="btnTrendDaily" onclick="updateAnalyticsChart('daily')">Daily</button>
                            <button type="button" class="px-4 py-1.5 text-xs font-bold font-heading rounded-lg transition-colors bg-transparent text-[#1E40AF] border border-[#1E40AF] hover:bg-blue-50" id="btnTrendWeekly" onclick="updateAnalyticsChart('weekly')">Weekly</button>
                            <button type="button" class="px-4 py-1.5 text-xs font-bold font-heading rounded-lg transition-colors bg-transparent text-[#1E40AF] border border-[#1E40AF] hover:bg-blue-50" id="btnTrendMonthly" onclick="updateAnalyticsChart('monthly')">Monthly</button>
                        </div>
                    </div>
                    <div class="p-6">
                        @php $trendMax = max(1, (int) ($trendRows->max('count') ?? 1)); @endphp
                        <div class="min-h-[200px] space-y-3">
                            @forelse($trendRows as $row)
                                <div class="flex flex-col gap-1">
                                    <div class="flex justify-between text-sm {{ (request()->routeIs('registrar.*') || request()->routeIs('admin.*') || request()->routeIs('unifast.*') || request()->routeIs('dean.*')) ? 'font-data' : '' }}">
                                        <span class="text-gray-700">{{ $row->period }}</span>
                                        <span class="font-semibold text-gray-900">{{ $row->count }}</span>
                                    </div>
                                    <div class="w-full h-2 bg-gray-100 rounded overflow-hidden">
                                        <div class="h-full bg-[#1E40AF] rounded" style="width: {{ min(100, (int)(($row->count / $trendMax) * 100)) }}%;"></div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 py-4 {{ (request()->routeIs('registrar.*') || request()->routeIs('admin.*') || request()->routeIs('unifast.*') || request()->routeIs('dean.*')) ? 'font-data' : '' }}">No enrollment submissions yet for the selected period. Trends appear once students submit enrollment forms (and match the current Academic Year / Semester filters).</p>
                            @endforelse
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <p class="text-xs font-semibold text-gray-500 mb-2 {{ (request()->routeIs('registrar.*') || request()->routeIs('admin.*') || request()->routeIs('unifast.*') || request()->routeIs('dean.*')) ? 'font-heading' : '' }}">Overview chart</p>
                            <div id="analyticsChartContainer" class="min-h-[180px] flex items-center justify-center bg-gray-50/50 rounded-lg">
                                <canvas id="analyticsChartCanvas" height="120" class="max-w-full"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-2xl overflow-hidden">
                    <div class="bg-[#1E40AF] px-6 py-4">
                        <h2 class="{{ (request()->routeIs('registrar.*') || request()->routeIs('admin.*') || request()->routeIs('unifast.*') || request()->routeIs('dean.*')) ? 'font-heading' : '' }} text-lg font-bold text-white">Program Distribution</h2>
                    </div>
                    <div class="p-6 min-h-[280px] flex flex-col items-center justify-center">
                        <canvas id="analyticsProgramChartCanvas" class="max-w-full" height="260"></canvas>
                        @if($programBreakdown->isEmpty())
                            <p class="text-sm text-gray-500 text-center mt-2 {{ (request()->routeIs('registrar.*') || request()->routeIs('admin.*') || request()->routeIs('unifast.*') || request()->routeIs('dean.*')) ? 'font-data' : '' }}">No program data for the selected filters.</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Student Geographic Distribution — balanced side-by-side grid, blue header, Roboto data --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Map Container --}}
                <div class="bg-white shadow-2xl rounded-xl overflow-hidden border border-gray-200 border-t-[10px] border-t-[#1E40AF] flex flex-col">
                    <div class="px-6 py-4 border-b border-gray-100 shrink-0">
                        <h2 class="font-heading text-lg font-bold text-gray-800">Student Geographic Distribution</h2>
                        <p class="text-gray-500 text-sm font-data mt-0.5">Interactive data map view.</p>
                    </div>
                    <div class="p-6 flex-1 min-h-[320px] flex items-center justify-center bg-gray-50/50">
                        <canvas id="analyticsGeographicChartCanvas" class="max-w-full" height="280"></canvas>
                    </div>
                </div>

                {{-- Data List Container --}}
                <div class="bg-white shadow-2xl rounded-xl overflow-hidden border border-gray-200 border-t-[10px] border-t-[#1E40AF] flex flex-col">
                    <div class="px-6 py-4 border-b border-gray-100 shrink-0">
                        <h2 class="font-heading text-lg font-bold text-gray-800">Municipality / City Totals</h2>
                        <p class="text-gray-500 text-sm font-data mt-0.5">Enrollees per municipality/city.</p>
                    </div>
                    <div class="flex-1 overflow-y-auto min-h-[320px] p-0">
                        <table class="w-full text-sm font-data" role="grid" aria-label="Geographic distribution by location">
                            <thead class="bg-gray-100 sticky top-0">
                                <tr>
                                    <th scope="col" class="py-3 px-6 text-left font-heading font-bold text-gray-700 border-b border-gray-200">Municipality/City</th>
                                    <th scope="col" class="py-3 px-6 text-right font-heading font-bold text-gray-700 border-b border-gray-200">Total Enrollees</th>
                                </tr>
                            </thead>
                            <tbody id="analyticsGeographicTableBody" class="divide-y divide-gray-100 bg-white">
                                <tr><td colspan="2" class="py-8 px-6 text-center text-gray-500 font-data" id="analyticsGeographicTableEmpty">No geographic data for the selected filters.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Overview chart controls (Dataset, Type, Hide) — solid DCOMC blue header --}}
            <div class="bg-white shadow-2xl rounded-xl overflow-hidden border border-gray-200">
                <div class="bg-[#1E40AF] px-6 py-4">
                    <h2 class="font-heading text-lg font-bold text-white">Overview Chart Options</h2>
                </div>
                <div class="p-5 flex flex-wrap items-end gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label for="analyticsDatasetSelect" class="text-xs font-heading font-bold text-gray-600 uppercase tracking-wide">Dataset</label>
                        <select id="analyticsDatasetSelect" class="border border-gray-300 rounded-lg px-4 py-2.5 text-sm bg-white min-w-[200px] shadow-sm focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:border-[#1E40AF] transition-colors cursor-pointer text-gray-800 font-data">
                            <option value="status">Status (Approved / Pending / Rejected)</option>
                            <option value="program">Program Breakdown</option>
                            <option value="gender">Gender Breakdown</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="analyticsChartTypeSelect" class="text-xs font-heading font-bold text-gray-600 uppercase tracking-wide">Chart type</label>
                        <select id="analyticsChartTypeSelect" class="border border-gray-300 rounded-lg px-4 py-2.5 text-sm bg-white min-w-[120px] shadow-sm focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:border-[#1E40AF] transition-colors cursor-pointer text-gray-800 font-data">
                            <option value="pie">Pie</option>
                            <option value="bar">Bar</option>
                            <option value="line">Line</option>
                        </select>
                    </div>
                    <button type="button" id="analyticsChartToggle" class="px-5 py-2.5 rounded-lg text-sm font-bold bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:ring-offset-2 transition-colors shrink-0 font-heading">Hide chart</button>
                </div>
            </div>

            {{-- Departmental Breakdown table — solid blue header bar, hover rows --}}
            <div class="w-full bg-white shadow-2xl rounded-xl overflow-hidden border border-gray-200 border-t-[10px] border-t-[#1E40AF]">
                <div class="bg-[#1E40AF] px-6 py-4">
                    <h2 class="font-heading text-lg font-bold text-white">Departmental Breakdown</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm font-data admin-table-wrap" role="grid">
                        <thead class="bg-[#1E40AF]">
                            <tr>
                                <th scope="col" class="text-left py-3 px-6 font-heading font-bold text-white border-b border-[#1E40AF]">Program/Major</th>
                                <th scope="col" class="text-right py-3 px-6 font-heading font-bold text-white border-b border-[#1E40AF]">Enrolled</th>
                                <th scope="col" class="text-right py-3 px-6 font-heading font-bold text-white border-b border-[#1E40AF]">Percentage</th>
                                <th scope="col" class="text-center py-3 px-6 font-heading font-bold text-white border-b border-[#1E40AF]">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @php
                                $totalDeptEnrollees = $departmentData->sum('count');
                            @endphp
                            @forelse($departmentData as $row)
                                <tr class="transition-colors duration-200 hover:bg-blue-50/50">
                                    <td class="py-3 px-6 text-gray-900 font-data">{{ $row->course ?: 'N/A' }}</td>
                                    <td class="py-3 px-6 text-right text-gray-700 font-bold font-data tabular-nums">{{ $row->count }}</td>
                                    <td class="py-3 px-6 text-right text-gray-500 font-medium font-data tabular-nums">
                                        {{ $totalDeptEnrollees > 0 ? number_format(($row->count / $totalDeptEnrollees) * 100, 1) : 0 }}%
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 font-heading border border-green-200 shadow-sm">Active</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 px-6 text-center text-gray-500 font-data">No program data available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
    </div>

    {{-- #region agent log --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const main = document.getElementById('main-content');
            const scroll = document.getElementById('analytics-content-scroll');
            if (main && scroll) {
                fetch('http://127.0.0.1:7868/ingest/7ab428e2-5531-4e28-bf38-6f2a014ed94c', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '3083bc' },
                    body: JSON.stringify({
                        sessionId: '3083bc',
                        hypothesisId: 'H-A',
                        location: 'analytics.blade.php:dimensions',
                        message: 'Checking scroll container dimensions',
                        data: {
                            mainClientHeight: main.clientHeight,
                            scrollHeight: scroll.scrollHeight,
                            scrollClientHeight: scroll.clientHeight,
                            windowHeight: window.innerHeight
                        },
                        timestamp: Date.now()
                    })
                }).catch(()=>{});
            }
        });
    </script>
    {{-- #endregion --}}

    @php
        $monthlyLabels = $trendRows->pluck('period')->values();
        $monthlyCounts = $trendRows->pluck('count')->values();

        $yearlyGroups = [];
        foreach($trendRows as $row) {
            $year = substr($row->period, 0, 4);
            if (!isset($yearlyGroups[$year])) $yearlyGroups[$year] = 0;
            $yearlyGroups[$year] += $row->count;
        }
        $yearlyLabels = array_keys($yearlyGroups);
        $yearlyCounts = array_values($yearlyGroups);

        $semesterGroups = ['1st Semester' => 0, '2nd Semester' => 0];
        foreach($trendRows as $row) {
            $month = (int) substr($row->period, 5, 2);
            if ($month >= 8 || $month <= 12) {
                $semesterGroups['1st Semester'] += $row->count;
            } else {
                $semesterGroups['2nd Semester'] += $row->count;
            }
        }
        $semesterLabels = array_keys($semesterGroups);
        $semesterCounts = array_values($semesterGroups);

        $analyticsProgramLabels = $programBreakdown->pluck('course')->map(fn ($c) => $c ?: 'N/A')->values();
        $analyticsProgramCounts = $programBreakdown->pluck('count')->values();
        $analyticsGenderLabels = $genderBreakdown->pluck('gender')->map(fn ($g) => $g ?: 'N/A')->values();
        $analyticsGenderCounts = $genderBreakdown->pluck('count')->values();
    @endphp

    <script>
        const analyticsDatasets = {
            daily: {
                label: 'Daily Trends',
                labels: @json($monthlyLabels),
                data: @json($monthlyCounts),
            },
            weekly: {
                label: 'Weekly Trends',
                labels: @json($semesterLabels),
                data: @json($semesterCounts),
            },
            monthly: {
                label: 'Monthly Trends',
                labels: @json($yearlyLabels),
                data: @json($yearlyCounts),
            },
            status: {
                label: 'Enrollment Status',
                labels: ['Approved', 'Pending', 'Rejected'],
                data: [{{ $approvedCount }}, {{ $pendingCount }}, {{ $rejectedCount }}],
            },
            program: {
                label: 'Program Breakdown',
                labels: @json($analyticsProgramLabels),
                data: @json($analyticsProgramCounts),
            },
            gender: {
                label: 'Gender Breakdown',
                labels: @json($analyticsGenderLabels),
                data: @json($analyticsGenderCounts),
            },
        };

        let analyticsChart = null;
        let currentChartDataset = 'monthly';

        function updateAnalyticsChart(datasetKey) {
            currentChartDataset = datasetKey;
            
            // Update button styles
            const buttons = ['btnTrendDaily', 'btnTrendWeekly', 'btnTrendMonthly'];
            buttons.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.className = "px-4 py-1.5 text-xs font-bold font-heading rounded-lg transition-colors bg-transparent text-[#1E40AF] border border-[#1E40AF] hover:bg-blue-50";
                }
            });
            
            const activeBtnMap = {
                'daily': 'btnTrendDaily',
                'weekly': 'btnTrendWeekly',
                'monthly': 'btnTrendMonthly'
            };
            
            const activeBtn = document.getElementById(activeBtnMap[datasetKey]);
            if (activeBtn) {
                activeBtn.className = "px-4 py-1.5 text-xs font-bold font-heading rounded-lg transition-colors bg-[#1E40AF] text-white border border-[#1E40AF]";
            }
            
            // If the user clicks a trend button, we override the dataset select
            const dsSelect = document.getElementById('analyticsDatasetSelect');
            if (dsSelect) {
                // If it's a trend option not in the dropdown, we temporarily ignore the dropdown
                dsSelect.dataset.override = datasetKey;
            }
            
            buildAnalyticsChart();
        }

        function buildAnalyticsChart() {
            const canvas = document.getElementById('analyticsChartCanvas');
            if (!canvas || !window.Chart) return;

            const dsSelect = document.getElementById('analyticsDatasetSelect');
            const typeSelect = document.getElementById('analyticsChartTypeSelect');
            
            // Use override from buttons if present, otherwise use dropdown
            let datasetKey = currentChartDataset;
            if (dsSelect && !dsSelect.dataset.override) {
                datasetKey = dsSelect.value;
            }
            const chartType = typeSelect ? typeSelect.value : 'bar';

            const cfg = analyticsDatasets[datasetKey];
            if (!cfg) return;

            if (analyticsChart) {
                analyticsChart.destroy();
            }

            analyticsChart = new Chart(canvas.getContext('2d'), {
                type: chartType,
                data: {
                    labels: cfg.labels,
                    datasets: [{
                        label: cfg.label,
                        data: cfg.data,
                        backgroundColor: chartType === 'pie' ? [
                            'rgba(30, 64, 175, 0.8)',
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(139, 92, 246, 0.8)',
                            'rgba(236, 72, 153, 0.8)',
                        ] : 'rgba(30, 64, 175, 0.85)',
                        borderColor: chartType === 'pie' ? '#fff' : '#1E40AF',
                        borderWidth: chartType === 'pie' ? 2 : 1,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                    },
                    scales: chartType === 'pie' ? {} : {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                        },
                    },
                },
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            // #region agent log
            (function () {
                var main = document.getElementById('main-content');
                var contentDiv = document.getElementById('analytics-content-scroll');
                var geoSection = document.getElementById('analyticsGeographicTableBody');
                var bodyClass = document.body && document.body.className ? document.body.className : '';
                var mainOh = main ? main.offsetHeight : 0;
                var mainSh = main ? main.scrollHeight : 0;
                var contentOh = contentDiv ? contentDiv.offsetHeight : 0;
                var contentSh = contentDiv ? contentDiv.scrollHeight : 0;
                var payload = {
                    sessionId: '3083bc',
                    location: 'analytics.blade.php:DOMContentLoaded',
                    message: 'Analytics layout and DOM',
                    data: {
                        geographicTableBodyExists: !!geoSection,
                        mainOffsetHeight: mainOh,
                        mainScrollHeight: mainSh,
                        contentDivOffsetHeight: contentOh,
                        contentDivScrollHeight: contentSh,
                        bodyClass: bodyClass.indexOf('dashboard-wrap') !== -1 ? 'dashboard-wrap' : 'other',
                    },
                    timestamp: Date.now(),
                    hypothesisId: 'H-A'
                };
                fetch('http://127.0.0.1:7868/ingest/7ab428e2-5531-4e28-bf38-6f2a014ed94c', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '3083bc' }, body: JSON.stringify(payload) }).catch(function () {});
            })();
            // #endregion

            const datasetSelect = document.getElementById('analyticsDatasetSelect');
            const typeSelect = document.getElementById('analyticsChartTypeSelect');
            const toggleBtn = document.getElementById('analyticsChartToggle');
            const container = document.getElementById('analyticsChartContainer');

            if (datasetSelect) {
                datasetSelect.addEventListener('change', () => {
                    datasetSelect.dataset.override = ''; // Clear override when user manually changes dropdown
                    buildAnalyticsChart();
                });
            }
            if (typeSelect) {
                typeSelect.addEventListener('change', buildAnalyticsChart);
            }
            if (toggleBtn && container) {
                toggleBtn.addEventListener('click', () => {
                    const hidden = container.classList.toggle('hidden');
                    toggleBtn.textContent = hidden ? 'Show chart' : 'Hide chart';
                    if (!hidden) {
                        setTimeout(buildAnalyticsChart, 50);
                    }
                });
            }

            buildAnalyticsChart();

            // Program Distribution pie/doughnut chart
            const programCanvas = document.getElementById('analyticsProgramChartCanvas');
            const hasProgramData = @json($programBreakdown->isNotEmpty());
            if (programCanvas && window.Chart && hasProgramData) {
                const programLabels = @json($analyticsProgramLabels);
                const programData = @json($analyticsProgramCounts);
                new Chart(programCanvas.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: programLabels,
                        datasets: [{
                            data: programData,
                            backgroundColor: [
                                'rgba(30, 64, 175, 0.8)',
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(16, 185, 129, 0.8)',
                                'rgba(245, 158, 11, 0.8)',
                                'rgba(139, 92, 246, 0.8)',
                                'rgba(236, 72, 153, 0.8)',
                                'rgba(20, 184, 166, 0.8)',
                                'rgba(249, 115, 22, 0.8)',
                            ],
                            borderColor: '#fff',
                            borderWidth: 2,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { position: 'bottom' },
                        },
                    },
                });
            }

            // Geographic Distribution: map your backend address data to geographicLabels and geographicData (do not change backend variable names)
            const geographicLabels = @json(isset($geographicLabels) ? $geographicLabels : []);
            const geographicData = @json(isset($geographicData) ? $geographicData : []);
            const geoCanvas = document.getElementById('analyticsGeographicChartCanvas');
            const geoTableBody = document.getElementById('analyticsGeographicTableBody');
            const geoTableEmpty = document.getElementById('analyticsGeographicTableEmpty');
            if (geoCanvas && window.Chart) {
                const hasGeoData = Array.isArray(geographicLabels) && geographicLabels.length > 0 && Array.isArray(geographicData) && geographicData.length === geographicLabels.length;
                if (hasGeoData) {
                    new Chart(geoCanvas.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: geographicLabels,
                            datasets: [{
                                label: 'Enrollees',
                                data: geographicData,
                                backgroundColor: 'rgba(30, 64, 175, 0.85)',
                                borderColor: '#1E40AF',
                                borderWidth: 1,
                                hoverBackgroundColor: 'rgba(30, 64, 175, 0.65)',
                            }],
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: { display: false },
                            },
                            scales: {
                                x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.06)' } },
                                y: { grid: { display: false } },
                            },
                        },
                    });
                    if (geoTableBody && geoTableEmpty) {
                        geoTableEmpty.remove();
                        geographicLabels.forEach((label, i) => {
                            const tr = document.createElement('tr');
                            tr.className = 'hover:bg-blue-50/50 transition-colors';
                            tr.innerHTML = '<td class="py-3 px-6 text-gray-900 font-data">' + (label || '—') + '</td><td class="py-3 px-6 text-right text-gray-700 font-bold font-data tabular-nums">' + (geographicData[i] ?? 0) + '</td>';
                            geoTableBody.appendChild(tr);
                        });
                    }
                }
            }
        });
    </script>
</body>
</html>
