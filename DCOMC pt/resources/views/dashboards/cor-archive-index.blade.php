<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COR Archive - DCOMC</title>
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
                {{-- Hero --}}
                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">COR Archive</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Read-only archive of deployed COR. Browse by program.</p>
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

                <div class="bg-white shadow-2xl rounded-xl border border-gray-200 p-6">
                    <h2 class="font-heading text-lg font-bold text-gray-800 mb-4">Programs</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($programs ?? [] as $prog)
                            <a href="{{ route($archive_program_route ?? 'cor.archive.program', ['programId' => $prog->id]) }}" class="folder-card-dcomc flex items-center gap-3 p-4">
                                <div class="folder-preview-dcomc w-16 shrink-0 rounded-lg">
                                    <span class="text-2xl text-[#1E40AF]/70">📁</span>
                                </div>
                                <span class="font-medium text-gray-800 font-data">{{ $prog->code ?? $prog->program_name }}</span>
                            </a>
                        @endforeach
                        @if(empty($programs) || count($programs) == 0)
                            <p class="text-sm text-gray-500 col-span-2 font-data">No programs with COR archive.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
