<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>View schedule - {{ $user->name }} - DCOMC</title>
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
        .btn-back-hero { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1rem; border-radius: 0.5rem; border: 2px solid rgba(255,255,255,0.5); background: rgba(255,255,255,0.2); color: #fff; font-size: 0.875rem; font-weight: 600; transition: background-color 0.2s; text-decoration: none; font-family: 'Roboto', sans-serif; }
        .btn-back-hero:hover { background: rgba(255,255,255,0.3); color: #fff; }
        .pill { display: inline-flex; align-items: center; padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; font-family: 'Roboto', sans-serif; background: #d97706; color: #fff; }
        .btn-remove { color: #dc2626; font-weight: 600; font-size: 0.875rem; background: none; border: none; cursor: pointer; font-family: 'Roboto', sans-serif; padding: 0.25rem 0; }
        .btn-remove:hover { color: #b91c1c; text-decoration: underline; }
    </style>
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">
    @php
        $isStaff = request()->routeIs('staff.*');
        $irregularitiesRoute = $isStaff ? 'staff.irregularities' : 'registrar.irregularities';
        $removeSubjectRoute = $isStaff ? 'staff.irregularities.schedule.remove-subject' : 'registrar.irregularities.schedule.remove-subject';
    @endphp
    @include('dashboards.partials.role-sidebar')

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full max-w-4xl mx-auto">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-data shadow-sm">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm font-data">{{ session('error') }}</div>
                @endif

                {{-- Hero Banner (same as Irregularities page) --}}
                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">View schedule</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">{{ $user->name }} ({{ $user->school_id ?? '—' }}) <span class="pill ml-2">Irregular</span></p>
                        </div>
                        <a href="{{ route($irregularitiesRoute, ['tab' => 'irregular']) }}" class="btn-back-hero shrink-0">← Back to Irregular Students</a>
                    </div>
                </section>

                {{-- Info card --}}
                <div class="bg-white shadow-2xl rounded-xl border border-gray-200 px-6 py-4 mb-6">
                    <p class="text-sm text-gray-600 font-data">This is the student’s deployed schedule (Create Schedule). Removing a subject also removes the student from that block in Students Explorer if it was the only subject for that block.</p>
                </div>

                {{-- Table: DCOMC blue header (same as Irregularities) --}}
                <div class="rounded-xl overflow-hidden border border-gray-200 shadow-2xl bg-white">
                    <div class="bg-[#1E40AF] px-6 py-4">
                        <h2 class="font-heading text-lg font-bold text-white">Schedule</h2>
                    </div>
                    @if($records->isEmpty())
                        <div class="p-12 text-center text-gray-500 font-data">No schedule deployed. Deploy a schedule from the Create Schedule tab.</div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm font-data" role="grid" aria-label="Student schedule">
                                <thead class="bg-gray-50 border-b border-gray-200 sticky top-0 z-10">
                                    <tr>
                                        <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Subject</th>
                                        <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Day · Time</th>
                                        <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Room · Professor</th>
                                        <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Block</th>
                                        <th scope="col" class="py-3 px-4 text-right font-heading font-bold text-gray-700">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach($records as $rec)
                                    <tr class="hover:bg-blue-50/50 transition-colors font-data">
                                        <td class="py-4 px-4">
                                            <span class="font-medium text-gray-900">{{ $rec->subject?->code ?? '—' }}</span>
                                            <span class="text-gray-600">{{ $rec->subject?->title ?? '' }}</span>
                                        </td>
                                        <td class="py-4 px-4 text-gray-600">{{ $rec->days_snapshot ?? '—' }} · {{ $rec->start_time_snapshot ? \Carbon\Carbon::parse($rec->start_time_snapshot)->format('H:i') : '—' }}-{{ $rec->end_time_snapshot ? \Carbon\Carbon::parse($rec->end_time_snapshot)->format('H:i') : '—' }}</td>
                                        <td class="py-4 px-4 text-gray-600">{{ $rec->room_name_snapshot ?? '—' }} · {{ $rec->professor_name_snapshot ?? '—' }}{{ $rec->is_overload ? ' (OVERLOAD)' : '' }}</td>
                                        <td class="py-4 px-4">
                                            @if($rec->block)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">{{ $rec->block->code ?? $rec->block->name }}</span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-4 text-right">
                                            <form method="POST" action="{{ route($removeSubjectRoute, $rec) }}" class="inline" onsubmit="return confirm('Remove this subject from the student\'s schedule? If it is the only subject for that block, they will be removed from that block in Students Explorer.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-remove">Remove</button>
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
    </main>
</body>
</html>
