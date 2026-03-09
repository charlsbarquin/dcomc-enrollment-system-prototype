<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dean Dashboard - DCOMC</title>
    @include('layouts.partials.offline-assets')
</head>
<body class="dashboard-wrap">

    @include('dashboards.partials.dean-sidebar')

    <main class="dashboard-main d-flex flex-column overflow-hidden">
        <div class="p-4 flex-grow-1 overflow-auto">
            @if(session('role_switch.active'))
                <div class="alert alert-warning mb-4">
                    <p class="fw-semibold mb-1">Admin role switch is active (mirroring DEAN).</p>
                    <p class="small mb-3">Logout is disabled until you switch back to admin.</p>
                    <form method="POST" action="{{ route('admin.role-switch.stop') }}" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-warning btn-sm">Switch Back to Admin</button>
                    </form>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger mb-4">{{ session('error') }}</div>
            @endif

            <div class="card shadow-sm border-start border-warning border-4">
                <div class="card-body">
                    <h1 class="h4 fw-bold mb-2">Welcome, {{ Auth::user()->name }}!</h1>
                    <p class="text-muted mb-2">You are securely logged into the Dean Dashboard.</p>
                    <p class="small mb-0">Role: <span class="badge bg-warning text-dark">{{ strtoupper(Auth::user()->role) }}</span></p>
                    <p class="small text-muted mb-0">Department: <strong>{{ Auth::user()->department?->name ?: 'Not set' }}</strong> @if(Auth::user()->program_scope)(Scope: {{ Auth::user()->program_scope }})@endif</p>
                </div>
            </div>
        </div>
    </main>

</body>
</html>
