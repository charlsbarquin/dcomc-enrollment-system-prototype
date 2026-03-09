<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COR — {{ $program->code ?? $program->program_name ?? '' }} {{ $yearLevel }} {{ $semester }} - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
    <style>details summary::-webkit-details-marker { display: none; } details summary::marker { content: none; }</style>
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">
    @php
        $actingRole = auth()->user()?->effectiveRole() ?? (auth()->user()->role ?? null);
        $isStaff = request()->routeIs('staff.*');
        $dashRoute = $isStaff ? 'staff.dashboard' : 'registrar.dashboard';
    @endphp
    @include('dashboards.partials.role-sidebar')

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full max-w-5xl mx-auto">
                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">COR — {{ $program->code ?? $program->program_name }} / {{ $yearLevel }} / {{ $semester }}</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">All blocks for this program, year level, and semester. Deployed COR appears under each block.</p>
                        </div>
                        <a href="{{ route($dashRoute) }}" class="btn-back-hero shrink-0 whitespace-nowrap">← Back to Dashboard</a>
                    </div>
                </section>

                @if(session('error'))
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm font-data shadow-sm">{{ session('error') }}</div>
                @endif
                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-data shadow-sm">{{ session('success') }}</div>
                @endif

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
                    @if(isset($deployedBlockId) && $deployedBlockId)
                        <p class="text-xs text-gray-500 mb-2 font-data">Deployed block in URL: {{ $deployedBlockId }} — Records loaded for this block: {{ ($deployedBlockRecords ?? collect())->count() }}. @if(($deployedBlockRecords ?? collect())->isEmpty()) If this is 0, the deploy created no records: add students to the block and ensure the schedule has at least one subject with professor and time, then deploy again.@endif</p>
                    @endif
                    <h2 class="font-heading text-lg font-bold text-gray-800 mb-4">Blocks</h2>
                    @if(($blocks ?? collect())->isEmpty())
                        <p class="text-sm text-gray-500 font-data">No blocks for this program, year level, and semester in the selected school year. Create blocks in Registrar settings first.</p>
                    @else
                        <p class="text-xs text-gray-500 mb-3 font-data">Click a block to expand and view the deployed schedule.</p>
                        <div class="space-y-3">
                            @foreach($blocks as $block)
                                @php
                                    $blockId = (int) $block->id;
                                    $useDeployedRecords = isset($deployedBlockId) && $deployedBlockId !== null && (int)$deployedBlockId > 0 && (int)$blockId === (int)$deployedBlockId;
                                    $recs = $useDeployedRecords ? ($deployedBlockRecords ?? collect()) : ($recordsByBlock[$blockId] ?? collect());
                                    $bySubject = $recs->groupBy('subject_id');
                                    $hasDeployed = $bySubject->isNotEmpty();
                                    $blockLabel = ($block->name ?? $block->code ?? 'Block #'.$block->id);
                                    $syLabel = !empty($block->school_year_label) ? ' — ' . $block->school_year_label : '';
                                @endphp
                                <details class="border border-gray-200 rounded-xl overflow-hidden {{ $hasDeployed ? 'bg-white' : 'bg-gray-50' }}" {{ ($deployedBlockId && (int)$blockId === (int)$deployedBlockId) ? 'open' : '' }}>
                                    <summary class="cursor-pointer list-none p-4 font-semibold text-gray-800 hover:bg-blue-50/50 transition flex items-center justify-between gap-2 font-data">
                                        <span>[ {{ $blockLabel }}{{ $syLabel }}{{ $block->shift ? ' (' . ucfirst($block->shift) . ')' : '' }} ]</span>
                                        @if($useDeployedRecords && ($deployedBlockRecords ?? collect())->isNotEmpty())<span class="text-xs font-normal text-green-600">(just deployed)</span>@endif
                                        <span class="text-gray-400 text-sm">▼</span>
                                    </summary>
                                    <div class="px-4 pb-4 pt-0 border-t border-gray-100">
                                        @if($hasDeployed)
                                            <div class="overflow-x-auto mt-2 rounded-lg border border-gray-200">
                                                <table class="w-full text-sm font-data">
                                                    <thead class="bg-gray-50 border-b border-gray-200">
                                                        <tr>
                                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Subject</th>
                                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Professor</th>
                                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Room</th>
                                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Days</th>
                                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Time</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100">
                                                        @foreach($bySubject as $subjId => $subjectRecords)
                                                            @php $r = $subjectRecords->first(); @endphp
                                                            <tr class="hover:bg-blue-50/50">
                                                                <td class="py-3 px-4">{{ $r->subject->code ?? '' }} — {{ $r->subject->title ?? '' }}</td>
                                                                <td class="py-3 px-4">{{ $r->professor_name_snapshot ?? '—' }}</td>
                                                                <td class="py-3 px-4">{{ $r->room_name_snapshot ?? '—' }}</td>
                                                                <td class="py-3 px-4">{{ $r->days_snapshot ?? '—' }}</td>
                                                                <td class="py-3 px-4">{{ $r->start_time_snapshot ? \Carbon\Carbon::parse($r->start_time_snapshot)->format('H:i') : '—' }} – {{ $r->end_time_snapshot ? \Carbon\Carbon::parse($r->end_time_snapshot)->format('H:i') : '—' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <p class="text-sm text-gray-500 italic mt-2 font-data">No COR deployed for this block.</p>
                                            @if($actingRole === 'dean')
                                                <p class="text-sm text-gray-600 mb-2 font-data">You can deploy or archive the schedule for this block here.</p>
                                                <div class="flex gap-2 items-center flex-wrap">
                                                    <form method="POST" action="{{ route('dean.schedule.deploy-cor') }}" class="inline">
                                                        @csrf
                                                        <input type="hidden" name="program_id" value="{{ $program->id }}">
                                                        <input type="hidden" name="academic_year_level_id" value="{{ $academic_year_level_id ?? '' }}">
                                                        <input type="hidden" name="semester" value="{{ $semester }}">
                                                        <input type="hidden" name="school_year" value="{{ $selectedSchoolYear ?? '' }}">
                                                        <input type="hidden" name="block_id" value="{{ $blockId }}">
                                                        <input type="hidden" name="shift" value="{{ $block->shift ?? '' }}">
                                                        <button type="submit" class="btn-primary">Deploy and Archive</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('dean.schedule.fetch-cor') }}" class="inline">
                                                        @csrf
                                                        <input type="hidden" name="program_id" value="{{ $program->id }}">
                                                        <input type="hidden" name="academic_year_level_id" value="{{ $academic_year_level_id ?? '' }}">
                                                        <input type="hidden" name="semester" value="{{ $semester }}">
                                                        <input type="hidden" name="school_year" value="{{ $selectedSchoolYear ?? '' }}">
                                                        <input type="hidden" name="block_id" value="{{ $blockId }}">
                                                        <input type="hidden" name="shift" value="{{ $block->shift ?? '' }}">
                                                        <button type="submit" class="btn-secondary">Fetch (Archive)</button>
                                                    </form>
                                                </div>
                                            @else
                                                <p class="text-sm text-gray-500 italic mt-2 font-data">Deploy from Schedule by Program (Dean only).</p>
                                            @endif
                                            @if($useDeployedRecords && isset($deployedBlockId) && $deployedBlockId)
                                                <p class="text-xs text-amber-700 mt-1 font-data">You were redirected here after deploy. If the deploy created 0 records, ensure the block has students and the schedule had at least one subject with professor and time.</p>
                                            @endif
                                        @endif
                                        @if($hasDeployed && ($actingRole === 'dean' || $actingRole === 'registrar'))
                                            <div class="mt-4 border-t border-gray-200 pt-3">
                                                <p class="text-sm font-semibold text-red-700 font-heading">Danger: delete archived schedule</p>
                                                <p class="text-xs text-red-600 mb-2 font-data">This will permanently remove archived COR records for this block and scope. The deletion is irreversible.</p>
                                                <form method="POST" action="{{ route($archive_delete_route) }}" onsubmit="return confirm('Are you sure you want to permanently delete the archived schedule for this block? This cannot be undone.');" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="program_id" value="{{ $program->id }}">
                                                    <input type="hidden" name="year_level" value="{{ $yearLevel }}">
                                                    <input type="hidden" name="semester" value="{{ $semester }}">
                                                    <input type="hidden" name="school_year" value="{{ $selectedSchoolYear ?? '' }}">
                                                    <input type="hidden" name="block_id" value="{{ $blockId }}">
                                                    <button type="submit" class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold font-data">Delete archived schedule (permanent)</button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>
</body>
</html>
