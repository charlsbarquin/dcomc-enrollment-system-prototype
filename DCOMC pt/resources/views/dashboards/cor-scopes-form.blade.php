<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $scope ? 'Edit' : 'New' }} COR Scope - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">
    @php $cor = request()->routeIs('admin.settings.*') ? 'admin.settings.cor-scopes' : 'registrar.cor-scopes'; $isAdmin = request()->routeIs('admin.*'); $dashRoute = $isAdmin ? 'admin.dashboard' : 'registrar.dashboard'; @endphp
    @include('dashboards.partials.role-sidebar')

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full max-w-3xl mx-auto">
                @if($errors->any())
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-data">
                        <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif

                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">{{ $scope ? 'Edit' : 'New' }} COR Scope Template</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Set Program, Year Level, Semester, and School Year. Then choose default subjects and fees.</p>
                        </div>
                        <a href="{{ route($cor . '.index') }}" class="btn-back-hero shrink-0 whitespace-nowrap">← Back to COR Scopes</a>
                    </div>
                </section>

                @if(!$scope)
                    <form method="GET" action="{{ route($cor . '.create') }}" class="bg-white shadow-2xl rounded-xl border border-gray-200 p-6 mb-6">
                    <p class="text-sm text-gray-600 mb-3">Select Program and Year Level to load subjects and fees.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Program</label>
                            <select name="program_id" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                <option value="">— Select —</option>
                                @foreach($programs as $p)
                                    <option value="{{ $p->id }}" {{ old('program_id', request('program_id')) == $p->id ? 'selected' : '' }}>{{ $p->code ?? $p->program_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Year Level</label>
                            <select name="academic_year_level_id" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                <option value="">— Select —</option>
                                @foreach($yearLevels as $y)
                                    <option value="{{ $y->id }}" {{ old('academic_year_level_id', request('academic_year_level_id')) == $y->id ? 'selected' : '' }}>{{ $y->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="mt-3 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded text-sm font-semibold">Load subjects & fees</button>
                </form>
            @endif

            <form method="POST" action="{{ $scope ? route('registrar.cor-scopes.update', $scope->id) : route('registrar.cor-scopes.store') }}">
                @csrf
                @if($scope) @method('PUT') @endif

                <div class="bg-white shadow-2xl rounded-xl border border-gray-200 p-6 mb-6">
                    <h2 class="font-heading text-lg font-bold text-gray-800 mb-4">Scope</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Program <span class="text-red-500">*</span></label>
                            <select name="program_id" required class="w-full border border-gray-300 rounded px-3 py-2">
                                <option value="">— Select —</option>
                                @foreach($programs as $p)
                                    <option value="{{ $p->id }}" {{ old('program_id', $scope?->program_id ?? request('program_id')) == $p->id ? 'selected' : '' }}>{{ $p->code ?? $p->program_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Year Level <span class="text-red-500">*</span></label>
                            <select name="academic_year_level_id" required class="w-full border border-gray-300 rounded px-3 py-2">
                                <option value="">— Select —</option>
                                @foreach($yearLevels as $y)
                                    <option value="{{ $y->id }}" {{ old('academic_year_level_id', $scope?->academic_year_level_id ?? request('academic_year_level_id')) == $y->id ? 'selected' : '' }}>{{ $y->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Semester <span class="text-red-500">*</span></label>
                            <select name="semester" required class="w-full border border-gray-300 rounded px-3 py-2">
                                <option value="">— Select —</option>
                                @foreach($semesters as $s)
                                    <option value="{{ $s }}" {{ old('semester', $scope?->semester) == $s ? 'selected' : '' }}>{{ $s }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">School Year <span class="text-red-500">*</span></label>
                            <select name="school_year" required class="w-full border border-gray-300 rounded px-3 py-2">
                                <option value="">— Select —</option>
                                @foreach($schoolYears as $sy)
                                    <option value="{{ $sy }}" {{ old('school_year', $scope?->school_year) == $sy ? 'selected' : '' }}>{{ $sy }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Major (optional)</label>
                            <input type="text" name="major" value="{{ old('major', $scope?->major) }}" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. English">
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-2">Default subjects</h2>
                    <p class="text-xs text-gray-500 mb-3">Only subjects for the selected program and year are listed. These will pre-load when creating a schedule for this scope.</p>
                    @if($subjects->isEmpty())
                        <p class="text-sm text-gray-500">Select Program and Year Level above (and click Load for new scope), or add subjects in Settings → Subjects for this program and year.</p>
                    @else
                        <div class="max-h-48 overflow-y-auto border border-gray-200 rounded p-2 space-y-1">
                            @foreach($subjects as $subj)
                                <label class="flex items-center gap-2 text-sm cursor-pointer">
                                    <input type="checkbox" name="subject_ids[]" value="{{ $subj->id }}" {{ in_array($subj->id, old('subject_ids', $selectedSubjectIds)) ? 'checked' : '' }}>
                                    <span>{{ $subj->code }} — {{ $subj->title }} ({{ $subj->units }} u)</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-2">Default fees</h2>
                    <p class="text-xs text-gray-500 mb-3">Only fees for the selected program and year are listed. These will pre-load when creating a schedule for this scope.</p>
                    @if($fees->isEmpty())
                        <p class="text-sm text-gray-500">Select Program and Year Level above (and click Load for new scope), or add fees in Settings → Fees for this program and year.</p>
                    @else
                        <div class="max-h-48 overflow-y-auto border border-gray-200 rounded p-2 space-y-1">
                            @foreach($fees as $fee)
                                <label class="flex items-center gap-2 text-sm cursor-pointer">
                                    <input type="checkbox" name="fee_ids[]" value="{{ $fee->id }}" {{ in_array($fee->id, old('fee_ids', $selectedFeeIds)) ? 'checked' : '' }}>
                                    <span>{{ $fee->name ?? $fee->feeCategory?->name }} — ₱{{ number_format($fee->amount ?? 0, 2) }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="btn-primary">{{ $scope ? 'Update' : 'Create' }} COR Scope</button>
                    <a href="{{ route($cor . '.index') }}" class="btn-secondary">Cancel</a>
                </div>
            </form>
            </div>
        </div>
    </main>
</body>
</html>
