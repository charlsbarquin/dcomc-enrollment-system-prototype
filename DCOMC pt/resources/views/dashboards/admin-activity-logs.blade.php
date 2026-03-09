@extends('dashboards.layouts.admin-shell')

@section('title', 'Activity Log')

@section('content')
    <div class="flex-1 overflow-y-auto p-4 md:p-6">
        <section class="hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Activity Log</h1>
                    <p class="text-white/90 text-sm font-data mb-0">Logins, logouts, and write actions. Records are automatically deleted after <strong>4 days</strong>.</p>
                </div>
                <a href="{{ route('admin.system.overview') }}" class="btn-back-hero shrink-0 whitespace-nowrap no-underline">← Back</a>
            </div>
        </section>

        <form method="get" action="{{ route('admin.activity-logs.index') }}" class="bg-white shadow-2xl rounded-xl border border-gray-200 overflow-hidden card-dcomc-top mb-6">
            <div class="bg-[#1E40AF] px-5 py-3">
                <h2 class="font-heading text-base font-bold text-white">Search &amp; Filter</h2>
            </div>
            <div class="p-4 flex flex-wrap items-end gap-3">
                <div class="min-w-[180px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1 font-data" for="action">Action</label>
                    <input id="action" name="action" value="{{ $action }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-full font-data input-dcomc-focus" placeholder="login, logout, request">
                </div>
                <div class="min-w-[160px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1 font-data" for="role">Role</label>
                    <select id="role" name="role" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-full font-data input-dcomc-focus">
                        <option value="">All</option>
                        <option value="student" {{ $role === 'student' ? 'selected' : '' }}>Student</option>
                        <option value="registrar" {{ $role === 'registrar' ? 'selected' : '' }}>Registrar</option>
                        <option value="staff" {{ $role === 'staff' ? 'selected' : '' }}>Staff</option>
                        <option value="dean" {{ $role === 'dean' ? 'selected' : '' }}>Dean</option>
                        <option value="unifast" {{ $role === 'unifast' ? 'selected' : '' }}>UniFast</option>
                        <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>Admin</option>
                    </select>
                </div>
                <div class="min-w-[140px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1 font-data" for="user_id">User ID</label>
                    <input id="user_id" name="user_id" value="{{ $userId }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-full font-data input-dcomc-focus" placeholder="e.g. 12">
                </div>
                <div class="flex-1 min-w-[220px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1 font-data" for="route">Route contains</label>
                    <input id="route" name="route" value="{{ $routeName }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-full font-data input-dcomc-focus" placeholder="e.g. registrar.responses.approve">
                </div>
                <button type="submit" class="btn-primary">Filter</button>
            </div>
        </form>

        <div class="bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden">
            <div class="bg-[#1E40AF] px-5 py-3">
                <h2 class="font-heading text-base font-bold text-white">Activity records</h2>
            </div>
            @if($logs->isEmpty())
                @include('dashboards.partials.admin-empty-state', ['title' => 'No activity records', 'text' => 'No entries match your filters or the log is empty. Records are kept for 4 days.'])
            @else
                <div class="overflow-x-auto admin-table-wrap">
                    <table class="min-w-full divide-y divide-gray-200 font-data">
                        <thead class="table-header-dcomc">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase border-0 text-white">Time</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase border-0 text-white">User</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase border-0 text-white">Role</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase border-0 text-white">Action</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase border-0 text-white">Description</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase border-0 text-white">Route</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase border-0 text-white">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach($logs as $log)
                                <tr class="hover:bg-blue-50/50 transition-colors">
                                    <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">{{ $log->created_at->format('D M j, Y g:i A') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <div class="font-semibold">{{ $log->user?->name ?? '—' }}</div>
                                        <div class="text-xs text-gray-500">ID: {{ $log->user_id ?? '—' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $log->role ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ $log->action }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 max-w-sm">{{ $log->description ?? '—' }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-600 font-mono max-w-xs break-words">{{ $log->route_name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $log->status_code ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="admin-pagination px-4 py-3 border-t border-gray-200">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
