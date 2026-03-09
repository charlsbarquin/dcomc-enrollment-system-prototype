<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Blocks - DCOMC</title>
    @include('layouts.partials.offline-assets')
    <style>
        .font-heading { font-family: 'Figtree', sans-serif; }
        .font-data { font-family: 'Roboto', sans-serif; }
        .forms-canvas { background: #f3f4f6; }
        .hero-gradient { background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 50%, #60A5FA 100%); }
        .input-dcomc-focus:focus { outline: none; border-color: #1E40AF; box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.2); }
        .btn-primary { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1.25rem; border-radius: 0.5rem; background: #1E40AF; color: #fff; font-size: 0.875rem; font-weight: 600; transition: background-color 0.2s; border: none; cursor: pointer; font-family: 'Roboto', sans-serif; text-decoration: none; }
        .btn-primary:hover { background: #1D3A8A; }
        .btn-secondary { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1rem; border-radius: 0.5rem; border: 2px solid #d1d5db; background: #fff; color: #374151; font-size: 0.875rem; font-weight: 600; transition: background-color 0.2s, border-color 0.2s; cursor: pointer; font-family: 'Roboto', sans-serif; text-decoration: none; }
        .btn-secondary:hover { background: #f9fafb; border-color: #9ca3af; }
    </style>
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">
    @php
        $isAdmin = request()->routeIs('admin.*');
        $isStaff = request()->routeIs('staff.*');
        $dashRoute = $isAdmin ? 'admin.dashboard' : ($isStaff ? 'staff.dashboard' : 'registrar.dashboard');
        $paginator = $blocks;
        $total = $paginator->total();
        $from = $total === 0 ? 0 : ($paginator->currentPage() - 1) * $paginator->perPage() + 1;
        $to = min($paginator->currentPage() * $paginator->perPage(), $total);
    @endphp
    @include('dashboards.partials.role-sidebar')

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-data shadow-sm">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-data">
                        <ul class="list-disc pl-5"><li>{{ $errors->first() }}</li></ul>
                    </div>
                @endif

                {{-- Hero Banner --}}
                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Blocks</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Organize student sections and manage departmental block assignments.</p>
                        </div>
                        <a href="{{ route($dashRoute) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-white/20 hover:bg-white/30 text-white text-sm font-medium transition-colors no-underline shrink-0 font-data whitespace-nowrap">← Back to Dashboard</a>
                    </div>
                </section>

                {{-- Filter: White floating card (GET form) --}}
                <form method="get" action="{{ request()->url() }}" class="bg-white shadow-2xl rounded-xl border border-gray-200 px-6 py-5 mb-6">
                    <input type="hidden" name="per_page" value="{{ request('per_page', 15) }}">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label for="blocks-filter-program" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Program</label>
                            <select id="blocks-filter-program" name="program" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus transition cursor-pointer">
                                <option value="">All</option>
                                @foreach($programs as $p)
                                    <option value="{{ $p }}" {{ request('program') === $p ? 'selected' : '' }}>{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="blocks-filter-year" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Year Level</label>
                            <select id="blocks-filter-year" name="year_level" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus transition cursor-pointer">
                                <option value="">All</option>
                                @foreach($yearLevels as $y)
                                    <option value="{{ $y }}" {{ request('year_level') === $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="blocks-filter-semester" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Semester</label>
                            <select id="blocks-filter-semester" name="semester" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus transition cursor-pointer">
                                <option value="">All</option>
                                @foreach($semesters as $s)
                                    <option value="{{ $s }}" {{ request('semester') === $s ? 'selected' : '' }}>{{ $s }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <a href="{{ request()->url() }}" class="btn-secondary">Reset</a>
                            <button type="submit" class="btn-primary">Apply</button>
                        </div>
                    </div>
                </form>

                {{-- Table: DCOMC blue header --}}
                <div class="rounded-xl overflow-hidden border border-gray-200 shadow-2xl bg-white">
                    <div class="bg-[#1E40AF] px-6 py-4 flex flex-wrap items-center justify-between gap-2">
                        <h2 class="font-heading text-lg font-bold text-white">Block List <span class="font-data font-normal text-white/90 text-base ml-1">({{ $from }}–{{ $to }} of {{ $total }})</span></h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm font-data" role="grid" aria-label="Blocks list">
                            <thead class="bg-gray-50 border-b border-gray-200 sticky top-0 z-10">
                                <tr>
                                    <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Block Name</th>
                                    <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Program</th>
                                    <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Capacity</th>
                                    <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Assigned Adviser</th>
                                    @if($isAdmin)
                                    <th scope="col" class="py-3 px-4 text-right font-heading font-bold text-gray-700">Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse($blocks as $block)
                                    @php
                                        $capacity = $block->capacity ?? $block->max_capacity ?? $block->max_students ?? 50;
                                        $current = $block->current_count_for_year ?? $block->current_size ?? 0;
                                    @endphp
                                    <tr class="hover:bg-blue-50/50 transition-colors font-data">
                                        <td class="py-4 px-4 text-gray-900 font-medium">{{ $block->code ?? $block->name }}</td>
                                        <td class="py-4 px-4 text-gray-600">{{ $block->program ?? '—' }}</td>
                                        <td class="py-4 px-4 text-gray-600">{{ $current }} / {{ $capacity }}</td>
                                        <td class="py-4 px-4 text-gray-500">—</td>
                                        @if($isAdmin)
                                        <td class="py-4 px-4 text-right">
                                            <form method="POST" action="{{ route('admin.blocks.delete', $block->id) }}" class="inline" onsubmit="return confirm('Remove this block? This is only allowed when no students and schedules are attached.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-secondary py-1.5 px-3 text-xs {{ ($block->current_count_for_year ?? $block->students_count ?? 0) > 0 ? 'opacity-50 cursor-not-allowed' : '' }}" {{ ($block->current_count_for_year ?? $block->students_count ?? 0) > 0 ? 'disabled' : '' }}>Remove</button>
                                            </form>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $isAdmin ? 5 : 4 }}" class="py-12 px-4 text-center text-gray-500 font-data">No blocks available yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($paginator->hasPages())
                        <div class="px-6 py-3 border-t border-gray-200 bg-gray-50 flex flex-wrap items-center justify-between gap-2 font-data text-sm">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-1.5 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 text-sm font-medium no-underline {{ $paginator->onFirstPage() ? 'opacity-50 pointer-events-none' : '' }}">Previous</a>
                                @foreach($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                                    <a href="{{ $url }}" class="px-3 py-1.5 rounded text-sm font-medium no-underline {{ $page === $paginator->currentPage() ? 'bg-[#1E40AF] text-white' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}">{{ $page }}</a>
                                @endforeach
                                <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-1.5 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 text-sm font-medium no-underline {{ $paginator->onLastPage() ? 'opacity-50 pointer-events-none' : '' }}">Next</a>
                            </div>
                            <label class="text-gray-600">Per page:</label>
                            <select onchange="window.location.href='{{ request()->url() }}?' + new URLSearchParams({ ...Object.fromEntries(new URLSearchParams(window.location.search)), per_page: this.value, page: '1' }).toString();" class="border border-gray-300 rounded px-2 py-1 text-sm bg-white">
                                @foreach([10, 15, 25, 50] as $n)
                                    <option value="{{ $n }}" {{ $paginator->perPage() == $n ? 'selected' : '' }}>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>
</body>
</html>
