@php
    $isStudent = strtolower($roleLabel ?? '') === 'student';
@endphp
@if($isStudent)
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Student Feedback &amp; Suggestions - DCOMC</title>
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
        input[type="range"] { accent-color: #F97316; }
        .skip-link { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
        .skip-link:focus { position: fixed; left: 0.5rem; top: 0.5rem; z-index: 100; width: auto; height: auto; padding: 0.5rem 0.75rem; margin: 0; overflow: visible; clip: auto; white-space: normal; background: #1E40AF; color: #fff; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; text-decoration: none; box-shadow: 0 0 0 2px #1E40AF; }
        @media print {
            header, .no-print { display: none !important; }
            body { background: #fff; }
            .bg-white.rounded-xl { box-shadow: none; border: 1px solid #e5e7eb; }
        }
    </style>
</head>
<body class="min-h-screen font-data antialiased" style="background-color: #F8FAFC;" x-data="{ navOpen: false }">

    {{-- Navbar: same as Student Dashboard & Edit Profile --}}
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

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-6 relative">
        <a href="#submit-btn" class="skip-link font-data">Skip to Send Feedback</a>
        <div class="mb-6 no-print">
            <a href="{{ route('student.dashboard') }}" class="font-data inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg border-2 border-[#1E40AF] text-[#1E40AF] bg-transparent hover:bg-[#1E40AF]/15 no-underline transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/50 focus:ring-offset-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Dashboard
            </a>
        </div>

        {{-- White card with DCOMC Blue header --}}
        <div class="bg-white rounded-xl overflow-hidden shadow-2xl border border-gray-200 transition-all duration-300">
            <div class="text-white py-3 px-5 flex items-center gap-2" style="background-color: #1E40AF;">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                <h1 class="font-heading text-lg font-bold mb-0">Student Feedback &amp; Suggestions</h1>
            </div>
            <div class="p-5 sm:p-6">
                @include('dashboards.partials.feedback-form')
            </div>
        </div>

        <footer class="max-w-6xl mx-auto px-4 sm:px-6 py-6 mt-8 border-t border-gray-200">
            <p class="text-center text-sm text-gray-500 font-data">© {{ date('Y') }} DCOMC Student Portal</p>
        </footer>
    </main>

</body>
</html>
@else
@php
    $storeRoute = $storeRoute ?? 'admin.feedback.store';
    $priority = old('priority', 3);
    $subjectOld = old('subject', '');
    $messageOld = old('message', '');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>System Feedback &amp; Suggestions - DCOMC</title>
    @include('layouts.partials.offline-assets')
    <style>
        .font-heading { font-family: 'Figtree', sans-serif; }
        .font-data { font-family: 'Roboto', sans-serif; }
        .hero-gradient { background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 50%, #60A5FA 100%); }
        .forms-canvas { background: #f3f4f6; }
        .input-dcomc-focus:focus { outline: none; border-color: #1E40AF; box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.2); }
        .forms-header-card { background: #fff; border-top: 10px solid #1E40AF; border-radius: 0.75rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); padding: 1.5rem 1.75rem; margin-bottom: 1rem; border-left: 4px solid transparent; transition: border-left-color 0.2s ease; }
        .forms-header-card.active { border-left-color: #1E40AF; }
        .forms-section-card { background: #fff; border-radius: 0.75rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); padding: 1.5rem 1.75rem; margin-bottom: 1rem; border-left: 4px solid transparent; transition: border-left-color 0.2s ease; }
        .forms-section-card.active { border-left-color: #1E40AF; }
    </style>
</head>
<body class="bg-gray-100 flex h-screen overflow-x-hidden">
    @include('dashboards.partials.role-sidebar')
    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="max-w-4xl mx-auto">
                {{-- Hero banner --}}
                <section class="hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">System Feedback &amp; Suggestions</h1>
                            <p class="text-white/90 text-sm sm:text-base">Help us improve the DCOMC Portal experience.</p>
                        </div>
                        <a href="{{ route($backRoute) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-white/20 hover:bg-white/30 text-white text-sm font-medium transition-colors no-underline shrink-0 font-data">← Back</a>
                    </div>
                </section>

                @if(session('success'))
                    <div class="mb-4 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800 shadow-sm font-data text-sm">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-4 rounded-xl border-2 border-red-200 bg-red-50/50 p-4 shadow-sm font-data text-sm text-red-800" role="alert">
                        <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif

                <form action="{{ route($storeRoute) }}" method="POST" id="feedback-form" class="space-y-6 font-data"
                      x-data="{ subjectLength: {{ strlen($subjectOld) }}, messageLength: {{ strlen($messageOld) }}, submitting: false }"
                      @submit="submitting = true">
                    @csrf

                    {{-- Card 1: Feedback Category --}}
                    <div class="forms-header-card" @focusin="$el.classList.add('active')" @focusout="$el.classList.remove('active')">
                        <label for="category" class="font-heading block text-sm font-bold text-gray-800 mb-2">Feedback Category</label>
                        <select id="category" name="category" class="font-data w-full border border-gray-300 rounded-lg px-3 py-2.5 bg-white input-dcomc-focus transition-colors">
                            <option value="">Select category (optional)</option>
                            <option value="Technical Issue" {{ old('category') === 'Technical Issue' ? 'selected' : '' }}>Technical Issue</option>
                            <option value="Bug report" {{ old('category') === 'Bug report' ? 'selected' : '' }}>Bug report</option>
                            <option value="Suggestion" {{ old('category') === 'Suggestion' ? 'selected' : '' }}>Suggestion</option>
                            <option value="Question" {{ old('category') === 'Question' ? 'selected' : '' }}>Question</option>
                            <option value="Other" {{ old('category') === 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>

                    {{-- Card 2: Message (Subject + large textarea) --}}
                    <div class="forms-section-card bg-white shadow-2xl rounded-xl" @focusin="$el.classList.add('active')" @focusout="$el.classList.remove('active')">
                        <div class="mb-4">
                            <label for="subject" class="font-heading block text-sm font-bold text-gray-800 mb-1">Subject <span class="text-red-600">*</span></label>
                            <span class="font-data text-xs text-gray-500 tabular-nums" x-text="subjectLength + ' / 160'"></span>
                            <input id="subject" name="subject" type="text" required minlength="3" maxlength="160"
                                   class="font-data w-full border border-gray-300 rounded-lg px-3 py-2.5 bg-white input-dcomc-focus mt-1"
                                   placeholder="Brief summary of your feedback"
                                   value="{{ $subjectOld }}"
                                   @input="subjectLength = $event.target.value.length">
                        </div>
                        <div>
                            <label for="message" class="font-heading block text-sm font-bold text-gray-800 mb-1">Your message <span class="text-red-600">*</span></label>
                            <span class="font-data text-xs text-gray-500 tabular-nums" x-text="messageLength + ' / 5000'"></span>
                            <textarea id="message" name="message" rows="8" required minlength="5" maxlength="5000"
                                      class="font-data w-full border border-gray-300 rounded-lg px-3 py-2.5 bg-white input-dcomc-focus mt-1 resize-y min-h-[12rem]"
                                      placeholder="Describe your feedback in detail..."
                                      @input="messageLength = $event.target.value.length">{{ $messageOld }}</textarea>
                        </div>
                        <div class="mt-4">
                            <label class="font-heading block text-sm font-bold text-gray-800 mb-2">Importance (optional)</label>
                            <input type="range" name="priority" min="1" max="5" step="1" value="{{ $priority }}"
                                   class="w-full h-3 rounded-lg appearance-none bg-gray-200 cursor-pointer input-dcomc-focus" style="accent-color: #1E40AF;">
                            <p class="font-data text-xs text-gray-500 mt-1">1 = least important, 5 = very important</p>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" id="submit-btn"
                                class="font-heading w-full sm:w-auto min-w-[200px] px-8 py-3.5 text-base font-bold rounded-xl text-white no-underline transition-colors focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:ring-offset-2 disabled:opacity-70"
                                style="background-color: #1E40AF;"
                                :disabled="submitting"
                                x-text="submitting ? 'Sending…' : 'Submit Feedback'"></button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
@endif
