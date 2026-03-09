<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @php
        $isRegistrarOrDean = isset($sidebar) && in_array($sidebar, ['registrar-sidebar', 'dean-sidebar', 'staff-sidebar', 'unifast-sidebar', 'admin-sidebar'], true);
    @endphp
    <style>
        .skip-link { position: absolute; left: -9999px; z-index: 1; }
        .skip-link:focus { left: 1rem; top: 1rem; padding: 0.5rem 1rem; background: #1E40AF; color: #fff; border-radius: 0.5rem; width: auto; height: auto; overflow: visible; clip: auto; }
        .chart-skeleton { background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%); background-size: 200% 100%; animation: chart-shimmer 1.2s ease-in-out infinite; border-radius: 0.5rem; }
        @keyframes chart-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        [data-chart-loaded="true"] .chart-skeleton { display: none !important; }
        .chart-canvas { opacity: 0; transition: opacity 0.25s ease-out; }
        [data-chart-loaded="true"] .chart-canvas { opacity: 1; }
    </style>
    @if($isRegistrarOrDean)
    <style>
        .font-heading { font-family: 'Figtree', sans-serif; }
        .font-data { font-family: 'Roboto', sans-serif; }
        .hero-gradient { background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 50%, #60A5FA 100%); }
        [x-cloak] { display: none !important; }
        .chart-skeleton { background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%); background-size: 200% 100%; animation: chart-skeleton-shimmer 1.2s ease-in-out infinite; }
        @keyframes chart-skeleton-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        @media (prefers-reduced-motion: reduce) {
            .transition-all, [class*="transition-"], [class*="hover:-translate"], [class*="hover:scale"] { transition: none !important; }
            .hover\:-translate-y-1:hover, .hover\:scale-\[1\.01\]:hover { transform: none !important; }
            .chart-skeleton { animation: none; background: #e5e7eb; }
        }
        @media print {
            .no-print { display: none !important; }
            .print\:block { display: block !important; }
            body { background: #fff; }
            .hero-gradient { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
    @endif
</head>
<body class="{{ $isRegistrarOrDean ? 'h-screen overflow-hidden' : 'bg-[#eef0f2] flex h-screen overflow-hidden' }}">
    <a href="#main-content" class="skip-link no-print">Skip to main content</a>
    @if($isRegistrarOrDean)
    <div class="w-full h-screen flex overflow-hidden">
    @endif
    @include('dashboards.partials.' . $sidebar)

    <main id="main-content" class="{{ $isRegistrarOrDean ? 'flex-1 flex flex-col min-w-0 h-screen overflow-hidden' : 'flex-1 flex flex-col min-h-0 h-screen overflow-y-auto' }}" role="main" tabindex="-1">
        <header class="bg-white border-b border-gray-200 px-6 py-4 flex flex-wrap items-center justify-between gap-4 w-full shrink-0">
            <div class="flex items-center gap-4 flex-wrap">
                <h1 class="text-xl font-bold text-[#0d3b66] tracking-tight">DASHBOARD</h1>
                <span class="text-sm text-gray-500">{{ $sidebar === 'admin-sidebar' ? 'DCOMC Administrator Dashboard' : ($sidebar === 'unifast-sidebar' ? 'UniFAST Management' : 'DCOMC Enrollment Overview') }}</span>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                @if($isAdmin && session('role_switch.active'))
                    @php
                        $cockpitMirrorLabel = strtoupper(session('role_switch.as_role') ?? '');
                        if ($cockpitMirrorLabel === 'DEAN' && !empty(session('role_switch.department_id'))) {
                            $md = \App\Models\Department::find(session('role_switch.department_id'));
                            $cockpitMirrorLabel .= $md ? ' (' . $md->name . ')' : '';
                        }
                    @endphp
                    <span class="text-xs bg-amber-100 text-amber-800 px-2 py-1 rounded border border-amber-300">Mirroring: {{ $cockpitMirrorLabel }}</span>
                    <form method="POST" action="{{ route('admin.role-switch.stop') }}" class="inline">@csrf @method('DELETE')<button type="submit" class="text-xs bg-amber-600 hover:bg-amber-700 text-white px-3 py-2 rounded font-semibold">Switch Back</button></form>
                @elseif($isAdmin)
                    @php $cockpitDeanDepts = \App\Models\Department::whereIn('name', [\App\Models\Department::NAME_EDUCATION, \App\Models\Department::NAME_ENTREPRENEURSHIP])->orderBy('name')->get(); @endphp
                    <form method="POST" action="{{ route('admin.role-switch.start') }}" class="inline flex flex-wrap items-center gap-2">@csrf
                        <select name="role" id="cockpitRoleSelect" class="border border-gray-300 rounded px-3 py-2 text-sm bg-white">
                            <option value="">Select role to mirror...</option>
                            <option value="student">Student</option>
                            <option value="registrar">Registrar</option>
                            <option value="staff">Staff</option>
                            <option value="unifast">UniFAST</option>
                            <option value="dean">Dean</option>
                        </select>
                        <div id="cockpitDeanChoice" class="hidden flex items-center gap-2">
                            <label for="cockpitDeanDept" class="text-sm text-gray-600">as</label>
                            <select name="department_id" id="cockpitDeanDept" class="border border-gray-300 rounded px-3 py-2 text-sm bg-white">
                                <option value="">— Educ / Entrep —</option>
                                @foreach($cockpitDeanDepts as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded text-sm font-semibold">Switch to Dean</button>
                        </div>
                    </form>
                    <script>
                        (function(){ var s = document.getElementById('cockpitRoleSelect'); if(!s) return; s.addEventListener('change', function(){ var v = this.value; var dc = document.getElementById('cockpitDeanChoice'); if(v === 'dean'){ dc.classList.remove('hidden'); dc.classList.add('flex'); document.getElementById('cockpitDeanDept').required = true; } else if(v){ dc.classList.add('hidden'); document.getElementById('cockpitDeanDept').required = false; document.getElementById('cockpitDeanDept').value = ''; this.form.submit(); } else { dc.classList.add('hidden'); } }); })();
                    </script>
                @endif
                @if(!$isRegistrarOrDean)
                <a href="{{ route($routes['analytics'], array_filter($filters ?? [])) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#0d3b66] hover:bg-[#0a2d4d] text-white rounded-lg text-sm font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-[#0d3b66] focus:ring-offset-2 transition-colors">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Analytics
                </a>
                @endif
            </div>
        </header>

        @if($isRegistrarOrDean)
        {{-- Filter Toolbar: master AY/Semester + Analytics --}}
        <div class="bg-white border-b border-gray-200 px-6 py-4 shrink-0 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <form method="GET" action="{{ route($routes['dashboard']) }}" class="flex flex-wrap items-center gap-6">
                    <p class="text-sm font-semibold text-gray-700 mb-0 font-heading">Currently Viewing Data for:</p>
                    <div class="flex flex-wrap items-end gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label for="cockpit-academic-year" class="text-xs font-medium text-gray-500 uppercase tracking-wide">Academic Year</label>
                            <select name="academic_year" id="cockpit-academic-year" class="border border-gray-300 rounded-lg px-4 py-2.5 text-sm bg-white min-w-[180px] shadow-sm focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:border-[#1E40AF] transition-colors cursor-pointer text-gray-800" onchange="this.form.submit()">
                                <option value="">All / Session default</option>
                                @foreach($academicYears ?? [] as $ay)
                                    <option value="{{ $ay }}" {{ ($filters['academic_year'] ?? '') === $ay ? 'selected' : '' }}>{{ $ay }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="cockpit-semester" class="text-xs font-medium text-gray-500 uppercase tracking-wide">Semester</label>
                            <select name="semester" id="cockpit-semester" class="border border-gray-300 rounded-lg px-4 py-2.5 text-sm bg-white min-w-[140px] shadow-sm focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:border-[#1E40AF] transition-colors cursor-pointer text-gray-800" onchange="this.form.submit()">
                                <option value="">All</option>
                                @foreach($semesters ?? [] as $sem)
                                    <option value="{{ $sem }}" {{ ($filters['semester'] ?? '') === $sem ? 'selected' : '' }}>{{ $sem }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-bold bg-[#1E40AF] text-white hover:bg-[#1D3A8A] focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:ring-offset-2 transition-colors shrink-0 font-heading border-0 cursor-pointer">Apply</button>
                    </div>
                </form>
                <a href="{{ route($routes['analytics'], array_filter($filters ?? [])) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold bg-[#1E40AF] text-white hover:bg-[#1D3A8A] shadow-sm focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:ring-offset-2 transition-colors shrink-0 no-underline font-data">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Analytics
                </a>
            </div>
        </div>
        @endif

        <div class="{{ $isRegistrarOrDean ? 'flex-1 w-full flex flex-col min-h-0 overflow-y-auto bg-gray-50 pt-2 px-8 pb-8 space-y-6' : 'p-6 flex flex-col gap-5' }}">
            @if($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-r"><ul class="list-disc pl-5 text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            @if(session('success'))
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-r">{{ session('success') }}</div>
            @endif
            @if(session('role_switch.active') && !$isAdmin)
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded">
                    <p class="font-semibold">Admin role switch is active (mirroring {{ strtoupper(session('role_switch.as_role')) }}).</p>
                    <form method="POST" action="{{ route('admin.role-switch.stop') }}" class="mt-2">@csrf @method('DELETE')<button type="submit" class="text-sm bg-amber-600 hover:bg-amber-700 text-white px-3 py-2 rounded font-semibold">Switch Back to Admin</button></form>
                </div>
            @endif

            @if(!$isRegistrarOrDean)
            <form method="GET" action="{{ route($routes['dashboard']) }}" class="flex flex-wrap items-center gap-3">
                <label class="text-sm font-medium text-gray-700">Academic Year</label>
                <select name="academic_year" class="border border-gray-300 rounded px-3 py-2 text-sm bg-white min-w-[140px]" onchange="this.form.submit()">
                    <option value="">All / Session default</option>
                    @foreach($academicYears ?? [] as $ay)
                        <option value="{{ $ay }}" {{ ($filters['academic_year'] ?? '') === $ay ? 'selected' : '' }}>{{ $ay }}</option>
                    @endforeach
                </select>
                <label class="text-sm font-medium text-gray-700">Semester</label>
                <select name="semester" class="border border-gray-300 rounded px-3 py-2 text-sm bg-white min-w-[120px]" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach($semesters ?? [] as $sem)
                        <option value="{{ $sem }}" {{ ($filters['semester'] ?? '') === $sem ? 'selected' : '' }}>{{ $sem }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-4 py-2 bg-[#1E40AF] hover:bg-[#1D3A8A] text-white rounded-lg text-sm font-bold font-heading border-0 cursor-pointer">Apply</button>
            </form>
            @endif

            @if($sidebar === 'registrar-sidebar')
            @php
                $recentApps = $recentApplications ?? collect();
                $regCountPending = $recentApps->filter(function ($a) {
                    $ps = $a->process_status ?? null; $ap = $a->approval_status ?? null;
                    if ($ap === 'rejected' || $ps === 'rejected') return false;
                    return $ps === 'needs_correction' || !in_array($ps, ['approved', 'scheduled', 'completed'], true);
                })->count();
                $regCountApproved = $recentApps->filter(function ($a) {
                    $ps = $a->process_status ?? null;
                    return in_array($ps, ['approved', 'scheduled', 'completed'], true);
                })->count();
                $regCountDenied = $recentApps->filter(function ($a) {
                    $ps = $a->process_status ?? null; $ap = $a->approval_status ?? null;
                    return $ap === 'rejected' || $ps === 'rejected';
                })->count();
                $regCountAll = $recentApps->count();
                $regRowsJson = $recentApps->map(function ($a) {
                    $ps = $a->process_status ?? null; $ap = $a->approval_status ?? null;
                    $bucket = 'pending';
                    if (in_array($ps, ['approved', 'scheduled'], true) || $ps === 'completed') $bucket = 'approved';
                    elseif ($ap === 'rejected' || $ps === 'rejected') $bucket = 'denied';
                    $searchText = strtolower(($a->user->name ?? '') . ' ' . ($a->user->school_id ?? '') . ' ' . ($a->user->email ?? ''));
                    return ['f' => $bucket, 's' => $searchText];
                })->values()->toJson();
            @endphp
            {{-- Error state (dedicated card when errors exist) --}}
            @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 flex flex-col gap-2">
                <p class="font-heading font-semibold text-red-800">Something went wrong</p>
                <ul class="list-disc pl-5 text-sm text-red-700 font-data">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
                <a href="{{ request()->fullUrl() }}" class="inline-flex items-center gap-2 text-sm font-semibold text-[#1E40AF] hover:text-[#1D3A8A] no-underline w-fit">Refresh page</a>
            </div>
            @endif
            {{-- Registrar Dashboard (banner directly under filter bar) --}}
            <div class="w-full flex flex-col gap-6" x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 280)" x-cloak>
                {{-- Skeleton (shown until loaded) --}}
                <div x-show="!loaded" x-transition:leave="transition ease-out duration-200" class="flex flex-col gap-6">
                    <section class="hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white opacity-90">
                        <div class="h-8 w-64 bg-white/20 rounded animate-pulse mb-2"></div>
                        <div class="h-4 w-96 bg-white/20 rounded animate-pulse"></div>
                    </section>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white rounded-xl border border-gray-200 p-5"><div class="h-4 w-24 bg-gray-200 rounded animate-pulse mb-3"></div><div class="h-9 w-16 bg-gray-200 rounded animate-pulse"></div></div>
                        <div class="bg-white rounded-xl border border-gray-200 p-5"><div class="h-4 w-24 bg-gray-200 rounded animate-pulse mb-3"></div><div class="h-9 w-16 bg-gray-200 rounded animate-pulse"></div></div>
                        <div class="bg-white rounded-xl border border-gray-200 p-5"><div class="h-4 w-24 bg-gray-200 rounded animate-pulse mb-3"></div><div class="h-9 w-16 bg-gray-200 rounded animate-pulse"></div></div>
                    </div>
                    <div class="bg-white shadow-2xl rounded-xl overflow-hidden border border-gray-200">
                        <div class="h-14 bg-gray-300 animate-pulse"></div>
                        <div class="p-4 space-y-3">
                            <div class="h-10 bg-gray-100 rounded animate-pulse"></div>
                            <div class="h-10 bg-gray-100 rounded animate-pulse w-3/4"></div>
                            <div class="h-10 bg-gray-100 rounded animate-pulse w-1/2"></div>
                        </div>
                    </div>
                </div>
                {{-- Content (shown when loaded) --}}
                <div x-show="loaded" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="flex flex-col gap-6">
                {{-- Welcome Banner + AY/Semester + Refresh --}}
                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Registrar Control Panel</h1>
                            <p class="text-white/90 text-sm sm:text-base mb-1">Manage campus enrollments and student records.</p>
                            @if(($filters['academic_year'] ?? null) || ($filters['semester'] ?? null))
                            <p class="text-white/80 text-xs sm:text-sm mt-2">Managing enrollments for {{ ($filters['academic_year'] ?? 'All years') }}@if($filters['semester'] ?? null), {{ $filters['semester'] }}@endif</p>
                            @endif
                            <p class="text-white/70 text-xs mt-1 font-data">Data as of {{ now()->format('M j, Y g:i A') }}</p>
                        </div>
                        <a href="{{ request()->fullUrl() }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-white/20 hover:bg-white/30 text-white text-sm font-medium transition-colors no-underline shrink-0" aria-label="Refresh dashboard">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Refresh
                        </a>
                    </div>
                </section>

                {{-- 3-Card Analytics Row (with View → and zero-count subtext) --}}
                <div class="w-full grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="{{ route($routes['student_status'], array_filter(array_merge($filters ?? [], ['process_status' => 'needs_correction']))) }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 transition-all duration-300 ease-in-out hover:shadow-xl hover:-translate-y-1 hover:scale-[1.01] no-underline">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-heading text-sm font-bold text-gray-600 uppercase tracking-wide mb-1">Pending Applications</p>
                                <p class="font-data text-3xl font-bold mt-1" style="color: #F97316;">{{ number_format($pendingCount ?? 0) }}</p>
                                @if(($pendingCount ?? 0) === 0)<p class="font-data text-xs text-gray-500 mt-1">No pending applications</p>@endif
                            </div>
                            <svg class="w-10 h-10 shrink-0" style="color: #F97316;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <p class="font-data text-sm font-medium text-[#1E40AF] mt-3">View pending →</p>
                    </a>
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 transition-all duration-300 ease-in-out hover:shadow-xl hover:-translate-y-1 hover:scale-[1.01]">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-heading text-sm font-bold text-gray-600 uppercase tracking-wide mb-1">Total Enrolled</p>
                                <p class="font-data text-3xl font-bold mt-1 text-[#1E40AF]">{{ number_format($totalEnrollees ?? 0) }}</p>
                                @if(($totalEnrollees ?? 0) === 0)<p class="font-data text-xs text-gray-500 mt-1">No enrollments yet</p>@endif
                            </div>
                            <svg class="w-10 h-10 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                        </div>
                    </div>
                    <a href="{{ route('registrar.feedback') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 transition-all duration-300 ease-in-out hover:shadow-xl hover:-translate-y-1 hover:scale-[1.01] no-underline">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-heading text-sm font-bold text-gray-600 uppercase tracking-wide mb-1">Feedback / Messages</p>
                                <p class="font-data text-3xl font-bold mt-1 {{ ($feedbackCount ?? 0) === 0 ? 'text-gray-400' : 'text-gray-800' }}">{{ number_format($feedbackCount ?? 0) }}</p>
                                @if(($feedbackCount ?? 0) === 0)<p class="font-data text-xs text-gray-500 mt-1">No new messages</p>@endif
                            </div>
                            <svg class="w-10 h-10 shrink-0 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <p class="font-data text-sm font-medium text-[#1E40AF] mt-3">View feedback</p>
                    </a>
                </div>

                {{-- Recent Enrollment Applications --}}
                <div class="w-full bg-white shadow-2xl rounded-xl overflow-hidden border border-gray-200"
                     data-count-all="{{ $regCountAll }}"
                     data-count-pending="{{ $regCountPending }}"
                     data-count-approved="{{ $regCountApproved }}"
                     data-count-denied="{{ $regCountDenied }}"
                     data-rows="{{ e($regRowsJson) }}"
                     data-toast-message="{{ session('success') ? e(session('success')) : '' }}"
                     x-data="registrarTable()"
                     @submit-success.window="if ($event.detail && $event.detail.message) { toast($event.detail.message, $event.detail.type || 'success'); }">
                    {{-- Toast (aria-live for screen readers) --}}
                    <div aria-live="polite" aria-atomic="true" class="fixed bottom-4 right-4 z-50 max-w-sm pointer-events-none no-print">
                        <template x-if="toastMessage">
                            <div x-show="toastMessage" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                                 class="px-4 py-3 rounded-lg shadow-lg font-data text-sm"
                                 :class="toastType === 'error' ? 'bg-red-600 text-white' : 'bg-green-600 text-white'"
                                 x-text="toastMessage"></div>
                        </template>
                    </div>

                    @if($regCountAll > 0)
                    {{-- Control: White Toolbar above blue header (Search + Filter Pills) --}}
                    <div class="bg-white border-b border-gray-200 px-5 py-5 no-print">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="flex-1 min-w-0 max-w-md">
                                <label for="registrar-search" class="sr-only">Search applications</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" aria-hidden="true">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </span>
                                    <input type="text"
                                           id="registrar-search"
                                           x-model="searchQueryRaw"
                                           placeholder="Search by Student Name or ID..."
                                           class="w-full font-data text-sm border border-gray-200 rounded-lg pl-11 pr-5 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:border-[#1E40AF] placeholder-gray-400 min-h-[44px]" />
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <button type="button" @click="filterStatus = 'all'" :class="filterStatus === 'all' ? 'bg-[#1E40AF] text-white border-2 border-[#1E40AF]' : 'bg-white text-[#1E40AF] border-2 border-blue-200 hover:border-[#1E40AF]/60'" class="font-heading text-sm font-semibold px-5 py-3 min-h-[44px] rounded-full border transition-colors duration-200">All (<span x-text="countAll"></span>)</button>
                                <button type="button" @click="filterStatus = 'pending'" :class="filterStatus === 'pending' ? 'bg-[#1E40AF] text-white border-2 border-[#1E40AF]' : 'bg-white text-[#1E40AF] border-2 border-blue-200 hover:border-[#1E40AF]/60'" class="font-heading text-sm font-semibold px-5 py-3 min-h-[44px] rounded-full border transition-colors duration-200">Pending (<span x-text="countPending"></span>)</button>
                                <button type="button" @click="filterStatus = 'approved'" :class="filterStatus === 'approved' ? 'bg-[#1E40AF] text-white border-2 border-[#1E40AF]' : 'bg-white text-[#1E40AF] border-2 border-blue-200 hover:border-[#1E40AF]/60'" class="font-heading text-sm font-semibold px-5 py-3 min-h-[44px] rounded-full border transition-colors duration-200">Approved (<span x-text="countApproved"></span>)</button>
                                <button type="button" @click="filterStatus = 'denied'" :class="filterStatus === 'denied' ? 'bg-[#1E40AF] text-white border-2 border-[#1E40AF]' : 'bg-white text-[#1E40AF] border-2 border-blue-200 hover:border-[#1E40AF]/60'" class="font-heading text-sm font-semibold px-5 py-3 min-h-[44px] rounded-full border transition-colors duration-200">Denied (<span x-text="countDenied"></span>)</button>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Blue header: Recent Enrollment Applications (no gap below; table sits flush) --}}
                    <div class="bg-[#1E40AF] px-6 py-4 flex flex-wrap items-center justify-between gap-2 border-b-0">
                        <h2 class="font-heading text-lg font-bold text-white">Recent Enrollment Applications</h2>
                    </div>

                    @if($regCountAll > 0)

                    {{-- Desktop table (flush under blue header) --}}
                    <div class="overflow-x-auto hidden md:block mt-0">
                        <table class="w-full font-data text-sm" role="grid">
                            <thead class="bg-gray-50 border-t-0 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="text-left py-3 px-4 font-heading font-semibold text-gray-700">Student Name</th>
                                    <th scope="col" class="text-left py-3 px-4 font-heading font-semibold text-gray-700">Form / Year</th>
                                    <th scope="col" class="text-left py-3 px-4 font-heading font-semibold text-gray-700">Status</th>
                                    <th scope="col" class="text-right py-3 px-4 font-heading font-semibold text-gray-700 no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentApplications ?? [] as $application)
                                @php
                                    $ps = $application->process_status ?? null;
                                    $approval = $application->approval_status ?? null;
                                    $badge = 'Pending';
                                    $badgeClass = 'bg-[#F97316] text-white';
                                    $badgePulse = false;
                                    $filterBucket = 'pending';
                                    if (in_array($ps, ['approved', 'scheduled'], true)) { $badge = 'Reviewing'; $badgeClass = 'bg-[#1E40AF] text-white'; $filterBucket = 'approved'; }
                                    elseif ($ps === 'completed') { $badge = 'Enrolled'; $badgeClass = 'bg-green-600 text-white'; $filterBucket = 'approved'; }
                                    elseif ($ps === 'needs_correction') { $badge = 'Pending'; $badgeClass = 'bg-[#F97316] text-white'; $badgePulse = true; $filterBucket = 'pending'; }
                                    elseif ($approval === 'rejected' || $ps === 'rejected') { $badge = 'Denied'; $badgeClass = 'bg-red-600 text-white'; $filterBucket = 'denied'; }
                                    else { $badgePulse = ($badge === 'Pending'); }
                                    $searchText = strtolower(($application->user->name ?? '') . ' ' . ($application->user->school_id ?? '') . ' ' . ($application->user->email ?? ''));
                                @endphp
                                <tr class="border-b border-gray-100 transition-colors duration-200 hover:bg-[#1E40AF]/5"
                                    data-filter="{{ $filterBucket }}"
                                    data-search="{{ e($searchText) }}"
                                    x-show="matchRow('{{ $filterBucket }}', {{ json_encode($searchText) }})">
                                    <td class="py-3 px-4 text-gray-900 font-data">{{ $application->user->name ?? $application->user->school_id ?? '—' }}</td>
                                    <td class="py-3 px-4 text-gray-700 font-data">{{ $application->enrollmentForm->title ?? '—' }}</td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold {{ $badgeClass }} {{ $badgePulse ? 'animate-pulse' : '' }}">{{ $badge }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-right no-print">
                                        <div class="inline-flex items-center gap-2">
                                            <a href="{{ route($routes['student_status'], array_merge($filters ?? [], ['student' => $application->user->name ?? $application->user->school_id ?? ''])) }}"
                                               class="inline-flex items-center justify-center w-10 h-10 rounded-full border-2 border-[#1E40AF] text-[#1E40AF] bg-transparent hover:bg-[#1E40AF]/10 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/50 min-w-[44px] min-h-[44px]"
                                               title="View details" aria-label="View details">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </a>
                                            @if(!in_array($ps, ['completed', 'scheduled'], true))
                                            <form method="POST" action="{{ route('registrar.student-status.enroll', $application->id) }}" class="inline-block" @submit="submittingId = {{ $application->id }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" :disabled="submittingId"
                                                        class="inline-flex items-center justify-center w-10 h-10 rounded-full border-2 border-[#10B981] text-[#10B981] bg-transparent hover:bg-[#10B981]/10 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-[#10B981]/50 disabled:opacity-50 disabled:pointer-events-none min-w-[44px] min-h-[44px]"
                                                        title="Approve" aria-label="Approve">
                                                    <span x-show="submittingId !== {{ $application->id }}"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></span>
                                                    <span x-show="submittingId === {{ $application->id }}" x-cloak class="inline-block w-5 h-5 border-2 border-[#10B981] border-t-transparent rounded-full animate-spin"></span>
                                                </button>
                                            </form>
                                            @endif
                                            <form method="POST" action="{{ route('registrar.student-status.reject', $application->id) }}" class="inline-block" onsubmit="return confirm('Reject this application?');" @submit="submittingId = {{ $application->id }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" :disabled="submittingId"
                                                        class="inline-flex items-center justify-center w-10 h-10 rounded-full border-2 border-[#EF4444] text-[#EF4444] bg-transparent hover:bg-[#EF4444]/10 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-[#EF4444]/50 disabled:opacity-50 disabled:pointer-events-none min-w-[44px] min-h-[44px]"
                                                        title="Deny" aria-label="Deny">
                                                    <span x-show="submittingId !== {{ $application->id }}"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L6 6M18 6v12M18 6L6 18"/></svg></span>
                                                    <span x-show="submittingId === {{ $application->id }}" x-cloak class="inline-block w-5 h-5 border-2 border-[#EF4444] border-t-transparent rounded-full animate-spin"></span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                                <tr x-show="visibleCount === 0" x-cloak class="border-b border-gray-100 transition-colors duration-200 hover:bg-[#1E40AF]/5 bg-gray-50">
                                    <td colspan="4" class="py-8 px-4 text-center font-data text-sm text-gray-500">No applications match your search or filter.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile cards --}}
                    <div class="md:hidden divide-y divide-gray-100">
                        @foreach($recentApplications ?? [] as $application)
                        @php
                            $ps = $application->process_status ?? null;
                            $approval = $application->approval_status ?? null;
                            $badge = 'Pending'; $badgeClass = 'bg-[#F97316] text-white'; $badgePulse = false; $filterBucket = 'pending';
                            if (in_array($ps, ['approved', 'scheduled'], true)) { $badge = 'Reviewing'; $badgeClass = 'bg-[#1E40AF] text-white'; $filterBucket = 'approved'; }
                            elseif ($ps === 'completed') { $badge = 'Enrolled'; $badgeClass = 'bg-green-600 text-white'; $filterBucket = 'approved'; }
                            elseif ($ps === 'needs_correction') { $badge = 'Pending'; $badgeClass = 'bg-[#F97316] text-white'; $badgePulse = true; $filterBucket = 'pending'; }
                            elseif ($approval === 'rejected' || $ps === 'rejected') { $badge = 'Denied'; $badgeClass = 'bg-red-600 text-white'; $filterBucket = 'denied'; }
                            else { $badgePulse = ($badge === 'Pending'); }
                            $searchText = strtolower(($application->user->name ?? '') . ' ' . ($application->user->school_id ?? '') . ' ' . ($application->user->email ?? ''));
                        @endphp
                        <div class="p-4 font-data text-sm transition-colors duration-200 hover:bg-[#1E40AF]/5" data-app-id="{{ $application->id }}"
                             x-show="matchRow('{{ $filterBucket }}', {{ json_encode($searchText) }})">
                            <div class="flex justify-between items-start gap-2 mb-2">
                                <p class="font-semibold text-gray-900">{{ $application->user->name ?? $application->user->school_id ?? '—' }}</p>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $badgeClass }} {{ $badgePulse ? 'animate-pulse' : '' }} shrink-0">{{ $badge }}</span>
                            </div>
                            <p class="text-gray-600 text-xs mb-3">{{ $application->enrollmentForm->title ?? '—' }}</p>
                            <div class="flex flex-wrap gap-2 no-print">
                                <a href="{{ route($routes['student_status'], array_merge($filters ?? [], ['student' => $application->user->name ?? $application->user->school_id ?? ''])) }}" class="inline-flex items-center justify-center w-10 h-10 min-h-[44px] min-w-[44px] rounded-full border-2 border-[#1E40AF] text-[#1E40AF] bg-transparent hover:bg-[#1E40AF]/10 transition-colors duration-200" aria-label="View" title="View"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                                @if(!in_array($ps, ['completed', 'scheduled'], true))
                                <form method="POST" action="{{ route('registrar.student-status.enroll', $application->id) }}" class="inline-block" @submit="submittingId = parseInt($el.closest('[data-app-id]').dataset.appId, 10)">@csrf @method('PATCH')
                                    <button type="submit" :disabled="submittingId" class="inline-flex items-center justify-center w-10 h-10 min-h-[44px] min-w-[44px] rounded-full border-2 border-[#10B981] text-[#10B981] bg-transparent hover:bg-[#10B981]/10 disabled:opacity-50 transition-colors duration-200" aria-label="Approve" title="Approve"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></button>
                                </form>
                                @endif
                                <form method="POST" action="{{ route('registrar.student-status.reject', $application->id) }}" class="inline-block" onsubmit="return confirm('Reject?');" @submit="submittingId = parseInt($el.closest('[data-app-id]').dataset.appId, 10)">@csrf @method('PATCH')
                                    <button type="submit" :disabled="submittingId" class="inline-flex items-center justify-center w-10 h-10 min-h-[44px] min-w-[44px] rounded-full border-2 border-[#EF4444] text-[#EF4444] bg-transparent hover:bg-[#EF4444]/10 disabled:opacity-50 transition-colors duration-200" aria-label="Deny" title="Deny"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L6 6M18 6v12M18 6L6 18"/></svg></button>
                                </form>
                            </div>
                        </div>
                        @endforeach
                        <div x-show="visibleCount === 0 && countAll > 0" x-cloak class="p-8 text-center font-data text-sm text-gray-500">No applications match your search or filter.</div>
                    </div>
                    @else
                    {{-- All Caught Up: empty student list --}}
                    <div class="flex flex-col items-center justify-center py-16 px-6 text-center">
                        <div class="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center mb-4" aria-hidden="true">
                            <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <p class="font-heading text-xl font-bold text-gray-800 mb-1">No pending applications. You are all caught up!</p>
                    </div>
                    @endif
                </div>
                </div>
            </div>
            @elseif($sidebar === 'dean-sidebar')
            {{-- Dean Dashboard: same layout as Registrar (hero, white stat cards, charts) --}}
            <div class="w-full flex flex-col gap-6" x-data="{ loaded: true }">
                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Dean Control Panel</h1>
                            <p class="text-white/90 text-sm sm:text-base mb-1">Department schedules, faculty load, and institutional reports.</p>
                            @if(($filters['academic_year'] ?? null) || ($filters['semester'] ?? null))
                            <p class="text-white/80 text-xs sm:text-sm mt-2">Viewing data for {{ ($filters['academic_year'] ?? 'All years') }}@if($filters['semester'] ?? null), {{ $filters['semester'] }}@endif</p>
                            @endif
                            <p class="text-white/70 text-xs mt-1 font-data">Department: {{ Auth::user()->department?->name ?? '—' }}</p>
                        </div>
                        <a href="{{ request()->fullUrl() }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-white/20 hover:bg-white/30 text-white text-sm font-medium transition-colors no-underline shrink-0" aria-label="Refresh dashboard">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Refresh
                        </a>
                    </div>
                </section>

                <div class="w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="{{ route('dean.reports') }}" class="bg-white shadow-2xl rounded-xl border border-gray-200 p-5 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 no-underline">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-heading text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Department Enrollment</p>
                                <p class="font-data text-2xl font-bold text-[#1E40AF]">{{ number_format($totalEnrollees ?? 0) }}</p>
                            </div>
                            <svg class="w-10 h-10 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/></svg>
                        </div>
                        <p class="font-data text-sm font-medium text-[#1E40AF] mt-3">View reports →</p>
                    </a>
                    <a href="{{ route('dean.manage-professor.index') }}" class="bg-white shadow-2xl rounded-xl border border-gray-200 p-5 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 no-underline">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-heading text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Faculty / Sections</p>
                                <p class="font-data text-2xl font-bold text-gray-800">{{ number_format(count($blockRows ?? [])) }}</p>
                            </div>
                            <svg class="w-10 h-10 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <p class="font-data text-sm font-medium text-[#1E40AF] mt-3">Manage Professor →</p>
                    </a>
                    <a href="{{ route('dean.reports') }}" class="bg-white shadow-2xl rounded-xl border border-gray-200 p-5 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 no-underline">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-heading text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Pending Approvals</p>
                                <p class="font-data text-2xl font-bold text-gray-800">{{ number_format($pendingCount ?? 0) }}</p>
                            </div>
                            <svg class="w-10 h-10 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <p class="font-data text-sm font-medium text-[#1E40AF] mt-3">Reports →</p>
                    </a>
                    <a href="{{ route('dean.feedback') }}" class="bg-white shadow-2xl rounded-xl border border-gray-200 p-5 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 no-underline">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-heading text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Feedback</p>
                                <p class="font-data text-2xl font-bold text-gray-800">{{ number_format($feedbackCount ?? 0) }}</p>
                            </div>
                            <svg class="w-10 h-10 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <p class="font-data text-sm font-medium text-[#1E40AF] mt-3">View feedback</p>
                    </a>
                </div>

                {{-- Chart cards: consistent design with DCOMC blue accent and Reports link --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white shadow-2xl rounded-xl border border-gray-200 overflow-hidden flex flex-col">
                        <div class="px-5 py-3 border-b border-gray-100 bg-gradient-to-r from-[#1E40AF]/5 to-transparent">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="font-heading text-sm font-bold text-gray-800">Program enrollment</h3>
                                <a href="{{ route($routes['reports'], array_filter($filters ?? [])) }}" class="text-xs font-semibold text-[#1E40AF] hover:text-[#1D3A8A] no-underline whitespace-nowrap">Reports →</a>
                            </div>
                        </div>
                        <div class="p-4 flex-1 flex flex-col min-h-0">
                            <div class="h-48 relative flex-1 min-h-[180px]" data-chart-loaded="false">
                                <div class="chart-skeleton absolute inset-0" aria-hidden="true"></div>
                                <canvas id="deanProgramPieChart" class="chart-canvas relative z-10 w-full h-full"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white shadow-2xl rounded-xl border border-gray-200 overflow-hidden flex flex-col">
                        <div class="px-5 py-3 border-b border-gray-100 bg-gradient-to-r from-[#1E40AF]/5 to-transparent">
                            <h3 class="font-heading text-sm font-bold text-gray-800">By location</h3>
                            <p class="text-xs text-gray-500 font-data mt-0.5">Enrollees by municipality</p>
                        </div>
                        <div class="p-4 flex-1 flex flex-col min-h-0">
                            <div class="h-48 flex items-center justify-center relative flex-1 min-h-[180px]" data-chart-loaded="false">
                                <div class="chart-skeleton absolute inset-0" aria-hidden="true"></div>
                                <canvas id="deanLocationRadarChart" class="chart-canvas relative z-10 w-full h-full"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white shadow-2xl rounded-xl border border-gray-200 overflow-hidden flex flex-col">
                        <div class="px-5 py-3 border-b border-gray-100 bg-gradient-to-r from-[#1E40AF]/5 to-transparent">
                            <h3 class="font-heading text-sm font-bold text-gray-800">Risk and success metrics</h3>
                            <p class="text-xs text-gray-500 font-data mt-0.5">Completion, coverage, overload</p>
                        </div>
                        <div class="p-4 flex-1 flex flex-col min-h-0">
                            <div class="min-h-[220px] flex items-center justify-center relative flex-1" data-chart-loaded="false">
                                <div class="chart-skeleton absolute inset-0" aria-hidden="true"></div>
                                <canvas id="deanRiskRadarChart" class="chart-canvas relative z-10 w-full h-full"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @elseif($sidebar === 'staff-sidebar')
            {{-- Staff Dashboard: same visual layout as Registrar/Dean (hero, white stat cards, charts in cards) --}}
            <div class="w-full flex flex-col gap-6" x-data="{ loaded: true }">
                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Staff Control Panel</h1>
                            <p class="text-white/90 text-sm sm:text-base mb-1">Enrollment data, reports, and task tracking.</p>
                            @if(($filters['academic_year'] ?? null) || ($filters['semester'] ?? null))
                            <p class="text-white/80 text-xs sm:text-sm mt-2">Viewing data for {{ ($filters['academic_year'] ?? 'All years') }}@if($filters['semester'] ?? null), {{ $filters['semester'] }}@endif</p>
                            @endif
                            <p class="text-white/70 text-xs mt-1 font-data">Data as of {{ now()->format('M j, Y g:i A') }}</p>
                        </div>
                        <a href="{{ request()->fullUrl() }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-white/20 hover:bg-white/30 text-white text-sm font-medium transition-colors no-underline shrink-0" aria-label="Refresh dashboard">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Refresh
                        </a>
                    </div>
                </section>

                <div class="w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <a href="{{ route($routes['student_status'], array_filter(array_merge($filters ?? [], ['process_status' => 'pending']))) }}" class="bg-white shadow-2xl rounded-xl border border-gray-200 p-5 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 no-underline">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-heading text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Pending Applications</p>
                                <p class="font-data text-2xl font-bold text-[#1E40AF]">{{ number_format($pendingCount ?? 0) }}</p>
                            </div>
                            <svg class="w-10 h-10 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <p class="font-data text-sm font-medium text-[#1E40AF] mt-3">View status</p>
                    </a>
                    <a href="{{ route($routes['reports'], array_filter($filters ?? [])) }}" class="bg-white shadow-2xl rounded-xl border border-gray-200 p-5 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 no-underline">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-heading text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Total Enrolled</p>
                                <p class="font-data text-2xl font-bold text-gray-800">{{ number_format($totalEnrollees ?? 0) }}</p>
                            </div>
                            <svg class="w-10 h-10 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/></svg>
                        </div>
                        <p class="font-data text-sm font-medium text-[#1E40AF] mt-3">Reports</p>
                    </a>
                    <a href="{{ route('staff.feedback') }}" class="bg-white shadow-2xl rounded-xl border border-gray-200 p-5 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 no-underline">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-heading text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Feedback</p>
                                <p class="font-data text-2xl font-bold text-gray-800">{{ number_format($feedbackCount ?? 0) }}</p>
                            </div>
                            <svg class="w-10 h-10 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <p class="font-data text-sm font-medium text-[#1E40AF] mt-3">View feedback</p>
                    </a>
                </div>

                {{-- Chart cards: identical design to Dean (shadow-2xl, rounded-xl, gradient header, Reports link) --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white shadow-2xl rounded-xl border border-gray-200 overflow-hidden flex flex-col">
                        <div class="px-5 py-3 border-b border-gray-100 bg-gradient-to-r from-[#1E40AF]/5 to-transparent">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="font-heading text-sm font-bold text-gray-800">Program enrollment</h3>
                                <a href="{{ route($routes['reports'], array_filter($filters ?? [])) }}" class="text-xs font-semibold text-[#1E40AF] hover:text-[#1D3A8A] no-underline whitespace-nowrap">Reports →</a>
                            </div>
                        </div>
                        <div class="p-4 flex-1 flex flex-col min-h-0">
                            <div class="h-48 relative flex-1 min-h-[180px]" data-chart-loaded="false">
                                <div class="chart-skeleton absolute inset-0" aria-hidden="true"></div>
                                <canvas id="staffProgramPieChart" class="chart-canvas relative z-10 w-full h-full"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white shadow-2xl rounded-xl border border-gray-200 overflow-hidden flex flex-col">
                        <div class="px-5 py-3 border-b border-gray-100 bg-gradient-to-r from-[#1E40AF]/5 to-transparent">
                            <h3 class="font-heading text-sm font-bold text-gray-800">By location</h3>
                            <p class="text-xs text-gray-500 font-data mt-0.5">Enrollees by municipality</p>
                        </div>
                        <div class="p-4 flex-1 flex flex-col min-h-0">
                            <div class="h-48 flex items-center justify-center relative flex-1 min-h-[180px]" data-chart-loaded="false">
                                <div class="chart-skeleton absolute inset-0" aria-hidden="true"></div>
                                <canvas id="staffLocationRadarChart" class="chart-canvas relative z-10 w-full h-full"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white shadow-2xl rounded-xl border border-gray-200 overflow-hidden flex flex-col">
                        <div class="px-5 py-3 border-b border-gray-100 bg-gradient-to-r from-[#1E40AF]/5 to-transparent">
                            <h3 class="font-heading text-sm font-bold text-gray-800">Risk and success metrics</h3>
                            <p class="text-xs text-gray-500 font-data mt-0.5">Completion, coverage, overload</p>
                        </div>
                        <div class="p-4 flex-1 flex flex-col min-h-0">
                            <div class="min-h-[220px] flex items-center justify-center relative flex-1" data-chart-loaded="false">
                                <div class="chart-skeleton absolute inset-0" aria-hidden="true"></div>
                                <canvas id="staffRiskRadarChart" class="chart-canvas relative z-10 w-full h-full"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Staff analytics: 6-chart operational grid; white floating cards (shadow-2xl), DCOMC Blue --}}
                <div class="w-full mt-8">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                        <div>
                            <p class="text-xs font-data text-gray-500 uppercase tracking-wider mb-0.5">Analytics</p>
                            <h2 class="font-heading text-lg font-bold text-gray-800">Enrollment & classification</h2>
                            @if(($filters['academic_year'] ?? null) || ($filters['semester'] ?? null))
                            <p class="text-xs font-data text-gray-500 mt-1">Data for {{ $filters['academic_year'] ?? 'All years' }}@if($filters['semester'] ?? null), {{ $filters['semester'] }}@endif</p>
                            @else
                            <p class="text-xs font-data text-gray-500 mt-1">Data as of {{ now()->format('M j, Y') }}</p>
                            @endif
                        </div>
                        <a href="{{ route($routes['reports'], array_filter($filters ?? [])) }}" class="text-sm font-semibold bg-[#1E40AF] hover:bg-[#1D3A8A] text-white no-underline font-data rounded-lg px-4 py-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1E40AF] focus-visible:ring-offset-2">Reports</a>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="bg-white rounded-xl border border-gray-200 shadow-2xl overflow-hidden flex flex-col min-h-[220px] transition-shadow duration-200 hover:shadow-2xl print:shadow-none">
                            <div class="px-4 py-3 border-b border-gray-100 bg-[#1E40AF]/5 shrink-0">
                                <h3 class="font-heading text-sm font-bold text-gray-800 leading-tight">Approved / Pending / Rejected</h3>
                                <p class="text-xs text-gray-500 font-data mt-0.5">Application status</p>
                            </div>
                            <div class="p-4 flex-1 flex flex-col min-h-0 flex items-center justify-center">
                                <div class="h-36 relative w-full min-h-[180px] flex items-center justify-center" data-chart-loaded="false">
                                    <div class="chart-skeleton absolute inset-0 rounded-md" aria-hidden="true"></div>
                                    <canvas id="staffApprovedDonutChart" class="chart-canvas relative z-10 w-full h-full"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-200 shadow-2xl overflow-hidden flex flex-col min-h-[220px] transition-shadow duration-200 hover:shadow-2xl print:shadow-none">
                            <div class="px-4 py-3 border-b border-gray-100 bg-[#1E40AF]/5 shrink-0">
                                <h3 class="font-heading text-sm font-bold text-gray-800 leading-tight">Student type</h3>
                                <p class="text-xs text-gray-500 font-data mt-0.5">Freshmen, Returnee, Transferee</p>
                            </div>
                            <div class="p-4 flex-1 flex flex-col min-h-0 flex items-center justify-center">
                                <div class="h-36 relative w-full min-h-[180px] flex items-center justify-center" data-chart-loaded="false">
                                    <div class="chart-skeleton absolute inset-0 rounded-md" aria-hidden="true"></div>
                                    <canvas id="staffStudentTypePieChart" class="chart-canvas relative z-10 w-full h-full"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-200 shadow-2xl overflow-hidden flex flex-col min-h-[220px] transition-shadow duration-200 hover:shadow-2xl print:shadow-none">
                            <div class="px-4 py-3 border-b border-gray-100 bg-[#1E40AF]/5 shrink-0">
                                <h3 class="font-heading text-sm font-bold text-gray-800 leading-tight">Gender</h3>
                                <p class="text-xs text-gray-500 font-data mt-0.5">Enrollees by gender</p>
                            </div>
                            <div class="p-4 flex-1 flex flex-col min-h-0 flex items-center justify-center">
                                <div class="h-36 relative w-full min-h-[180px] flex items-center justify-center" data-chart-loaded="false">
                                    <div class="chart-skeleton absolute inset-0 rounded-md" aria-hidden="true"></div>
                                    <canvas id="staffGenderPieChart" class="chart-canvas relative z-10 w-full h-full"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-200 shadow-2xl overflow-hidden flex flex-col min-h-[220px] transition-shadow duration-200 hover:shadow-2xl print:shadow-none">
                            <div class="px-4 py-3 border-b border-gray-100 bg-[#1E40AF]/5 shrink-0">
                                <h3 class="font-heading text-sm font-bold text-gray-800 leading-tight">Financial</h3>
                                <p class="text-xs text-gray-500 font-data mt-0.5">Income classification</p>
                            </div>
                            <div class="p-4 flex-1 flex flex-col min-h-0 flex items-center justify-center">
                                <div class="h-36 relative w-full min-h-[180px] flex items-center justify-center" data-chart-loaded="false">
                                    <div class="chart-skeleton absolute inset-0 rounded-md" aria-hidden="true"></div>
                                    <canvas id="staffFinancialPieChart" class="chart-canvas relative z-10 w-full h-full"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-200 shadow-2xl overflow-hidden flex flex-col min-h-[220px] transition-shadow duration-200 hover:shadow-2xl print:shadow-none">
                            <div class="px-4 py-3 border-b border-gray-100 bg-[#1E40AF]/5 shrink-0">
                                <h3 class="font-heading text-sm font-bold text-gray-800 leading-tight">Block assignments</h3>
                                <p class="text-xs text-gray-500 font-data mt-0.5">Students by block (hover for details)</p>
                            </div>
                            <div class="p-4 flex-1 flex flex-col min-h-0 flex items-center justify-center">
                                <div class="h-36 relative w-full min-h-[180px] flex items-center justify-center" data-chart-loaded="false">
                                    <div class="chart-skeleton absolute inset-0 rounded-md" aria-hidden="true"></div>
                                    <canvas id="staffBlockPieChart" class="chart-canvas relative z-10 w-full h-full"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-200 shadow-2xl overflow-hidden flex flex-col min-h-[220px] transition-shadow duration-200 hover:shadow-2xl print:shadow-none">
                            <div class="px-4 py-3 border-b border-gray-100 bg-[#1E40AF]/5 shrink-0">
                                <h3 class="font-heading text-sm font-bold text-gray-800 leading-tight">Document Request Status</h3>
                                <p class="text-xs text-gray-500 font-data mt-0.5">Pending vs Released</p>
                            </div>
                            <div class="p-4 flex-1 flex flex-col min-h-0 flex items-center justify-center">
                                <div class="h-36 relative w-full min-h-[180px] flex items-center justify-center" data-chart-loaded="false">
                                    <div class="chart-skeleton absolute inset-0 rounded-md" aria-hidden="true"></div>
                                    <canvas id="staffDocumentRequestChart" class="chart-canvas relative z-10 w-full h-full"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @elseif($sidebar === 'unifast-sidebar')
            {{-- UniFAST Management Cockpit: same hero design as System Reporting page --}}
            <div class="w-full flex flex-col gap-6">
                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-0">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">UniFAST Management</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Scholarship and billing oversight.</p>
                            @if(($filters['academic_year'] ?? null) || ($filters['semester'] ?? null))
                            <p class="text-white/80 text-xs sm:text-sm mt-2 font-data">Viewing data for {{ ($filters['academic_year'] ?? 'All years') }}@if($filters['semester'] ?? null), {{ $filters['semester'] }}@endif</p>
                            @else
                            <p class="text-white/70 text-xs mt-1 font-data">Data as of {{ now()->format('M j, Y') }}</p>
                            @endif
                        </div>
                        <a href="{{ route('unifast.assessments') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-base font-bold text-[#1E40AF] bg-white hover:bg-gray-100 no-underline transition-colors shrink-0 font-heading focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#1E40AF]">Assessment Monitoring</a>
                    </div>
                </section>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
                    <a href="{{ route('unifast.assessments') }}" class="bg-white shadow-2xl rounded-xl border border-gray-200 p-5 transition-all duration-300 hover:shadow-xl hover:border-[#1E40AF]/30 no-underline block focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1E40AF] focus-visible:ring-offset-2 rounded-xl">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-heading text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Total Scholars</p>
                                <p class="font-data text-2xl font-bold text-[#1E40AF]">{{ number_format($totalEnrollees ?? 0) }}</p>
                            </div>
                            <svg class="w-10 h-10 shrink-0 text-[#1E40AF]/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <p class="font-data text-xs text-gray-500 mt-2">View in Assessment Monitoring</p>
                    </a>
                    <a href="{{ route('unifast.assessments', ['status' => 'pending']) }}" class="bg-white shadow-2xl rounded-xl border border-gray-200 p-5 transition-all duration-300 hover:shadow-xl hover:border-[#1E40AF]/30 no-underline block focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1E40AF] focus-visible:ring-offset-2 rounded-xl">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-heading text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Pending Billing</p>
                                <p class="font-data text-2xl font-bold text-[#1E40AF]">{{ number_format($pendingCount ?? 0) }}</p>
                            </div>
                            <svg class="w-10 h-10 shrink-0 text-[#1E40AF]/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <p class="font-data text-xs text-gray-500 mt-2">View pending assessments</p>
                    </a>
                    <a href="{{ route('unifast.reports') }}" class="bg-white shadow-2xl rounded-xl border border-gray-200 p-5 transition-all duration-300 hover:shadow-xl hover:border-[#1E40AF]/30 no-underline block focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1E40AF] focus-visible:ring-offset-2 rounded-xl">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-heading text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Total Disbursement</p>
                                <p class="font-data text-2xl font-bold text-[#1E40AF]">{{ number_format($totalDisbursement ?? 0) }}</p>
                            </div>
                            <svg class="w-10 h-10 shrink-0 text-[#1E40AF]/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <p class="font-data text-xs text-gray-500 mt-2">See Reports</p>
                    </a>
                    <a href="{{ route('unifast.assessments', ['status' => 'approved']) }}" class="bg-white shadow-2xl rounded-xl border border-gray-200 p-5 transition-all duration-300 hover:shadow-xl hover:border-[#1E40AF]/30 no-underline block focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1E40AF] focus-visible:ring-offset-2 rounded-xl">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-heading text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Verified Assessments</p>
                                <p class="font-data text-2xl font-bold text-[#1E40AF]">{{ number_format($approvedCount ?? 0) }}</p>
                            </div>
                            <svg class="w-10 h-10 shrink-0 text-[#1E40AF]/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <p class="font-data text-xs text-gray-500 mt-2">View verified</p>
                    </a>
                </div>
                {{-- Charts: improved layout, spacing, responsive single column on small screens --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6 xl:gap-8 mt-2">
                    <div class="bg-white shadow-2xl rounded-xl border border-gray-200 border-t-4 border-t-[#1E40AF] overflow-hidden flex flex-col min-h-[280px]">
                        <div class="px-5 py-4 border-b border-gray-100 bg-[#1E40AF]/5 shrink-0">
                            <h3 class="font-heading text-sm font-bold text-gray-800">Application status</h3>
                            <p class="text-xs text-gray-500 font-data mt-0.5">Approved, Pending, Rejected</p>
                        </div>
                        <div class="p-5 flex-1 flex flex-col min-h-0">
                            <div class="h-[220px] min-h-[200px] relative flex-1 w-full" data-chart-loaded="false">
                                <div class="chart-skeleton absolute inset-0 rounded-lg" aria-hidden="true"></div>
                                <canvas id="approvedDonutChart" class="chart-canvas relative z-10 w-full h-full" style="max-height: 220px;"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white shadow-2xl rounded-xl border border-gray-200 border-t-4 border-t-[#1E40AF] overflow-hidden flex flex-col min-h-[280px]">
                        <div class="px-5 py-4 border-b border-gray-100 bg-[#1E40AF]/5 shrink-0">
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <h3 class="font-heading text-sm font-bold text-gray-800">Program enrollment</h3>
                                <a href="{{ route('unifast.reports') }}" class="text-xs font-semibold text-[#1E40AF] hover:text-[#1D3A8A] no-underline whitespace-nowrap focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1E40AF] focus-visible:ring-offset-1 rounded">Reports</a>
                            </div>
                            <p class="text-xs text-gray-500 font-data mt-0.5">By program (hover for details)</p>
                        </div>
                        <div class="p-5 flex-1 flex flex-col min-h-0">
                            <div class="h-[220px] min-h-[200px] relative flex-1 w-full" data-chart-loaded="false">
                                <div class="chart-skeleton absolute inset-0 rounded-lg" aria-hidden="true"></div>
                                <canvas id="programPieChart" class="chart-canvas relative z-10 w-full h-full" style="max-height: 220px;"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white shadow-2xl rounded-xl border border-gray-200 border-t-4 border-t-[#1E40AF] overflow-hidden flex flex-col min-h-[280px]">
                        <div class="px-5 py-4 border-b border-gray-100 bg-[#1E40AF]/5 shrink-0">
                            <h3 class="font-heading text-sm font-bold text-gray-800">By location</h3>
                            <p class="text-xs text-gray-500 font-data mt-0.5">Enrollees by municipality</p>
                        </div>
                        <div class="p-5 flex-1 flex flex-col min-h-0">
                            <div class="h-[220px] min-h-[200px] flex items-center justify-center relative w-full" data-chart-loaded="false">
                                <div class="chart-skeleton absolute inset-0 rounded-lg" aria-hidden="true"></div>
                                <canvas id="locationRadarChart" class="chart-canvas relative z-10 w-full h-full" style="max-height: 220px;"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white shadow-2xl rounded-xl border border-gray-200 border-t-4 border-t-[#1E40AF] overflow-hidden flex flex-col min-h-[280px]">
                        <div class="px-5 py-4 border-b border-gray-100 bg-[#1E40AF]/5 shrink-0">
                            <h3 class="font-heading text-sm font-bold text-gray-800">Gender distribution</h3>
                            <p class="text-xs text-gray-500 font-data mt-0.5">Male, Female, Other</p>
                        </div>
                        <div class="p-5 flex-1 flex flex-col min-h-0">
                            <div class="h-[220px] min-h-[200px] relative flex-1 w-full" data-chart-loaded="false">
                                <div class="chart-skeleton absolute inset-0 rounded-lg" aria-hidden="true"></div>
                                <canvas id="genderPieChart" class="chart-canvas relative z-10 w-full h-full" style="max-height: 220px;"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white shadow-2xl rounded-xl border border-gray-200 border-t-4 border-t-[#1E40AF] overflow-hidden flex flex-col min-h-[280px]">
                        <div class="px-5 py-4 border-b border-gray-100 bg-[#1E40AF]/5 shrink-0">
                            <h3 class="font-heading text-sm font-bold text-gray-800">Student type</h3>
                            <p class="text-xs text-gray-500 font-data mt-0.5">Freshmen, Returnee, Transferee</p>
                        </div>
                        <div class="p-5 flex-1 flex flex-col min-h-0">
                            <div class="h-[220px] min-h-[200px] relative flex-1 w-full" data-chart-loaded="false">
                                <div class="chart-skeleton absolute inset-0 rounded-lg" aria-hidden="true"></div>
                                <canvas id="studentTypePieChart" class="chart-canvas relative z-10 w-full h-full" style="max-height: 220px;"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white shadow-2xl rounded-xl border border-gray-200 border-t-4 border-t-[#1E40AF] overflow-hidden flex flex-col min-h-[280px]">
                        <div class="px-5 py-4 border-b border-gray-100 bg-[#1E40AF]/5 shrink-0">
                            <h3 class="font-heading text-sm font-bold text-gray-800">Financial classification</h3>
                            <p class="text-xs text-gray-500 font-data mt-0.5">Income classification</p>
                        </div>
                        <div class="p-5 flex-1 flex flex-col min-h-0">
                            <div class="h-[220px] min-h-[200px] relative flex-1 w-full" data-chart-loaded="false">
                                <div class="chart-skeleton absolute inset-0 rounded-lg" aria-hidden="true"></div>
                                <canvas id="financialPieChart" class="chart-canvas relative z-10 w-full h-full" style="max-height: 220px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @elseif($sidebar === 'admin-sidebar')
            {{-- Admin: DCOMC Administrator Dashboard — hero, stat tiles, 7-chart grid --}}
            <div id="cockpit-charts-start" aria-hidden="true"></div>
            <div class="space-y-8">
                <section class="hero-gradient rounded-2xl shadow-xl px-6 py-5 sm:px-8 sm:py-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold text-white tracking-tight">DCOMC Administrator Dashboard</h1>
                            <p class="text-white/90 text-sm sm:text-base mt-2 font-data">System-wide enrollment and application data. Displaying: {{ ($filters['academic_year'] ?? null) ? $filters['academic_year'] : 'All years' }}{{ ($filters['semester'] ?? null) ? ' · ' . $filters['semester'] : '' }}. Use the filters above to narrow by academic year and semester.</p>
                        </div>
                        <a href="{{ request()->fullUrl() }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold bg-white text-[#1E40AF] hover:bg-gray-100 no-underline transition-colors shrink-0 font-data" aria-label="Refresh dashboard">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Refresh
                        </a>
                    </div>
                </section>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 sm:gap-6">
                    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 border-t-4 border-t-[#1E40AF] transition-shadow hover:shadow-2xl">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-heading text-xs font-bold text-gray-500 uppercase tracking-wider">Students Population</p>
                                <p class="text-3xl font-bold text-[#1E40AF] mt-2 font-data tabular-nums">{{ number_format($studentsPopulation ?? 0) }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-[#1E40AF]/10 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 border-t-4 border-t-[#1E40AF] transition-shadow hover:shadow-2xl">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-heading text-xs font-bold text-gray-500 uppercase tracking-wider">Applications</p>
                                <p class="text-3xl font-bold text-[#1E40AF] mt-2 font-data tabular-nums">{{ number_format($totalEnrollees ?? 0) }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-[#1E40AF]/10 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 border-t-4 border-t-[#1E40AF] transition-shadow hover:shadow-2xl">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-heading text-xs font-bold text-gray-500 uppercase tracking-wider">Approved</p>
                                <p class="text-3xl font-bold text-[#1E40AF] mt-2 font-data tabular-nums">{{ number_format($approvedCount ?? 0) }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-[#1E40AF]/10 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 border-t-4 border-t-[#1E40AF] transition-shadow hover:shadow-2xl">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-heading text-xs font-bold text-gray-500 uppercase tracking-wider">Pending</p>
                                <p class="text-3xl font-bold text-[#1E40AF] mt-2 font-data tabular-nums">{{ number_format($pendingCount ?? 0) }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-[#1E40AF]/10 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 xl:gap-8">
                    <article class="bg-white rounded-xl shadow-2xl overflow-hidden flex flex-col min-h-[320px] border-t-[10px] border-t-[#1E40AF]">
                        <div class="px-5 py-4 border-b border-gray-100 shrink-0">
                            <h2 class="font-heading text-base font-bold text-gray-800">Enrollment Trends</h2>
                            <p class="text-gray-500 text-xs font-data mt-0.5">Enrollments over time</p>
                        </div>
                        <div class="p-5 flex-1 min-h-[220px] relative" data-chart-loaded="false">
                            <div class="chart-skeleton absolute inset-0 rounded-lg" aria-hidden="true"></div>
                            <canvas id="adminTrendChart" class="chart-canvas relative z-10 w-full h-full"></canvas>
                        </div>
                    </article>
                    <article class="bg-white rounded-xl shadow-2xl overflow-hidden flex flex-col min-h-[320px] border-t-[10px] border-t-[#1E40AF]">
                        <div class="px-5 py-4 border-b border-gray-100 shrink-0">
                            <h2 class="font-heading text-base font-bold text-gray-800">Application Status</h2>
                            <p class="text-gray-500 text-xs font-data mt-0.5">Approved, Pending, Rejected</p>
                        </div>
                        <div class="p-5 flex-1 flex flex-col sm:flex-row items-center justify-center gap-6 min-h-0">
                            <div class="w-36 h-36 relative flex-shrink-0" data-chart-loaded="false">
                                <div class="chart-skeleton absolute inset-0 rounded-full" aria-hidden="true"></div>
                                <canvas id="adminApprovedDonutChart" class="chart-canvas relative z-10 w-full h-full"></canvas>
                            </div>
                            <div class="text-sm font-data min-w-0 space-y-2">
                                <p class="font-heading text-xs font-semibold text-gray-500 uppercase tracking-wide">Summary</p>
                                <p class="text-gray-700">Approved: <span class="font-bold text-[#1E40AF] tabular-nums">{{ $approvedCount ?? 0 }}</span></p>
                                <p class="text-gray-700">Pending: <span class="font-bold text-[#1E40AF] tabular-nums">{{ $pendingCount ?? 0 }}</span></p>
                                <p class="text-gray-700">Rejected: <span class="font-bold text-[#1E40AF] tabular-nums">{{ $rejectedCount ?? 0 }}</span></p>
                            </div>
                        </div>
                    </article>
                    <article class="bg-white rounded-xl shadow-2xl overflow-hidden flex flex-col min-h-[320px] border-t-[10px] border-t-[#1E40AF]">
                        <div class="px-5 py-4 border-b border-gray-100 shrink-0">
                            <h2 class="font-heading text-base font-bold text-gray-800">Program Enrollment</h2>
                            <p class="text-gray-500 text-xs font-data mt-0.5">By program</p>
                        </div>
                        <div class="p-5 flex-1 min-h-[240px] relative" data-chart-loaded="false">
                            <div class="chart-skeleton absolute inset-0 rounded-lg" aria-hidden="true"></div>
                            <canvas id="adminProgramChart" class="chart-canvas relative z-10 w-full h-full"></canvas>
                        </div>
                    </article>
                    <article class="bg-white rounded-xl shadow-2xl overflow-hidden flex flex-col min-h-[320px] border-t-[10px] border-t-[#1E40AF]">
                        <div class="px-5 py-4 border-b border-gray-100 shrink-0">
                            <h2 class="font-heading text-base font-bold text-gray-800">Gender Distribution</h2>
                            <p class="text-gray-500 text-xs font-data mt-0.5">Male, Female</p>
                        </div>
                        <div class="p-5 flex-1 min-h-[240px] relative" data-chart-loaded="false">
                            <div class="chart-skeleton absolute inset-0 rounded-lg" aria-hidden="true"></div>
                            <canvas id="adminGenderPieChart" class="chart-canvas relative z-10 w-full h-full"></canvas>
                        </div>
                    </article>
                    <article class="bg-white rounded-xl shadow-2xl overflow-hidden flex flex-col min-h-[320px] border-t-[10px] border-t-[#1E40AF]">
                        <div class="px-5 py-4 border-b border-gray-100 shrink-0">
                            <h2 class="font-heading text-base font-bold text-gray-800">By Location</h2>
                            <p class="text-gray-500 text-xs font-data mt-0.5">Enrollees by municipality</p>
                        </div>
                        <div class="p-5 flex-1 min-h-[240px] relative flex items-center justify-center" data-chart-loaded="false">
                            <div class="chart-skeleton absolute inset-0 rounded-lg" aria-hidden="true"></div>
                            <canvas id="adminLocationChart" class="chart-canvas relative z-10 w-full h-full"></canvas>
                        </div>
                    </article>
                    <article class="bg-white rounded-xl shadow-2xl overflow-hidden flex flex-col min-h-[320px] border-t-[10px] border-t-[#1E40AF]">
                        <div class="px-5 py-4 border-b border-gray-100 shrink-0">
                            <h2 class="font-heading text-base font-bold text-gray-800">Block & Section</h2>
                            <p class="text-gray-500 text-xs font-data mt-0.5">Hover for counts and percentage</p>
                        </div>
                        <div class="p-5 flex-1 min-h-[240px] relative flex items-center justify-center" data-chart-loaded="false">
                            <div class="chart-skeleton absolute inset-0 rounded-lg" aria-hidden="true"></div>
                            <canvas id="adminBlockPieChart" class="chart-canvas relative z-10 w-full h-full"></canvas>
                        </div>
                    </article>
                </div>
            </div>
            @else
            {{-- 1. Most important: Students population, Applications, Approved, Pending, Rejected --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Students population</p>
                    <p class="text-2xl font-bold text-[#0d3b66] mt-1">{{ number_format($studentsPopulation ?? 0) }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Applications</p>
                    <p class="text-2xl font-bold text-[#0d3b66] mt-1">{{ number_format($totalEnrollees ?? 0) }}</p>
                </div>
                <a href="{{ route($routes['student_status'], ['process_status' => 'approved'] + ($filters ?? [])) }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:border-green-400 transition-colors">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Approved</p>
                    <p class="text-2xl font-bold text-green-700 mt-1">{{ number_format($approvedCount ?? 0) }}</p>
                </a>
                <a href="{{ route($routes['student_status'], array_filter($filters ?? [])) }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:border-amber-400 transition-colors">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Pending</p>
                    <p class="text-2xl font-bold text-amber-700 mt-1">{{ number_format($pendingCount ?? 0) }}</p>
                </a>
                <a href="{{ route($routes['student_status'], array_filter($filters ?? [])) }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:border-red-400 transition-colors">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Rejected</p>
                    <p class="text-2xl font-bold text-red-700 mt-1">{{ number_format($rejectedCount ?? 0) }}</p>
                </a>
            </div>

            {{-- 2. Enrollment trends + Application status (donut) --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-2">Enrollment trends</h3>
                    <a href="{{ route($routes['analytics'], array_filter($filters ?? [])) }}" class="text-sm font-semibold text-[#0d3b66] hover:text-[#0a2d4d] no-underline">View Analytics →</a>
                    @if(isset($trendRows) && $trendRows->isNotEmpty())
                        <div class="mt-3 h-14 flex items-end gap-0.5">
                            @php $maxTrend = $trendRows->max('count') ?: 1; @endphp
                            @foreach($trendRows as $t)
                                <div class="flex-1 min-w-0 bg-blue-200 rounded-t hover:bg-blue-300" title="{{ $t->period }}: {{ $t->count }}" style="height: {{ max(6, 100 * $t->count / $maxTrend) }}%"></div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5" id="cockpit-charts-start">
                    <h3 class="text-sm font-semibold text-gray-800 mb-2">Application status</h3>
                    <div class="flex items-center gap-3">
                        <div class="w-32 h-32 relative" data-chart-loaded="false">
                            <div class="chart-skeleton absolute inset-0" aria-hidden="true"></div>
                            <canvas id="approvedDonutChart" class="chart-canvas relative z-10 w-full h-full"></canvas>
                        </div>
                        <div class="text-sm">
                            <p>Approved: <span class="font-bold text-[#0d3b66]">{{ $approvedCount ?? 0 }}</span></p>
                            <p>Pending: <span class="font-bold text-[#0d3b66]">{{ $pendingCount ?? 0 }}</span></p>
                            <p>Rejected: <span class="font-bold text-[#0d3b66]">{{ $rejectedCount ?? 0 }}</span></p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. Program, Gender, Location (one row) --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-2">Program enrollment</h3>
                    <div class="h-44 relative" data-chart-loaded="false">
                        <div class="chart-skeleton absolute inset-0" aria-hidden="true"></div>
                        <canvas id="programPieChart" class="chart-canvas relative z-10 w-full h-full"></canvas>
                    </div>
                    <a href="{{ route($routes['reports'], array_filter($filters ?? [])) }}" class="text-xs font-semibold text-[#0d3b66] hover:text-[#0a2d4d] no-underline">Reports →</a>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-2">Gender distribution</h3>
                    <div class="h-44 relative" data-chart-loaded="false">
                        <div class="chart-skeleton absolute inset-0" aria-hidden="true"></div>
                        <canvas id="genderPieChart" class="chart-canvas relative z-10 w-full h-full"></canvas>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-2">By location</h3>
                    <p class="text-xs text-gray-500 mb-1">Enrollees by student address (municipality).</p>
                    <div class="h-44 flex items-center justify-center relative" data-chart-loaded="false">
                        <div class="chart-skeleton absolute inset-0" aria-hidden="true"></div>
                        <canvas id="locationRadarChart" class="chart-canvas relative z-10"></canvas>
                    </div>
                    <a href="{{ route($routes['reports'], array_filter($filters ?? [])) }}" class="text-xs font-semibold text-[#0d3b66] hover:text-[#0a2d4d] no-underline mt-2 inline-block">Reports →</a>
                </div>
            </div>

            {{-- 4. Student type overview (compact) --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">Student type (Freshmen, Returnee, Transferee)</h3>
                <div class="h-40 relative" data-chart-loaded="false">
                    <div class="chart-skeleton absolute inset-0" aria-hidden="true"></div>
                    <canvas id="studentTypePieChart" class="chart-canvas relative z-10 w-full h-full"></canvas>
                </div>
            </div>

            {{-- 5. Block and section assignments (Pie) --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">Block and section assignments</h3>
                <p class="text-xs text-gray-500 mb-2">Students in blocks by program.</p>
                <div class="min-h-[260px] flex items-center justify-center relative" data-chart-loaded="false">
                    <div class="chart-skeleton absolute inset-0" aria-hidden="true"></div>
                    <canvas id="blockPieChart" class="chart-canvas relative z-10"></canvas>
                </div>
                @if(empty($blockRadarLabels))
                    <p class="text-sm text-gray-500 text-center py-4">No block data for selected filters.</p>
                @endif
                <a href="{{ route($routes['reports'], array_filter($filters ?? [])) }}" class="text-xs font-semibold text-[#0d3b66] hover:text-[#0a2d4d] no-underline mt-2 inline-block">Reports →</a>
            </div>

            {{-- 6. Financial + Risk (bottom) --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-2">Financial classification</h3>
                    <div class="h-44 relative" data-chart-loaded="false">
                        <div class="chart-skeleton absolute inset-0" aria-hidden="true"></div>
                        <canvas id="financialPieChart" class="chart-canvas relative z-10 w-full h-full"></canvas>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-2">Risk and success metrics</h3>
                    <div class="min-h-[220px] flex items-center justify-center relative" data-chart-loaded="false">
                        <div class="chart-skeleton absolute inset-0" aria-hidden="true"></div>
                        <canvas id="riskRadarChart" class="chart-canvas relative z-10"></canvas>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </main>
    @if($isRegistrarOrDean)
    </div>
    @endif

    <script>
document.addEventListener('alpine:init', function () {
    Alpine.data('registrarTable', function () {
        return {
            countAll: 0,
            countPending: 0,
            countApproved: 0,
            countDenied: 0,
            rows: [],
            searchQueryRaw: '',
            searchQuery: '',
            searchDebounceTimer: null,
            filterStatus: 'all',
            visibleCount: 0,
            toastMessage: '',
            toastType: 'success',
            submittingId: null,
            init: function () {
                var comp = this;
                var d = comp.$el && comp.$el.dataset ? comp.$el.dataset : {};
                comp.countAll = parseInt(d.countAll || '0', 10);
                comp.countPending = parseInt(d.countPending || '0', 10);
                comp.countApproved = parseInt(d.countApproved || '0', 10);
                comp.countDenied = parseInt(d.countDenied || '0', 10);
                try { comp.rows = JSON.parse(d.rows || '[]'); } catch (e) { comp.rows = []; }
                if (d.toastMessage) {
                    comp.toastMessage = d.toastMessage;
                    comp.toastType = 'success';
                    setTimeout(function () { comp.toastMessage = ''; }, 5000);
                }
                comp.$watch('searchQueryRaw', function (val) {
                    clearTimeout(comp.searchDebounceTimer);
                    comp.searchDebounceTimer = setTimeout(function () { comp.searchQuery = val; }, 200);
                });
                comp.$watch('searchQuery', function () { comp.updateVisibleCount(); });
                comp.$watch('filterStatus', function () { comp.updateVisibleCount(); });
                comp.updateVisibleCount();
            },
            updateVisibleCount: function () {
                var q = (this.searchQuery || '').toLowerCase().trim();
                var status = this.filterStatus;
                this.visibleCount = this.rows.filter(function (r) {
                    var matchFilter = status === 'all' || r.f === status;
                    var matchSearch = !q || (r.s && r.s.indexOf(q) !== -1);
                    return matchFilter && matchSearch;
                }).length;
            },
            matchRow: function (filterBucket, searchText) {
                var q = (this.searchQuery || '').toLowerCase().trim();
                var matchFilter = this.filterStatus === 'all' || filterBucket === this.filterStatus;
                var matchSearch = !q || (searchText && searchText.indexOf(q) !== -1);
                return matchFilter && matchSearch;
            },
            toast: function (message, type) {
                this.toastMessage = message;
                this.toastType = type || 'success';
                var t = this;
                setTimeout(function () { t.toastMessage = ''; }, 5000);
            }
        };
    });
});
    </script>
    <script>window.cockpitSidebar = @json($sidebar ?? '');</script>
    <script>
(function () {
    function markLoaded(canvas) { var p = canvas && canvas.closest && canvas.closest('[data-chart-loaded]'); if (p) p.setAttribute('data-chart-loaded', 'true'); }
    var isUnifastCockpit = (typeof window.cockpitSidebar === 'string' && window.cockpitSidebar === 'unifast-sidebar');
    var isAdminCockpit = (typeof window.cockpitSidebar === 'string' && window.cockpitSidebar === 'admin-sidebar');
    var chartPalette = isUnifastCockpit || isAdminCockpit ? { primary: '#1E40AF', primaryRgba: 'rgba(30, 64, 175, 0.2)', pie: ['#1E40AF', '#3B82F6', '#60A5FA', '#93C5FD', '#64748b', '#94a3b8'], donut: ['#1E40AF', '#eab308', '#dc2626'], gender: ['#1E40AF', '#60A5FA', '#93C5FD'] } : { primary: '#0d3b66', primaryRgba: 'rgba(13,59,102,0.2)', pie: ['#0d3b66', '#1e5f8a', '#2d7ab8', '#3b82f6', '#64748b', '#94a3b8'], donut: ['#0d3b66', '#eab308', '#dc2626'], gender: ['#0d3b66', '#94a3b8', '#cbd5e1'] };
    var trendRows = @json($trendRows ?? []);
    function runCharts() {
    const approved = {{ (int) ($approvedCount ?? 0) }};
    const pending = {{ (int) ($pendingCount ?? 0) }};
    const rejected = {{ (int) ($rejectedCount ?? 0) }};
    // Admin dashboard: 7 charts — unified DCOMC styling (Figtree titles, Roboto data, padding, rounded bars)
    var chartFonts = { fontFamily: "'Roboto', sans-serif", titleFont: "'Figtree', sans-serif" };
    var adminChartLayout = { padding: { top: 12, right: 16, bottom: 12, left: 16 } };
    if (isAdminCockpit) {
        var adminTrendEl = document.getElementById('adminTrendChart');
        if (adminTrendEl && typeof Chart !== 'undefined') {
            var trendLabels = (trendRows && trendRows.length) ? trendRows.map(function (r) { return (r.period || 'N/A').toString().substring(0, 10); }) : ['No data'];
            var trendData = (trendRows && trendRows.length) ? trendRows.map(function (r) { return r.count || 0; }) : [0];
            if (Chart.getChart(adminTrendEl)) Chart.getChart(adminTrendEl).destroy();
            new Chart(adminTrendEl, {
                type: 'bar',
                data: {
                    labels: trendLabels,
                    datasets: [{ label: 'Enrollments', data: trendData, backgroundColor: 'rgba(30, 64, 175, 0.85)', borderColor: '#1E40AF', borderWidth: 1, borderRadius: 6 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    layout: adminChartLayout,
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: true, bodyFont: { family: chartFonts.fontFamily, size: 13 }, padding: 10 }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1, font: { family: chartFonts.fontFamily, size: 11 } }, grid: { color: 'rgba(0,0,0,0.06)' } },
                        x: { ticks: { maxRotation: 45, minRotation: 0, font: { family: chartFonts.fontFamily, size: 11 } }, grid: { display: false } }
                    }
                }
            });
            markLoaded(adminTrendEl);
        }
        var adminDonutEl = document.getElementById('adminApprovedDonutChart');
        if (adminDonutEl && typeof Chart !== 'undefined') {
            if (Chart.getChart(adminDonutEl)) Chart.getChart(adminDonutEl).destroy();
            new Chart(adminDonutEl, {
                type: 'doughnut',
                data: {
                    labels: ['Approved', 'Pending', 'Rejected'],
                    datasets: [{ data: [approved, pending, rejected], backgroundColor: ['#1E40AF', '#eab308', '#dc2626'], borderWidth: 2, borderColor: '#fff', hoverOffset: 8 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '58%',
                    layout: adminChartLayout,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14, font: { family: chartFonts.fontFamily, size: 12 } } },
                        tooltip: { bodyFont: { family: chartFonts.fontFamily }, padding: 10 }
                    }
                }
            });
            markLoaded(adminDonutEl);
        }
    }
    const ctx = document.getElementById('approvedDonutChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Approved', 'Pending', 'Rejected'],
                datasets: [{ data: [approved, pending, rejected], backgroundColor: chartPalette.donut, borderWidth: 0 }]
            },
            options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' } } }
        });
        markLoaded(ctx);
    }

    const locationCounts = @json($locationCounts ?? ['Daraga' => 0, 'Legazpi' => 0, 'Guinobatan' => 0]);
    const locationLabels = Object.keys(locationCounts);
    const locationValues = Object.values(locationCounts);
    const locationRadarCtx = document.getElementById('locationRadarChart');
    if (locationRadarCtx && locationLabels.length >= 2) {
        const locMax = Math.max(...locationValues, 1);
        const locStep = locMax <= 10 ? 2 : (locMax <= 50 ? 10 : Math.ceil(locMax / 5 / 10) * 10);
        new Chart(locationRadarCtx, {
            type: 'radar',
            data: {
                labels: locationLabels,
                datasets: [{
                    label: 'Enrollees',
                    data: locationValues,
                    backgroundColor: chartPalette.primaryRgba,
                    borderColor: chartPalette.primary,
                    borderWidth: 2,
                    pointBackgroundColor: chartPalette.primary,
                    pointRadius: 4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    r: {
                        min: 0,
                        max: Math.ceil(locMax * 1.15 / locStep) * locStep || 10,
                        ticks: { stepSize: locStep },
                        pointLabels: { font: { size: 11 } }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            afterLabel: function (ctx) {
                                return ctx.raw + ' enrollees (student address: ' + locationLabels[ctx.dataIndex] + ')';
                            }
                        }
                    }
                }
            }
        });
        markLoaded(locationRadarCtx);
    }

    const studentTypes = @json($studentTypeBreakdown ?? []);
    var studentTypeEl = document.getElementById('studentTypePieChart');
    if (studentTypes.length && studentTypeEl) {
        new Chart(studentTypeEl, {
            type: 'pie',
            data: {
                labels: studentTypes.map(function (o) { return o.label; }),
                datasets: [{ data: studentTypes.map(function (o) { return o.count; }), backgroundColor: chartPalette.pie, borderWidth: 0 }]
            },
            options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
        });
        markLoaded(studentTypeEl);
    }

    const genders = @json($genderBreakdown ?? []);
    var genderEl = document.getElementById('genderPieChart');
    if (genders.length && genderEl) {
        new Chart(genderEl, {
            type: 'pie',
            data: {
                labels: genders.map(function (o) { return o.label; }),
                datasets: [{ data: genders.map(function (o) { return o.count; }), backgroundColor: chartPalette.gender, borderWidth: 0 }]
            },
            options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' } } }
        });
        markLoaded(genderEl);
    }

    const programs = @json($programBreakdown ?? []);
    var programEl = document.getElementById('programPieChart');
    if (programs.length && programEl) {
        new Chart(programEl, {
            type: 'pie',
            data: {
                labels: programs.map(function (o) { return (o.label || 'N/A').substring(0, 20); }),
                datasets: [{ data: programs.map(function (o) { return o.count; }), backgroundColor: chartPalette.pie, borderWidth: 0 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: !isUnifastCockpit, position: 'bottom', labels: { boxWidth: 10 } },
                    tooltip: { enabled: true }
                }
            }
        });
        markLoaded(programEl);
    }

    const financials = @json($financialBreakdown ?? []);
    var financialEl = document.getElementById('financialPieChart');
    if (financialEl && financials.length) {
        new Chart(financialEl, {
            type: 'pie',
            data: {
                labels: financials.map(function (o) { return (o.label || 'N/A').substring(0, 18); }),
                datasets: [{ data: financials.map(function (o) { return o.count; }), backgroundColor: chartPalette.pie, borderWidth: 0 }]
            },
            options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10 } } } }
        });
        markLoaded(financialEl);
    }

    const blockPieLabels = @json($blockRadarLabels ?? []);
    const blockPieValues = @json($blockRadarValues ?? []);
    var blockPieCtx = document.getElementById('blockPieChart');
    if (blockPieCtx && blockPieLabels.length) {
        new Chart(blockPieCtx, {
            type: 'pie',
            data: {
                labels: blockPieLabels.map(function (l) { return (l || 'N/A').substring(0, 22); }),
                datasets: [{
                    data: blockPieValues,
                    backgroundColor: ['#0d3b66', '#1e5f8a', '#2d7ab8', '#3b82f6', '#64748b', '#94a3b8', '#cbd5e1', '#e2e8f0', '#0f766e', '#0369a1', '#7c3aed', '#be185d'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: !isUnifastCockpit, position: 'bottom', labels: { boxWidth: 10 } },
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            afterLabel: function (ctx) {
                                const total = blockPieValues.reduce(function (a, b) { return a + b; }, 0);
                                const pct = total ? ((ctx.raw / total) * 100).toFixed(1) : 0;
                                return ctx.raw + ' students (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
        markLoaded(blockPieCtx);
    }
    // Admin: Program, Gender, Student Type, By Location, Block (destroy before create; unified layout & fonts)
    if (isAdminCockpit) {
        var adminPieDefaults = { borderWidth: 2, borderColor: '#fff', hoverOffset: 6 };
        var adminProgramEl = document.getElementById('adminProgramChart');
        if (adminProgramEl && typeof Chart !== 'undefined') {
            if (Chart.getChart(adminProgramEl)) Chart.getChart(adminProgramEl).destroy();
            var apLabels = programs.length ? programs.map(function (o) { return (o.label || 'N/A').substring(0, 20); }) : ['No data'];
            var apData = programs.length ? programs.map(function (o) { return o.count; }) : [0];
            if (apData.length === 0 || apData.every(function (v) { return v === 0; })) { apLabels = ['No data']; apData = [0]; }
            new Chart(adminProgramEl, {
                type: 'pie',
                data: {
                    labels: apLabels,
                    datasets: [{ data: apData, backgroundColor: ['#1E40AF', '#3B82F6', '#60A5FA', '#93C5FD', '#64748b', '#94a3b8'], borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    layout: adminChartLayout,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12, font: { family: chartFonts.fontFamily, size: 12 } } },
                        tooltip: { bodyFont: { family: chartFonts.fontFamily }, padding: 10 }
                    }
                }
            });
            markLoaded(adminProgramEl);
        }
        var adminGenderEl = document.getElementById('adminGenderPieChart');
        if (adminGenderEl && typeof Chart !== 'undefined') {
            if (Chart.getChart(adminGenderEl)) Chart.getChart(adminGenderEl).destroy();
            var agLabels = genders.length ? genders.map(function (o) { return o.label; }) : ['No data'];
            var agData = genders.length ? genders.map(function (o) { return o.count; }) : [0];
            if (agData.length === 0 || agData.every(function (v) { return v === 0; })) { agLabels = ['No data']; agData = [0]; }
            new Chart(adminGenderEl, {
                type: 'pie',
                data: {
                    labels: agLabels,
                    datasets: [{ data: agData, backgroundColor: ['#1E40AF', '#60A5FA', '#93C5FD', '#64748b', '#94a3b8'], borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    layout: adminChartLayout,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12, font: { family: chartFonts.fontFamily, size: 12 } } },
                        tooltip: { bodyFont: { family: chartFonts.fontFamily }, padding: 10 }
                    }
                }
            });
            markLoaded(adminGenderEl);
        }
        var adminStudentTypeEl = document.getElementById('adminStudentTypePieChart');
        if (adminStudentTypeEl && typeof Chart !== 'undefined') {
            if (Chart.getChart(adminStudentTypeEl)) Chart.getChart(adminStudentTypeEl).destroy();
            var astLabels = studentTypes.length ? studentTypes.map(function (o) { return o.label; }) : ['No data'];
            var astData = studentTypes.length ? studentTypes.map(function (o) { return o.count; }) : [0];
            if (astData.length === 0 || astData.every(function (v) { return v === 0; })) { astLabels = ['No data']; astData = [0]; }
            new Chart(adminStudentTypeEl, {
                type: 'pie',
                data: {
                    labels: astLabels,
                    datasets: [{ data: astData, backgroundColor: ['#1E40AF', '#3B82F6', '#60A5FA', '#93C5FD', '#64748b', '#94a3b8'], borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    layout: adminChartLayout,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12, font: { family: chartFonts.fontFamily, size: 12 } } },
                        tooltip: { bodyFont: { family: chartFonts.fontFamily }, padding: 10 }
                    }
                }
            });
            markLoaded(adminStudentTypeEl);
        }
        var adminLocationEl = document.getElementById('adminLocationChart');
        if (adminLocationEl && typeof Chart !== 'undefined') {
            if (Chart.getChart(adminLocationEl)) Chart.getChart(adminLocationEl).destroy();
            var locLabels = Object.keys(locationCounts);
            var locValues = Object.values(locationCounts);
            if (locLabels.length >= 1) {
                var locMax = Math.max.apply(null, locValues.concat([1]));
                var locStep = locMax <= 10 ? 2 : (locMax <= 50 ? 10 : Math.ceil(locMax / 5 / 10) * 10);
                new Chart(adminLocationEl, {
                    type: 'radar',
                    data: {
                        labels: locLabels,
                        datasets: [{
                            label: 'Enrollees',
                            data: locValues,
                            backgroundColor: 'rgba(30, 64, 175, 0.18)',
                            borderColor: '#1E40AF',
                            borderWidth: 2,
                            pointBackgroundColor: '#1E40AF',
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        layout: adminChartLayout,
                        scales: {
                            r: {
                                min: 0,
                                max: Math.ceil(locMax * 1.15 / locStep) * locStep || 10,
                                ticks: {
                                    stepSize: locStep,
                                    font: { family: chartFonts.fontFamily, size: 10 },
                                    callback: function (val) { if (val === 2) return ''; return val; }
                                },
                                pointLabels: { font: { size: 11, family: chartFonts.fontFamily }, padding: 6 }
                            }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                enabled: true,
                                bodyFont: { family: chartFonts.fontFamily },
                                padding: 10,
                                callbacks: {
                                    afterLabel: function (ctx) {
                                        return ctx.raw + ' enrollees (' + (locLabels[ctx.dataIndex] || '') + ')';
                                    }
                                }
                            }
                        }
                    }
                });
                markLoaded(adminLocationEl);
            } else {
                markLoaded(adminLocationEl);
            }
        }
        var adminBlockCtx = document.getElementById('adminBlockPieChart');
        if (adminBlockCtx && typeof Chart !== 'undefined') {
            if (Chart.getChart(adminBlockCtx)) Chart.getChart(adminBlockCtx).destroy();
            var ablLabels = blockPieLabels.length ? blockPieLabels.map(function (l) { return (l || 'N/A').substring(0, 22); }) : ['No data'];
            var ablData = blockPieLabels.length ? blockPieValues : [0];
            var ablColors = ['#1E40AF', '#2563eb', '#3B82F6', '#60A5FA', '#93C5FD', '#bfdbfe', '#64748b', '#94a3b8', '#cbd5e1', '#e2e8f0'];
            var ablColorArr = ablLabels.length ? ablLabels.map(function (_, i) { return ablColors[i % ablColors.length]; }) : ['#94a3b8'];
            new Chart(adminBlockCtx, {
                type: 'pie',
                data: {
                    labels: ablLabels,
                    datasets: [{ data: ablData, backgroundColor: ablColorArr, borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    layout: adminChartLayout,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            enabled: true,
                            bodyFont: { family: chartFonts.fontFamily },
                            padding: 10,
                            callbacks: {
                                afterLabel: function (ctx) {
                                    if (ablData.length === 1 && ablData[0] === 0 && ablLabels[0] === 'No data') return '';
                                    var total = ablData.reduce(function (a, b) { return a + b; }, 0);
                                    var pct = total ? ((ctx.raw / total) * 100).toFixed(1) : 0;
                                    return ctx.raw + ' students (' + pct + '%)';
                                }
                            }
                        }
                    }
                }
            });
            markLoaded(adminBlockCtx);
        }
    }

    const radarMetrics = @json($radarMetrics ?? []);
    const radarTooltips = @json($radarTooltips ?? []);
    var radarCtx = document.getElementById('riskRadarChart');
    if (radarCtx && Object.keys(radarMetrics).length) {
        const labels = Object.keys(radarMetrics);
        const values = labels.map(function (k) { return radarMetrics[k]; });
        const n = labels.length;
        new Chart(radarCtx, {
            type: 'radar',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Zone 100%', data: Array(n).fill(100), backgroundColor: 'rgba(239,68,68,0.12)', borderColor: 'rgba(239,68,68,0.4)', borderWidth: 1, pointRadius: 0, fill: true },
                    { label: 'Zone 66%',  data: Array(n).fill(66),  backgroundColor: 'rgba(234,179,8,0.18)',  borderColor: 'rgba(234,179,8,0.45)',  borderWidth: 1, pointRadius: 0, fill: true },
                    { label: 'Zone 33%',  data: Array(n).fill(33),  backgroundColor: 'rgba(34,197,94,0.18)',  borderColor: 'rgba(34,197,94,0.45)',  borderWidth: 1, pointRadius: 0, fill: true },
                    { label: 'Current',   data: values,            backgroundColor: 'rgba(13,59,102,0.25)', borderColor: '#dc2626', borderWidth: 2, pointBackgroundColor: '#eab308', pointRadius: 4, fill: true }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: { r: { min: 0, max: 100, ticks: { stepSize: 25 }, pointLabels: { font: { size: 10 } } } },
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { afterLabel: function (ctx) { return radarTooltips[ctx.label] || ''; } } }
                }
            }
        });
        markLoaded(radarCtx);
    }

    // Dean dashboard charts (same data, DCOMC Blue styling). Always create when canvas exists, use placeholder if no data.
    var deanProgramEl = document.getElementById('deanProgramPieChart');
    if (deanProgramEl) {
        var deanProgramLabels = programs.length ? programs.map(function (o) { return (o.label || 'N/A').substring(0, 20); }) : ['No data'];
        var deanProgramData = programs.length ? programs.map(function (o) { return o.count; }) : [1];
        new Chart(deanProgramEl, {
            type: 'pie',
            data: {
                labels: deanProgramLabels,
                datasets: [{ data: deanProgramData, backgroundColor: ['#1E40AF', '#3B82F6', '#60A5FA', '#93C5FD', '#64748b', '#94a3b8'], borderWidth: 0 }]
            },
            options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10 } } } }
        });
        markLoaded(deanProgramEl);
    }
    var deanLocCtx = document.getElementById('deanLocationRadarChart');
    if (deanLocCtx) {
        var deanLocLabels = locationLabels.length >= 2 ? locationLabels : ['Daraga', 'Legazpi', 'Guinobatan'];
        var deanLocValues = locationValues.length >= 2 ? locationValues : [0, 0, 0];
        var deanLocMax = Math.max.apply(null, deanLocValues);
        var deanLocScaleMax = 10;
        var deanLocStep = 1;
        var deanLocAllZero = deanLocMax === 0;
        if (deanLocAllZero) {
            deanLocScaleMax = 1;
            deanLocStep = 1;
        } else if (deanLocMax <= 10) {
            deanLocStep = 2;
            deanLocScaleMax = Math.ceil(deanLocMax * 1.15 / 2) * 2 || 10;
        } else if (deanLocMax <= 50) {
            deanLocStep = 10;
            deanLocScaleMax = Math.ceil(deanLocMax * 1.15 / 10) * 10;
        } else {
            deanLocStep = Math.ceil(deanLocMax / 5 / 10) * 10;
            deanLocScaleMax = Math.ceil(deanLocMax * 1.15 / deanLocStep) * deanLocStep;
        }
        new Chart(deanLocCtx, {
            type: 'radar',
            data: {
                labels: deanLocLabels,
                datasets: [{ label: 'Enrollees', data: deanLocValues, backgroundColor: 'rgba(30, 64, 175, 0.2)', borderColor: '#1E40AF', borderWidth: 2, pointBackgroundColor: '#1E40AF', pointRadius: 4, fill: true }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    r: {
                        min: 0,
                        max: deanLocScaleMax,
                        ticks: {
                            stepSize: deanLocStep,
                            callback: deanLocAllZero ? function(value) { return value === 0 ? '0' : ''; } : undefined
                        },
                        pointLabels: { font: { size: 11 } }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: function() { return []; },
                            label: function(ctx) { return ctx.raw + ' enrollees'; },
                            afterLabel: function(ctx) { return ctx.raw === 0 ? 'No enrollees in this municipality yet.' : ''; }
                        }
                    }
                }
            }
        });
        markLoaded(deanLocCtx);
    }
    var deanRadarCtx = document.getElementById('deanRiskRadarChart');
    if (deanRadarCtx) {
        var deanRadarLabels = Object.keys(radarMetrics).length ? Object.keys(radarMetrics) : ['Enrollment Completion', 'Test Plan Coverage', 'Consistency Check Impact', 'Overload/Underload OK'];
        var deanRadarValues = Object.keys(radarMetrics).length ? Object.keys(radarMetrics).map(function (k) { return radarMetrics[k]; }) : [0, 0, 100, 100];
        var n = deanRadarLabels.length;
        new Chart(deanRadarCtx, {
            type: 'radar',
            data: {
                labels: deanRadarLabels,
                datasets: [
                    { label: 'Zone 100%', data: Array(n).fill(100), backgroundColor: 'rgba(239,68,68,0.12)', borderColor: 'rgba(239,68,68,0.4)', borderWidth: 1, pointRadius: 0, fill: true },
                    { label: 'Zone 66%',  data: Array(n).fill(66),  backgroundColor: 'rgba(234,179,8,0.18)',  borderColor: 'rgba(234,179,8,0.45)',  borderWidth: 1, pointRadius: 0, fill: true },
                    { label: 'Zone 33%',  data: Array(n).fill(33),  backgroundColor: 'rgba(34,197,94,0.18)',  borderColor: 'rgba(34,197,94,0.45)',  borderWidth: 1, pointRadius: 0, fill: true },
                    { label: 'Current',   data: deanRadarValues,   backgroundColor: 'rgba(30,64,175,0.25)', borderColor: '#1E40AF', borderWidth: 2, pointBackgroundColor: '#1E40AF', pointRadius: 4, fill: true }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: { r: { min: 0, max: 100, ticks: { stepSize: 25 }, pointLabels: { font: { size: 10 } } } },
                plugins: { legend: { display: false }, tooltip: { callbacks: { afterLabel: function (ctx) { return (radarTooltips && radarTooltips[ctx.label]) || ''; } } } }
            }
        });
        markLoaded(deanRadarCtx);
    }

    // Staff dashboard charts (same data as Dean, DCOMC Blue styling). Create when canvas exists.
    var staffProgramEl = document.getElementById('staffProgramPieChart');
    if (staffProgramEl) {
        var staffProgramLabels = programs.length ? programs.map(function (o) { return (o.label || 'N/A').substring(0, 20); }) : ['No data'];
        var staffProgramData = programs.length ? programs.map(function (o) { return o.count; }) : [1];
        new Chart(staffProgramEl, {
            type: 'pie',
            data: {
                labels: staffProgramLabels,
                datasets: [{ data: staffProgramData, backgroundColor: ['#1E40AF', '#3B82F6', '#60A5FA', '#93C5FD', '#64748b', '#94a3b8'], borderWidth: 0 }]
            },
            options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10 } } } }
        });
        markLoaded(staffProgramEl);
    }
    var staffLocCtx = document.getElementById('staffLocationRadarChart');
    if (staffLocCtx) {
        var staffLocLabels = locationLabels.length >= 2 ? locationLabels : ['Daraga', 'Legazpi', 'Guinobatan'];
        var staffLocValues = locationValues.length >= 2 ? locationValues : [0, 0, 0];
        var staffLocMax = Math.max.apply(null, staffLocValues);
        var staffLocScaleMax = 10;
        var staffLocStep = 1;
        var staffLocAllZero = staffLocMax === 0;
        if (staffLocAllZero) {
            staffLocScaleMax = 1;
            staffLocStep = 1;
        } else if (staffLocMax <= 10) {
            staffLocStep = 2;
            staffLocScaleMax = Math.ceil(staffLocMax * 1.15 / 2) * 2 || 10;
        } else if (staffLocMax <= 50) {
            staffLocStep = 10;
            staffLocScaleMax = Math.ceil(staffLocMax * 1.15 / 10) * 10;
        } else {
            staffLocStep = Math.ceil(staffLocMax / 5 / 10) * 10;
            staffLocScaleMax = Math.ceil(staffLocMax * 1.15 / staffLocStep) * staffLocStep;
        }
        new Chart(staffLocCtx, {
            type: 'radar',
            data: {
                labels: staffLocLabels,
                datasets: [{ label: 'Enrollees', data: staffLocValues, backgroundColor: 'rgba(30, 64, 175, 0.2)', borderColor: '#1E40AF', borderWidth: 2, pointBackgroundColor: '#1E40AF', pointRadius: 4, fill: true }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    r: {
                        min: 0,
                        max: staffLocScaleMax,
                        ticks: {
                            stepSize: staffLocStep,
                            callback: staffLocAllZero ? function(value) { return value === 0 ? '0' : ''; } : undefined
                        },
                        pointLabels: { font: { size: 11 } }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: function() { return []; },
                            label: function(ctx) { return ctx.raw + ' enrollees'; },
                            afterLabel: function(ctx) { return ctx.raw === 0 ? 'No enrollees in this municipality yet.' : ''; }
                        }
                    }
                }
            }
        });
        markLoaded(staffLocCtx);
    }
    var staffRadarCtx = document.getElementById('staffRiskRadarChart');
    if (staffRadarCtx) {
        var staffRadarLabels = Object.keys(radarMetrics).length ? Object.keys(radarMetrics) : ['Enrollment Completion', 'Test Plan Coverage', 'Consistency Check Impact', 'Overload/Underload OK'];
        var staffRadarValues = Object.keys(radarMetrics).length ? Object.keys(radarMetrics).map(function (k) { return radarMetrics[k]; }) : [0, 0, 100, 100];
        var staffN = staffRadarLabels.length;
        new Chart(staffRadarCtx, {
            type: 'radar',
            data: {
                labels: staffRadarLabels,
                datasets: [
                    { label: 'Zone 100%', data: Array(staffN).fill(100), backgroundColor: 'rgba(239,68,68,0.12)', borderColor: 'rgba(239,68,68,0.4)', borderWidth: 1, pointRadius: 0, fill: true },
                    { label: 'Zone 66%',  data: Array(staffN).fill(66),  backgroundColor: 'rgba(234,179,8,0.18)',  borderColor: 'rgba(234,179,8,0.45)',  borderWidth: 1, pointRadius: 0, fill: true },
                    { label: 'Zone 33%',  data: Array(staffN).fill(33),  backgroundColor: 'rgba(34,197,94,0.18)',  borderColor: 'rgba(34,197,94,0.45)',  borderWidth: 1, pointRadius: 0, fill: true },
                    { label: 'Current',   data: staffRadarValues,   backgroundColor: 'rgba(30,64,175,0.25)', borderColor: '#1E40AF', borderWidth: 2, pointBackgroundColor: '#1E40AF', pointRadius: 4, fill: true }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: { r: { min: 0, max: 100, ticks: { stepSize: 25 }, pointLabels: { font: { size: 10 } } } },
                plugins: { legend: { display: false }, tooltip: { callbacks: { afterLabel: function (ctx) { return (radarTooltips && radarTooltips[ctx.label]) || ''; } } } }
            }
        });
        markLoaded(staffRadarCtx);
    }

    // Shared options for staff Enrollment & classification charts (Roboto 12px, count+% tooltips, segment borders)
    var staffChartFont = { family: "'Roboto', sans-serif", size: 12 };
    var staffTooltipCountPct = function(ctx) {
        var arr = ctx.dataset.data;
        var total = arr.reduce(function(a, b) { return a + b; }, 0);
        var pct = total ? ((ctx.raw / total) * 100).toFixed(1) : 0;
        return (typeof ctx.raw === 'number' ? ctx.raw.toLocaleString() : ctx.raw) + ' (' + pct + '%)';
    };
    var staffDonutCenterTotalPlugin = {
        id: 'staffDonutCenterTotal',
        afterDraw: function(chart) {
            if (chart.config.type !== 'doughnut' || !chart.chartArea) return;
            if (chart.options.plugins && chart.options.plugins.staffDonutCenterTotal === false) return;
            var ds = chart.config.data.datasets[0];
            var total = (ds.data || []).reduce(function(a, b) { return a + b; }, 0);
            var ctx = chart.ctx;
            var centerX = (chart.chartArea.left + chart.chartArea.right) / 2;
            var centerY = (chart.chartArea.top + chart.chartArea.bottom) / 2;
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.font = 'bold 14px ' + staffChartFont.family;
            ctx.fillStyle = '#1f2937';
            ctx.fillText(total.toLocaleString(), centerX, centerY - 6);
            ctx.font = '12px ' + staffChartFont.family;
            ctx.fillStyle = '#6b7280';
            ctx.fillText('Total', centerX, centerY + 10);
            ctx.restore();
        }
    };

    // Staff: Approved/Pending/Rejected donut (center total, borders, tooltip, empty state)
    var staffDonutCtx = document.getElementById('staffApprovedDonutChart');
    if (staffDonutCtx) {
        var donutSum = approved + pending + rejected;
        var donutLabels = donutSum ? ['Approved', 'Pending', 'Rejected'] : ['No data'];
        var donutData = donutSum ? [approved, pending, rejected] : [1];
        var donutColors = donutSum ? ['#1E40AF', '#F59E0B', '#dc2626'] : ['#94a3b8'];
        new Chart(staffDonutCtx, {
            type: 'doughnut',
            data: {
                labels: donutLabels,
                datasets: [{
                    data: donutData,
                    backgroundColor: donutColors,
                    borderWidth: 1,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                animation: { duration: 200 },
                plugins: {
                    legend: { position: 'bottom', labels: { font: staffChartFont, boxWidth: 10, padding: 8 } },
                    tooltip: {
                        bodyFont: staffChartFont,
                        callbacks: { afterLabel: staffTooltipCountPct }
                    },
                    staffDonutCenterTotal: donutSum > 0
                }
            },
            plugins: [staffDonutCenterTotalPlugin]
        });
        markLoaded(staffDonutCtx);
    }
    // Staff: Student type pie (tooltip, borders, empty state)
    var staffStudentTypeEl = document.getElementById('staffStudentTypePieChart');
    if (staffStudentTypeEl) {
        var stLabels = studentTypes.length ? studentTypes.map(function (o) { return o.label; }) : ['No data'];
        var stData = studentTypes.length ? studentTypes.map(function (o) { return o.count; }) : [1];
        var stColors = studentTypes.length ? ['#1E40AF', '#3B82F6', '#60A5FA', '#64748b', '#94a3b8'] : ['#94a3b8'];
        new Chart(staffStudentTypeEl, {
            type: 'pie',
            data: {
                labels: stLabels,
                datasets: [{ data: stData, backgroundColor: stColors, borderWidth: 1, borderColor: '#fff' }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                animation: { duration: 200 },
                plugins: {
                    legend: { position: 'bottom', labels: { font: staffChartFont, boxWidth: 10, padding: 8 } },
                    tooltip: { bodyFont: staffChartFont, callbacks: { afterLabel: staffTooltipCountPct } }
                }
            }
        });
        markLoaded(staffStudentTypeEl);
    }
    // Staff: Gender pie
    var staffGenderEl = document.getElementById('staffGenderPieChart');
    if (staffGenderEl) {
        var gLabels = genders.length ? genders.map(function (o) { return o.label; }) : ['No data'];
        var gData = genders.length ? genders.map(function (o) { return o.count; }) : [1];
        var gColors = genders.length ? ['#1E40AF', '#94a3b8', '#cbd5e1'] : ['#94a3b8'];
        new Chart(staffGenderEl, {
            type: 'pie',
            data: {
                labels: gLabels,
                datasets: [{ data: gData, backgroundColor: gColors, borderWidth: 1, borderColor: '#fff' }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                animation: { duration: 200 },
                plugins: {
                    legend: { position: 'bottom', labels: { font: staffChartFont, boxWidth: 10, padding: 8 } },
                    tooltip: { bodyFont: staffChartFont, callbacks: { afterLabel: staffTooltipCountPct } }
                }
            }
        });
        markLoaded(staffGenderEl);
    }
    // Staff: Financial pie
    var staffFinancialEl = document.getElementById('staffFinancialPieChart');
    if (staffFinancialEl) {
        var finLabels = financials.length ? financials.map(function (o) { return (o.label || 'N/A').substring(0, 18); }) : ['No data'];
        var finData = financials.length ? financials.map(function (o) { return o.count; }) : [1];
        var finColors = financials.length ? ['#1E40AF', '#3B82F6', '#60A5FA', '#64748b', '#94a3b8'] : ['#94a3b8'];
        new Chart(staffFinancialEl, {
            type: 'pie',
            data: {
                labels: finLabels,
                datasets: [{ data: finData, backgroundColor: finColors, borderWidth: 1, borderColor: '#fff' }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                animation: { duration: 200 },
                plugins: {
                    legend: { position: 'bottom', labels: { font: staffChartFont, boxWidth: 10, padding: 8 } },
                    tooltip: { bodyFont: staffChartFont, callbacks: { afterLabel: staffTooltipCountPct } }
                }
            }
        });
        markLoaded(staffFinancialEl);
    }
    // Staff: Block pie (no legend — data only on hover via tooltips; blue scale + gray)
    var staffBlockPieCtx = document.getElementById('staffBlockPieChart');
    if (staffBlockPieCtx) {
        var blockBlueGray = ['#1E40AF', '#2563eb', '#3B82F6', '#60A5FA', '#93C5FD', '#bfdbfe', '#64748b', '#94a3b8', '#cbd5e1', '#e2e8f0'];
        var blLabels = blockPieLabels.length ? blockPieLabels.map(function (l) { return (l || 'N/A').substring(0, 22); }) : ['No data'];
        var blData = blockPieLabels.length ? blockPieValues : [1];
        var blColors = blockPieLabels.length ? blLabels.map(function (_, i) { return blockBlueGray[i % blockBlueGray.length]; }) : ['#94a3b8'];
        new Chart(staffBlockPieCtx, {
            type: 'pie',
            data: {
                labels: blLabels,
                datasets: [{ data: blData, backgroundColor: blColors, borderWidth: 1, borderColor: '#fff' }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                animation: { duration: 200 },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        bodyFont: staffChartFont,
                        callbacks: {
                            title: function (items) { return items.length && items[0].label ? items[0].label : ''; },
                            afterLabel: function (ctx) {
                                if (blData.length === 1 && blData[0] === 1 && blLabels[0] === 'No data') return '';
                                var total = blData.reduce(function (a, b) { return a + b; }, 0);
                                var pct = total ? ((ctx.raw / total) * 100).toFixed(1) : 0;
                                return ctx.raw.toLocaleString() + ' students (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
        markLoaded(staffBlockPieCtx);
    }
    // Staff: Document Request Status (Pending vs Released — from existing approval counts)
    var staffDocReqCtx = document.getElementById('staffDocumentRequestChart');
    if (staffDocReqCtx) {
        var docPending = pending;
        var docReleased = approved;
        var docSum = docPending + docReleased;
        var docLabels = docSum ? ['Pending', 'Released'] : ['No data'];
        var docData = docSum ? [docPending, docReleased] : [1];
        var docColors = docSum ? ['#F59E0B', '#1E40AF'] : ['#94a3b8'];
        new Chart(staffDocReqCtx, {
            type: 'doughnut',
            data: {
                labels: docLabels,
                datasets: [{ data: docData, backgroundColor: docColors, borderWidth: 1, borderColor: '#fff' }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                animation: { duration: 200 },
                plugins: {
                    legend: { position: 'bottom', labels: { font: staffChartFont, boxWidth: 10, padding: 8 } },
                    tooltip: { bodyFont: staffChartFont, callbacks: { afterLabel: staffTooltipCountPct } }
                }
            }
        });
        markLoaded(staffDocReqCtx);
    }
    }
    var chartsEl = document.getElementById('cockpit-charts-start');
    var chartsRan = false;
    function runOnce() { if (!chartsRan) { chartsRan = true; runCharts(); } }
    if (chartsEl && typeof IntersectionObserver !== 'undefined' && !isAdminCockpit) {
        var obs = new IntersectionObserver(function (entries) {
            for (var i = 0; i < entries.length; i++) {
                if (entries[i].isIntersecting) { runOnce(); obs.disconnect(); return; }
            }
        }, { rootMargin: '100px', threshold: 0 });
        obs.observe(chartsEl);
    }
    if (isAdminCockpit) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () { setTimeout(runOnce, 0); });
        } else {
            setTimeout(runOnce, 0);
        }
    } else if (!chartsEl || typeof IntersectionObserver === 'undefined') {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', runOnce);
        } else {
            runOnce();
        }
    }
})();
    </script>
</body>
</html>
