@extends('dashboards.layouts.admin-shell')

@section('title', 'Maintenance Mode')

@section('content')
    <div class="flex-1 overflow-y-auto p-4 md:p-6">
        <section class="hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Maintenance Mode</h1>
                    <p class="text-white/90 text-sm font-data mb-0">Enable or disable maintenance mode for the application.</p>
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

        <div class="max-w-3xl space-y-6">
            <div class="bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden border-t-[10px] border-t-[#1E40AF]">
                <div class="bg-[#1E40AF] px-5 py-3">
                    <h2 class="font-heading text-base font-bold text-white">Current status</h2>
                </div>
                <div class="p-5">
                    <p class="font-data font-semibold {{ $maintenance ? 'text-red-700' : 'text-green-700' }}">
                        {{ $maintenance ? 'Maintenance mode is ON' : 'Maintenance mode is OFF' }}
                    </p>
                </div>
            </div>

            @if(!$maintenance)
                <div class="bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden border-t-[10px] border-t-[#1E40AF]">
                    <div class="bg-[#1E40AF] px-5 py-3">
                        <h2 class="font-heading text-base font-bold text-white">Enable maintenance mode</h2>
                    </div>
                    <form method="POST" action="{{ route('admin.system.maintenance.down') }}" class="p-5 space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1 font-data" for="retry">Retry (seconds)</label>
                                <input id="retry" name="retry" type="number" min="0" max="86400" value="60" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1 font-data" for="refresh">Refresh (seconds)</label>
                                <input id="refresh" name="refresh" type="number" min="0" max="86400" value="" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus" placeholder="Optional">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1 font-data" for="status">HTTP Status</label>
                                <input id="status" name="status" type="number" min="200" max="599" value="503" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1 font-data" for="secret">Secret bypass</label>
                                <input id="secret" name="secret" type="text" maxlength="64" value="" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus" placeholder="Optional (e.g. my-secret)">
                                <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-700 font-data">
                                    <input type="checkbox" name="with_secret" value="1"> Generate random secret
                                </label>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1 font-data" for="redirect">Redirect path</label>
                                <input id="redirect" name="redirect" type="text" maxlength="255" value="" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus" placeholder="Optional">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1 font-data" for="render">Prerender view</label>
                                <input id="render" name="render" type="text" maxlength="255" value="" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus" placeholder="Optional">
                            </div>
                        </div>
                        <button type="submit" class="btn-primary" onclick="return confirm('Enable maintenance mode?');">Enable maintenance mode</button>
                    </form>
                </div>
            @else
                <div class="bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden border-t-[10px] border-t-[#1E40AF]">
                    <div class="bg-[#1E40AF] px-5 py-3">
                        <h2 class="font-heading text-base font-bold text-white">Disable maintenance mode</h2>
                    </div>
                    <form method="POST" action="{{ route('admin.system.maintenance.up') }}" class="p-5">
                        @csrf
                        <button type="submit" class="px-5 py-2.5 rounded-lg font-semibold bg-green-600 hover:bg-green-700 text-white transition font-data" onclick="return confirm('Disable maintenance mode?');">Disable maintenance mode</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection
