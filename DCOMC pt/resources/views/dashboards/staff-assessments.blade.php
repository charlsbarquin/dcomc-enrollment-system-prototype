<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Assessment Monitoring - DCOMC Staff</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">
    @include('dashboards.partials.role-sidebar')

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-data shadow-sm">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-data">
                        <ul class="list-disc pl-5">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
                    </div>
                @endif

                {{-- Hero Banner (DCOMC Blue gradient) --}}
                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Assessment Monitoring</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Staff / Accounting — review and update assessment status, income class, and UniFAST eligibility.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 shrink-0">
                            <a href="{{ route('staff.assessments.export', array_filter($filters ?? [])) }}" class="btn-white-hero">Export CSV</a>
                            <a href="{{ route('staff.dashboard') }}" class="btn-back-hero">Back to Dashboard</a>
                        </div>
                    </div>
                </section>

                {{-- Search & Filter Card (white, rounded-xl) --}}
                <form method="GET" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="filter-status" class="block font-heading text-sm font-semibold text-gray-700 mb-1">Status</label>
                            <select name="status" id="filter-status" class="w-full font-data text-sm border border-gray-200 rounded-lg px-4 py-2.5 bg-white input-dcomc-focus">
                                <option value="">All Status</option>
                                <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="reviewed" {{ ($filters['status'] ?? '') === 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                                <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>
                        <div>
                            <label for="filter-from" class="block font-heading text-sm font-semibold text-gray-700 mb-1">From date</label>
                            <input type="date" name="from_date" id="filter-from" value="{{ $filters['from_date'] ?? '' }}" class="w-full font-data text-sm border border-gray-200 rounded-lg px-4 py-2.5 bg-white input-dcomc-focus">
                        </div>
                        <div>
                            <label for="filter-to" class="block font-heading text-sm font-semibold text-gray-700 mb-1">To date</label>
                            <input type="date" name="to_date" id="filter-to" value="{{ $filters['to_date'] ?? '' }}" class="w-full font-data text-sm border border-gray-200 rounded-lg px-4 py-2.5 bg-white input-dcomc-focus">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="btn-primary w-full md:w-auto">Apply Filters</button>
                        </div>
                    </div>
                </form>

                {{-- Table: Solid DCOMC Blue header, white card, hover:bg-blue-50/50 --}}
                <div class="bg-white shadow-2xl rounded-xl overflow-hidden border border-gray-200">
                    <div class="table-header-dcomc px-6 py-4 flex flex-wrap items-center justify-between gap-2">
                        <h2 class="font-heading text-lg font-bold text-white">Assessments</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full font-data text-sm" role="grid">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="text-left py-3 px-4 font-heading font-semibold text-gray-700">Student</th>
                                    <th scope="col" class="text-left py-3 px-4 font-heading font-semibold text-gray-700">Total Assessed</th>
                                    <th scope="col" class="text-left py-3 px-4 font-heading font-semibold text-gray-700">Income Class</th>
                                    <th scope="col" class="text-left py-3 px-4 font-heading font-semibold text-gray-700">Status</th>
                                    <th scope="col" class="text-left py-3 px-4 font-heading font-semibold text-gray-700">UniFast</th>
                                    <th scope="col" class="text-left py-3 px-4 font-heading font-semibold text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assessments as $assessment)
                                    <tr class="border-b border-gray-100 transition-colors duration-200 hover:bg-blue-50/50">
                                        <td class="py-3 px-4 text-gray-900 font-data">{{ $assessment->student?->name ?? 'N/A' }}</td>
                                        <td class="py-3 px-4 text-gray-700 font-data">PHP {{ number_format((float)$assessment->total_assessed, 2) }}</td>
                                        <td class="py-3 px-4 text-gray-700 font-data">{{ $assessment->income_classification ?? 'N/A' }}</td>
                                        <td class="py-3 px-4 font-data uppercase">{{ $assessment->assessment_status }}</td>
                                        <td class="py-3 px-4 text-gray-700 font-data">{{ $assessment->unifast_eligible ? 'Eligible' : 'Not Eligible' }}</td>
                                        <td class="py-3 px-4">
                                            <form method="POST" action="{{ route('staff.assessments.status', $assessment->id) }}" class="flex flex-wrap gap-2 items-center">
                                                @csrf
                                                @method('PATCH')
                                                <select name="assessment_status" class="font-data text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white input-dcomc-focus">
                                                    <option value="pending" {{ $assessment->assessment_status === 'pending' ? 'selected' : '' }}>Pending</option>
                                                    <option value="reviewed" {{ $assessment->assessment_status === 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                                                    <option value="approved" {{ $assessment->assessment_status === 'approved' ? 'selected' : '' }}>Approved</option>
                                                    <option value="rejected" {{ $assessment->assessment_status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                                </select>
                                                <input type="text" name="income_classification" value="{{ $assessment->income_classification }}" placeholder="Income Class" class="font-data text-xs border border-gray-200 rounded-lg px-2 py-1.5 w-28 bg-white input-dcomc-focus">
                                                <button type="submit" class="btn-primary text-xs px-3 py-1.5">Update</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-8 px-4 text-center font-data text-gray-500">No assessments available.</td>
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
