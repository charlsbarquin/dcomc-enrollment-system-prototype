<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COR Scope Templates - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">
    @php
        $cor = request()->routeIs('admin.settings.*') ? 'admin.settings.cor-scopes' : 'registrar.cor-scopes';
        $isAdmin = request()->routeIs('admin.*');
        $dashRoute = $isAdmin ? 'admin.dashboard' : 'registrar.dashboard';
    @endphp
    @include('dashboards.partials.role-sidebar')

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full max-w-4xl mx-auto">
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
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">COR Scope Templates</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Define default subjects and fees per Program, Year Level, Semester, and School Year. Schedules for a given configuration will auto-load from the matching scope.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 shrink-0">
                            <a href="{{ route($cor . '.create') }}" class="btn-white-hero">+ New COR Scope</a>
                            <a href="{{ route($dashRoute) }}" class="btn-back-hero whitespace-nowrap">← Back to Dashboard</a>
                        </div>
                    </div>
                </section>

                <div class="rounded-xl overflow-hidden border border-gray-200 shadow-2xl bg-white">
                    <div class="bg-[#1E40AF] px-6 py-4">
                        <h2 class="font-heading text-lg font-bold text-white">Templates</h2>
                    </div>
                    <div class="p-6">
                        @if($scopes->isEmpty())
                            <p class="text-sm text-gray-500 font-data">No COR Scope Templates yet. Create one so schedules can auto-load subjects and fees.</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm font-data">
                                    <thead class="bg-gray-50 border-b border-gray-200">
                                        <tr>
                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Program</th>
                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Year</th>
                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Semester</th>
                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">School Year</th>
                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Major</th>
                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Subjects</th>
                                            <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Fees</th>
                                            <th class="py-3 px-4 text-right font-heading font-bold text-gray-700">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        @foreach($scopes as $scopeItem)
                                            <tr class="hover:bg-blue-50/50 transition-colors font-data">
                                                <td class="py-4 px-4">{{ $scopeItem->program ? ($scopeItem->program->code ?? $scopeItem->program->program_name) : '—' }}</td>
                                                <td class="py-4 px-4">{{ $scopeItem->academicYearLevel ? $scopeItem->academicYearLevel->name : '—' }}</td>
                                                <td class="py-4 px-4">{{ $scopeItem->semester ?? '—' }}</td>
                                                <td class="py-4 px-4">{{ $scopeItem->school_year ?? '—' }}</td>
                                                <td class="py-4 px-4">{{ $scopeItem->major ?: '—' }}</td>
                                                <td class="py-4 px-4">{{ $scopeItem->scopeSubjects->count() }}</td>
                                                <td class="py-4 px-4">{{ $scopeItem->scopeFees->count() }}</td>
                                                <td class="py-4 px-4 text-right">
                                                    <a href="{{ route($cor . '.edit', $scopeItem->id) }}" class="btn-action-edit">Edit</a>
                                                    <form method="POST" action="{{ route($cor . '.destroy', $scopeItem->id) }}" class="inline ml-1" onsubmit="return confirm('Remove this COR Scope?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:underline text-sm font-data">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
