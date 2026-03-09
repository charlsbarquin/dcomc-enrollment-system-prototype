<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Irregularities - Student Records - DCOMC</title>
    @include('layouts.partials.offline-assets')
    <style>
        .font-heading { font-family: 'Figtree', sans-serif; }
        .font-data { font-family: 'Roboto', sans-serif; }
        .forms-canvas { background: #f3f4f6; }
        .hero-gradient { background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 50%, #60A5FA 100%); }
        .input-dcomc-focus:focus { outline: none; border-color: #1E40AF; box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.2); }
        .btn-primary { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1.25rem; border-radius: 0.5rem; background: #1E40AF; color: #fff; font-size: 0.875rem; font-weight: 600; transition: background-color 0.2s; border: none; cursor: pointer; font-family: 'Roboto', sans-serif; }
        .btn-primary:hover { background: #1D3A8A; }
        .btn-primary:focus { outline: none; box-shadow: 0 0 0 2px #fff, 0 0 0 4px #1E40AF; }
        .btn-secondary { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1rem; border-radius: 0.5rem; border: 2px solid #d1d5db; background: #fff; color: #374151; font-size: 0.875rem; font-weight: 600; transition: background-color 0.2s, border-color 0.2s; cursor: pointer; font-family: 'Roboto', sans-serif; text-decoration: none; }
        .btn-secondary:hover { background: #f9fafb; border-color: #9ca3af; }
        .btn-secondary:focus { outline: none; box-shadow: 0 0 0 2px #fff, 0 0 0 4px #d1d5db; }
        .pill-approve, .pill-regular { background: #059669; color: #fff; }
        .pill-pending, .pill-irregular { background: #d97706; color: #fff; }
        .pill-rejected, .pill-conflict { background: #dc2626; color: #fff; }
        .pill { display: inline-flex; align-items: center; padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; font-family: 'Roboto', sans-serif; }
        .btn-action-edit { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; border-radius: 0.5rem; border: 2px solid #d1d5db; background: #fff; color: #374151; font-size: 0.75rem; font-weight: 600; transition: background-color 0.2s; cursor: pointer; font-family: 'Roboto', sans-serif; text-decoration: none; }
        .btn-action-edit:hover { background: #f9fafb; }
        .btn-action-edit:focus { outline: none; box-shadow: 0 0 0 2px #1E40AF; }
    </style>
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">
    @php
        $isStaff = request()->routeIs('staff.*');
        $irregularitiesRoute = $isStaff ? 'staff.irregularities' : 'registrar.irregularities';
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
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Irregularities</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Monitor and manage students with irregular schedules or back-subjects.</p>
                        </div>
                    </div>
                    <div class="flex gap-2 mt-4 border-t border-white/20 pt-4">
                        <a href="{{ route($irregularitiesRoute, ['tab' => 'irregular'] + request()->only('q')) }}" class="pb-2 px-4 text-sm font-semibold {{ ($tab ?? 'irregular') === 'irregular' ? 'text-white border-b-2 border-white' : 'text-white/80 hover:text-white' }} font-data">Irregular Students</a>
                        <a href="{{ route($irregularitiesRoute, ['tab' => 'create-schedule']) }}" class="pb-2 px-4 text-sm font-medium {{ ($tab ?? '') === 'create-schedule' ? 'text-white border-b-2 border-white' : 'text-white/80 hover:text-white' }} font-data">Create Schedule</a>
                    </div>
                </section>

                @if(($tab ?? 'irregular') === 'create-schedule')
                    <p class="mb-4 text-sm text-gray-600 font-data">Create schedules for <strong>shifters/irregular</strong> students. Add subjects and choose a slot from the COR Archive. Deploy to students in the table; deployed schedules are saved to <strong>Irregular COR Archive</strong>.</p>
                    @if($createScheduleData)
                        @include('dashboards.partials.create-schedule-workspace', $createScheduleData)
                    @else
                        <p class="text-gray-500 font-data">Unable to load schedule workspace.</p>
                    @endif
                @else
                    {{-- Filter: White floating card (match Students Explorer) --}}
                    <div class="bg-white shadow-2xl rounded-xl border border-gray-200 px-6 py-5 mb-6">
                        <form method="GET" action="{{ route($irregularitiesRoute) }}" class="flex flex-wrap items-end gap-4">
                            <input type="hidden" name="tab" value="irregular">
                            <div class="min-w-[200px] flex-1">
                                <label for="irreg-q" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Search</label>
                                <input type="text" id="irreg-q" name="q" value="{{ request('q') }}" placeholder="Name, email, or school ID..." class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus transition">
                            </div>
                            <button type="submit" class="btn-primary">Search</button>
                            @if(request('q'))
                                <a href="{{ route($irregularitiesRoute, ['tab' => 'irregular']) }}" class="btn-secondary">Clear</a>
                            @endif
                        </form>
                    </div>

                    {{-- Table: DCOMC blue header, hover rows --}}
                    <div class="rounded-xl overflow-hidden border border-gray-200 shadow-2xl bg-white">
                        <div class="bg-[#1E40AF] px-6 py-4">
                            <h2 class="font-heading text-lg font-bold text-white">Irregular Students</h2>
                        </div>
                        {{-- Table title already "Irregular Students" --}}
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm font-data" role="grid" aria-label="Irregular students list">
                                <thead class="bg-gray-50 border-b border-gray-200 sticky top-0 z-10">
                                    <tr>
                                        <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Student Name</th>
                                        <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Deficiency</th>
                                        <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Status</th>
                                        <th scope="col" class="py-3 px-4 text-right font-heading font-bold text-gray-700">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @forelse($students as $student)
                                        @php
                                            $currentProgram = $student->course ?? $student->block?->program ?? $student->blockAssignments->first()?->block?->program ?? '—';
                                            $deficiencyParts = [];
                                            if ($student->block_id) {
                                                $deficiencyParts[] = ($student->block?->code ?? $student->block?->name) . ' (' . ($student->block?->year_level ?? '—') . ', ' . ($student->block?->semester ?? '—') . ')';
                                            }
                                            foreach ($student->blockAssignments as $assign) {
                                                $deficiencyParts[] = ($assign->block?->code ?? $assign->block?->name) . ' (' . ($assign->block?->year_level ?? '—') . ', ' . ($assign->block?->semester ?? '—') . ')';
                                            }
                                            $deficiency = !empty($deficiencyParts) ? implode(' · ', $deficiencyParts) : 'None (deploy from Create Schedule)';
                                            $statusType = strtolower($student->student_type ?? 'irregular');
                                        @endphp
                                        <tr class="hover:bg-blue-50/50 transition-colors font-data">
                                            <td class="py-4 px-4 text-gray-900">{{ $student->name }}</td>
                                            <td class="py-4 px-4 text-gray-600">{{ $deficiency }}</td>
                                            <td class="py-4 px-4">
                                                <span class="pill pill-irregular">{{ $student->student_type ?? 'Irregular' }}</span>
                                            </td>
                                            <td class="py-4 px-4 text-right">
                                                <a href="{{ route($isStaff ? 'staff.irregularities.schedule' : 'registrar.irregularities.schedule', $student->id) }}" class="btn-action-edit">View schedule</a>
                                                <a href="{{ $isStaff ? route('staff.students-explorer') : route('registrar.students-explorer') }}?q={{ urlencode($student->name) }}" class="btn-action-edit">View in Explorer</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="py-12 px-4 text-center text-gray-500 font-data">No irregular students found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($students->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 font-data">
                            {{ $students->links() }}
                        </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </main>
</body>
</html>
