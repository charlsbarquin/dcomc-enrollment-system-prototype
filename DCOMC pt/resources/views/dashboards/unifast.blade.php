<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniFAST Dashboard - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">

    @include('dashboards.partials.unifast-sidebar')

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full">
                @if(session('role_switch.active'))
                    <div class="mb-6 p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-sm font-data shadow-sm">
                        <p class="font-semibold mb-1">Admin role switch is active (mirroring UNIFAST).</p>
                        <p class="mb-3">Logout is disabled until you switch back to admin.</p>
                        <form method="POST" action="{{ route('admin.role-switch.stop') }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-secondary">Switch Back to Admin</button>
                        </form>
                    </div>
                @endif

                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">UniFAST Dashboard</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">You are securely logged into the UniFAST portal.</p>
                        </div>
                    </div>
                </section>

                <div class="bg-white shadow-2xl rounded-xl border border-gray-200 p-6">
                    <h2 class="font-heading text-lg font-bold text-gray-800 mb-2">Welcome, {{ Auth::user()->name }}!</h2>
                    <p class="text-gray-600 font-data mb-2">You are securely logged into the UniFAST Dashboard.</p>
                    <p class="text-sm text-gray-500 font-data">Role: <span class="pill pill-neutral">{{ strtoupper(Auth::user()->role) }}</span></p>
                </div>
            </div>
        </div>
    </main>

</body>
</html>
