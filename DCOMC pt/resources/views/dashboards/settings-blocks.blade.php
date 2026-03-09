<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Block Settings - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">
    @php $set = request()->routeIs('admin.settings.*') ? 'admin.settings' : 'registrar.settings'; $isAdmin = request()->routeIs('admin.*'); $dashRoute = $isAdmin ? 'admin.dashboard' : 'registrar.dashboard'; @endphp
    @include('dashboards.partials.role-sidebar')

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full max-w-6xl mx-auto">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-data shadow-sm">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-data">
                        <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif

                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Block Settings</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Manually add blocks for the automatic assignment system. Block codes: <strong>Program Year - Section</strong> (e.g. BEED 1 - 1). Leave code blank to auto-generate.</p>
                        </div>
                        <a href="{{ route($dashRoute) }}" class="btn-back-hero shrink-0 whitespace-nowrap">← Back to Dashboard</a>
                    </div>
                </section>

                <div class="bg-white shadow-2xl rounded-xl border border-gray-200 px-6 py-5 mb-6 card-dcomc-top">
                    <h2 class="font-heading text-lg font-bold text-gray-800 mb-4">Add Block</h2>
                    <form method="POST" action="{{ route($set . '.blocks.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end text-sm">
                        @csrf
                        <div class="md:col-span-2">
                            <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Program</label>
                            <select name="program" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus">
                                <option value="">Select program</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program }}" {{ old('program') === $program ? 'selected' : '' }}>{{ $program }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Year Level</label>
                            <select name="year_level" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus">
                                <option value="">Select year level</option>
                                @foreach($yearLevels as $level)
                                    <option value="{{ $level }}" {{ old('year_level') === $level ? 'selected' : '' }}>{{ $level }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Semester</label>
                            <select name="semester" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus">
                                <option value="">Select semester</option>
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester }}" {{ old('semester') === $semester ? 'selected' : '' }}>{{ $semester }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Shift</label>
                            <select name="shift" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus">
                                <option value="day" {{ old('shift', 'day') === 'day' ? 'selected' : '' }}>Day</option>
                                <option value="night" {{ old('shift') === 'night' ? 'selected' : '' }}>Night</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Block Code</label>
                            <input type="text" name="code" value="{{ old('code') }}" placeholder="Leave blank to auto-generate" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus">
                        </div>
                        <div>
                            <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Capacity</label>
                            <input type="number" name="capacity" min="1" max="100" value="{{ old('capacity', 50) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus">
                        </div>
                        <div class="md:col-span-5 flex justify-end">
                            <button type="submit" class="btn-primary">Add Block</button>
                        </div>
                    </form>
                </div>

                <div class="rounded-xl overflow-hidden border border-gray-200 shadow-2xl bg-white">
                    <div class="bg-[#1E40AF] px-6 py-4">
                        <h2 class="font-heading text-lg font-bold text-white">Block Records</h2>
                    </div>
                    <div class="p-6">
                        @if($blocks->isEmpty())
                            <p class="text-sm text-gray-500 font-data">No blocks defined yet.</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm font-data">
                                    <thead class="bg-gray-50 border-b border-gray-200">
                                        <tr>
                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Code</th>
                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Program</th>
                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Year / Sem</th>
                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Shift</th>
                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Capacity</th>
                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Visible</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        @foreach($blocks as $block)
                                            <tr class="hover:bg-blue-50/50 transition-colors font-data">
                                                <td class="py-4 px-4 font-semibold">{{ $block->code ?? $block->name }}</td>
                                                <td class="py-4 px-4">{{ $block->program ?? 'N/A' }}</td>
                                                <td class="py-4 px-4">{{ $block->year_level ?? 'N/A' }} / {{ $block->semester ?? 'N/A' }}</td>
                                                <td class="py-4 px-4 uppercase">{{ $block->shift ?? 'day' }}</td>
                                                <td class="py-4 px-4">{{ $block->capacity ?? $block->max_students ?? 50 }}</td>
                                                <td class="py-4 px-4">
                                                    <form method="POST" action="{{ route($set . '.blocks.toggle', $block->id) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <label class="inline-flex items-center gap-2 text-xs text-gray-600 cursor-pointer font-data">
                                                            <input type="checkbox" onchange="this.form.submit()" {{ $block->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-[#1E40AF] focus:ring-[#1E40AF]">
                                                            <span>{{ $block->is_active ? 'Show' : 'Hide' }}</span>
                                                        </label>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
