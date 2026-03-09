<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Dashboard</title>
    @include('layouts.partials.offline-assets')
</head>
<body class="dashboard-wrap">

    @include('dashboards.partials.registrar-sidebar')

    <main class="dashboard-main d-flex flex-column overflow-hidden">
        <div class="p-4 flex-grow-1 overflow-auto">
            @if(session('role_switch.active'))
                <div class="alert alert-warning mb-4">
                    <p class="fw-semibold mb-1">Admin role switch is active (mirroring REGISTRAR).</p>
                    <p class="small mb-3">Logout is disabled until you switch back to admin.</p>
                    <form method="POST" action="{{ route('admin.role-switch.stop') }}" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-warning btn-sm">Switch Back to Admin</button>
                    </form>
                </div>
            @endif

            <h1 class="h4 fw-bold text-primary mb-1">Registrar Dashboard</h1>
            <p class="text-muted small mb-4">Welcome to the DCOMC Management System.</p>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <a href="{{ route('registrar.student-status', ['process_status' => 'needs_correction']) }}" class="card shadow-sm border-start border-warning border-4 text-decoration-none text-dark">
                        <div class="card-body">
                            <p class="small text-muted text-uppercase mb-0">Needs Correction</p>
                            <p class="h4 fw-bold text-primary mb-0">{{ $qaCounts['needs_correction'] ?? 0 }}</p>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="{{ route('registrar.student-status', ['process_status' => 'approved']) }}" class="card shadow-sm border-start border-success border-4 text-decoration-none text-dark">
                        <div class="card-body">
                            <p class="small text-muted text-uppercase mb-0">Approved Unscheduled</p>
                            <p class="h4 fw-bold text-primary mb-0">{{ $qaCounts['approved'] ?? 0 }}</p>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="{{ route('registrar.student-status', ['process_status' => 'scheduled']) }}" class="card shadow-sm border-start border-info border-4 text-decoration-none text-dark">
                        <div class="card-body">
                            <p class="small text-muted text-uppercase mb-0">Scheduled Pending</p>
                            <p class="h4 fw-bold text-primary mb-0">{{ $qaCounts['scheduled'] ?? 0 }}</p>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="{{ route('registrar.student-status', ['process_status' => 'completed']) }}" class="card shadow-sm border-start border-primary border-4 text-decoration-none text-dark">
                        <div class="card-body">
                            <p class="small text-muted text-uppercase mb-0">Completed</p>
                            <p class="h4 fw-bold text-primary mb-0">{{ $qaCounts['completed'] ?? 0 }}</p>
                        </div>
                    </a>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h3 class="h6 fw-semibold text-warning mb-2">Needs Correction</h3>
                            <ul class="list-group list-group-flush list-group-numbered small">
                                @forelse(($needsCorrection ?? collect()) as $row)
                                    <li class="list-group-item px-0 py-1">{{ $row->user?->name ?? 'N/A' }}</li>
                                @empty
                                    <li class="list-group-item px-0 py-1 text-muted">No records</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h3 class="h6 fw-semibold text-success mb-2">Approved Unscheduled</h3>
                            <ul class="list-group list-group-flush list-group-numbered small">
                                @forelse(($approvedUnscheduled ?? collect()) as $row)
                                    <li class="list-group-item px-0 py-1">{{ $row->user?->name ?? 'N/A' }}</li>
                                @empty
                                    <li class="list-group-item px-0 py-1 text-muted">No records</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h3 class="h6 fw-semibold text-info mb-2">Scheduled Pending Assessment</h3>
                            <ul class="list-group list-group-flush list-group-numbered small">
                                @forelse(($scheduledPending ?? collect()) as $row)
                                    <li class="list-group-item px-0 py-1">{{ $row->user?->name ?? 'N/A' }}</li>
                                @empty
                                    <li class="list-group-item px-0 py-1 text-muted">No records</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

</body>
</html>