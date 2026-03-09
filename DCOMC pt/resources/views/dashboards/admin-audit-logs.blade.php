@extends('dashboards.layouts.admin-shell')

@section('title', 'Audit Log')

@section('content')
    <div class="flex-1 overflow-y-auto p-4 md:p-6">
        <section class="hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Audit Log</h1>
                    <p class="text-white/90 text-sm font-data mb-0">System audit trail by actor and action.</p>
                </div>
                <a href="{{ route('admin.system.overview') }}" class="btn-back-hero shrink-0 whitespace-nowrap no-underline">← Back</a>
            </div>
        </section>

        <form method="get" action="{{ route('admin.audit-logs.index') }}" class="bg-white shadow-2xl rounded-xl border border-gray-200 overflow-hidden card-dcomc-top mb-6">
            <div class="bg-[#1E40AF] px-5 py-3">
                <h2 class="font-heading text-base font-bold text-white">Search &amp; Filter</h2>
            </div>
            <div class="p-4 flex flex-wrap items-end gap-3">
                <div class="min-w-[220px] flex-1">
                    <label class="block text-xs font-medium text-gray-500 mb-1 font-data" for="action">Action contains</label>
                    <input id="action" name="action" value="{{ $action }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus" placeholder="e.g. maintenance, failed_job, feedback">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 font-data" for="role">Actor role</label>
                    <select id="role" name="role" class="border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus">
                        <option value="">All</option>
                        <option value="admin" {{ $actorRole === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="registrar" {{ $actorRole === 'registrar' ? 'selected' : '' }}>Registrar</option>
                        <option value="staff" {{ $actorRole === 'staff' ? 'selected' : '' }}>Staff</option>
                        <option value="dean" {{ $actorRole === 'dean' ? 'selected' : '' }}>Dean</option>
                        <option value="unifast" {{ $actorRole === 'unifast' ? 'selected' : '' }}>UniFast</option>
                        <option value="student" {{ $actorRole === 'student' ? 'selected' : '' }}>Student</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Filter</button>
            </div>
        </form>

        <div class="bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden">
            <div class="bg-[#1E40AF] px-5 py-3">
                <h2 class="font-heading text-base font-bold text-white">Audit log</h2>
            </div>
            @if($logs->isEmpty())
                @include('dashboards.partials.admin-empty-state', ['title' => 'No audit logs yet', 'text' => 'No audit entries match your filters.'])
            @else
                <div class="overflow-x-auto admin-table-wrap">
                    <table class="min-w-full divide-y divide-gray-200 font-data">
                        <thead class="table-header-dcomc">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase border-0 text-white">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase border-0 text-white">Actor</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase border-0 text-white">Role</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase border-0 text-white">Action</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase border-0 text-white">Target</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach($logs as $log)
                                <tr class="hover:bg-blue-50/50 transition-colors">
                                    <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">{{ $log->created_at->format('M j, Y H:i') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $log->actor?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $log->actor_role ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $log->action }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        @if($log->target_type)
                                            <span class="font-mono text-xs">{{ class_basename($log->target_type) }}#{{ $log->target_id }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
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
