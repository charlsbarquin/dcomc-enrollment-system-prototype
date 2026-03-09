@extends('dashboards.layouts.admin-shell')

@section('title', 'Application Logs')

@section('content')
    <div class="flex-1 overflow-y-auto p-4 md:p-6" x-data="{ raw: {{ !empty($rawMode) ? 'true' : 'false' }} }">
        <section class="hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Application Logs</h1>
                    <p class="text-white/90 text-sm font-data mb-0">View and search laravel.log. Clear log when needed.</p>
                </div>
                <a href="{{ route('admin.system.overview') }}" class="btn-back-hero shrink-0 whitespace-nowrap no-underline">← Back</a>
            </div>
        </section>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-data">{{ session('success') }}</div>
        @endif

        <div class="bg-white shadow-2xl rounded-xl border border-gray-200 overflow-hidden card-dcomc-top mb-6">
            <div class="bg-[#1E40AF] px-5 py-3">
                <h2 class="font-heading text-base font-bold text-white">Search &amp; Filter</h2>
            </div>
            <div class="p-4 flex flex-wrap items-end gap-3">
                <form method="get" action="{{ route('admin.system.logs') }}" class="flex flex-wrap items-center gap-3 flex-1 min-w-[220px]">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-medium text-gray-500 mb-1 font-data" for="q">Search</label>
                        <input id="q" name="q" value="{{ $query }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus" placeholder="Filter lines (case-insensitive)">
                    </div>
                    <input type="hidden" name="lines" value="{{ $maxLines }}">
                    <input type="hidden" name="mode" :value="raw ? 'raw' : 'friendly'">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 font-data">
                        <input type="checkbox" name="summary" value="1" {{ !empty($summaryOnly) ? 'checked' : '' }}> Summary only
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 font-data">
                        <input type="checkbox" @change="raw = $event.target.checked" :checked="raw"> Raw view
                    </label>
                    <button type="submit" class="btn-primary">Apply</button>
                </form>
                <form method="post" action="{{ route('admin.system.logs.clear') }}" class="inline" onsubmit="return confirm('Clear laravel.log?');">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold bg-red-600 hover:bg-red-700 text-white transition font-data">Clear log</button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden">
            <div class="bg-[#1E40AF] px-5 py-3 flex flex-wrap items-center gap-2">
                <h2 class="font-heading text-base font-bold text-white">Application log</h2>
                <span class="text-white/80 text-xs font-mono font-data">File: {{ $logPath }}</span>
                @if(!$exists)<span class="text-red-200 text-xs">(not found)</span>@endif
                @if(!empty($truncated))<span class="text-white/70 text-xs">(showing tail)</span>@endif
            </div>
            @if(!$exists)
                @include('dashboards.partials.admin-empty-state', ['title' => 'No log file found', 'text' => 'The log file does not exist or is not readable.'])
            @else
                @if(!empty($rawMode))
                    <pre class="p-4 text-xs leading-relaxed overflow-x-auto whitespace-pre-wrap font-mono text-gray-800">{{ implode(PHP_EOL, $lines) }}</pre>
                @else
                    @php $entries = $entries ?? []; @endphp
                    @if(empty($entries))
                        @include('dashboards.partials.admin-empty-state', ['title' => 'No log entries', 'text' => 'No log entries found in the current view. Try changing the filter.'])
                    @else
                        <div class="divide-y divide-gray-200">
                            @foreach($entries as $i => $entry)
                                <div class="p-4 hover:bg-blue-50/50 transition-colors" x-data="{ open: false }">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="text-xs font-mono text-gray-800 break-words font-data">{{ $entry['header'] }}</div>
                                        @if(!empty($entry['details']))
                                            <button type="button" class="shrink-0 px-3 py-1.5 text-xs font-semibold rounded bg-[#1E40AF] hover:bg-[#1D3A8A] text-white transition font-data"
                                                    @click="open = !open" x-text="open ? 'Hide details' : 'Show details'"></button>
                                        @else
                                            <span class="shrink-0 text-xs text-gray-500 font-data">No details</span>
                                        @endif
                                    </div>
                                    @if(!empty($entry['details']))
                                        <div x-show="open" x-cloak class="mt-3">
                                            <pre class="text-xs leading-relaxed whitespace-pre-wrap bg-gray-50 border border-gray-200 rounded-lg p-3 overflow-x-auto font-mono">{{ $entry['details'] }}</pre>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif
            @endif
        </div>
    </div>
@endsection
