<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Settings - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">
    @php $set = request()->routeIs('admin.settings.*') ? 'admin.settings' : 'registrar.settings'; $isAdmin = request()->routeIs('admin.*'); $dashRoute = $isAdmin ? 'admin.dashboard' : 'registrar.dashboard'; @endphp
    @include('dashboards.partials.role-sidebar')

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full max-w-6xl mx-auto">
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
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Subject Settings</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Manage raw subjects and arrange them by course, year, and semester.</p>
                        </div>
                        <a href="{{ route($dashRoute) }}" class="btn-back-hero shrink-0 whitespace-nowrap">← Back to Dashboard</a>
                    </div>
                </section>

                <div class="bg-white shadow-2xl rounded-xl border border-gray-200 px-6 py-5 mb-6 card-dcomc-top">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-sm font-medium text-gray-700 font-data">Show:</span>
                        <a href="{{ route($set . '.subjects', ['mode' => 'raw']) }}" class="px-4 py-2 rounded-lg text-sm font-medium font-data {{ ($subjectMode ?? 'arrange') === 'raw' ? 'bg-[#1E40AF] text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">Raw subjects</a>
                        <a href="{{ route($set . '.subjects') }}" class="px-4 py-2 rounded-lg text-sm font-medium font-data {{ ($subjectMode ?? 'arrange') === 'arrange' ? 'bg-[#1E40AF] text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">Arrange subjects</a>
                    </div>
                </div>

                @if(!empty($breadcrumb))
                    <nav class="flex items-center gap-2 text-sm text-gray-600 mb-4 font-data" aria-label="Breadcrumb">
                        @foreach($breadcrumb as $i => $item)
                            @if($i > 0)<span class="text-gray-400">/</span>@endif
                            <a href="{{ $item['url'] }}" class="text-[#1E40AF] hover:underline">{{ $item['label'] }}</a>
                        @endforeach
                    </nav>
                @endif

                @if(($subjectMode ?? '') === 'raw')
                    <div class="bg-white shadow-2xl rounded-xl border border-gray-200 p-6 mb-6 card-dcomc-top">
                        <h2 class="font-heading text-lg font-bold text-gray-800 mb-4">Raw subjects</h2>
                        <form method="POST" action="{{ route($set . '.raw-subjects.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end text-sm mb-6">
                            @csrf
                            <div>
                                <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Code</label>
                                <input type="text" name="code" value="{{ old('code') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus" placeholder="e.g., GE 2" required>
                            </div>
                            <div>
                                <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Title</label>
                                <input type="text" name="title" value="{{ old('title') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus" placeholder="Subject title" required>
                            </div>
                            <div>
                                <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Units</label>
                                <input type="number" step="0.5" min="0" name="units" value="{{ old('units', 3) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus" required>
                            </div>
                            <div class="md:col-span-4">
                                <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Pre-requisites (optional)</label>
                                <input type="text" name="prerequisites" value="{{ old('prerequisites') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus" placeholder="e.g., NSTP 1">
                            </div>
                            <div>
                                <button type="submit" class="btn-primary">Add raw subject</button>
                            </div>
                        </form>
                        @if(($rawSubjects ?? collect())->isEmpty())
                            <p class="text-sm text-gray-500 font-data">No raw subjects yet. Add one above.</p>
                        @else
                            <div class="overflow-x-auto rounded-xl border border-gray-200">
                                <table class="w-full text-sm font-data">
                                    <thead class="bg-gray-50 border-b border-gray-200">
                                        <tr>
                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Code</th>
                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Title</th>
                                            <th class="py-3 px-4 text-center font-heading font-bold text-gray-700">Units</th>
                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Pre-requisites</th>
                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Visible</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        @foreach($rawSubjects as $raw)
                                            <tr class="hover:bg-blue-50/50 transition-colors font-data">
                                                <td class="py-4 px-4 font-semibold">{{ $raw->code }}</td>
                                                <td class="py-4 px-4">{{ $raw->title }}</td>
                                                <td class="py-4 px-4 text-center">{{ $raw->units }}</td>
                                                <td class="py-4 px-4">{{ $raw->prerequisites ?: '—' }}</td>
                                                <td class="py-4 px-4">
                                                    <form method="POST" action="{{ route($set . '.raw-subjects.toggle', $raw->id) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <label class="inline-flex items-center gap-2 text-xs text-gray-600 cursor-pointer font-data">
                                                            <input type="checkbox" onchange="this.form.submit()" {{ $raw->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-[#1E40AF] focus:ring-[#1E40AF]">
                                                            <span>{{ $raw->is_active ? 'Show' : 'Hide' }}</span>
                                                        </label>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endif

                @if(($subjectMode ?? 'arrange') === 'arrange' && ($viewMode ?? 'programs') === 'programs')
                    <div class="bg-white shadow-2xl rounded-xl border border-gray-200 p-6 card-dcomc-top">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Courses / Programs</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @forelse($programs ?? [] as $prog)
                            @php $label = ($displayLabels ?? [])[$prog] ?? $prog; @endphp
                            <a href="{{ route($set . '.subjects') }}?program={{ urlencode($prog) }}" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-blue-50 hover:border-blue-300 transition">
                                <svg class="w-8 h-8 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                                <span class="font-medium text-gray-800">{{ $label }}</span>
                            </a>
                        @empty
                            <p class="text-sm text-gray-500 col-span-2">No programs defined. Check config/fee_programs.php.</p>
                        @endforelse
                    </div>
                </div>
            @endif

            @if(($subjectMode ?? 'arrange') === 'arrange' && ($viewMode ?? '') === 'years')
                <div class="bg-white shadow-2xl rounded-xl border border-gray-200 p-6 card-dcomc-top">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Year levels</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach($yearLevels ?? [] as $yr)
                            <a href="{{ route($set . '.subjects') }}?program={{ urlencode($program) }}&year={{ urlencode($yr) }}" class="flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:bg-blue-50 hover:border-blue-300 transition">
                                <svg class="w-8 h-8 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                                <span class="font-medium text-gray-800">{{ $yr }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(($subjectMode ?? 'arrange') === 'arrange' && ($viewMode ?? '') === 'semesters')
                <div class="bg-white shadow-2xl rounded-xl border border-gray-200 p-6 card-dcomc-top">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Semesters</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($semesters ?? [] as $sem)
                            <a href="{{ route($set . '.subjects') }}?program={{ urlencode($program) }}&year={{ urlencode($year) }}&semester={{ urlencode($sem) }}" class="flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:bg-blue-50 hover:border-blue-300 transition">
                                <svg class="w-8 h-8 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                                <span class="font-medium text-gray-800">{{ $sem }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(($subjectMode ?? 'arrange') === 'arrange' && ($viewMode ?? '') === 'table')
                <div class="bg-white shadow-2xl rounded-xl border border-gray-200 p-6 mb-6">
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">Subjects — {{ $programLabel ?? $program }} — {{ $year }} — {{ $semester ?? '' }}</h2>
                        <form method="POST" action="{{ route($set . '.subjects.push') }}" class="inline" onsubmit="return confirm('Push all {{ $subjects->count() }} subject(s) here to the Dean\'s Schedule by Program for the same program, year, and semester? The Dean will see them when opening that scope.');">
                            @csrf
                            <input type="hidden" name="program" value="{{ $program }}">
                            <input type="hidden" name="year" value="{{ $year }}">
                            <input type="hidden" name="semester" value="{{ $semester ?? '' }}">
                            <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded text-sm font-semibold" title="Add all subjects on this page to the Dean Schedule by Program (same path) so the Dean sees them in the schedule form">Push</button>
                        </form>
                    </div>
                    <form method="POST" action="{{ route($set . '.subjects.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end text-sm mb-6">
                        @csrf
                        <input type="hidden" name="program" value="{{ old('program', $program) }}">
                        <input type="hidden" name="year_level" value="{{ old('year_level', $year) }}">
                        <input type="hidden" name="semester" value="{{ old('semester', $semester ?? '') }}">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1 uppercase">Semester</label>
                            <p class="py-2 text-sm font-medium text-gray-700">{{ $semester ?? '' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-600 mb-1 uppercase">Raw subject</label>
                            <select name="raw_subject_id" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required>
                                <option value="">— Select subject —</option>
                                @foreach($rawSubjects ?? [] as $r)
                                    <option value="{{ $r->id }}" {{ (string)old('raw_subject_id') === (string)$r->id ? 'selected' : '' }}>{{ $r->code }} - {{ $r->title }} ({{ $r->units }} units)</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1 uppercase">Major (optional)</label>
                            <input type="text" name="major" value="{{ old('major') }}" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g., English, Science">
                        </div>
                        <div class="md:col-span-3 flex items-end gap-2">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-semibold">Add subject</button>
                            <a href="{{ route($set . '.subjects') }}?program={{ urlencode($program) }}&year={{ urlencode($year) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded text-sm font-semibold">Back to semesters</a>
                        </div>
                    </form>

                    @if($subjects->isEmpty())
                        <p class="text-sm text-gray-500">No subjects for this course, year, and semester yet. Add one above.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-2 text-left">Code</th>
                                        <th class="p-2 text-left">Title</th>
                                        <th class="p-2 text-center">Units</th>
                                        <th class="p-2 text-left">Pre-requisites</th>
                                        <th class="p-2 text-left">Major</th>
                                        <th class="p-2 text-left">Visible</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subjects as $subject)
                                        <tr class="border-t">
                                            <td class="p-2 font-semibold">{{ $subject->code }}</td>
                                            <td class="p-2">{{ $subject->title }}</td>
                                            <td class="p-2 text-center">{{ $subject->units }}</td>
                                            <td class="p-2">{{ $subject->prerequisites ?: '—' }}</td>
                                            <td class="p-2">{{ $subject->major ?: '—' }}</td>
                                            <td class="p-2">
                                                <form method="POST" action="{{ route($set . '.subjects.toggle', $subject->id) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <label class="inline-flex items-center gap-2 text-xs text-gray-600 cursor-pointer">
                                                        <input type="checkbox" onchange="this.form.submit()" {{ $subject->is_active ? 'checked' : '' }}>
                                                        <span>{{ $subject->is_active ? 'Show' : 'Hide' }}</span>
                                                    </label>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif
            </div>
        </div>
    </main>
</body>
</html>
