<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit Profile - DCOMC</title>
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
        header a { text-decoration: none !important; }
        header a:hover { text-decoration: none !important; }
        [x-cloak] { display: none !important; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #1E40AF; box-shadow: 0 0 0 2px #1E40AF; }
        .skip-link { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
        .skip-link:focus { position: fixed; left: 0.5rem; top: 0.5rem; z-index: 100; width: auto; height: auto; padding: 0.5rem 0.75rem; margin: 0; overflow: visible; clip: auto; white-space: normal; background: #1E40AF; color: #fff; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; text-decoration: none; box-shadow: 0 0 0 2px #1E40AF; }
        @media print {
            header, .no-print { display: none !important; }
            body { background: #fff; }
            .rounded-xl.overflow-hidden { box-shadow: none; border: 1px solid #e5e7eb; }
        }
    </style>
</head>
<body class="min-h-screen font-data antialiased" style="background-color: #F8FAFC;" x-data="{ navOpen: false }">

    {{-- Navbar: same as Student Dashboard --}}
    <header class="sticky top-0 z-50 bg-white shadow-sm border-b border-gray-200" style="border-bottom-width: 1px; border-bottom-color: #e5e7eb;">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between min-h-[4.5rem] py-3">
                <a href="{{ route('student.dashboard') }}" class="flex items-center gap-4 shrink-0 no-underline hover:no-underline pl-1">
                    <img src="{{ asset('images/logo.png') }}" alt="DCOMC" class="h-10 w-auto object-contain flex-shrink-0 pl-4" onerror="this.style.display='none'">
                    <span class="font-heading font-semibold text-[#1E40AF] no-underline">DCOMC Student Portal</span>
                </a>
                <nav class="hidden md:flex items-center gap-3 flex-wrap justify-end">
                    <a href="{{ route('student.cor') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg border-2 border-[#1E40AF] text-[#1E40AF] bg-transparent hover:bg-[#1E40AF]/15 no-underline transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/50 focus:ring-offset-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        View COR
                    </a>
                    <a href="{{ route('student.profile.edit') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg border-2 border-[#1E40AF] text-[#1E40AF] bg-transparent hover:bg-[#1E40AF]/15 no-underline transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/50 focus:ring-offset-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Edit Profile
                    </a>
                    <a href="{{ route('student.feedback') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium rounded-lg border-2 border-[#1E40AF] text-[#1E40AF] bg-transparent hover:bg-[#1E40AF]/15 no-underline transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/50 focus:ring-offset-2">Feedback</a>
                    <a href="{{ route('student.dashboard') }}#account-security" class="inline-flex items-center px-4 py-2.5 text-sm font-medium rounded-lg border-2 border-[#1E40AF] text-[#1E40AF] bg-transparent hover:bg-[#1E40AF]/15 no-underline transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/50 focus:ring-offset-2">Account security</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2.5 text-sm font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 transition-all duration-300 ease-in-out shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500/50 focus:ring-offset-2">Log Out</button>
                    </form>
                </nav>
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
                <a href="{{ route('student.cor') }}" class="flex items-center gap-2 w-full text-left px-4 py-3 text-sm font-medium rounded-xl text-[#1E40AF] bg-white border-2 border-[#1E40AF]/40 hover:bg-[#1E40AF]/10 no-underline hover:no-underline transition-all duration-200" @click="navOpen = false">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    View COR
                </a>
                <a href="{{ route('student.profile.edit') }}" class="flex items-center gap-2 w-full text-left px-4 py-3 text-sm font-medium rounded-xl text-[#1E40AF] bg-white border-2 border-[#1E40AF]/40 hover:bg-[#1E40AF]/10 no-underline hover:no-underline transition-all duration-200" @click="navOpen = false">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Edit Profile
                </a>
                <a href="{{ route('student.feedback') }}" class="block w-full text-left px-3 py-2.5 text-sm font-medium rounded-lg text-[#1E40AF] bg-white border border-[#1E40AF]/30 hover:bg-[#1E40AF]/10 no-underline hover:no-underline transition-colors" @click="navOpen = false">Feedback</a>
                <a href="{{ route('student.dashboard') }}#account-security" class="block w-full text-left px-3 py-2.5 text-sm font-medium rounded-lg text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 transition-colors" @click="navOpen = false">Account security</a>
                <form method="POST" action="{{ route('logout') }}" class="pt-1">
                    @csrf
                    <button type="submit" class="block w-full text-left px-3 py-2.5 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors">Log Out</button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-6 relative" x-data="{ formDirty: false, submitting: false, showSuccess: true }" x-init="window.addEventListener('beforeunload', function(e) { if (formDirty) { e.preventDefault(); e.returnValue = ''; } })">
        <a href="#submit-btn" class="skip-link font-data">Skip to Save</a>
        <div class="mb-6 no-print">
            <a href="{{ route('student.dashboard') }}"
               class="font-data inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg border-2 border-[#1E40AF] text-[#1E40AF] bg-transparent hover:bg-[#1E40AF]/15 no-underline transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/50 focus:ring-offset-2"
               @click.prevent="if(formDirty && !confirm('You have unsaved changes. Leave anyway?')) return; window.location = '{{ route('student.dashboard') }}'">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Dashboard
            </a>
        </div>
        <form method="POST"
              action="{{ route('student.profile.edit.update') }}"
              class="space-y-6"
              @submit="submitting = true; formDirty = false"
              @input="formDirty = true"
              @change="formDirty = true">
            @csrf

            @if(session('success'))
                <div x-show="showSuccess"
                     x-cloak
                     role="alert"
                     class="rounded-xl border border-green-200 bg-green-50 p-4 pr-12 text-green-800 shadow-sm relative">
                    {{ session('success') }}
                    <button type="button"
                            @click="showSuccess = false"
                            class="absolute top-3 right-3 text-green-600 hover:text-green-800 focus:outline-none focus:ring-2 focus:ring-green-500/50 rounded p-0.5 min-w-[1.5rem] min-h-[1.5rem] flex items-center justify-center"
                            aria-label="Dismiss">×</button>
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-xl border-2 border-[#F97316] bg-[#F97316]/5 p-4 shadow-sm" role="alert">
                    <ul class="list-disc list-inside font-data text-sm text-[#F97316] font-medium space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Personal Information --}}
            <div class="bg-white rounded-xl overflow-hidden shadow-sm border border-gray-200 transition-all duration-300">
                <div class="text-white py-3 px-5 flex items-center gap-2" style="background-color: #1E40AF;">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <h2 class="font-heading text-lg font-bold mb-0">Personal Information</h2>
                </div>
                <div class="p-5 sm:p-6">
                    <div id="contact-details-alert" class="font-data rounded-lg border-l-4 border-[#F97316] bg-[#F97316]/10 px-4 py-3 mb-5 text-sm text-gray-800 flex items-start gap-3">
                        <svg class="w-5 h-5 shrink-0 text-[#F97316] mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="mb-0">Please ensure your contact details are up to date for official college correspondence.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                Last Name *
                            </label>
                            <input type="text" name="last_name" value="{{ Auth::user()->last_name }}" required class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                First Name *
                            </label>
                            <input type="text" name="first_name" value="{{ Auth::user()->first_name }}" required class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                Middle Name
                            </label>
                            <input type="text" name="middle_name" value="{{ Auth::user()->middle_name }}" class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Gender *
                            </label>
                            <select name="gender" required class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                                <option value="Male" {{ Auth::user()->gender == 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ Auth::user()->gender == 'Female' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Date of Birth *
                            </label>
                            <input type="date" name="date_of_birth" value="{{ Auth::user()->date_of_birth }}" required class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                Civil Status *
                            </label>
                            <select name="civil_status" required class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                                <option value="Single" {{ Auth::user()->civil_status == 'Single' ? 'selected' : '' }}>Single</option>
                                <option value="Married" {{ Auth::user()->civil_status == 'Married' ? 'selected' : '' }}>Married</option>
                                <option value="Separated" {{ Auth::user()->civil_status == 'Separated' ? 'selected' : '' }}>Separated</option>
                                <option value="Widowed" {{ Auth::user()->civil_status == 'Widowed' ? 'selected' : '' }}>Widowed</option>
                            </select>
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                Cellular Phone Number *
                            </label>
                            <input type="text" name="phone" value="{{ Auth::user()->phone }}" required class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors" aria-describedby="contact-details-alert">
                            <p class="font-data text-xs text-gray-500 mt-1">e.g. 09XX XXX XXXX. Include country code if outside the Philippines.</p>
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                Email Address
                            </label>
                            <input type="email" value="{{ Auth::user()->email }}" disabled class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-gray-100 text-gray-600">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Academic Information (Locked) --}}
            <div class="bg-white rounded-xl overflow-hidden shadow-sm border border-gray-200 transition-all duration-300">
                <div class="text-white py-3 px-5 flex items-center gap-2" style="background-color: #1E40AF;">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <h2 class="font-heading text-lg font-bold mb-0">Academic Information (Locked)</h2>
                </div>
                <div class="p-5 sm:p-6">
                    <div class="rounded-lg border border-[#F97316]/30 bg-[#F97316]/5 p-4 mb-4">
                        <p class="font-data text-sm text-[#F97316] font-medium inline-flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            These fields are managed by the registrar and cannot be edited by students.
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                                School ID
                            </label>
                            <input type="text" value="{{ Auth::user()->school_id }}" disabled class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-gray-100 text-gray-600">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                College/Campus
                            </label>
                            <input type="text" value="{{ Auth::user()->college }}" disabled class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-gray-100 text-gray-600">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                Degree/Course/Major
                            </label>
                            <input type="text" value="{{ Auth::user()->course }}" disabled class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-gray-100 text-gray-600">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Year Level
                            </label>
                            <input type="text" value="{{ Auth::user()->year_level }}" disabled class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-gray-100 text-gray-600">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Semester
                            </label>
                            <input type="text" value="{{ Auth::user()->semester }}" disabled class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-gray-100 text-gray-600">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                School Year
                            </label>
                            <input type="text" value="{{ Auth::user()->school_year ?? Auth::user()->block?->school_year_label ?? '—' }}" disabled class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-gray-100 text-gray-600">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                                No. of Units Enrolled (System Controlled)
                            </label>
                            <input type="number" value="{{ Auth::user()->units_enrolled }}" disabled class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-gray-100 text-gray-600">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Student Type
                            </label>
                            <input type="text" value="{{ Auth::user()->student_type }}" disabled class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-gray-100 text-gray-600">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                Block / Section
                            </label>
                            <input type="text" value="{{ Auth::user()->block ? (Auth::user()->block->code ?? Auth::user()->block->name ?? 'N/A') : 'Not assigned' }}" disabled class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-gray-100 text-gray-600">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Shift
                            </label>
                            <input type="text" value="{{ Auth::user()->shift ? ucfirst(Auth::user()->shift) : 'N/A' }}" disabled class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-gray-100 text-gray-600">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Family Background --}}
            <div class="bg-white rounded-xl overflow-hidden shadow-sm border border-gray-200 transition-all duration-300">
                <div class="text-white py-3 px-5 flex items-center gap-2" style="background-color: #1E40AF;">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <h2 class="font-heading text-lg font-bold mb-0">Family Background</h2>
                </div>
                <div class="p-5 sm:p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Father's Name/Spouse *
                            </label>
                            <input type="text" name="father_name" value="{{ Auth::user()->father_name }}" required class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                Occupation *
                            </label>
                            <input type="text" name="father_occupation" value="{{ Auth::user()->father_occupation }}" required class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Mother's Name/Spouse *
                            </label>
                            <input type="text" name="mother_name" value="{{ Auth::user()->mother_name }}" required class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                Occupation *
                            </label>
                            <input type="text" name="mother_occupation" value="{{ Auth::user()->mother_occupation }}" required class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Annual Family Income (PHP) *
                            </label>
                            <select name="monthly_income" required class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                                <option value="">Select</option>
                                <option {{ Auth::user()->monthly_income == 'Below 10,000' ? 'selected' : '' }}>Below 10,000</option>
                                <option {{ Auth::user()->monthly_income == '10,000-20,000' ? 'selected' : '' }}>10,000-20,000</option>
                                <option {{ Auth::user()->monthly_income == '30,000-40,000' ? 'selected' : '' }}>30,000-40,000</option>
                                <option {{ Auth::user()->monthly_income == '50,000-100,000' ? 'selected' : '' }}>50,000-100,000</option>
                                <option {{ Auth::user()->monthly_income == 'More than 100,000' ? 'selected' : '' }}>More than 100,000</option>
                            </select>
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                Number of Siblings/Children in the Family *
                            </label>
                            <input type="number" name="num_siblings" value="{{ Auth::user()->num_siblings }}" required class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Home Address --}}
            <div class="bg-white rounded-xl overflow-hidden shadow-sm border border-gray-200 transition-all duration-300">
                <div class="text-white py-3 px-5 flex items-center gap-2" style="background-color: #1E40AF;">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <h2 class="font-heading text-lg font-bold mb-0">Home Address</h2>
                </div>
                <div class="p-5 sm:p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                                House Number *
                            </label>
                            <input type="text" name="house_number" value="{{ Auth::user()->house_number }}" required class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.196-5.196a2 2 0 010-2.828l2.828-2.828a2 2 0 012.828 0L9 14l2 2 4 4 2-2 4.472-4.472a2 2 0 000-2.828l-2.828-2.828a2 2 0 00-2.828 0L9 20z"/></svg>
                                Street *
                            </label>
                            <input type="text" name="street" value="{{ Auth::user()->street }}" required class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Barangay *
                            </label>
                            <input type="text" name="barangay" value="{{ Auth::user()->barangay }}" required class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                Municipality *
                            </label>
                            <input type="text" name="municipality" value="{{ Auth::user()->municipality }}" required class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                Province *
                            </label>
                            <input type="text" name="province" value="{{ Auth::user()->province }}" required class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                Zip Code *
                            </label>
                            <input type="text" name="zip_code" value="{{ Auth::user()->zip_code }}" required class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Boarding Address (Optional) --}}
            <div class="bg-white rounded-xl overflow-hidden shadow-sm border border-gray-200 transition-all duration-300">
                <div class="text-white py-3 px-5 flex items-center gap-2" style="background-color: #1E40AF;">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <h2 class="font-heading text-lg font-bold mb-0">Boarding Address (Optional)</h2>
                </div>
                <div class="p-5 sm:p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                                House Number
                            </label>
                            <input type="text" name="boarding_house_number" value="{{ Auth::user()->boarding_house_number }}" class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.196-5.196a2 2 0 010-2.828l2.828-2.828a2 2 0 012.828 0L9 14l2 2 4 4 2-2 4.472-4.472a2 2 0 000-2.828l-2.828-2.828a2 2 0 00-2.828 0L9 20z"/></svg>
                                Street
                            </label>
                            <input type="text" name="boarding_street" value="{{ Auth::user()->boarding_street }}" class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Barangay
                            </label>
                            <input type="text" name="boarding_barangay" value="{{ Auth::user()->boarding_barangay }}" class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                Municipality
                            </label>
                            <input type="text" name="boarding_municipality" value="{{ Auth::user()->boarding_municipality }}" class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                Province
                            </label>
                            <input type="text" name="boarding_province" value="{{ Auth::user()->boarding_province }}" class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                        </div>
                        <div>
                            <label class="font-data block text-sm font-medium text-gray-700 mb-1 inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                Tel/Cell Phone Number
                            </label>
                            <input type="text" name="boarding_phone" value="{{ Auth::user()->boarding_phone }}" class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex flex-wrap items-center justify-end gap-4 pt-2 pb-4">
                <a href="{{ route('student.dashboard') }}"
                   class="font-data inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 text-sm font-medium rounded-lg text-[#F97316] hover:bg-[#F97316]/10 focus:outline-none focus:ring-2 focus:ring-[#F97316]/50 focus:ring-offset-2 transition-all duration-300 no-underline"
                   @click.prevent="if(formDirty && !confirm('You have unsaved changes. Leave anyway?')) return; window.location = '{{ route('student.dashboard') }}'">Cancel</a>
                <button type="submit"
                        id="submit-btn"
                        class="font-data inline-flex items-center justify-center min-h-[44px] px-6 py-2.5 text-sm font-medium rounded-lg text-white shadow-sm transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/50 focus:ring-offset-2 disabled:opacity-70 disabled:cursor-not-allowed"
                        style="background-color: #1E40AF;"
                        :disabled="submitting"
                        :aria-busy="submitting"
                        x-text="submitting ? 'Saving…' : 'Save Changes'">Save Changes</button>
            </div>
        </form>
    </main>

</body>
</html>
