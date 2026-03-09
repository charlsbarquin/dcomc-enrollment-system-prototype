<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Student Dashboard - DCOMC</title>
    @include('layouts.partials.offline-assets')
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'dcomc-blue': '#1E40AF',
                        'dcomc-orange': '#F97316',
                    },
                    fontFamily: {
                        'figtree': ['Figtree', 'sans-serif'],
                        'roboto': ['Roboto', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        .font-heading { font-family: 'Figtree', sans-serif; }
        .font-data { font-family: 'Roboto', sans-serif; }
        .hero-gradient { background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 50%, #60A5FA 100%); }
        .gf-input { width: 100%; border: none; border-bottom: 1px solid #DADCE0; background-color: transparent; padding: 8px 0; transition: border-bottom 0.2s ease; }
        .gf-input:focus { outline: none; border-bottom: 2px solid #1E40AF; }
        [x-cloak] { display: none !important; }
        header a { text-decoration: none !important; }
        header a:hover { text-decoration: none !important; }
        @media (prefers-reduced-motion: reduce) {
            .transition-all, [class*="transition-"], [class*="hover:-translate"], [class*="hover:scale"] { transition: none !important; }
            .hover\:-translate-y-0\.5:hover, .hover\:-translate-y-1:hover, .hover\:scale-\[1\.02\]:hover, .hover\:scale-\[1\.01\]:hover { transform: none !important; }
            .animate-pulse { animation: none !important; }
        }
        @media print {
            header, .no-print, button[aria-label="Dismiss"] { display: none !important; }
            body { background: #fff; }
            .card, section { break-inside: avoid; box-shadow: none; border: 1px solid #e5e7eb; }
            .hero-gradient { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body class="min-h-screen font-data antialiased"
      style="background-color: #F8FAFC;"
      x-data="{ showAccountSecurity: false, pageLoaded: false, alertSuccess: true, alertError: true }"
      x-init="setTimeout(() => { pageLoaded = true }, 50)"
      @open-account-security.window="showAccountSecurity = true; setTimeout(() => { document.getElementById('account-security')?.scrollIntoView({ behavior: 'smooth', block: 'start' }) }, 150)">

    {{-- Navbar: no underlines; logo larger; buttons keep blue text on hover --}}
    <header class="sticky top-0 z-50 bg-white shadow-sm border-b border-gray-200" style="border-bottom-width: 1px; border-bottom-color: #e5e7eb;" x-data="{ navOpen: false }">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between min-h-[4.5rem] py-3">
                <a href="{{ route('student.dashboard') }}" class="flex items-center gap-4 shrink-0 no-underline hover:no-underline pl-1">
                    <img src="{{ asset('images/logo.png') }}" alt="DCOMC" class="h-10 w-auto object-contain flex-shrink-0 pl-4" onerror="this.style.display='none'">
                    <span class="font-heading font-semibold text-[#1E40AF] no-underline">DCOMC Student Portal</span>
                </a>
                {{-- Unified nav: clean outlined buttons; hover = solid blue + white text (duration-300) --}}
                <nav class="hidden md:flex items-center gap-3 flex-wrap justify-end">
                    @php $canViewCorNav = (\App\Services\AcademicCalendarService::isStudentEnrolledForActiveSy(Auth::user())) || in_array(optional($latestApplication ?? null)->process_status ?? null, ['completed', 'scheduled'], true); @endphp
                    @if($canViewCorNav)
                    <a href="{{ route('student.cor') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg border-2 border-[#1E40AF] text-[#1E40AF] bg-transparent hover:bg-[#1E40AF]/15 no-underline transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/50 focus:ring-offset-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        View COR
                    </a>
                    @else
                    <span class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg border-2 border-gray-300 text-gray-400 bg-gray-50 cursor-not-allowed" title="Available once enrollment is finalized">View COR</span>
                    @endif
                    <a href="{{ route('student.profile.edit') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg border-2 border-[#1E40AF] text-[#1E40AF] bg-transparent hover:bg-[#1E40AF]/15 no-underline transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/50 focus:ring-offset-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Edit Profile
                    </a>
                    <a href="{{ route('student.feedback') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium rounded-lg border-2 border-[#1E40AF] text-[#1E40AF] bg-transparent hover:bg-[#1E40AF]/15 no-underline transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/50 focus:ring-offset-2">Feedback</a>
                    <button type="button"
                            class="inline-flex items-center px-4 py-2.5 text-sm font-medium rounded-lg border-2 border-[#1E40AF] text-[#1E40AF] bg-transparent hover:bg-[#1E40AF]/15 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/50 focus:ring-offset-2"
                            @click="showAccountSecurity = !showAccountSecurity; setTimeout(function(){ var el = document.getElementById('account-security'); if(el){ el.scrollIntoView({behavior:'smooth', block:'start'}); } }, 50);">
                        Account security
                    </button>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2.5 text-sm font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 transition-all duration-300 ease-in-out shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500/50 focus:ring-offset-2">Log Out</button>
                    </form>
                </nav>
                {{-- Mobile: hamburger --}}
                <button type="button"
                        class="md:hidden flex items-center justify-center w-10 h-10 rounded-lg text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/50 focus:ring-offset-2"
                        @click="navOpen = !navOpen"
                        :aria-expanded="navOpen"
                        aria-label="Toggle menu">
                    <svg x-show="!navOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="navOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L6 6M18 6v12M18 6L6 18"/></svg>
                </button>
            </div>
        </div>
        {{-- Mobile: slide-down menu --}}
        <div x-show="navOpen"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden border-t border-gray-200 bg-gray-50">
            <div class="max-w-6xl mx-auto px-4 py-3 space-y-1">
                @php $canViewCorMobile = (\App\Services\AcademicCalendarService::isStudentEnrolledForActiveSy(Auth::user())) || in_array(optional($latestApplication ?? null)->process_status ?? null, ['completed', 'scheduled'], true); @endphp
                @if($canViewCorMobile)
                <a href="{{ route('student.cor') }}" class="flex items-center gap-2 w-full text-left px-4 py-3 text-sm font-medium rounded-xl text-[#1E40AF] bg-white border-2 border-[#1E40AF]/40 hover:bg-[#1E40AF]/10 no-underline hover:no-underline transition-all duration-200" @click="navOpen = false">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    View COR
                </a>
                @else
                <span class="flex items-center gap-2 w-full text-left px-4 py-3 text-sm font-medium rounded-xl text-gray-400 bg-gray-100 border-2 border-gray-200 cursor-not-allowed">View COR (when enrolled)</span>
                @endif
                <a href="{{ route('student.profile.edit') }}" class="flex items-center gap-2 w-full text-left px-4 py-3 text-sm font-medium rounded-xl text-[#1E40AF] bg-white border-2 border-[#1E40AF]/40 hover:bg-[#1E40AF]/10 no-underline hover:no-underline transition-all duration-200" @click="navOpen = false">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Edit Profile
                </a>
                <a href="{{ route('student.feedback') }}" class="block w-full text-left px-3 py-2.5 text-sm font-medium rounded-lg text-[#1E40AF] bg-white border border-[#1E40AF]/30 hover:bg-[#1E40AF]/10 no-underline hover:no-underline transition-colors" @click="navOpen = false">Feedback</a>
                <button type="button"
                        class="block w-full text-left px-3 py-2.5 text-sm font-medium rounded-lg text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 transition-colors"
                        @click="navOpen = false; $dispatch('open-account-security')">
                    Account security
                </button>
                <form method="POST" action="{{ route('logout') }}" class="pt-1">
                    @csrf
                    <button type="submit" class="block w-full text-left px-3 py-2.5 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors">Log Out</button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-6 overflow-y-auto">
        <div x-data="{ show: false }"
             x-init="setTimeout(() => show = true, 100)"
             x-show="show"
             x-transition:enter="transition ease-out duration-500"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-cloak>

                @if(session('role_switch.active'))
                    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                        <p class="font-heading font-semibold text-amber-800 mb-1">Admin role switch is active (mirroring STUDENT).</p>
                        <p class="text-sm text-amber-700 mb-3">Logout is disabled until you switch back to admin.</p>
                        <form method="POST" action="{{ route('admin.role-switch.stop') }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-3 py-2 text-sm font-medium text-amber-800 bg-amber-200 rounded-lg hover:bg-amber-300 transition-all duration-300 hover:-translate-y-0.5">Switch Back to Admin</button>
                        </form>
                    </div>
                @endif

                {{-- Hero: Modern gradient banner --}}
                <section class="hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 mb-8 text-white">
                    <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Welcome back, {{ Auth::user()->name }}!</h1>
                    <p class="text-white/80 text-sm mb-3">Your enrollment and schedule at a glance.</p>
                    <p class="text-white/90 text-sm sm:text-base mb-1">Student ID / School ID: <strong>{{ Auth::user()->school_id ?? Auth::user()->email }}</strong></p>
                    @if(Auth::user()->year_level && Auth::user()->semester)
                        <p class="text-sm text-white/80 mb-1">{{ Auth::user()->year_level }} - {{ Auth::user()->semester }}</p>
                    @endif
                    <p class="text-sm text-white/80 mb-0">
                        Block: <strong>{{ Auth::user()->block?->code ?? Auth::user()->block?->name ?? 'Not assigned yet' }}</strong>
                    </p>
                </section>

                @if(session('success'))
                    <div x-show="alertSuccess"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 pr-12 text-green-800 shadow-sm relative">
                        {{ session('success') }}
                        <button type="button" @click="alertSuccess = false" class="absolute top-3 right-3 text-green-600 hover:text-green-800 focus:outline-none focus:ring-2 focus:ring-green-500/50 rounded p-0.5" aria-label="Dismiss">×</button>
                    </div>
                @endif

                @if(session('error'))
                    <div x-show="alertError"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 pr-12 text-red-800 shadow-sm relative">
                        {{ session('error') }}
                        <button type="button" @click="alertError = false" class="absolute top-3 right-3 text-red-600 hover:text-red-800 focus:outline-none focus:ring-2 focus:ring-red-500/50 rounded p-0.5" aria-label="Dismiss">×</button>
                    </div>
                @endif

                @php
                    $workflowStatus = $latestApplication?->process_status ?? null;
                    $isEnrolledForActiveSy = \App\Services\AcademicCalendarService::isStudentEnrolledForActiveSy(Auth::user());
                    $progressStep = 0;
                    if (!$latestApplication) {
                        $progressStep = $isEnrolledForActiveSy ? 4 : 0;
                    } elseif ($workflowStatus === 'completed' || $workflowStatus === 'scheduled') {
                        $progressStep = 4;
                    } elseif ($workflowStatus === 'approved') {
                        $progressStep = 3;
                    } elseif ($workflowStatus === 'needs_correction' || $latestApplication->approval_status === 'rejected') {
                        $progressStep = 2;
                    } else {
                        $progressStep = 2;
                    }
                    if ($latestApplication && $progressStep < 2) {
                        $progressStep = 1;
                    }
                    $canViewCor = ($progressStep >= 4) || $isEnrolledForActiveSy;
                @endphp

                {{-- 3-column Identity cards (white, hover lift) --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                    {{-- Card 1: Enrollment Status — visual hierarchy with left border --}}
                    @php
                        $statusAccent = '#1E40AF';
                        if (!$latestApplication && !$isEnrolledForActiveSy) { $statusAccent = '#F97316'; }
                        elseif ($latestApplication && ($workflowStatus === 'needs_correction' || $latestApplication->approval_status === 'rejected')) { $statusAccent = $workflowStatus === 'needs_correction' ? '#F97316' : '#dc2626'; }
                        elseif ($latestApplication && $workflowStatus !== 'completed' && $workflowStatus !== 'scheduled' && $workflowStatus !== 'approved' && $latestApplication->approval_status !== 'rejected') { $statusAccent = '#F97316'; }
                    @endphp
                    <div class="bg-white rounded-xl border border-gray-200 border-l-4 shadow-sm p-5 transition-all duration-300 ease-in-out hover:shadow-xl hover:-translate-y-1 hover:scale-[1.01]" style="border-left-color: {{ $statusAccent }};">
                        <h3 class="font-heading text-sm font-bold text-gray-600 uppercase tracking-wide mb-3 inline-flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            Enrollment Status
                        </h3>
                        @if(!$latestApplication)
                            @if($isEnrolledForActiveSy)
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-[#1E40AF] text-white">Approved</span>
                                <p class="font-data text-base mt-3 mb-0 leading-relaxed text-gray-700">You are enrolled for the current school year.</p>
                            @else
                                {{-- Empty state: no application yet --}}
                                <div class="flex flex-col">
                                    <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium text-white w-fit bg-[#F97316] animate-pulse">Not Enrolled</span>
                                    <p class="font-heading font-bold text-gray-800 mt-2 mb-1 text-base">No application yet</p>
                                    <p class="text-gray-600 text-sm mb-3 leading-relaxed">Submit an enrollment form when the period is open, or contact the registrar's office.</p>
                                    <a href="#enrollment-access" class="text-sm font-medium text-[#F97316] hover:underline focus:outline-none focus:ring-2 focus:ring-[#F97316]/50 focus:ring-offset-1 rounded">Check enrollment access below →</a>
                                </div>
                            @endif
                        @elseif($workflowStatus === 'completed')
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-[#1E40AF] text-white">Approved</span>
                            <p class="font-data text-base mt-3 mb-0 leading-relaxed text-gray-700">Enrollment approved, scheduled, and cleared.</p>
                        @elseif($workflowStatus === 'scheduled')
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-[#1E40AF] text-white">Approved</span>
                            <p class="font-data text-base mt-3 mb-0 leading-relaxed text-gray-700">Approved; class schedule available in COR.</p>
                        @elseif($workflowStatus === 'approved')
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-green-600 text-white">Approved</span>
                            <p class="font-data text-base mt-3 mb-0 leading-relaxed text-gray-700">Application for <strong>{{ $latestApplication->enrollmentForm?->title ?? 'enrollment' }}</strong> approved.</p>
                        @elseif($workflowStatus === 'needs_correction')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium text-white font-semibold w-fit bg-[#F97316]">Needs Correction</span>
                            <p class="font-data text-base mt-3 mb-0 leading-relaxed text-gray-700">Please coordinate with the registrar.</p>
                        @elseif($latestApplication->approval_status === 'rejected')
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-red-600 text-white">Rejected</span>
                            <p class="font-data text-base mt-3 mb-0 leading-relaxed text-gray-700">Application was rejected.</p>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium text-white font-semibold w-fit bg-[#F97316] animate-pulse">Pending</span>
                            <p class="font-data text-base mt-3 mb-0 leading-relaxed text-gray-700">Application under review.</p>
                        @endif
                        @if($latestApplication?->process_notes)
                            <p class="text-gray-500 text-xs mt-2 mb-0 leading-relaxed">{{ $latestApplication->process_notes }}</p>
                        @endif
                    </div>

                    {{-- Card 2: Current Year / Block --}}
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 transition-all duration-300 ease-in-out hover:shadow-xl hover:-translate-y-1 hover:scale-[1.01]">
                        <h3 class="font-heading text-sm font-bold text-gray-600 uppercase tracking-wide mb-3 inline-flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Current Year / Block
                        </h3>
                        <p class="font-data text-xl font-bold text-gray-900 mb-1">
                            @if(Auth::user()->year_level && Auth::user()->semester)
                                {{ Auth::user()->year_level }} — {{ Auth::user()->semester }}
                            @else
                                —
                            @endif
                        </p>
                        <p class="font-heading text-sm font-bold text-gray-600 mb-0 leading-relaxed">
                            Block: <span class="font-data text-base font-semibold text-gray-900">{{ Auth::user()->block?->code ?? Auth::user()->block?->name ?? 'Not assigned yet' }}</span>
                        </p>
                    </div>

                    {{-- Card 3: Quick Actions (blue links + icons, hover bg) --}}
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 transition-all duration-300 ease-in-out hover:shadow-xl hover:-translate-y-1 hover:scale-[1.01]">
                        <h3 class="font-heading text-sm font-bold text-gray-600 uppercase tracking-wide mb-3 inline-flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Quick Actions
                        </h3>
                        <ul class="flex flex-col gap-1 list-none pl-0 mb-0">
                            <li>
                                @if($canViewCor)
                                <a href="{{ route('student.cor') }}" class="inline-flex items-center gap-2 text-sm font-medium text-[#1E40AF] no-underline rounded-lg py-2.5 px-3 -mx-3 hover:bg-[#1E40AF]/10 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/50 focus:ring-offset-1 border border-transparent hover:border-[#1E40AF]/20">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    View COR
                                </a>
                                @else
                                <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-400 rounded-lg py-2.5 px-3 -mx-3 cursor-not-allowed" title="Available once enrollment is finalized">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    View COR <span class="text-xs">(available when enrolled)</span>
                                </span>
                                @endif
                            </li>
                            <li>
                                <a href="{{ route('student.profile.edit') }}" class="inline-flex items-center gap-2 text-sm font-medium text-[#1E40AF] no-underline rounded-lg py-2.5 px-3 -mx-3 hover:bg-[#1E40AF]/10 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/50 focus:ring-offset-1 border border-transparent hover:border-[#1E40AF]/20">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    Edit Profile
                                </a>
                            </li>
                            @if($enrollmentOpen && $availableForm && !$existingApplication)
                                <li>
                                    <a href="{{ route('student.enrollment-form') }}" class="inline-flex items-center gap-2 text-sm font-medium text-[#1E40AF] no-underline rounded-lg py-2.5 px-3 -mx-3 hover:bg-[#1E40AF]/10 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/50 focus:ring-offset-1 border border-transparent hover:border-[#1E40AF]/20">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        Enrollment Form
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>

                {{-- Enrollment Progress: dynamic bar (first segment orange when status = Application); increased height; bold labels --}}
                <section class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-8 relative" x-data="{ tooltip: null }">
                    <h2 class="font-heading text-lg font-bold text-gray-900 mb-4">Enrollment Progress</h2>
                    <div class="w-full">
                        <div class="w-full h-5 bg-gray-200 rounded-full overflow-hidden mb-3">
                            <div class="h-full rounded-full transition-all duration-500 ease-out" style="width: {{ $progressStep * 25 }}%; background-color: {{ $progressStep >= 4 ? '#059669' : '#F97316' }};"></div>
                        </div>
                        <div class="relative grid grid-cols-4 gap-2 text-center">
                            <span class="{{ $progressStep >= 1 ? 'text-[#1E40AF] font-bold' : 'text-gray-400 font-bold' }} text-sm font-data cursor-default py-1"
                                  @mouseenter="tooltip = 'Application submitted'"
                                  @mouseleave="tooltip = null">Application</span>
                            <span class="{{ $progressStep >= 2 ? 'text-[#1E40AF] font-bold' : 'text-gray-400 font-bold' }} text-sm font-data cursor-default py-1"
                                  @mouseenter="tooltip = 'Under review'"
                                  @mouseleave="tooltip = null">Review</span>
                            <span class="{{ $progressStep >= 3 ? 'text-[#1E40AF] font-bold' : 'text-gray-400 font-bold' }} text-sm font-data cursor-default py-1"
                                  @mouseenter="tooltip = 'Cleared for enrollment'"
                                  @mouseleave="tooltip = null">Payment/Approval</span>
                            <span class="{{ $progressStep >= 4 ? 'text-[#1E40AF] font-bold' : 'text-gray-400 font-bold' }} text-sm font-data cursor-default py-1"
                                  @mouseenter="tooltip = 'You\'re enrolled'"
                                  @mouseleave="tooltip = null">Enrolled</span>
                            <p x-show="tooltip" x-cloak x-transition
                               class="absolute left-1/2 -translate-x-1/2 top-full mt-1 text-xs text-gray-600 bg-gray-100 border border-gray-200 rounded px-2 py-1 shadow-sm z-10 whitespace-nowrap"
                               x-text="tooltip"></p>
                        </div>
                    </div>
                </section>

                {{-- Section: Actions & requests --}}
                <h2 class="font-heading text-base font-bold text-gray-700 mb-4 mt-2">Actions & requests</h2>

                {{-- Application Status & Block/Shift: header blue = welcome banner gradient start (#1E40AF) for color harmony --}}
                <div class="card shadow-sm mb-4 border-0 rounded-xl overflow-hidden bg-white transition-all duration-300 ease-in-out focus-within:ring-2 focus-within:ring-[#1E40AF]/20">
                    <div class="card-header text-white py-3 px-5" style="background-color: #1E40AF;">
                        <h2 class="font-heading h6 mb-0 fw-bold">Application Status</h2>
                    </div>
                    <div class="card-body p-5">
                        @php
                            $workflowStatus = $latestApplication?->process_status ?? null;
                            $isEnrolledForActiveSy = \App\Services\AcademicCalendarService::isStudentEnrolledForActiveSy(Auth::user());
                        @endphp
                        @if(!$latestApplication)
                            @if($isEnrolledForActiveSy)
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-[#1E40AF] text-white">Enrolled</span>
                                <p class="font-heading font-bold text-sm text-gray-600 mt-2 mb-0 leading-relaxed">Status</p>
                                <p class="font-data text-base text-gray-900 mt-1 mb-0 leading-relaxed">You are enrolled for the current school year. (Enrolled via form or manual registration.)</p>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium text-white font-semibold w-fit bg-[#F97316] animate-pulse">Not Enrolled</span>
                                <p class="font-heading font-bold text-sm text-gray-600 mt-2 mb-0 leading-relaxed">Status</p>
                                <p class="font-data text-base text-gray-900 mt-1 mb-0 leading-relaxed">No enrollment for the current term. Submit the enrollment form or contact the registrar to enroll for the active school year.</p>
                            @endif
                        @elseif($workflowStatus === 'completed')
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-[#1E40AF] text-white">Completed</span>
                            <p class="font-heading font-bold text-sm text-gray-600 mt-2 mb-0 leading-relaxed">Status</p>
                            <p class="font-data text-base text-gray-900 mt-1 mb-0 leading-relaxed">Your enrollment is approved, scheduled, and financially cleared.</p>
                        @elseif($workflowStatus === 'scheduled')
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-[#1E40AF] text-white">Scheduled</span>
                            <p class="font-heading font-bold text-sm text-gray-600 mt-2 mb-0 leading-relaxed">Status</p>
                            <p class="font-data text-base text-gray-900 mt-1 mb-0 leading-relaxed">Your enrollment is approved and your class schedule is now available in your COR.</p>
                        @elseif($workflowStatus === 'approved')
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-green-600 text-white">Approved</span>
                            <p class="font-heading font-bold text-sm text-gray-600 mt-2 mb-0 leading-relaxed">Status</p>
                            <p class="font-data text-base text-gray-900 mt-1 mb-0 leading-relaxed">Your latest application for <strong>{{ $latestApplication->enrollmentForm?->title ?? 'an enrollment form' }}</strong> has been approved.</p>
                        @elseif($workflowStatus === 'needs_correction')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium text-white font-semibold w-fit bg-[#F97316]">Needs Correction</span>
                            <p class="font-heading font-bold text-sm text-gray-600 mt-2 mb-0 leading-relaxed">Status</p>
                            <p class="font-data text-base text-gray-900 mt-1 mb-0 leading-relaxed">Your application needs correction. Please coordinate with the registrar to update your submission.</p>
                        @elseif($latestApplication->approval_status === 'rejected')
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-red-600 text-white">Rejected</span>
                            <p class="font-heading font-bold text-sm text-gray-600 mt-2 mb-0 leading-relaxed">Status</p>
                            <p class="font-data text-base text-gray-900 mt-1 mb-0 leading-relaxed">Your latest application for <strong>{{ $latestApplication->enrollmentForm?->title ?? 'an enrollment form' }}</strong> was rejected.</p>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium text-white font-semibold w-fit bg-[#F97316] animate-pulse">Pending</span>
                            <p class="font-heading font-bold text-sm text-gray-600 mt-2 mb-0 leading-relaxed">Status</p>
                            <p class="font-data text-base text-gray-900 mt-1 mb-0 leading-relaxed">Your latest application for <strong>{{ $latestApplication->enrollmentForm?->title ?? 'an enrollment form' }}</strong> is under review.</p>
                        @endif
                        @if($latestApplication?->process_notes)
                            <p class="small text-muted mt-2 mb-0 leading-relaxed">{{ $latestApplication->process_notes }}</p>
                        @endif
                    </div>
                </div>

                {{-- Account security --}}
                <div id="account-security"
                     x-show="showAccountSecurity"
                     x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-2"
                     class="card shadow-sm mb-4 border-0 rounded-xl overflow-hidden bg-white">
                    <div class="card-header text-white py-3 px-5" style="background-color: #374151;">
                        <h2 class="font-heading h6 mb-0 fw-bold">Account security</h2>
                    </div>
                    <div class="card-body p-5">
                        @if (session('status') === 'password-updated')
                            <div class="alert alert-success">Your password has been updated.</div>
                        @endif
                        <form method="POST" action="{{ route('password.update') }}" class="row g-3" style="max-width: 32rem;">
                            @csrf
                            @method('PUT')
                            <div class="col-12">
                                <label for="current_password" class="form-label">Current password</label>
                                <input id="current_password" name="current_password" type="password" autocomplete="current-password" required class="form-control">
                                @error('current_password', 'updatePassword')
                                    <p class="small text-danger mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">New password</label>
                                <input id="password" name="password" type="password" autocomplete="new-password" required class="form-control">
                                @error('password', 'updatePassword')
                                    <p class="small text-danger mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Confirm new password</label>
                                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="form-control">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="px-4 py-2 text-sm font-medium rounded-lg bg-gray-800 text-white hover:bg-gray-900 transition-all duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-gray-500/50 focus:ring-offset-2">Update password</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Block / Shift Change Request (header blue = #1E40AF to match welcome banner) --}}
                <div class="card shadow-sm mb-4 border-0 rounded-xl overflow-hidden bg-white transition-all duration-300 ease-in-out">
                    <div class="card-header text-white py-3 px-5" style="background-color: #1E40AF;">
                        <h2 class="font-heading h6 mb-0 fw-bold">Block / Shift Change Request</h2>
                    </div>
                    <div class="card-body p-5">
                        @if($pendingBlockChangeRequest)
                            <div class="alert alert-warning mb-0">You already have a pending request submitted on {{ $pendingBlockChangeRequest->created_at?->format('M d, Y h:i A') }}.</div>
                        @else
                            <form method="POST" action="{{ route('student.block-change-request') }}" class="row g-3">
                                @csrf
                                <div class="col-md-6">
                                    <select name="requested_block_id" class="form-select form-select-sm">
                                        <option value="">Request New Block (optional)</option>
                                        @foreach($availableBlocks as $block)
                                            <option value="{{ $block->id }}">{{ $block->code ?? $block->name }} ({{ strtoupper($block->shift ?? 'day') }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <select name="requested_shift" class="form-select form-select-sm">
                                        <option value="">Request Shift Change (optional)</option>
                                        <option value="day">DAY</option>
                                        <option value="night">NIGHT</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <input type="number" name="replacement_student_id" placeholder="Replacement Student User ID (required)" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="reason" placeholder="Valid reason for request (required)" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-12 d-flex justify-content-end mt-2 pt-1">
                                    <button type="submit" class="btn px-5 py-2.5 text-sm font-medium rounded-lg text-white border-0 transition-all duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/50 focus:ring-offset-2 shadow-sm" style="background-color: #1E40AF; min-width: 10rem;">Submit Request</button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Enrollment Access (all branches retained) --}}
                <div id="enrollment-access" class="card shadow-sm border-0 rounded-xl overflow-hidden bg-white transition-all duration-300 ease-in-out">
                    <div class="card-header text-white py-3 px-5" style="background-color: #1E40AF;">
                        <h2 class="font-heading h6 mb-0 fw-bold">Enrollment Access</h2>
                    </div>

                    @if(!$enrollmentOpen)
                        <div class="card-body text-center py-5">
                            <h3 class="font-heading h5 text-danger mb-2">Enrollment Currently Closed</h3>
                            <p class="text-muted mb-0">The enrollment period is not active. Please check back later or contact the registrar's office.</p>
                        </div>
                    @elseif(!Auth::user()->year_level || !Auth::user()->semester)
                        <div class="card-body text-center py-5">
                            <h3 class="font-heading h5 text-warning mb-2">Profile Incomplete</h3>
                            <p class="text-muted mb-0">Your year level and semester are not set. Please contact the registrar's office to update your information.</p>
                        </div>
                    @elseif(!$availableForm)
                        <div class="card-body text-center py-5">
                            <h3 class="font-heading h5 text-secondary mb-2">No Form Available</h3>
                            <p class="text-muted mb-0">There is no enrollment form assigned to your year level and semester at this time.</p>
                        </div>
                    @elseif($existingApplication && $existingApplication->approval_status === 'pending')
                        <div class="card-body text-center py-5">
                            <h3 class="font-heading h5 text-warning mb-2">Application Pending</h3>
                            <p class="text-warning mb-1">You already submitted your enrollment application.</p>
                            <p class="text-muted mb-0">Please wait for registrar/admin review.</p>
                        </div>
                    @elseif($existingApplication && $existingApplication->approval_status === 'rejected')
                        <div class="card-body text-center py-5">
                            <h3 class="font-heading h5 text-danger mb-2">Application Rejected</h3>
                            <p class="text-danger mb-1">Your enrollment application was rejected.</p>
                            <p class="text-muted mb-0">You cannot access this form again unless your previous application is deleted by admin.</p>
                        </div>
                    @elseif($existingApplication && $existingApplication->approval_status === 'approved')
                        <div class="card-body text-center py-5">
                            <h3 class="font-heading h5 text-success mb-2">Application Enrolled</h3>
                            <p class="text-success mb-1">Your enrollment has been approved.</p>
                            <p class="text-muted mb-0">You are now enrolled to the destination year/semester set by the registrar.</p>
                        </div>
                    @else
                        <div class="card-body text-center py-5">
                            <h3 class="font-heading h5 text-success mb-2">Enrollment Form Available</h3>
                            <p class="text-muted mb-4">{{ $availableForm->title }}</p>
                            <a href="{{ route('student.enrollment-form') }}" class="inline-flex items-center px-5 py-2.5 text-base font-medium rounded-lg text-white no-underline transition-all duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/50 focus:ring-offset-2 hover:no-underline" style="background-color: #1E40AF;">Proceed to Enrollment Form</a>
                        </div>
                    @endif
                </div>
        </div>

        {{-- Footer --}}
        <footer class="max-w-6xl mx-auto px-4 sm:px-6 py-6 mt-8 border-t border-gray-200">
            <p class="text-center text-sm text-gray-500 font-data">© {{ date('Y') }} DCOMC Student Portal</p>
        </footer>
    </main>

    {{-- Back to top (visible after scroll) --}}
    <div x-data="{ show: false }"
         x-init="window.addEventListener('scroll', () => { show = window.scrollY > 400 })"
         x-show="show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-cloak
         class="fixed bottom-6 right-6 z-40 no-print">
        <button type="button"
                @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                class="flex items-center justify-center w-10 h-10 rounded-full text-white shadow-lg transition-all duration-300 ease-in-out hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/50 focus:ring-offset-2"
                style="background-color: #1E40AF;"
                aria-label="Back to top">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
        </button>
    </div>

</body>
</html>
