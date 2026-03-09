<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Schedule by Program</title>
    @include('layouts.partials.offline-assets')
</head>
<body class="bg-gray-100 flex h-screen">
    @include('dashboards.partials.registrar-sidebar')

    <main class="flex-1 p-8 overflow-y-auto">
        <div class="max-w-6xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Schedule by Program</h1>
            <p class="text-sm text-gray-600 mb-4">Same structure as Subject Settings. Open Program → Year → Semester to add or remove subjects in the schedule. Day, time, room, and professor are set by the Dean on their Schedule by Program page.</p>

            @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc pl-5 text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(!empty($breadcrumb))
                <nav class="flex items-center gap-2 text-sm text-gray-600 mb-4">
                    @foreach($breadcrumb as $i => $item)
                        @if($i > 0)<span class="text-gray-400">/</span>@endif
                        <a href="{{ $item['url'] }}" class="hover:text-blue-600">{{ $item['label'] }}</a>
                    @endforeach
                </nav>
            @endif

            @if(($viewMode ?? 'programs') === 'programs')
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Programs</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @forelse($programs ?? [] as $prog)
                            @php $label = ($displayLabels ?? [])[$prog] ?? $prog; @endphp
                            <a href="{{ route('registrar.schedule.by-scope') }}?program={{ urlencode($prog) }}" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-blue-50 hover:border-blue-300 transition">
                                <span class="text-xl">📁</span>
                                <span class="font-medium text-gray-800">{{ $label }}</span>
                            </a>
                        @empty
                            <p class="text-sm text-gray-500 col-span-2">No programs defined.</p>
                        @endforelse
                    </div>
                </div>
            @endif

            @if(($viewMode ?? '') === 'years')
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Year levels</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach($yearLevels ?? [] as $yr)
                            <a href="{{ route('registrar.schedule.by-scope') }}?program={{ urlencode($program) }}&year={{ urlencode($yr) }}" class="flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:bg-blue-50 hover:border-blue-300 transition">
                                <span class="text-xl">📁</span>
                                <span class="font-medium text-gray-800">{{ $yr }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(($viewMode ?? '') === 'semesters')
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Semesters</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($semesters ?? [] as $sem)
                            <a href="{{ route('registrar.schedule.by-scope') }}?program={{ urlencode($program) }}&year={{ urlencode($year) }}&semester={{ urlencode($sem) }}" class="flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:bg-blue-50 hover:border-blue-300 transition">
                                <span class="text-xl">📁</span>
                                <span class="font-medium text-gray-800">{{ $sem }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(($viewMode ?? '') === 'table')
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Schedule — {{ $programLabel ?? $program }} — {{ $year }} — {{ $semester ?? '' }}</h2>
                    <p class="text-sm text-gray-500 mb-4">Add or remove subjects in the schedule for this program, year, and semester. Day, time, room, and professor are set by the Dean on their Schedule by Program page.</p>

                    @if($subjects->isEmpty())
                        <p class="text-sm text-gray-500">No subjects for this program, year, and semester. Add subjects in Subject Settings first.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-2 text-left">Code</th>
                                        <th class="p-2 text-left">Title</th>
                                        <th class="p-2 text-center">Units</th>
                                        <th class="p-2 text-left">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $subjectIdsInSchedule = $subjectIdsInSchedule ?? []; @endphp
                                    @foreach($subjects as $subject)
                                        @php $inSchedule = in_array($subject->id, $subjectIdsInSchedule); @endphp
                                        <tr class="border-t align-middle">
                                            <td class="p-2 font-medium">{{ $subject->code }}</td>
                                            <td class="p-2">{{ $subject->title }}</td>
                                            <td class="p-2 text-center">{{ $subject->units }}</td>
                                            <td class="p-2">
                                                @if($inSchedule)
                                                    <form method="POST" action="{{ route('registrar.schedule.slots.delete') }}" class="inline" onsubmit="return confirm('Remove this subject from the schedule? The Dean will no longer see it for this program/year/semester.');">
                                                        @csrf
                                                        <input type="hidden" name="program_id" value="{{ $program_id }}">
                                                        <input type="hidden" name="academic_year_level_id" value="{{ $academic_year_level_id }}">
                                                        <input type="hidden" name="semester" value="{{ $semester }}">
                                                        <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                                                        <button type="submit" class="px-3 py-1.5 bg-red-100 text-red-700 rounded text-sm font-medium hover:bg-red-200">Delete</button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('registrar.schedule.slots.add') }}" class="inline">
                                                        @csrf
                                                        <input type="hidden" name="program_id" value="{{ $program_id }}">
                                                        <input type="hidden" name="academic_year_level_id" value="{{ $academic_year_level_id }}">
                                                        <input type="hidden" name="semester" value="{{ $semester }}">
                                                        <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                                                        <button type="submit" class="px-3 py-1.5 bg-blue-100 text-blue-700 rounded text-sm font-medium hover:bg-blue-200">Add</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <a href="{{ route('registrar.schedule.by-scope') }}?program={{ urlencode($program) }}&year={{ urlencode($year) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded text-sm font-semibold">Back to semesters</a>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </main>
</body>
</html>
