<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dean Faculty Availability - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">

    @include('dashboards.partials.dean-sidebar')

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full max-w-7xl mx-auto">
                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Faculty Availability & Load</h1>
                    <p class="text-white/90 text-sm font-data">Department / scope: {{ Auth::user()->department?->name ?? $scope ?? 'ALL' }}</p>
                    <p class="text-white/80 text-xs mt-1 font-data">Same professors as Manage Professor. Click View to see assigned subjects (regular and overload schedule).</p>
                </section>

                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-data shadow-sm">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm font-data">{{ session('error') }}</div>
                @endif

                <div class="bg-white shadow-2xl rounded-xl overflow-hidden border border-gray-200">
                    <div class="bg-[#1E40AF] px-6 py-4">
                        <h2 class="font-heading text-lg font-bold text-white">Faculty</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm font-data">
                            <thead class="bg-[#1E40AF]">
                                <tr>
                                    <th class="p-3 text-left font-heading font-bold text-white">Professor</th>
                                    <th class="p-3 text-left font-heading font-bold text-white">Employment</th>
                                    <th class="p-3 text-left font-heading font-bold text-white">Gender</th>
                                    <th class="p-3 text-right font-heading font-bold text-white">Total Units</th>
                                    <th class="p-3 text-right font-heading font-bold text-white">Max Units</th>
                                    <th class="p-3 text-left font-heading font-bold text-white">Faculty type</th>
                                    <th class="p-3 text-left font-heading font-bold text-white">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($faculty as $item)
                                    <tr class="border-t border-gray-100 hover:bg-blue-50/50 transition-colors">
                                <td class="p-3">
                                    <span class="font-medium text-gray-800">{{ $item->name }}</span>
                                    @if($item->is_overload)
                                        <span class="ml-2 bg-red-100 text-red-700 text-xs font-semibold px-1.5 py-0.5 rounded">OVERLOAD</span>
                                    @endif
                                </td>
                                <td class="p-3 text-gray-600">{{ $item->employment_label ?? ucfirst(str_replace('-', ' ', $item->faculty_type ?? '')) }}</td>
                                <td class="p-3 text-gray-600">{{ $item->gender ?? '—' }}</td>
                                <td class="p-3 text-right text-gray-800">{{ $item->assigned_units }}</td>
                                <td class="p-3 text-right text-gray-600">{{ $item->max_units ?: '—' }}</td>
                                <td class="p-3 text-gray-600">{{ $item->faculty_type ? ucfirst(str_replace('-', ' ', $item->faculty_type)) : '—' }}</td>
                                <td class="p-3">
                                    <a href="{{ route('dean.faculty-availability.show', $item->id) }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-[#1E40AF] text-white text-sm font-medium no-underline hover:bg-[#1D3A8A] font-data">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="p-6 text-center text-gray-500">No faculty data found.</td></tr>
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
