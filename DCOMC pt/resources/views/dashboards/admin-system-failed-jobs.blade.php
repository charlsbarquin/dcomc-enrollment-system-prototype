@extends('dashboards.layouts.admin-shell')

@section('title', 'Failed Jobs')

@section('content')
    <div class="flex-1 overflow-y-auto p-4 md:p-6">
        <section class="hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Failed Jobs</h1>
                    <p class="text-white/90 text-sm font-data mb-0">Retry or remove failed queue jobs.</p>
                </div>
                <a href="{{ route('admin.system.overview') }}" class="btn-back-hero shrink-0 whitespace-nowrap no-underline">← Back</a>
            </div>
        </section>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-data">{{ session('success') }}</div>
        @endif

        @if(!$enabled)
            <div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-xl p-5 font-data text-sm">
                Failed jobs table is not available. Ensure queue failed job storage is configured and migrated.
            </div>
        @else
            <div class="bg-white shadow-2xl rounded-xl border border-gray-200 overflow-hidden card-dcomc-top mb-4">
                <div class="bg-[#1E40AF] px-5 py-3">
                    <h2 class="font-heading text-base font-bold text-white">Search &amp; Filter</h2>
                </div>
                <div class="p-4">
                    <p class="text-sm text-gray-600 font-data mb-0">Failed jobs are listed below. Use Retry to re-queue or Forget to remove.</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden">
                <div class="bg-[#1E40AF] px-5 py-3">
                    <h2 class="font-heading text-base font-bold text-white">Failed jobs</h2>
                </div>
                @if($failedJobs->isEmpty())
                    @include('dashboards.partials.admin-empty-state', ['title' => 'No failed jobs', 'text' => 'The queue has no failed jobs at the moment.'])
                @else
                    <div class="overflow-x-auto admin-table-wrap">
                        <table class="min-w-full divide-y divide-gray-200 font-data">
                            <thead class="table-header-dcomc">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase border-0 text-white">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase border-0 text-white">Connection</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase border-0 text-white">Queue</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase border-0 text-white">Failed at</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase border-0 text-white">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach($failedJobs as $job)
                                    <tr class="hover:bg-blue-50/50 transition-colors">
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ $job->id }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $job->connection }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $job->queue }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $job->failed_at }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <form method="POST" action="{{ route('admin.system.failed-jobs.retry', ['id' => $job->id]) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="btn-primary text-xs py-1.5 px-3">Retry</button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.system.failed-jobs.forget', ['id' => $job->id]) }}" class="inline" onsubmit="return confirm('Delete failed job {{ $job->id }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="px-3 py-1.5 rounded text-xs font-semibold bg-red-600 hover:bg-red-700 text-white transition">Forget</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(method_exists($failedJobs, 'links'))
                    <div class="admin-pagination px-4 py-3 border-t border-gray-200">
                        {{ $failedJobs->links() }}
                    </div>
                    @endif
                @endif
            </div>
        @endif
    </div>
@endsection
