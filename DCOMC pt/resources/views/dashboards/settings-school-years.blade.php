<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Year Settings - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
</head>
@php $set = request()->routeIs('admin.settings.*') ? 'admin.settings' : 'registrar.settings'; $isAdmin = request()->routeIs('admin.*'); $dashRoute = $isAdmin ? 'admin.dashboard' : 'registrar.dashboard'; @endphp
<body class="{{ $isAdmin ? 'dashboard-wrap bg-[#F1F5F9] min-h-screen flex overflow-x-hidden' : 'bg-[#F1F5F9] min-h-screen flex overflow-x-hidden' }} text-gray-800 font-data">
    @include('dashboards.partials.role-sidebar')
    @if($isAdmin)
    @include('dashboards.partials.admin-loading-bar')
    @endif

    <main class="{{ $isAdmin ? 'dashboard-main flex-1 flex flex-col min-w-0 overflow-hidden' : 'flex-1 flex flex-col min-w-0 overflow-hidden' }}">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full max-w-4xl mx-auto">
                <nav class="text-sm text-gray-600 font-data mb-4" aria-label="Breadcrumb">
                    <a href="{{ route($dashRoute) }}" class="text-[#1E40AF] hover:underline">Dashboard</a>
                    <span class="mx-1">→</span>
                    <a href="{{ route($set . '.school-years') }}" class="text-gray-800 font-medium">Settings → School Years</a>
                </nav>
                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-data shadow-sm">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-data">
                        <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                @if(!empty($promptOpenEnrollment))
                    <div class="mb-6 p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-sm font-data shadow-sm">
                        <strong>Next term:</strong> Current month ({{ now()->format('F') }}) is past the end of the current semester. Set the new <strong>Active School Year</strong> above and save to open enrollment for the next term. All students will be set to Not Enrolled until they re-enroll.
                    </div>
                @endif

                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">School Year Settings</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Manage school year records and active term. Philippine calendar: 1st Sem Aug–Dec, 2nd Sem Jan–May, Midyear Jun–Jul (optional).</p>
                        </div>
                        <a href="{{ route($dashRoute) }}" class="btn-back-hero shrink-0 whitespace-nowrap">← Back to Dashboard</a>
                    </div>
                </section>

                <div class="bg-white shadow-2xl rounded-xl border border-gray-200 px-6 py-5 mb-6 card-dcomc-top">
                    <h2 class="font-heading text-lg font-bold text-gray-800 mb-2">Active School Year &amp; Semester Dates</h2>
                    <p class="text-xs text-gray-600 mb-4 font-data">Set which school year is current. Changing this sets all students to <strong>Not Enrolled</strong> so they must re-enroll for the new term.</p>
                    <form method="POST" action="{{ route($set . '.academic-calendar.store') }}" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Current Active School Year</label>
                                <select name="active_school_year_id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus">
                                    <option value="">— None (no active SY) —</option>
                                    @foreach($schoolYears ?? [] as $sy)
                                        <option value="{{ $sy->id }}" {{ (old('active_school_year_id', optional($calendar)->active_school_year_id ?? '') == $sy->id) ? 'selected' : '' }}>{{ $sy->label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">1st Sem Start (Month)</label>
                                <select name="first_semester_start_month" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus">
                                    @foreach($monthNames ?? [] as $num => $name)
                                        <option value="{{ $num }}" {{ (old('first_semester_start_month', optional($calendar)->first_semester_start_month ?? 8) == $num) ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">1st Sem End (Month)</label>
                                <select name="first_semester_end_month" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus">
                                    @foreach($monthNames ?? [] as $num => $name)
                                        <option value="{{ $num }}" {{ (old('first_semester_end_month', optional($calendar)->first_semester_end_month ?? 12) == $num) ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">2nd Sem Start (Month)</label>
                                <select name="second_semester_start_month" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus">
                                    @foreach($monthNames ?? [] as $num => $name)
                                        <option value="{{ $num }}" {{ (old('second_semester_start_month', optional($calendar)->second_semester_start_month ?? 1) == $num) ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">2nd Sem End (Month)</label>
                                <select name="second_semester_end_month" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus">
                                    @foreach($monthNames ?? [] as $num => $name)
                                        <option value="{{ $num }}" {{ (old('second_semester_end_month', optional($calendar)->second_semester_end_month ?? 5) == $num) ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Midyear Start (optional)</label>
                                <select name="midyear_start_month" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus">
                                    <option value="">—</option>
                                    @foreach($monthNames ?? [] as $num => $name)
                                        <option value="{{ $num }}" {{ (old('midyear_start_month', optional($calendar)->midyear_start_month ?? '') == $num) ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Midyear End (optional)</label>
                                <select name="midyear_end_month" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus">
                                    <option value="">—</option>
                                    @foreach($monthNames ?? [] as $num => $name)
                                        <option value="{{ $num }}" {{ (old('midyear_end_month', optional($calendar)->midyear_end_month ?? '') == $num) ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn-primary">Save Active SY &amp; Calendar</button>
                    </form>
                </div>

                <div class="bg-white shadow-2xl rounded-xl border border-gray-200 px-6 py-5 mb-6 card-dcomc-top">
                    <h2 class="font-heading text-lg font-bold text-gray-800 mb-4">Add School Year</h2>
                    <form method="POST" action="{{ route($set . '.school-years.generate') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                        @csrf
                        <div>
                            <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Start Year</label>
                            <input type="number" name="start_year" min="2000" max="2100" value="{{ old('start_year', now()->month >= 6 ? now()->year : now()->year - 1) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus">
                        </div>
                        <div>
                            <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">End Year</label>
                            <input type="number" name="end_year" min="2001" max="2101" value="{{ old('end_year', now()->month >= 6 ? now()->year + 1 : now()->year) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus">
                        </div>
                        <button type="submit" class="btn-primary">Add Record</button>
                    </form>
                </div>

                <div class="rounded-xl overflow-hidden border border-gray-200 shadow-2xl bg-white">
                    <div class="bg-[#1E40AF] px-6 py-4 flex flex-wrap items-center justify-between gap-2">
                        <h2 class="font-heading text-lg font-bold text-white">School Year Records</h2>
                        <form method="POST" action="{{ route($set . '.school-years.clear') }}" onsubmit="return confirm('Clear all school year records?');" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-semibold font-data">Clear All Years</button>
                        </form>
                    </div>
                    <div class="p-6">
                        @if($schoolYears->isEmpty())
                            <p class="text-sm text-gray-500 font-data">No school year records yet.</p>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                @foreach($schoolYears as $year)
                                    <div class="border border-gray-200 rounded-xl p-4 bg-gray-50 font-data">
                                        <p class="font-semibold text-gray-800">{{ $year->label }}</p>
                                        <p class="text-xs text-gray-500 mt-1">Created {{ $year->created_at->diffForHumans() }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
