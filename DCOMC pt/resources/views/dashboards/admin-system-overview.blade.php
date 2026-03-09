@extends('dashboards.layouts.admin-shell')

@section('title', 'System Overview')

@section('content')
    <div class="flex-1 overflow-y-auto p-4 md:p-6">
        <section class="hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">System Overview</h1>
                    <p class="text-white/90 text-sm font-data mb-0">Cache, queue, and quick links.</p>
                </div>
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold {{ $maintenance ? 'bg-red-500/90 text-white' : 'bg-green-500/90 text-white' }}">
                    {{ $maintenance ? 'Maintenance mode: ON' : 'Maintenance mode: OFF' }}
                </span>
            </div>
        </section>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-data shadow-sm">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden border-t-[10px] border-t-[#1E40AF]">
                <div class="bg-[#1E40AF] px-5 py-3">
                    <h2 class="font-heading text-base font-bold text-white">Cache</h2>
                </div>
                <div class="p-5">
                    <p class="text-sm text-gray-600 font-data mb-0">Default driver: <strong class="text-gray-800">{{ $cacheDriver }}</strong></p>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden border-t-[10px] border-t-[#1E40AF]">
                <div class="bg-[#1E40AF] px-5 py-3">
                    <h2 class="font-heading text-base font-bold text-white">Queue</h2>
                </div>
                <div class="p-5 space-y-1">
                    <p class="text-sm text-gray-600 font-data mb-0">Default connection: <strong class="text-gray-800">{{ $queueDriver }}</strong></p>
                    <p class="text-sm text-gray-600 font-data mb-0">Pending jobs: <strong class="text-gray-800">{{ $pendingJobs === null ? 'N/A' : $pendingJobs }}</strong></p>
                    <p class="text-sm text-gray-600 font-data mb-0">Failed jobs: <strong class="text-gray-800">{{ $failedJobs === null ? 'N/A' : $failedJobs }}</strong></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden border-t-[10px] border-t-[#1E40AF]">
            <div class="bg-[#1E40AF] px-5 py-3">
                <h2 class="font-heading text-base font-bold text-white">Quick links</h2>
            </div>
            <div class="p-5 flex flex-wrap gap-2">
                <a href="{{ route('admin.system.backup') }}" class="btn-secondary text-sm no-underline">Backup</a>
                <a href="{{ route('admin.system.logs') }}" class="btn-secondary text-sm no-underline">Application logs</a>
                <a href="{{ route('admin.system.failed-jobs') }}" class="btn-secondary text-sm no-underline">Failed jobs</a>
                <a href="{{ route('admin.system.maintenance') }}" class="btn-secondary text-sm no-underline">Maintenance mode</a>
                <a href="{{ route('admin.audit-logs.index') }}" class="btn-secondary text-sm no-underline">Audit log</a>
            </div>
        </div>
    </div>
@endsection
