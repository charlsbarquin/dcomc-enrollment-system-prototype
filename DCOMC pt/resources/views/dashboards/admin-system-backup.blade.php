@extends('dashboards.layouts.admin-shell')

@section('title', 'Database Backup')

@section('content')
    <div class="flex-1 overflow-y-auto p-4 md:p-6">
        <section class="hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Database Backup</h1>
                    <p class="text-white/90 text-sm font-data mb-0">Auto backup schedule and manual download.</p>
                </div>
                <a href="{{ route('admin.system.overview') }}" class="btn-back-hero shrink-0 whitespace-nowrap no-underline">← Back</a>
            </div>
        </section>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-data">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm font-data">
                <ul class="list-disc pl-5 mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="space-y-6 max-w-3xl">
            <div class="bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden border-t-[10px] border-t-[#1E40AF]">
                <div class="bg-[#1E40AF] px-5 py-3">
                    <h2 class="font-heading text-base font-bold text-white">Auto backup (daily at 5:00 PM)</h2>
                </div>
                <div class="p-5">
                    <p class="text-sm text-gray-600 font-data mb-2">
                        When ON, the database is backed up every day at 5:00 PM. The previous backup file is deleted before saving the new one.
                    </p>
                    <p class="text-sm font-data mb-3">
                        <span class="font-medium text-gray-700">Current status:</span>
                        <span class="{{ $autoBackupEnabled ? 'text-green-700 font-semibold' : 'text-gray-500' }}">{{ $autoBackupEnabled ? 'ON' : 'OFF' }}</span>
                    </p>
                    <form method="POST" action="{{ route('admin.system.backup.toggle') }}" class="inline">
                        @csrf
                        <input type="hidden" name="enabled" value="{{ $autoBackupEnabled ? '0' : '1' }}">
                        <button type="submit" class="{{ $autoBackupEnabled ? 'btn-secondary' : 'btn-primary' }}">
                            {{ $autoBackupEnabled ? 'Turn OFF' : 'Turn ON' }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden border-t-[10px] border-t-[#1E40AF]">
                <div class="bg-[#1E40AF] px-5 py-3">
                    <h2 class="font-heading text-base font-bold text-white">Manual backup</h2>
                </div>
                <div class="p-5">
                    <p class="text-sm text-gray-600 font-data mb-3">Download a full database backup now. The file is generated on demand.</p>
                    <a href="{{ route('admin.system.backup.download') }}" class="btn-primary no-underline">Download backup now</a>
                </div>
            </div>

            @if($hasExistingBackup)
                <div class="p-4 bg-blue-50 border border-blue-200 text-blue-800 rounded-xl text-sm font-data">
                    <strong>Note:</strong> An auto backup file exists from the last scheduled run. The next run at 5:00 PM will replace it.
                </div>
            @endif

            <div class="p-4 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl text-sm font-data">
                <strong>Windows:</strong> For auto backup to run at 5:00 PM, the scheduler must be running. Create a task that runs every minute: <code class="bg-white px-1 rounded">php artisan schedule:run</code>. Or run <code class="bg-white px-1 rounded">php artisan backup:database</code> manually.
            </div>
        </div>
    </div>
@endsection
