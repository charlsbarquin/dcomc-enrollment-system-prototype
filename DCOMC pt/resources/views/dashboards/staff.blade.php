<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - DCOMC</title>
    @include('layouts.partials.offline-assets')
</head>
<body class="dashboard-wrap">

    @include('dashboards.partials.staff-sidebar')

    <main class="dashboard-main d-flex flex-column overflow-hidden">
        <div class="p-4 flex-grow-1 overflow-auto">
            @if(session('role_switch.active'))
                <div class="alert alert-warning mb-4">
                    <p class="fw-semibold mb-1">Admin role switch is active (mirroring STAFF).</p>
                    <p class="small mb-3">Logout is disabled until you switch back to admin.</p>
                    <form method="POST" action="{{ route('admin.role-switch.stop') }}" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-warning btn-sm">Switch Back to Admin</button>
                    </form>
                </div>
            @endif

            <div class="card shadow-sm border-start border-primary border-4">
                <div class="card-body">
                    <h1 class="h4 fw-bold mb-2">Welcome, {{ Auth::user()->name }}!</h1>
                    <p class="text-muted mb-2">You are securely logged into the Staff/Accounting Dashboard.</p>
                    <p class="small mb-0">Role: <span class="badge bg-primary">{{ strtoupper(Auth::user()->role) }}</span></p>
                </div>
            </div>
        </div>
    </main>

</body>
</html>