<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Monitoring - UNIFAST - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.unifast-styles')
</head>
<body class="dashboard-wrap font-data unifast-focus-visible">
    @include('dashboards.partials.unifast-sidebar')

    <main class="dashboard-main d-flex flex-column overflow-hidden">
        <div class="p-4 flex-grow-1 overflow-auto">
            {{-- Hero: same design as System Reporting & Institutional Data --}}
            <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div>
                        <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Assessment Monitoring</h1>
                        <p class="text-white/90 text-sm sm:text-base font-data">Review and manage scholarship assessments and eligibility with live system data.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <a href="{{ route('unifast.assessments.export', array_filter($filters ?? [])) }}" class="btn-dcomc-white-pill">Export CSV</a>
                        <a href="{{ route('unifast.dashboard') }}" class="btn-dcomc-white-pill">Back to Dashboard</a>
                    </div>
                </div>
            </section>

            @if(session('success'))
                <div class="alert alert-success mb-4 rounded-xl border-0 shadow-sm font-data" role="alert">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger mb-4 rounded-xl border-0 shadow-sm font-data" role="alert">
                    @foreach($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Toast for Save (client-side) --}}
            <div id="unifast-toast" class="position-fixed top-0 end-0 p-3 font-data" style="z-index: 1100; display: none;" role="status" aria-live="polite">
                <div class="toast show border-0 shadow-lg rounded-xl" role="alert">
                    <div class="toast-body d-flex align-items-center gap-2 text-success fw-semibold">
                        <span id="unifast-toast-text">Saved.</span>
                    </div>
                </div>
            </div>

            {{-- Search & Filter Card --}}
            <div class="card shadow-sm border border-gray-200 rounded-xl mb-4">
                <div class="card-body">
                    <h2 class="h6 fw-bold text-gray-800 mb-3 font-heading">Search & filter</h2>
                    <form method="GET" class="row g-3" id="unifast-filter-form">
                        <div class="col-12 col-md-3">
                            <label for="filter_search" class="form-label small text-uppercase fw-bold text-gray-600 mb-1">Search by name or email</label>
                            <input type="text" name="search" id="filter_search" value="{{ $filters['search'] ?? '' }}" placeholder="Type name or email…" class="form-control form-control-sm border border-gray-300 rounded-lg input-dcomc-focus font-data" maxlength="120">
                        </div>
                        <div class="col-6 col-md-2">
                            <label for="filter_status" class="form-label small text-uppercase fw-bold text-gray-600 mb-1">Status</label>
                            <select name="status" id="filter_status" class="form-select form-select-sm border border-gray-300 rounded-lg input-dcomc-focus font-data">
                                <option value="">All Status</option>
                                <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="reviewed" {{ ($filters['status'] ?? '') === 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                                <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label for="filter_from_date" class="form-label small text-uppercase fw-bold text-gray-600 mb-1">From date</label>
                            <input type="date" name="from_date" id="filter_from_date" value="{{ $filters['from_date'] ?? '' }}" class="form-control form-control-sm border border-gray-300 rounded-lg input-dcomc-focus font-data">
                        </div>
                        <div class="col-6 col-md-2">
                            <label for="filter_to_date" class="form-label small text-uppercase fw-bold text-gray-600 mb-1">To date</label>
                            <input type="date" name="to_date" id="filter_to_date" value="{{ $filters['to_date'] ?? '' }}" class="form-control form-control-sm border border-gray-300 rounded-lg input-dcomc-focus font-data">
                        </div>
                        <div class="col-6 col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-sm px-4 py-2 rounded-xl font-bold text-white border-0 btn-dcomc-primary font-heading">Apply filters</button>
                            <a href="{{ route('unifast.assessments') }}" class="btn btn-sm btn-outline-secondary rounded-xl font-data">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Table: Solid DCOMC Blue header, hover rows, Roboto --}}
            <div class="bg-white shadow-2xl rounded-xl border border-gray-200 overflow-hidden">
                <table class="table table-hover align-middle mb-0 font-data" aria-describedby="assessments-table-caption">
                    <caption id="assessments-table-caption" class="visually-hidden">UNIFAST assessment monitoring: student, income class, status, eligibility, and actions.</caption>
                    <thead>
                        <tr class="table-header-dcomc text-white">
                            <th scope="col" class="py-3 px-4 font-heading font-semibold">Student</th>
                            <th scope="col" class="py-3 px-4 font-heading font-semibold">Income class</th>
                            <th scope="col" class="py-3 px-4 font-heading font-semibold">Assessment status</th>
                            <th scope="col" class="py-3 px-4 font-heading font-semibold">UNIFAST eligibility</th>
                            <th scope="col" class="py-3 px-4 font-heading font-semibold text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody class="font-data">
                        @forelse($assessments as $assessment)
                            <tr class="hover-bg-blue-50">
                                <td class="py-3 px-4">{{ $assessment->student?->name ?? 'N/A' }}</td>
                                <td class="py-3 px-4">{{ $assessment->income_classification ?? 'N/A' }}</td>
                                <td class="py-3 px-4 text-uppercase">{{ $assessment->assessment_status }}</td>
                                <td class="py-3 px-4 fw-semibold {{ $assessment->unifast_eligible ? 'text-success' : 'text-danger' }}">
                                    {{ $assessment->unifast_eligible ? 'Eligible' : 'Not eligible' }}
                                </td>
                                <td class="py-3 px-4 text-end">
                                    <form method="POST" action="{{ route('unifast.assessments.eligibility', $assessment->id) }}" class="d-inline-flex align-items-center gap-2 unifast-eligibility-form" data-assessment-id="{{ $assessment->id }}">
                                        @csrf
                                        @method('PATCH')
                                        <label for="eligibility-{{ $assessment->id }}" class="visually-hidden">Set UNIFAST eligibility for {{ $assessment->student?->name ?? 'student' }}</label>
                                        <select name="unifast_eligible" id="eligibility-{{ $assessment->id }}" class="form-select form-select-sm border border-gray-300 rounded-lg input-dcomc-focus font-data" style="width: auto; min-width: 7rem;">
                                            <option value="1" {{ $assessment->unifast_eligible ? 'selected' : '' }}>Eligible</option>
                                            <option value="0" {{ !$assessment->unifast_eligible ? 'selected' : '' }}>Not eligible</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm px-3 py-2 rounded-xl font-bold text-white border-0 btn-dcomc-primary font-heading unifast-save-btn">Save</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-0">
                                    <div class="text-center py-12 px-6">
                                        <div class="mb-4 text-gray-400">
                                            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </div>
                                        <p class="font-data text-gray-600 fw-semibold mb-2">No assessments found</p>
                                        <p class="font-data text-gray-500 small mb-4">Try adjusting your search or filters, or check back later.</p>
                                        <a href="{{ route('unifast.dashboard') }}" class="btn btn-sm px-4 py-2 rounded-xl font-bold text-white border-0 btn-dcomc-primary font-heading text-decoration-none">Back to Dashboard</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var toast = document.getElementById('unifast-toast');
        var toastText = document.getElementById('unifast-toast-text');
        document.querySelectorAll('.unifast-eligibility-form').forEach(function (form) {
            form.addEventListener('submit', function () {
                var btn = form.querySelector('.unifast-save-btn');
                if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }
            });
        });
        @if(session('success'))
        if (toast && toastText) {
            toastText.textContent = {{ json_encode(session('success')) }};
            toast.style.display = 'block';
            setTimeout(function () { toast.style.display = 'none'; }, 4000);
        }
        @endif
    });
    </script>
</body>
</html>
