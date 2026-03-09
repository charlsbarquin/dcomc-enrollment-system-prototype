<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Irregular COR Archive - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">
    @php
        $isStaff = request()->routeIs('staff.*');
        $dashRoute = $isStaff ? 'staff.dashboard' : 'registrar.dashboard';
        $createRoute = $isStaff ? route('staff.irregularities', ['tab' => 'create-schedule']) : route('registrar.irregularities', ['tab' => 'create-schedule']);
        $showRoute = $isStaff ? 'staff.irregular-cor-archive.show' : 'registrar.irregular-cor-archive.show';
    @endphp
    @include('dashboards.partials.role-sidebar')

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-data shadow-sm">{{ session('success') }}</div>
                @endif

                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Irregular COR Archive</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Deployed schedules from Irregularities → Create Schedule. Each row is one deployment batch (date and deployer).</p>
                        </div>
                        <a href="{{ $createRoute }}" class="btn-white-hero shrink-0 whitespace-nowrap">← Create Schedule</a>
                    </div>
                </section>

                <div class="rounded-xl overflow-hidden border border-gray-200 shadow-2xl bg-white">
                    <div class="bg-[#1E40AF] px-6 py-4">
                        <h2 class="font-heading text-lg font-bold text-white">Deployment Batches</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm font-data" role="grid" aria-label="Irregular COR deployment batches">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Deploy date</th>
                                    <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Deployed by</th>
                                    <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Program</th>
                                    <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Year / Semester</th>
                                    <th scope="col" class="py-3 px-4 text-center font-heading font-bold text-gray-700">Students</th>
                                    <th scope="col" class="py-3 px-4 text-center font-heading font-bold text-gray-700">Records</th>
                                    <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse($batches as $batch)
                                    @php
                                        $program = $programs->get($batch->program_id);
                                        $deployer = $deployers->get($batch->deployed_by);
                                    @endphp
                                    <tr class="hover:bg-blue-50/50 transition-colors font-data">
                                        <td class="py-4 px-4 text-gray-800">{{ $batch->deploy_date }}</td>
                                        <td class="py-4 px-4 text-gray-700">{{ $deployer ? $deployer->name : '—' }}</td>
                                        <td class="py-4 px-4 text-gray-700">{{ $program ? ($program->program_name ?? $program->code) : '—' }}</td>
                                        <td class="py-4 px-4 text-gray-700">{{ $batch->year_level ?? '—' }} / {{ $batch->semester ?? '—' }}</td>
                                        <td class="py-4 px-4 text-center text-gray-700">{{ $batch->student_count ?? 0 }}</td>
                                        <td class="py-4 px-4 text-center text-gray-700">{{ $batch->record_count ?? 0 }}</td>
                                        <td class="py-4 px-4">
                                            <a href="{{ route($showRoute, ['date' => $batch->deploy_date, 'deployedBy' => $batch->deployed_by]) }}" class="btn-action-readonly">View records</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-12 px-4 text-center text-gray-500 font-data">No irregular COR deployments yet. Deploy a schedule from <strong>Irregularities → Create Schedule</strong> to see it here.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
