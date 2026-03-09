<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Year Level Settings - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
</head>
@php $set = request()->routeIs('admin.settings.*') ? 'admin.settings' : 'registrar.settings'; $isAdmin = request()->routeIs('admin.*'); $dashRoute = $isAdmin ? 'admin.dashboard' : 'registrar.dashboard'; @endphp
<body class="{{ $isAdmin ? 'dashboard-wrap bg-[#F1F5F9] min-h-screen flex overflow-x-hidden' : 'bg-[#F1F5F9] min-h-screen flex overflow-x-hidden' }} text-gray-800 font-data">
    @include('dashboards.partials.role-sidebar')
    @if($isAdmin)
    @include('dashboards.partials.admin-loading-bar')
    @endif

    <main class="{{ $isAdmin ? 'dashboard-main flex-1 flex flex-col min-w-0 overflow-hidden' : 'flex-1 flex flex-col min-w-0 overflow-hidden' }}">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full max-w-4xl mx-auto">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-data shadow-sm">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-data">
                        <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif

                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Year Level Settings</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Add custom year levels for future registration and deployment functions.</p>
                        </div>
                        <a href="{{ route($dashRoute) }}" class="btn-back-hero shrink-0 whitespace-nowrap">← Back to Dashboard</a>
                    </div>
                </section>

                <div class="bg-white shadow-2xl rounded-xl border border-gray-200 px-6 py-5 mb-6 card-dcomc-top">
                    <h2 class="font-heading text-lg font-bold text-gray-800 mb-4">Add Year Level</h2>
                    <form method="POST" action="{{ route($set . '.year-levels.store') }}" class="flex flex-col md:flex-row gap-3">
                        @csrf
                        <input type="text" name="name" placeholder="e.g., 1st Year, 5th Year, Graduate Level" value="{{ old('name') }}" class="flex-1 border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus">
                        <button type="submit" class="btn-primary">Add Year Level</button>
                    </form>
                </div>

                <div class="rounded-xl overflow-hidden border border-gray-200 shadow-2xl bg-white">
                    <div class="bg-[#1E40AF] px-6 py-4">
                        <h2 class="font-heading text-lg font-bold text-white">Year Level Records</h2>
                    </div>
                    <div class="p-6">
                        @if($yearLevels->isEmpty())
                            <p class="text-sm text-gray-500 font-data">No year levels yet.</p>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($yearLevels as $yearLevel)
                                    <div class="border border-gray-200 rounded-xl p-4 bg-white hover:bg-blue-50/30 transition-colors flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-gray-800 font-data">{{ $yearLevel->name }}</p>
                                            <p class="text-xs text-gray-500 mt-1 font-data">Added {{ $yearLevel->created_at->diffForHumans() }}</p>
                                            <p class="text-xs mt-1 font-data {{ $yearLevel->is_active ? 'text-green-700' : 'text-gray-500' }}">
                                                {{ $yearLevel->is_active ? 'Visible in system functions' : 'Hidden from system functions' }}
                                            </p>
                                        </div>
                                        <form method="POST" action="{{ route($set . '.year-levels.toggle', $yearLevel->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <label class="inline-flex items-center gap-2 text-xs text-gray-600 cursor-pointer font-data">
                                                <input type="checkbox" onchange="this.form.submit()" {{ $yearLevel->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-[#1E40AF] focus:ring-[#1E40AF]">
                                                <span>Show</span>
                                            </label>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
