<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Schedule - DCOMC</title>
    @include('layouts.partials.offline-assets')
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    @include('dashboards.partials.registrar-sidebar')

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="bg-white flex justify-center items-end border-b border-gray-200 pt-4 relative shrink-0">
            <div class="flex space-x-8 text-sm font-medium text-gray-600 px-6">
                <span class="pb-3 px-4 border-b-4 text-blue-700 border-blue-700 rounded-t-md flex items-center">
                    📅 Create Schedule
                </span>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8">
            <div class="max-w-7xl mx-auto">

                <p class="mb-4 text-sm text-gray-600">Create schedules for <strong>shifters/irregular</strong> students. You can add or remove subjects and set time, day, room, and professor per subject (from your COR Archive). Deploy only to the students you add to the table below. Transferee, returnee, and regular students use the dean’s Schedule by Program; their COR Archive is separate from the shifter/irregular COR Archive.</p>

                @if(session('success'))
                    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                        {{ session('success') }}
                    </div>
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

                <div class="flex justify-end mb-6">
                    <form method="POST" action="{{ route('registrar.schedule.templates.store') }}">
                        @csrf
                        <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white px-6 py-2.5 rounded-lg text-sm font-semibold shadow-sm">
                            + Create Schedule
                        </button>
                    </form>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">Existing schedules</h2>
                        <p class="text-xs text-gray-500">{{ $templates->count() }} schedule(s)</p>
                    </div>

                    @if($templates->isEmpty())
                        <p class="text-sm text-gray-500">No schedules created yet.</p>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($templates as $template)
                                <div class="border border-gray-200 rounded-lg p-4 bg-white hover:border-blue-400 hover:shadow-md transition-all flex flex-col">
                                    <a href="{{ route('registrar.schedule.templates.edit', $template->id) }}" class="block flex-1 group">
                                        <h3 class="font-semibold text-gray-900 mb-1 group-hover:text-blue-700">{{ $template->title }}</h3>
                                        @if($template->program || $template->year_level || $template->semester)
                                            <p class="text-xs text-gray-600 mb-1">
                                                {{ $template->program ?? 'Any Program' }}
                                                · {{ $template->year_level ?? 'Any Year' }}
                                                · {{ $template->semester ?? 'Any Sem' }}
                                                · {{ $template->school_year ?? 'Any SY' }}
                                                @if($template->major) · {{ $template->major }} @endif
                                                @if($template->block) · Block: {{ $template->block->code ?? $template->block->name }} @endif
                                            </p>
                                        @endif
                                        @if($template->description)
                                            <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $template->description }}</p>
                                        @endif
                                        <p class="text-[11px] text-gray-400 mt-2">
                                            {{ $template->getSubjectIds() ? count($template->getSubjectIds()) : 0 }} subjects ·
                                            {{ count($template->getFeeEntries()) }} fees ·
                                            {{ $template->created_at->diffForHumans() }}
                                        </p>
                                        <span class="inline-block mt-2 text-sm font-medium text-blue-600 group-hover:underline">Open to edit subjects, time, room, professor & deploy →</span>
                                    </a>
                                    <div class="mt-3 border-t border-gray-200 pt-3 flex items-center justify-end gap-2">
                                        <form method="POST" action="{{ route('registrar.schedule.templates.deploy', $template->id) }}" onsubmit="return confirm('Deploy this schedule to the students you added in the schedule’s student table only. Continue?');" class="inline">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 rounded text-xs font-semibold {{ $template->is_active ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-gray-100 text-gray-700 border border-gray-300 hover:bg-gray-200' }}">
                                                {{ $template->is_active ? '✓ Deployed' : 'Deploy' }}
                                            </button>
                                        </form>
                                        @if($template->is_active)
                                            <form method="POST" action="{{ route('registrar.schedule.templates.undeploy', $template->id) }}" onsubmit="return confirm('Undeploy this schedule? Listed students will no longer see it in View COR.');" class="inline">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 rounded text-xs font-semibold bg-red-100 text-red-800 border border-red-300 hover:bg-red-200">
                                                    Undeploy
                                                </button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('registrar.schedule.templates.destroy', $template->id) }}" onsubmit="return confirm('Delete this schedule?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-semibold">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>

</body>
</html>

