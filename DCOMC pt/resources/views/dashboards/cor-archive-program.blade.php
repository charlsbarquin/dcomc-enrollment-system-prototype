<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COR Archive — {{ $program->code ?? $program->program_name }} - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">
    @php
        $isStaff = request()->routeIs('staff.*');
        $dashRoute = $isStaff ? 'staff.dashboard' : 'registrar.dashboard';
    @endphp
    @include('dashboards.partials.role-sidebar')

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full max-w-4xl mx-auto">
                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">COR Archive — {{ $program->code ?? $program->program_name }}</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Select a school year, then open a year level folder to see semesters and blocks.</p>
                        </div>
                        <a href="{{ route($dashRoute) }}" class="btn-back-hero shrink-0 whitespace-nowrap">← Back to Dashboard</a>
                    </div>
                </section>

                @if(!empty($breadcrumb))
                    <nav class="flex items-center gap-2 text-sm text-gray-600 mb-4 font-data" aria-label="Breadcrumb">
                        @foreach($breadcrumb as $i => $item)
                            @if($i > 0)<span class="text-gray-400">/</span>@endif
                            @if(!empty($item['url']))<a href="{{ $item['url'] }}" class="text-[#1E40AF] hover:underline">{{ $item['label'] }}</a>@else<span>{{ $item['label'] }}</span>@endif
                        @endforeach
                    </nav>
                @endif

                <div class="bg-white shadow-2xl rounded-xl border border-gray-200 px-6 py-5 mb-6">
                    <label for="school-year-select" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">School year</label>
                    <select id="school-year-select" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus min-w-[160px]">
                        @foreach($schoolYears ?? [] as $sy)
                            <option value="{{ $sy }}" {{ ($selectedSchoolYear ?? '') == $sy ? 'selected' : '' }}>{{ $sy }}</option>
                        @endforeach
                    </select>
                    <script>
                        document.getElementById('school-year-select').addEventListener('change', function() {
                            var url = new URL(window.location.href);
                            url.searchParams.set('school_year', this.value);
                            window.location.href = url.toString();
                        });
                    </script>
                </div>

                <div class="bg-white shadow-2xl rounded-xl border border-gray-200 p-6">
                    <h2 class="font-heading text-lg font-bold text-gray-800 mb-2">Year Level</h2>
                    <p class="text-sm text-gray-500 mb-4 font-data">School year <strong>{{ $selectedSchoolYear ?? '—' }}</strong>. Open a year level to see semester folders, then blocks.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        @forelse($yearLevels ?? [] as $yr)
                            <a href="{{ route($archive_year_route ?? 'cor.archive.year', ['programId' => $program->id, 'yearLevel' => $yr, 'school_year' => $selectedSchoolYear ?? '']) }}" class="folder-card-dcomc flex items-center gap-3 p-4">
                                <div class="folder-preview-dcomc w-14 shrink-0 rounded-lg"><span class="text-xl text-[#1E40AF]/70">📁</span></div>
                                <span class="font-medium text-gray-800 font-data">{{ $yr }}</span>
                            </a>
                        @empty
                            <p class="text-sm text-gray-500 col-span-3 font-data">No year levels defined. Add year levels in Settings first.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
