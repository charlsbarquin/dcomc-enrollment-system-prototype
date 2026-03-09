<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
    <style>
        .admin-chart-card { background: #fff; border-radius: 0.75rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid #e5e7eb; overflow: hidden; }
        .admin-chart-card .admin-card-strip { height: 10px; background: #1E40AF; }
        .admin-stat-card { background: #fff; border-radius: 0.75rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid #e5e7eb; overflow: hidden; text-decoration: none; color: inherit; display: block; transition: box-shadow 0.2s; }
        .admin-stat-card:hover { box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); }
        .admin-stat-card .admin-card-strip { height: 10px; background: #1E40AF; }
        .admin-dashboard-main .table-dcomc thead th { background: #1E40AF; color: #fff; font-family: 'Figtree', sans-serif; font-weight: 600; padding: 0.75rem 1rem; }
        .admin-dashboard-main .list-group-item { font-family: 'Roboto', sans-serif; }
    </style>
</head>
<body class="dashboard-wrap bg-[#F1F5F9]">

    @include('dashboards.partials.admin-sidebar')

    <main class="dashboard-main d-flex flex-column overflow-hidden admin-dashboard-main">
        {{-- Hero: DCOMC Blue gradient --}}
        <section class="hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mx-4 mt-4 mb-0">
            <div class="d-flex flex-column flex-md-row align-items-start justify-content-between gap-4">
                <div>
                    <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">System Administrator Control</h1>
                    <p class="text-white/90 text-sm sm:text-base font-data mb-0">DCOMC ERP hub. Monitor enrollment, system health, and institutional reports.</p>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 shrink-0">
                    @if(session('role_switch.active'))
                        @php
                            $mirrorLabel = strtoupper(session('role_switch.as_role') ?? '');
                            if ($mirrorLabel === 'DEAN' && !empty(session('role_switch.department_id'))) {
                                $mirrorDept = \App\Models\Department::find(session('role_switch.department_id'));
                                $mirrorLabel .= $mirrorDept ? ' (' . $mirrorDept->name . ')' : '';
                            }
                        @endphp
                        <span class="badge bg-warning text-dark">Mirroring: {{ $mirrorLabel }}</span>
                        <form method="POST" action="{{ route('admin.role-switch.stop') }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-warning btn-sm">Switch Back</button>
                        </form>
                    @else
                        @php $deanDepartments = \App\Models\Department::whereIn('name', [\App\Models\Department::NAME_EDUCATION, \App\Models\Department::NAME_ENTREPRENEURSHIP])->orderBy('name')->get(); @endphp
                        <form id="adminRoleSwitchForm" method="POST" action="{{ route('admin.role-switch.start') }}" class="d-inline-flex flex-wrap align-items-center gap-2">
                            @csrf
                            <select name="role" id="adminRoleSelect" class="form-select form-select-sm" style="width: auto; min-width: 160px;">
                                <option value="" selected>Select role to mirror...</option>
                                <option value="student">Student</option>
                                <option value="registrar">Registrar</option>
                                <option value="staff">Staff</option>
                                <option value="unifast">UniFAST</option>
                                <option value="dean">Dean</option>
                            </select>
                            <div id="adminDeanChoice" class="d-none d-flex align-items-center gap-2">
                                <label for="adminDeanDept" class="small text-white/90 mb-0">as</label>
                                <select name="department_id" id="adminDeanDept" class="form-select form-select-sm" style="width: auto;">
                                    <option value="">— Educ / Entrep —</option>
                                    @foreach($deanDepartments as $d)
                                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-light btn-sm">Switch to Dean</button>
                            </div>
                        </form>
                        <script>
                            document.getElementById('adminRoleSelect') && document.getElementById('adminRoleSelect').addEventListener('change', function() {
                                var v = this.value, deanChoice = document.getElementById('adminDeanChoice'), form = this.form;
                                if (v === 'dean') { deanChoice.classList.remove('d-none'); deanChoice.classList.add('d-flex'); document.getElementById('adminDeanDept').required = true; }
                                else if (v) { deanChoice.classList.add('d-none'); document.getElementById('adminDeanDept').required = false; document.getElementById('adminDeanDept').value = ''; form.submit(); }
                                else { deanChoice.classList.add('d-none'); }
                            });
                        </script>
                    @endif
                    <a href="{{ route('admin.analytics', array_filter($filters ?? [])) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold bg-white text-[#1E40AF] hover:bg-gray-100 no-underline font-data border border-white/30">Analytics</a>
                </div>
            </div>
        </section>

        <div class="p-4 flex-grow-1 overflow-auto">
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="GET" action="{{ route('admin.dashboard') }}" class="row g-2 align-items-center mb-4">
                <div class="col-auto">
                    <label class="form-label small mb-0 font-data">Academic Year:</label>
                    <select name="academic_year" class="form-select form-select-sm" style="width: auto; min-width: 140px;" onchange="this.form.submit()">
                        <option value="">All / Session default</option>
                        @foreach($academicYears ?? [] as $ay)
                            <option value="{{ $ay }}" {{ ($filters['academic_year'] ?? '') === $ay ? 'selected' : '' }}>{{ $ay }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-0 font-data">Semester:</label>
                    <select name="semester" class="form-select form-select-sm" style="width: auto; min-width: 120px;" onchange="this.form.submit()">
                        <option value="">All</option>
                        @foreach($semesters ?? [] as $sem)
                            <option value="{{ $sem }}" {{ ($filters['semester'] ?? '') === $sem ? 'selected' : '' }}>{{ $sem }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="admin-btn-primary text-sm">Apply</button>
                </div>
            </form>

            @php $totalEnrollment = ($qaCounts['needs_correction'] ?? 0) + ($qaCounts['approved'] ?? 0) + ($qaCounts['scheduled'] ?? 0) + ($qaCounts['completed'] ?? 0); @endphp

            {{-- Stat cards: white floating (shadow-2xl, rounded-xl) --}}
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <a href="{{ route('admin.student-status', $filters ?? []) }}" class="admin-stat-card">
                        <div class="admin-card-strip"></div>
                        <div class="card-body">
                            <p class="small text-muted text-uppercase mb-0 font-data">Total Enrollment</p>
                            <p class="h4 fw-bold text-[#1E40AF] mb-0 font-heading">{{ number_format($totalEnrollment) }}</p>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="{{ route('admin.student-status', ['process_status' => 'needs_correction'] + ($filters ?? [])) }}" class="admin-stat-card">
                        <div class="admin-card-strip"></div>
                        <div class="card-body">
                            <p class="small text-muted text-uppercase mb-0 font-data">Needs Correction</p>
                            <p class="h4 fw-bold text-[#1E40AF] mb-0 font-heading">{{ $qaCounts['needs_correction'] ?? 0 }}</p>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="{{ route('admin.student-status', ['process_status' => 'scheduled'] + ($filters ?? [])) }}" class="admin-stat-card">
                        <div class="admin-card-strip"></div>
                        <div class="card-body">
                            <p class="small text-muted text-uppercase mb-0 font-data">Approved / Scheduled</p>
                            <p class="h4 fw-bold text-[#1E40AF] mb-0 font-heading">{{ ($qaCounts['approved'] ?? 0) + ($qaCounts['scheduled'] ?? 0) }}</p>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="{{ route('admin.student-status', ['process_status' => 'completed'] + ($filters ?? [])) }}" class="admin-stat-card">
                        <div class="admin-card-strip"></div>
                        <div class="card-body">
                            <p class="small text-muted text-uppercase mb-0 font-data">Completed</p>
                            <p class="h4 fw-bold text-[#1E40AF] mb-0 font-heading">{{ $qaCounts['completed'] ?? 0 }}</p>
                        </div>
                    </a>
                </div>
            </div>

            {{-- 6-chart grid: white cards with blue header strip --}}
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="admin-chart-card h-100">
                        <div class="admin-card-strip"></div>
                        <div class="card-body">
                            <h2 class="h6 font-heading fw-bold text-gray-800 mb-3">Executive Summary</h2>
                            @php $pct = $totalEnrollment > 0 ? round(100 * ($qaCounts['completed'] ?? 0) / $totalEnrollment, 0) : 0; @endphp
                            <p class="small text-muted text-uppercase mb-0 font-data">Process completion</p>
                            <p class="h4 fw-bold text-[#1E40AF] mb-2 font-heading">{{ $pct }}%</p>
                            <div class="progress" style="height: 6px; background: #e2e8f0;">
                                <div class="progress-bar" role="progressbar" style="width: {{ min($pct, 100) }}%; background: #1E40AF;"></div>
                            </div>
                            <p class="small text-muted mt-2 mb-1 font-data">Enrollment trends</p>
                            <a href="{{ route('admin.analytics', array_filter($filters ?? [])) }}" class="text-sm font-semibold text-[#1E40AF] hover:text-[#1D3A8A] no-underline font-data">View full Analytics →</a>
                            @if(isset($trendRows) && $trendRows->isNotEmpty())
                                <div class="d-flex align-items-end mt-2" style="height: 40px; gap: 2px;">
                                    @php $maxTrend = $trendRows->max('count') ?: 1; @endphp
                                    @foreach($trendRows as $t)
                                        <div class="flex-grow-1 rounded-top" title="{{ $t->period }}: {{ $t->count }}" style="height: {{ max(8, 100 * $t->count / $maxTrend) }}%; min-width: 4px; background: #1E40AF;"></div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="admin-chart-card h-100">
                        <div class="admin-card-strip"></div>
                        <div class="card-body">
                            <h3 class="h6 font-heading fw-bold text-gray-800 mb-3">Approved vs Not Approved</h3>
                            <div class="d-flex align-items-center gap-3">
                                <div class="flex-shrink-0" style="width: 140px; height: 140px;">
                                    <canvas id="approvedDonutChart" width="140" height="140"></canvas>
                                </div>
                                <div class="small font-data">
                                    <p class="mb-0">Approved: <strong class="text-[#1E40AF]">{{ $approvedCount ?? 0 }}</strong></p>
                                    <p class="mb-0">Not approved: <strong class="text-[#1E40AF]">{{ $notApprovedCount ?? 0 }}</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="admin-chart-card h-100">
                        <div class="admin-card-strip"></div>
                        <div class="card-body">
                            <h3 class="h6 font-heading fw-bold text-gray-800 mb-2">Enrollees by program</h3>
                            <ul class="list-group list-group-flush font-data small">
                                @forelse($programBreakdown ?? [] as $row)
                                    <li class="list-group-item d-flex justify-content-between px-0 py-1">
                                        <span class="text-truncate me-2">{{ $row->label ?: 'N/A' }}</span>
                                        <span class="fw-semibold text-[#1E40AF]">{{ number_format($row->count) }}</span>
                                    </li>
                                @empty
                                    <li class="list-group-item text-muted px-0 py-1">No data</li>
                                @endforelse
                            </ul>
                            <a href="{{ route('admin.reports', array_filter($filters ?? [])) }}" class="text-sm font-semibold mt-2 inline-block text-[#1E40AF] hover:text-[#1D3A8A] no-underline font-data">Open Reports →</a>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="admin-chart-card h-100">
                        <div class="admin-card-strip"></div>
                        <div class="card-body">
                            <h3 class="h6 font-heading fw-bold text-gray-800 mb-2">Enrollees by location</h3>
                            <ul class="list-group list-group-flush font-data small">
                                @foreach($locationCounts ?? ['Daraga' => 0, 'Legazpi' => 0, 'Guinobatan' => 0] as $loc => $cnt)
                                    <li class="list-group-item d-flex justify-content-between px-0 py-1">
                                        <span>{{ $loc }}</span>
                                        <span class="fw-semibold text-[#1E40AF]">{{ number_format($cnt) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            <a href="{{ route('admin.reports', array_filter($filters ?? [])) }}" class="text-sm font-semibold mt-2 inline-block text-[#1E40AF] hover:text-[#1D3A8A] no-underline font-data">Open Reports →</a>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="admin-chart-card h-100">
                        <div class="admin-card-strip"></div>
                        <div class="card-body">
                            <h3 class="h6 font-heading fw-bold text-gray-800 mb-2">Needs Correction</h3>
                            <ul class="list-group list-group-flush list-group-numbered font-data small">
                                @forelse($needsCorrection ?? [] as $row)
                                    <li class="list-group-item px-0 py-1">{{ $row->user?->name ?? 'N/A' }}</li>
                                @empty
                                    <li class="list-group-item px-0 py-1 text-muted">No records</li>
                                @endforelse
                            </ul>
                            <a href="{{ route('admin.student-status', ['process_status' => 'needs_correction'] + ($filters ?? [])) }}" class="text-sm font-semibold mt-2 inline-block text-[#1E40AF] hover:text-[#1D3A8A] no-underline font-data">View all →</a>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="admin-chart-card h-100">
                        <div class="admin-card-strip"></div>
                        <div class="card-body">
                            <h3 class="h6 font-heading fw-bold text-gray-800 mb-2">Approved Unscheduled</h3>
                            <ul class="list-group list-group-flush list-group-numbered font-data small">
                                @forelse($approvedUnscheduled ?? [] as $row)
                                    <li class="list-group-item px-0 py-1">{{ $row->user?->name ?? 'N/A' }}</li>
                                @empty
                                    <li class="list-group-item px-0 py-1 text-muted">No records</li>
                                @endforelse
                            </ul>
                            <a href="{{ route('admin.student-status', ['process_status' => 'approved'] + ($filters ?? [])) }}" class="text-sm font-semibold mt-2 inline-block text-[#1E40AF] hover:text-[#1D3A8A] no-underline font-data">View all →</a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Scheduled Pending list (extra card to keep data) --}}
            <div class="row g-3">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="admin-chart-card h-100">
                        <div class="admin-card-strip"></div>
                        <div class="card-body">
                            <h3 class="h6 font-heading fw-bold text-gray-800 mb-2">Scheduled Pending Assessment</h3>
                            <ul class="list-group list-group-flush list-group-numbered font-data small">
                                @forelse($scheduledPending ?? [] as $row)
                                    <li class="list-group-item px-0 py-1">{{ $row->user?->name ?? 'N/A' }}</li>
                                @empty
                                    <li class="list-group-item px-0 py-1 text-muted">No records</li>
                                @endforelse
                            </ul>
                            <a href="{{ route('admin.student-status', ['process_status' => 'scheduled'] + ($filters ?? [])) }}" class="text-sm font-semibold mt-2 inline-block text-[#1E40AF] hover:text-[#1D3A8A] no-underline font-data">View all →</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
(function () {
    var approved = {{ (int) ($approvedCount ?? 0) }};
    var notApproved = {{ (int) ($notApprovedCount ?? 0) }};
    var ctx = document.getElementById('approvedDonutChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Approved', 'Not Approved'],
            datasets: [{
                data: [approved, notApproved],
                backgroundColor: ['#1E40AF', '#94a3b8'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
})();
    </script>
</body>
</html>
