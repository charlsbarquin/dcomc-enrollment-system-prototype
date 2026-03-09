<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Students Explorer - DCOMC</title>
    @include('layouts.partials.offline-assets')
    <style>
        .font-heading { font-family: 'Figtree', sans-serif; }
        .font-data { font-family: 'Roboto', sans-serif; }
        .forms-canvas { background: #f3f4f6; }
        .hero-gradient { background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 50%, #60A5FA 100%); }
        .input-dcomc-focus:focus { outline: none; border-color: #1E40AF; box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.2); }
        #tree-panel.tree-panel--collapsed { width: 3rem; min-width: 3rem; }
        #tree-panel.tree-panel--collapsed #tree-panel-expanded { display: none !important; }
        #tree-panel.tree-panel--collapsed #tree-panel-collapsed-bar { display: flex !important; }
        /* Match Manual Registration button styles (plain CSS - @apply does not work in inline <style>) */
        .btn-primary { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1.25rem; border-radius: 0.5rem; background: #1E40AF; color: #fff; font-size: 0.875rem; font-weight: 600; transition: background-color 0.2s; border: none; cursor: pointer; font-family: 'Roboto', sans-serif; }
        .btn-primary:hover { background: #1D3A8A; }
        .btn-primary:focus { outline: none; box-shadow: 0 0 0 2px #fff, 0 0 0 4px #1E40AF; }
        .btn-secondary { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1rem; border-radius: 0.5rem; border: 2px solid #d1d5db; background: #fff; color: #374151; font-size: 0.875rem; font-weight: 600; transition: background-color 0.2s, border-color 0.2s; cursor: pointer; font-family: 'Roboto', sans-serif; text-decoration: none; }
        .btn-secondary:hover { background: #f9fafb; border-color: #9ca3af; }
        .btn-secondary:focus { outline: none; box-shadow: 0 0 0 2px #fff, 0 0 0 4px #d1d5db; }
        .btn-ghost-hero { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1rem; border-radius: 0.5rem; background: rgba(255,255,255,0.2); color: #fff; font-size: 0.875rem; font-weight: 500; transition: background-color 0.2s; border: none; cursor: pointer; font-family: 'Roboto', sans-serif; }
        .btn-ghost-hero:hover { background: rgba(255,255,255,0.3); }
        .btn-ghost-hero:focus { outline: none; box-shadow: 0 0 0 2px rgba(255,255,255,0.5); }
        .btn-white-hero { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1rem; border-radius: 0.5rem; border: 2px solid #d1d5db; background: #fff; color: #1f2937; font-size: 0.875rem; font-weight: 600; transition: background-color 0.2s; text-decoration: none; cursor: pointer; font-family: 'Roboto', sans-serif; }
        .btn-white-hero:hover { background: #f9fafb; }
        .btn-white-hero:focus { outline: none; box-shadow: 0 0 0 2px #fff, 0 0 0 4px #d1d5db; }
        .btn-select-all { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; border-radius: 0.5rem; border: 2px solid #d1d5db; background: #fff; color: #374151; font-size: 0.75rem; font-weight: 600; transition: background-color 0.2s; cursor: pointer; font-family: 'Roboto', sans-serif; }
        .btn-select-all:hover { background: #f9fafb; }
        .btn-select-all:focus { outline: none; box-shadow: 0 0 0 2px #1E40AF; }
        .btn-action-cor { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; border-radius: 0.5rem; background: #1E40AF; color: #fff; font-size: 0.75rem; font-weight: 600; transition: background-color 0.2s; text-decoration: none; cursor: pointer; font-family: 'Roboto', sans-serif; border: none; }
        .btn-action-cor:hover { background: #1D3A8A; }
        .btn-action-cor:focus { outline: none; box-shadow: 0 0 0 2px #fff, 0 0 0 4px #1E40AF; }
        .btn-action-edit { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; border-radius: 0.5rem; border: 2px solid #d1d5db; background: #fff; color: #374151; font-size: 0.75rem; font-weight: 600; transition: background-color 0.2s; cursor: pointer; font-family: 'Roboto', sans-serif; }
        .btn-action-edit:hover { background: #f9fafb; }
        .btn-action-edit:focus { outline: none; box-shadow: 0 0 0 2px #1E40AF; }
        #students-toast-container { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999; display: flex; flex-direction: column; gap: 0.5rem; pointer-events: none; }
        #students-toast-container .toast { pointer-events: auto; min-width: 280px; max-width: 400px; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15); animation: toast-in 0.25s ease-out; }
        #students-toast-container .toast.success { background: #059669; color: #fff; }
        #students-toast-container .toast.error { background: #dc2626; color: #fff; }
        @keyframes toast-in { from { opacity: 0; transform: translateY(0.5rem); } to { opacity: 1; transform: translateY(0); } }
        .table-skeleton td { padding: 1rem; }
        .table-skeleton .skeleton-line { height: 1rem; background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%); background-size: 200% 100%; animation: skeleton 1s ease-in-out infinite; border-radius: 4px; }
        @keyframes skeleton { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        .tree-capacity-bar { height: 4px; min-width: 48px; background: #e5e7eb; border-radius: 2px; overflow: hidden; }
        .tree-capacity-fill { height: 100%; background: #1E40AF; border-radius: 2px; transition: width 0.2s ease; }
    </style>
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">
    @php
        $isStaff = request()->routeIs('staff.*');
    @endphp
    @include('dashboards.partials.role-sidebar')

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        {{-- Scrollable content area: same structure as Manual Registration (p-6 md:p-8 forms-canvas) --}}
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-data shadow-sm">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-data">
                        <ul class="list-disc pl-5"><li>{{ $errors->first() }}</li></ul>
                    </div>
                @endif

                {{-- Hero Banner: full width, matches Manual Registration (rounded-2xl shadow-lg mb-6) --}}
                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Students Explorer</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Browse students by program, year, and block. Edit records and manage block assignments.</p>
                        </div>
                        @unless($isStaff)
                        <div class="flex flex-wrap items-center gap-3 shrink-0">
                            <button type="button" id="btn-rebalance" class="btn-ghost-hero" title="Rebalance students across blocks">
                                <span class="text-base leading-none" aria-hidden="true">⚖</span>
                                <span>Rebalance</span>
                            </button>
                            <button type="button" id="btn-promotion" class="btn-white-hero" title="Run year level promotion">
                                <span class="text-base leading-none" aria-hidden="true">↑</span>
                                <span>Run Promotion</span>
                            </button>
                            <a href="#" id="link-transfer-log" class="btn-white-hero" title="View transfer history">
                                <span class="text-base leading-none" aria-hidden="true">📋</span>
                                <span>Transfer Log</span>
                            </a>
                        </div>
                        @endunless
                    </div>
                </section>

                {{-- Filter: single white floating card (shadow-2xl rounded-xl), no inner shadow/gray --}}
                <div id="filter-panel" class="bg-white shadow-2xl rounded-xl border border-gray-200 px-6 py-5 mb-6 shrink-0">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label for="filter-student-number" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Student Number</label>
                            <input type="text" id="filter-student-number" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus transition" placeholder="">
                        </div>
                        <div>
                            <label for="filter-program" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Program</label>
                            <input type="text" id="filter-program" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus transition" placeholder="">
                        </div>
                        <div>
                            <label for="filter-school-year" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">School Year</label>
                            <select id="filter-school-year" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus transition cursor-pointer">
                                <option value="">All</option>
                                @foreach($schoolYears ?? [] as $sy)
                                    <option value="{{ $sy }}">{{ $sy }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="filter-first-name" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">First Name</label>
                            <input type="text" id="filter-first-name" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus transition" placeholder="">
                        </div>
                        <div>
                            <label for="filter-year-level" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Year Level</label>
                            <select id="filter-year-level" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus transition cursor-pointer">
                                <option value="">All</option>
                                @foreach($yearLevels ?? [] as $level)
                                    <option value="{{ $level }}">{{ $level }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="filter-last-name" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Last Name</label>
                            <input type="text" id="filter-last-name" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus transition" placeholder="">
                        </div>
                        <div>
                            <label for="filter-semester" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Semester</label>
                            <select id="filter-semester" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus transition cursor-pointer">
                                <option value="">All</option>
                                @foreach($semesters ?? [] as $sem)
                                    <option value="{{ $sem }}">{{ $sem }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="filter-status" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Status</label>
                            <select id="filter-status" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus transition cursor-pointer">
                                <option value="">All</option>
                                @foreach($statusOptions ?? [] as $st)
                                    <option value="{{ $st }}">{{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2 flex flex-col justify-end">
                            <label for="filter-folder" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Folder</label>
                            <div class="flex gap-2 flex-wrap">
                                <input type="text" id="filter-folder" class="flex-1 min-w-0 border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus transition" placeholder="Search folder...">
                                <button type="button" id="filter-reset" class="btn-secondary shrink-0">
                                    <span aria-hidden="true">↺</span>
                                    <span>Reset</span>
                                </button>
                                <button type="button" id="filter-apply" class="btn-primary shrink-0">
                                    <span aria-hidden="true">✓</span>
                                    <span>Apply Filters</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="filter-summary" class="text-xs text-gray-500 font-data mt-2 hidden" aria-live="polite"></div>
                </div>

                {{-- Unified data area: program sidebar + students table (wide, single row) --}}
                <div id="students-table-view" class="flex flex-col flex-1 min-h-0">
                    <div id="block-view-only" class="hidden mb-4 space-y-2">
                        <div id="block-info" class="flex flex-wrap items-center gap-3">
                            <div>
                                <h2 id="block-title" class="font-heading text-lg font-bold text-gray-800"></h2>
                                <p id="block-meta" class="text-sm text-gray-600 font-data"></p>
                            </div>
                            <a id="btn-print-all-cor" href="#" class="hidden btn-primary no-underline font-data bg-green-600 hover:bg-green-700 focus:ring-green-500">Print all COR</a>
                            <a id="btn-print-master-list" href="#" class="hidden btn-primary no-underline font-data">Print master list</a>
                        </div>
                        @unless($isStaff)
                        <div id="transfer-hint" class="text-xs text-gray-500 font-data">
                            Select students: click, Ctrl+click, or <kbd class="px-1 bg-gray-200 rounded">Shift+↑/↓</kbd> to multi-select. Cut: <kbd class="px-1 bg-gray-200 rounded">Ctrl+C</kbd>. Click a block in the left tree, then Paste: <kbd class="px-1 bg-gray-200 rounded">Ctrl+V</kbd>. Or drag students onto a block.
                        </div>
                        @endunless
                        <div id="paste-target-hint" class="text-xs text-green-700 font-data hidden">
                            Paste target: <strong id="paste-target-label"></strong>
                        </div>
                        <div id="block-search-bar" class="pt-2 pb-1">
                            <label for="block-search-input" class="sr-only">Search by name or school ID</label>
                            <input type="text" id="block-search-input" class="w-full max-w-md border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus transition" placeholder="Search by name or school ID..." autocomplete="off">
                        </div>
                    </div>
                    @unless($isStaff)
                    <div class="mb-4 flex items-center gap-4 hidden font-data" id="transfer-actions">
                        <span id="selected-count" class="text-sm text-gray-600">0 selected</span>
                        <span class="text-sm text-gray-500">Cut (Ctrl+C) or drag to a block</span>
                    </div>
                    @endunless

                    {{-- Row: Program list (left) + Students table (stretch) --}}
                    <div class="flex gap-0 min-h-[480px] rounded-xl overflow-hidden border border-gray-200 shadow-2xl bg-white">
                        {{-- Program sidebar: clean white list, blue active state --}}
                        <div class="flex shrink-0 transition-[width] duration-200 ease-out w-72 bg-white border-r border-gray-200 flex flex-col" id="tree-panel">
                            <div id="tree-panel-expanded" class="flex flex-col h-full min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2 p-4 pb-2 shrink-0 border-b border-gray-200">
                                    <p class="text-sm font-heading font-bold text-gray-700 mb-0 truncate">Programs</p>
                                    <button type="button" id="tree-panel-toggle" class="shrink-0 p-1.5 rounded-lg hover:bg-gray-100 text-gray-600 hover:text-gray-800 transition text-lg leading-none" title="Collapse sidebar" aria-label="Collapse sidebar">‹</button>
                                </div>
                                <div class="flex-1 overflow-y-auto px-3 py-3 min-h-0" id="tree-panel-content">
                                    <a href="#" id="nav-students-table" class="flex items-center gap-2 py-2.5 px-3 rounded-lg text-sm font-medium text-[#1E40AF] bg-[#EFF6FF] hover:bg-[#DBEAFE] border border-[#1E40AF]/30 mb-3 font-data">
                                        <span>📋</span> Students Table
                                    </a>
                                    <p class="text-xs text-gray-500 mb-2 px-1 font-data">📁 Programs → Year → Blocks</p>
                                    <div id="tree-container" class="font-data">Loading...</div>
                                </div>
                            </div>
                            <div id="tree-panel-collapsed-bar" class="hidden items-center justify-start flex-col pt-4 bg-gray-50 border-r border-gray-200 w-12 flex-shrink-0 flex-1 min-h-0">
                                <button type="button" id="tree-panel-expand" class="p-2 rounded-lg hover:bg-gray-200 text-gray-600 hover:text-gray-800 transition text-lg font-medium" title="Expand sidebar" aria-label="Expand sidebar">›</button>
                            </div>
                        </div>

                        {{-- Students table: fills remaining width, DCOMC blue header, generous row padding --}}
                        <div class="flex-1 flex flex-col min-w-0">
                            <div class="bg-[#1E40AF] px-6 py-4 shrink-0 flex items-center justify-between flex-wrap gap-2">
                                <h2 class="font-heading text-lg font-bold text-white">Students <span id="students-count-label" class="font-data font-normal text-white/90 text-base ml-1" aria-live="polite"></span></h2>
                            </div>
                            <div class="flex-1 overflow-auto min-h-0" id="students-wrap" tabindex="0">
                                <table class="w-full text-sm font-data transition-opacity duration-200" id="students-table" role="grid" aria-label="Students list">
                                    <thead class="bg-gray-50 border-b border-gray-200 sticky top-0 z-10">
                                        <tr>
                                            <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Name</th>
                                            <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">School ID</th>
                                            <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Program</th>
                                            <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Year / Semester</th>
                                            <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Status</th>
                                            <th scope="col" class="py-3 px-4 text-right font-heading font-bold text-gray-700"><span class="inline-flex items-center gap-2"><button type="button" id="select-all-btn" class="btn-select-all">Select all</button><span class="text-gray-500 font-normal text-xs">Actions</span></span></th>
                                        </tr>
                                    </thead>
                                    <tbody id="students-tbody" class="divide-y divide-gray-100 bg-white">
                                        <tr><td colspan="6" class="py-10 px-4 text-gray-500 text-center font-data">Loading...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div id="students-pagination" class="hidden shrink-0 px-6 py-3 border-t border-gray-200 bg-gray-50 flex flex-wrap items-center justify-between gap-2 font-data text-sm"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="students-toast-container" aria-live="polite"></div>

    {{-- Edit Student Record modal (DCOMC blue header) --}}
    <div id="studentEditModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="studentEditModalTitle">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden border border-gray-200">
            <div class="px-6 py-4 bg-[#1E40AF] text-white flex justify-between items-center">
                <h3 id="studentEditModalTitle" class="font-heading font-semibold text-lg">Edit Student Record</h3>
                <button type="button" onclick="closeStudentEdit()" class="text-white hover:text-white/80 text-2xl leading-none focus:outline-none focus:ring-2 focus:ring-white/50 rounded p-1" aria-label="Close">&times;</button>
            </div>
            <form id="studentEditForm" method="POST" action="" class="p-6 overflow-y-auto max-h-[calc(90vh-130px)]">
                @csrf
                @method('PATCH')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div><label class="block mb-1 font-semibold">School ID</label><input id="student_school_id" name="school_id" class="w-full border border-gray-300 rounded-lg p-2 input-dcomc-focus"></div>
                    <div><label class="block mb-1 font-semibold">Email</label><input id="student_email" name="email" class="w-full border border-gray-300 rounded-lg p-2 input-dcomc-focus" required></div>
                    <div><label class="block mb-1 font-semibold">First Name</label><input id="student_first_name" name="first_name" class="w-full border border-gray-300 rounded-lg p-2 input-dcomc-focus" required></div>
                    <div><label class="block mb-1 font-semibold">Middle Name</label><input id="student_middle_name" name="middle_name" class="w-full border border-gray-300 rounded-lg p-2 input-dcomc-focus"></div>
                    <div><label class="block mb-1 font-semibold">Last Name</label><input id="student_last_name" name="last_name" class="w-full border border-gray-300 rounded-lg p-2 input-dcomc-focus" required></div>
                    <div><label class="block mb-1 font-semibold">Phone</label><input id="student_phone" name="phone" class="w-full border border-gray-300 rounded-lg p-2 input-dcomc-focus"></div>
                    <div>
                        <label class="block mb-1 font-semibold">Program / Course</label>
                        <select id="student_course" name="course" class="w-full border border-gray-300 rounded-lg p-2 bg-white input-dcomc-focus" required>
                            <option value="">— Select —</option>
                            @foreach($availableCourses ?? [] as $course)
                                <option value="{{ $course }}">{{ $course }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="student_major_wrap" class="hidden">
                        <label class="block mb-1 font-semibold">Major <span class="text-gray-500 text-xs">(Secondary Education only)</span></label>
                        <select id="student_major" name="major" class="w-full border border-gray-300 rounded-lg p-2 bg-white input-dcomc-focus">
                            <option value="">— None —</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold">Year Level</label>
                        <select id="student_year_level" name="year_level" class="w-full border border-gray-300 rounded-lg p-2 bg-white input-dcomc-focus" required>
                            <option value="">— Select —</option>
                            @foreach($yearLevels ?? collect() as $level)
                                <option value="{{ $level }}">{{ $level }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold">Semester</label>
                        <select id="student_semester" name="semester" class="w-full border border-gray-300 rounded-lg p-2 bg-white input-dcomc-focus" required>
                            <option value="">— Select —</option>
                            @foreach($semesters ?? collect() as $sem)
                                <option value="{{ $sem }}">{{ $sem }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold">School Year</label>
                        <select id="student_school_year" name="school_year" class="w-full border border-gray-300 rounded-lg p-2 bg-white input-dcomc-focus">
                            <option value="">— Select (defaults to enrollment year) —</option>
                            @foreach($schoolYears ?? collect() as $sy)
                                <option value="{{ $sy }}">{{ $sy }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-0.5">Based on when the student enrolled if not set.</p>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold">Block</label>
                        <select id="student_block_id" name="block_id" class="w-full border border-gray-300 rounded-lg p-2 bg-white input-dcomc-focus">
                            <option value="">Unassigned</option>
                            @foreach($blocks ?? collect() as $block)
                                <option value="{{ $block->id }}">{{ $block->code ?? $block->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold">Shift</label>
                        <select id="student_shift" name="shift" class="w-full border border-gray-300 rounded-lg p-2 bg-white input-dcomc-focus">
                            <option value="">N/A</option>
                            <option value="day">Day</option>
                            <option value="night">Night</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold">Student Type</label>
                        <select id="student_type" name="student_type" class="w-full border border-gray-300 rounded-lg p-2 bg-white input-dcomc-focus">
                            <option value="">N/A</option>
                            <option value="Freshman">Freshman</option>
                            <option value="Regular">Regular</option>
                            <option value="Transferee">Transferee</option>
                            <option value="Returnee">Returnee</option>
                            <option value="Irregular">Irregular</option>
                        </select>
                    </div>
                    <div id="student_previous_program_wrap" class="hidden md:col-span-2">
                        <label class="block mb-1 font-semibold">Old program (before transfer)</label>
                        <input type="text" id="student_previous_program" name="previous_program" class="w-full border border-gray-300 rounded-lg p-2 input-dcomc-focus" placeholder="e.g. Bachelor of Elementary Education" maxlength="255">
                        <p class="text-xs text-gray-500 mt-0.5">Shown for Irregular students; the program they were in before transferring.</p>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold">Gender</label>
                        <select id="student_gender" name="gender" class="w-full border border-gray-300 rounded-lg p-2 bg-white input-dcomc-focus">
                            <option value="">N/A</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    @unless($isStaff)
                    <div id="block_assignments_wrap" class="hidden col-span-2 border-t pt-4 mt-2">
                        <label class="block mb-1 font-semibold">Block assignments (irregular)</label>
                        <p class="text-xs text-gray-500 mb-2">Irregular students can be in multiple blocks. They appear in each block roster.</p>
                        <div id="block_assignments_list" class="mb-2 text-sm text-gray-600"></div>
                        <div class="flex items-center gap-2">
                            <select id="block_assign_add_select" class="border rounded px-2 py-1.5 text-sm flex-1">
                                <option value="">— Select block —</option>
                                @foreach($blocks ?? collect() as $b)
                                    <option value="{{ $b->id }}">{{ $b->code ?? $b->name }} — {{ $b->program ?? '—' }} ({{ $b->year_level ?? '—' }}, {{ $b->semester ?? '—' }})</option>
                                @endforeach
                            </select>
                            <button type="button" id="block_assign_add_btn" class="btn-secondary py-1.5 px-3 text-sm bg-amber-100 text-amber-800 border-amber-300 hover:bg-amber-200 focus:ring-amber-400">Add to block</button>
                        </div>
                    </div>
                    @endunless
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" id="studentEditCancelBtn" onclick="closeStudentEdit()" class="btn-secondary">Cancel</button>
                    <button type="submit" id="studentEditSubmitBtn" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
(function () {
    const base = '{{ $isStaff ? '/staff' : '/registrar' }}';
    const allowTransfer = @json(!$isStaff);
    const treeUrl = base + '/block-explorer/tree';
    const studentsTableUrl = base + '/students-explorer/students';
    const transferUrl = base + '/blocks/transfer';
    const rebalanceUrl = base + '/blocks/rebalance';
    const promotionUrl = base + '/blocks/promotion';
    const assignIrregularUrl = base + '/block-explorer/assign-irregular';
    const removeIrregularUrl = base + '/block-explorer/assign-irregular';
    const blockAssignmentsUrlTemplate = base + '/students-explorer/students/__ID__/block-assignments';

    const studentUpdateRouteTemplate = @js($updateRouteTemplate ?? '');
    const allBlocksForAssign = @json($blocks ?? []);
    const majorsByProgram = @json($majorsByProgram ?? []);

    const SELECTED_CLASS = 'bg-[#EFF6FF] border-l-4 border-l-[#1E40AF]';

    let treeData = [];
    let blockProgramMap = {}; // blockId -> program_id (for cross-program warning)
    let viewMode = 'table'; // 'table' | 'block'
    let currentBlockId = null;
    let selectedIds = new Set();
    let lastClickedBlockId = null;
    let lastClickedBlockCode = null;
    let cutBuffer = null;
    let currentStudentIds = [];
    let currentFocusIndex = -1;
    let currentBlockStudentsList = []; // full list for current block (for search filter)
    let currentEditStudentId = null;
    let currentEditStudentProgram = ''; // for filtering Assign to block by program (irregulars)
    let pagination = { currentPage: 1, perPage: 25, total: 0, lastPage: 1 };
    let studentEditFormDirty = false;

    function csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function toast(message, type) {
        type = type || 'success';
        const container = document.getElementById('students-toast-container');
        if (!container) return;
        const el = document.createElement('div');
        el.className = 'toast ' + type;
        el.setAttribute('role', 'alert');
        el.textContent = message;
        container.appendChild(el);
        setTimeout(function() {
            if (el.parentNode) el.parentNode.removeChild(el);
        }, 4000);
    }

    function renderSkeletonRows(n) {
        const cols = 6;
        let html = '';
        for (let i = 0; i < n; i++) {
            html += '<tr class="table-skeleton border-t border-gray-100"><td class="table-skeleton"><div class="skeleton-line w-24"></div></td><td class="table-skeleton"><div class="skeleton-line w-20"></div></td><td class="table-skeleton"><div class="skeleton-line w-28"></div></td><td class="table-skeleton"><div class="skeleton-line w-16"></div></td><td class="table-skeleton"><div class="skeleton-line w-20"></div></td><td class="table-skeleton"><div class="skeleton-line w-32 ml-auto"></div></td></tr>';
        }
        return html;
    }

    function updateFilterSummary() {
        const params = getFilterParams();
        const keys = Object.keys(params);
        const summaryEl = document.getElementById('filter-summary');
        if (!summaryEl) return;
        if (keys.length === 0) {
            summaryEl.classList.add('hidden');
            summaryEl.textContent = '';
            return;
        }
        summaryEl.classList.remove('hidden');
        summaryEl.textContent = 'Filters: ' + keys.map(k => k.replace(/_/g, ' ') + ' = ' + params[k]).join('; ');
    }

    function isSecondaryEducation(program) {
        if (!program) return false;
        return /secondary\s*education/i.test(program);
    }

    function updateMajorDropdown(program, selectedMajor) {
        const wrap = document.getElementById('student_major_wrap');
        const select = document.getElementById('student_major');
        const valueToKeep = selectedMajor !== undefined ? selectedMajor : select.value;
        select.innerHTML = '<option value="">— None —</option>';
        if (isSecondaryEducation(program)) {
            wrap.classList.remove('hidden');
            const majors = majorsByProgram[program] || [];
            majors.forEach(function(m) {
                const opt = document.createElement('option');
                opt.value = m;
                opt.textContent = m;
                if (m === valueToKeep) opt.selected = true;
                select.appendChild(opt);
            });
            if (valueToKeep && select.value !== valueToKeep) select.value = valueToKeep;
        } else {
            wrap.classList.add('hidden');
            select.value = '';
        }
    }

    document.getElementById('student_course').addEventListener('change', function() {
        updateMajorDropdown(this.value);
    });

    function togglePreviousProgramVisibility(isIrregular) {
        const wrap = document.getElementById('student_previous_program_wrap');
        if (isIrregular) {
            wrap.classList.remove('hidden');
        } else {
            wrap.classList.add('hidden');
            document.getElementById('student_previous_program').value = '';
        }
    }

    document.getElementById('student_type').addEventListener('change', function() {
        togglePreviousProgramVisibility(this.value === 'Irregular');
    });

    window.closeStudentEdit = function() {
        if (studentEditFormDirty && !confirm('Discard unsaved changes?')) return;
        const modal = document.getElementById('studentEditModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        studentEditFormDirty = false;
    };

    window.openStudentEdit = function(button) {
        const raw = button.getAttribute('data-student');
        if (!raw) return;
        let student;
        try {
            student = typeof raw === 'string' ? JSON.parse(raw) : raw;
        } catch (e) {
            return;
        }
        document.getElementById('studentEditForm').action = studentUpdateRouteTemplate.replace('__ID__', student.id) + '?redirect=students-explorer';
        document.getElementById('student_school_id').value = student.school_id || '';
        document.getElementById('student_email').value = student.email || '';
        document.getElementById('student_first_name').value = student.first_name || '';
        document.getElementById('student_middle_name').value = student.middle_name || '';
        document.getElementById('student_last_name').value = student.last_name || '';
        document.getElementById('student_phone').value = student.phone || '';
        document.getElementById('student_course').value = student.course || '';
        document.getElementById('student_year_level').value = student.year_level || '';
        document.getElementById('student_semester').value = student.semester || '';
        const schoolYearSelect = document.getElementById('student_school_year');
        const schoolYearValue = student.school_year || student.enrollment_school_year || (schoolYearSelect && schoolYearSelect.options.length > 1 ? schoolYearSelect.options[1].value : '');
        if (schoolYearSelect) {
            const hasOption = schoolYearValue && Array.from(schoolYearSelect.options).some(function(o) { return o.value === schoolYearValue; });
            if (schoolYearValue && !hasOption) {
                const opt = document.createElement('option');
                opt.value = schoolYearValue;
                opt.textContent = schoolYearValue + ' (enrollment)';
                schoolYearSelect.appendChild(opt);
            }
            schoolYearSelect.value = schoolYearValue || '';
        }
        document.getElementById('student_block_id').value = student.block_id || '';
        document.getElementById('student_shift').value = student.shift || '';
        document.getElementById('student_type').value = student.student_type || '';
        document.getElementById('student_gender').value = student.gender || '';
        document.getElementById('student_previous_program').value = student.previous_program || '';
        togglePreviousProgramVisibility(student.student_type === 'Irregular');
        updateMajorDropdown(student.course || '', student.major || '');
        currentEditStudentId = student.id;
        currentEditStudentProgram = (student.course || (student.block && student.block.program) || '').trim();
        const isIrregular = (student.student_type === 'Irregular' || student.student_type === 'Shifter' || student.status_color === 'yellow');
        const wrap = document.getElementById('block_assignments_wrap');
        if (wrap) {
            if (allowTransfer && isIrregular) {
                wrap.classList.remove('hidden');
                loadBlockAssignments(student.id);
            } else {
                wrap.classList.add('hidden');
            }
        }
        studentEditFormDirty = false;
        const modal = document.getElementById('studentEditModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(function() {
            const first = modal.querySelector('input:not([type="hidden"]), select, textarea');
            if (first) first.focus();
        }, 50);
    };

    (function initStudentEditForm() {
        const form = document.getElementById('studentEditForm');
        const cancelBtn = document.getElementById('studentEditCancelBtn');
        const submitBtn = document.getElementById('studentEditSubmitBtn');
        if (!form || !submitBtn) return;
        form.querySelectorAll('input, select, textarea').forEach(function(el) {
            el.addEventListener('input', function() { studentEditFormDirty = true; });
            el.addEventListener('change', function() { studentEditFormDirty = true; });
        });
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (submitBtn.disabled) return;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving…';
            const action = form.action;
            const formData = new FormData(form);
            const body = new URLSearchParams(formData);
            fetch(action, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' }, body: body })
                .then(function(r) {
                    if (r.redirected) return { ok: true };
                    return r.json().then(function(data) { return { ok: r.ok, data: data }; });
                })
                .then(function(result) {
                    if (result.ok) {
                        studentEditFormDirty = false;
                        toast('Student updated.');
                        closeStudentEdit();
                        if (viewMode === 'table') loadAllStudents(); else if (currentBlockId) loadStudents(currentBlockId);
                    } else {
                        toast((result.data && result.data.message) || 'Update failed.', 'error');
                    }
                })
                .catch(function() { toast('Update failed.', 'error'); })
                .finally(function() { submitBtn.disabled = false; submitBtn.textContent = 'Save Changes'; });
        });
        const modalEl = document.getElementById('studentEditModal');
        modalEl.addEventListener('keydown', function(e) {
            if (e.key !== 'Tab' || !this.classList.contains('flex')) return;
            const focusables = this.querySelectorAll('input:not([type="hidden"]), select, textarea, button, [href]');
            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            if (e.shiftKey) {
                if (document.activeElement === first) { e.preventDefault(); last && last.focus(); }
            } else {
                if (document.activeElement === last) { e.preventDefault(); first && first.focus(); }
            }
        });
        modalEl.addEventListener('click', function(e) {
            if (e.target === modalEl) closeStudentEdit();
        });
    })();

    function loadBlockAssignments(userId) {
        if (!allowTransfer) return;
        const listEl = document.getElementById('block_assignments_list');
        const addSelect = document.getElementById('block_assign_add_select');
        if (!listEl || !addSelect || !userId) { if (listEl) listEl.innerHTML = ''; return; }
        fetch(blockAssignmentsUrlTemplate.replace('__ID__', userId), { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(function(data) {
                const assignments = data.assignments || [];
                if (assignments.length === 0) {
                    listEl.innerHTML = '<span class="text-gray-400">None. Add below.</span>';
                } else {
                    listEl.innerHTML = assignments.map(function(a) {
                        const b = a.block || {};
                        return '<span class="inline-flex items-center mr-2 mb-1 px-2 py-0.5 rounded bg-amber-50 text-amber-800">' + escapeHtml(b.code || b.name || 'Block') + ' (' + escapeHtml(b.year_level || '') + ', ' + escapeHtml(b.semester || '') + ')<button type="button" class="ml-1 remove-assign text-red-600 hover:text-red-800 text-xs font-semibold" data-user-id="' + userId + '" data-block-id="' + (a.block_id || '') + '" title="Remove from block">Remove</button></span>';
                    }).join('');
                    listEl.querySelectorAll('.remove-assign').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            removeBlockAssignment(btn.dataset.userId, btn.dataset.blockId);
                        });
                    });
                }
                const assignedIds = assignments.map(function(a) { return String(a.block_id); });
                const programNorm = (currentEditStudentProgram || '').trim().toLowerCase();
                const blocksInProgram = programNorm ? allBlocksForAssign.filter(function(b) {
                    return ((b.program || '').trim().toLowerCase()) === programNorm;
                }) : allBlocksForAssign;
                const blocksAvailable = blocksInProgram.filter(function(b) { return assignedIds.indexOf(String(b.id)) === -1; });
                addSelect.innerHTML = '<option value="">— Select block —</option>' + blocksAvailable.map(function(b) {
                    return '<option value="' + b.id + '">' + escapeHtml(b.code || b.name || '') + ' (' + (b.year_level || '') + ', ' + (b.semester || '') + ')</option>';
                }).join('');
            })
            .catch(function() { listEl.innerHTML = '<span class="text-red-500">Failed to load.</span>'; });
    }

    function removeBlockAssignment(userId, blockId) {
        if (!allowTransfer || !userId || !blockId) return;
        fetch(removeIrregularUrl, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
            body: JSON.stringify({ user_id: parseInt(userId, 10), block_id: parseInt(blockId, 10) })
        }).then(r => r.json()).then(function() { if (currentEditStudentId) loadBlockAssignments(currentEditStudentId); }).catch(function() {});
    }

    var blockAssignAddBtn = document.getElementById('block_assign_add_btn');
    if (blockAssignAddBtn && allowTransfer) {
        blockAssignAddBtn.addEventListener('click', function() {
            const userId = currentEditStudentId;
            const select = document.getElementById('block_assign_add_select');
            const blockId = select ? select.value : '';
            if (!userId || !blockId) return;
            fetch(assignIrregularUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
                body: JSON.stringify({ user_id: parseInt(userId, 10), block_id: parseInt(blockId, 10) })
            }).then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); }).then(function(res) {
                if (res.data && res.data.success === false) {
                    alert(res.data.message || 'Assignment failed.');
                    return;
                }
                loadBlockAssignments(userId);
                if (currentBlockId) { loadStudents(currentBlockId); fetchTree(); }
            }).catch(function() {});
        });
    }

    function fetchTree() {
        const el = document.getElementById('tree-container');
        if (el) el.innerHTML = '<div class="animate-pulse space-y-2"><div class="h-4 bg-gray-200 rounded w-3/4"></div><div class="h-4 bg-gray-200 rounded w-1/2"></div><div class="h-4 bg-gray-200 rounded w-5/6"></div></div>';
        fetch(treeUrl).then(r => r.json()).then(data => {
            treeData = data.tree || [];
            renderTree();
        }).catch(() => { if (el) el.innerHTML = '<p class="text-red-500 text-sm">Failed to load.</p>'; });
    }

    function escapeHtml(s) {
        if (!s) return '';
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    function statusBadge(s) {
        const type = s.student_type || '';
        const color = s.status_color || '';
        const processStatus = (s.process_status || '').toLowerCase();
        if (color === 'yellow') return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-500 text-white">' + escapeHtml(type || 'Irregular') + '</span>';
        if (color === 'blue') return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-600 text-white">' + escapeHtml(type || 'Returnee') + '</span>';
        if (color === 'green') return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-600 text-white">' + escapeHtml(type || 'Transferee') + '</span>';
        if (processStatus === 'approved' || processStatus === 'completed') return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-600 text-white">' + escapeHtml(s.process_status) + '</span>';
        if (processStatus === 'pending' || processStatus === 'in progress') return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-500 text-white">' + escapeHtml(s.process_status) + '</span>';
        if (processStatus === 'rejected' || processStatus === 'denied') return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-600 text-white">' + escapeHtml(s.process_status) + '</span>';
        if (s.process_status) return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-600 text-white">' + escapeHtml(s.process_status) + '</span>';
        return escapeHtml(type || '—');
    }

    function renderTree() {
        const el = document.getElementById('tree-container');
        if (!treeData.length) { el.innerHTML = '<p class="text-gray-500">No blocks.</p>'; return; }
        let html = '';
        treeData.forEach(prog => {
            const progLabel = escapeHtml(prog.label || 'Program');
            html += '<details class="tree-program mb-2 rounded border border-gray-200 bg-gray-50">';
            html += '<summary class="list-none cursor-pointer font-medium text-gray-700 py-2 px-3 hover:bg-gray-100 rounded flex items-center justify-between"><span>📁 ' + progLabel + '</span><span class="text-xs text-gray-400">▾</span></summary>';
            html += '<div class="pl-2 pb-2">';
            (prog.years || []).forEach(yr => {
                const yrLabel = escapeHtml(yr.label || '');
                html += '<details class="tree-year mb-1 rounded border border-gray-100 bg-white ml-2">';
                html += '<summary class="list-none cursor-pointer text-gray-600 text-sm py-1.5 px-3 hover:bg-gray-50 rounded flex items-center justify-between"><span>📂 ' + yrLabel + '</span><span class="text-xs text-gray-400">▾</span></summary>';
                html += '<div class="pl-2 pb-1">';
                (yr.blocks || []).forEach(blk => {
                    const cap = blk.max_capacity || 50;
                    const size = blk.current_size || 0;
                    const pct = cap > 0 ? Math.min(100, Math.round((size / cap) * 100)) : 0;
                    const blkLabel = escapeHtml(blk.label || blk.code || 'Block');
                    const programId = blk.program_id != null ? blk.program_id : (prog.program_id != null ? prog.program_id : '');
                    html += '<div class="ml-2 py-0.5 flex items-center gap-2"><a href="#" class="block-link drop-target text-sm py-1.5 px-2 text-[#1E40AF] hover:underline hover:bg-[#EFF6FF] flex-1 min-w-0 block rounded font-data" data-block-id="' + blk.id + '" data-program-id="' + programId + '" data-code="' + (blk.code || '') + '" data-size="' + size + '" data-cap="' + cap + '">📄 ' + blkLabel + ' (' + size + '/' + cap + ')</a><div class="tree-capacity-bar shrink-0 w-12" role="presentation"><div class="tree-capacity-fill" style="width:' + pct + '%"></div></div></div>';
                });
                html += '</div></details>';
            });
            html += '</div></details>';
        });
        el.innerHTML = html;
        blockProgramMap = {};
        treeData.forEach(prog => {
            const progId = prog.program_id != null ? prog.program_id : prog.id;
            (prog.years || []).forEach(yr => {
                (yr.blocks || []).forEach(blk => {
                    blockProgramMap[blk.id] = blk.program_id != null ? blk.program_id : progId;
                });
            });
        });
        el.querySelectorAll('.block-link').forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                const id = link.dataset.blockId;
                const code = link.dataset.code;
                lastClickedBlockId = id;
                lastClickedBlockCode = code || ('Block ' + id);
                document.querySelectorAll('.drop-target').forEach(t => t.classList.remove('ring-2', 'ring-[#1E40AF]', 'bg-[#EFF6FF]'));
                link.classList.add('ring-2', 'ring-[#1E40AF]', 'bg-[#EFF6FF]');
                document.getElementById('paste-target-hint').classList.remove('hidden');
                document.getElementById('paste-target-label').textContent = lastClickedBlockCode;
                selectBlock(id, code, link.dataset.size, link.dataset.cap);
            });
            link.addEventListener('dragstart', e => e.preventDefault());
            link.addEventListener('dragover', e => { e.preventDefault(); e.dataTransfer.dropEffect = 'move'; link.classList.add('bg-[#EFF6FF]', 'ring-2', 'ring-[#1E40AF]'); });
            link.addEventListener('dragleave', () => { link.classList.remove('bg-[#EFF6FF]', 'ring-2', 'ring-[#1E40AF]'); });
            link.addEventListener('drop', function(e) {
                e.preventDefault();
                link.classList.remove('bg-[#EFF6FF]', 'ring-2', 'ring-[#1E40AF]');
                const raw = e.dataTransfer.getData('application/json');
                if (!raw) return;
                try {
                    const { fromBlockId, studentIds } = JSON.parse(raw);
                    const toBlockId = parseInt(this.dataset.blockId, 10);
                    if (toBlockId === parseInt(fromBlockId, 10)) return;
                    doTransfer(parseInt(fromBlockId, 10), toBlockId, studentIds, true);
                } catch (err) {}
            });
        });
    }

    function switchToTableView() {
        viewMode = 'table';
        currentBlockId = null;
        document.getElementById('filter-panel').classList.remove('hidden');
        document.getElementById('block-view-only').classList.add('hidden');
        var printAllBtn = document.getElementById('btn-print-all-cor');
        if (printAllBtn) printAllBtn.classList.add('hidden');
        var ta2 = document.getElementById('transfer-actions');
        if (ta2) ta2.classList.add('hidden');
        document.querySelectorAll('.block-link').forEach(l => { l.classList.remove('ring-2', 'ring-green-500', 'bg-green-50'); });
        document.getElementById('paste-target-hint').classList.add('hidden');
        const nav = document.getElementById('nav-students-table');
        nav.classList.add('text-[#1E40AF]', 'bg-[#EFF6FF]', 'hover:bg-[#DBEAFE]', 'border-[#1E40AF]/30');
        nav.classList.remove('text-gray-600', 'bg-white', 'hover:bg-gray-50', 'border-gray-200');
        loadAllStudents();
    }

    function switchToBlockView() {
        viewMode = 'block';
        document.getElementById('filter-panel').classList.add('hidden');
        document.getElementById('block-view-only').classList.remove('hidden');
        const nav = document.getElementById('nav-students-table');
        nav.classList.remove('text-[#1E40AF]', 'bg-[#EFF6FF]', 'hover:bg-[#DBEAFE]', 'border-[#1E40AF]/30');
        nav.classList.add('text-gray-600', 'bg-white', 'hover:bg-gray-50', 'border-gray-200');
    }

    function selectBlock(id, code, size, cap) {
        switchToBlockView();
        currentBlockId = id;
        selectedIds.clear();
        document.getElementById('block-info').classList.remove('hidden');
        document.getElementById('block-title').textContent = code || 'Block ' + id;
        document.getElementById('block-meta').textContent = (size || 0) + ' / ' + (cap || 50) + ' students';
        var ta = document.getElementById('transfer-actions');
        if (ta) ta.classList.add('hidden');
        var th = document.getElementById('transfer-hint');
        if (th) th.classList.add('hidden');
        var link = document.querySelector('.block-link[data-block-id="' + id + '"]');
        if (link) link.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        loadStudents(id);
    }

    function studentMatchesSearch(s, term) {
        if (!term) return true;
        const t = term.toLowerCase();
        const first = (s.first_name || '').toLowerCase();
        const last = (s.last_name || '').toLowerCase();
        const schoolId = (s.school_id || '').toLowerCase();
        const fullName = (first + ' ' + last + ' ' + last + ' ' + first).trim();
        return fullName.includes(t) || schoolId.includes(t);
    }

    function renderBlockStudentsRows(rows) {
        const tbody = document.getElementById('students-tbody');
        if (!rows.length) {
            const msg = currentBlockStudentsList.length ? 'No matching students.' : 'No students.';
            tbody.innerHTML = '<tr><td colspan="6" class="py-10 px-4 text-gray-500 text-center font-data">' + msg + '</td></tr>';
            currentStudentIds = [];
            return;
        }
        tbody.innerHTML = rows.map(s => {
            const name = (s.last_name || '') + ', ' + (s.first_name || '') || s.name || '—';
            const yearSem = (s.year_level || '—') + ' / ' + (s.semester || '—');
            const status = statusBadge(s);
            const program = escapeHtml(s.course || '—');
            const payload = JSON.stringify({
                id: s.id,
                school_id: s.school_id,
                first_name: s.first_name,
                middle_name: s.middle_name,
                last_name: s.last_name,
                email: s.email,
                course: s.course,
                major: s.major,
                year_level: s.year_level,
                semester: s.semester,
                school_year: s.school_year,
                enrollment_school_year: s.enrollment_school_year,
                block_id: s.block_id,
                shift: s.shift,
                student_type: s.student_type,
                previous_program: s.previous_program,
                phone: s.phone,
                gender: s.gender
            });
            return '<tr class="border-t border-gray-100 student-row cursor-pointer hover:bg-blue-50/50 select-none transition-colors font-data" data-student-id="' + s.id + '" draggable="true">' +
                '<td class="py-4 px-4">' + escapeHtml(name) + '</td><td class="py-4 px-4">' + escapeHtml(s.school_id || '—') + '</td><td class="py-4 px-4">' + program + '</td>' +
                '<td class="py-4 px-4">' + escapeHtml(yearSem) + '</td><td class="py-4 px-4">' + status + '</td>' +
                '<td class="py-4 px-4 text-right"><span class="inline-flex items-center gap-2 flex-wrap justify-end"><a href="' + base + '/students/' + s.id + '/cor" class="btn-action-cor" target="_blank" onclick="event.stopPropagation();">View/Print COR</a><button type="button" class="btn-action-edit" data-student="' + payload.replace(/"/g, '&quot;') + '" onclick="event.stopPropagation(); openStudentEdit(this);">View / Edit</button></span></td></tr>';
        }).join('');
        const allIds = rows.map(r => r.id);
        currentStudentIds = allIds;
        var printAllBtn = document.getElementById('btn-print-all-cor');
        if (printAllBtn) {
            printAllBtn.classList.remove('hidden');
            printAllBtn.href = base + '/block-explorer/blocks/' + currentBlockId + '/print-all-cor';
        }
        var printMasterListBtn = document.getElementById('btn-print-master-list');
        if (printMasterListBtn) {
            printMasterListBtn.classList.remove('hidden');
            printMasterListBtn.href = base + '/block-explorer/blocks/' + currentBlockId + '/print-master-list';
        }
        tbody.querySelectorAll('.student-row').forEach((row, i) => {
            const id = rows[i].id;
            const rowIndex = i;
            row.addEventListener('click', e => {
                if (e.target.tagName === 'BUTTON') return;
                currentFocusIndex = rowIndex;
                if (e.shiftKey) {
                    selectRange(id, allIds);
                } else if (e.ctrlKey || e.metaKey) {
                    toggleSelect(id);
                } else {
                    selectedIds.clear();
                    selectedIds.add(id);
                    refreshRowHighlights();
                    updateSelectionUI();
                }
            });
            row.addEventListener('dragstart', function(e) {
                const ids = (selectedIds.has(id) && selectedIds.size > 0) ? Array.from(selectedIds) : [id];
                e.dataTransfer.setData('application/json', JSON.stringify({ fromBlockId: currentBlockId, studentIds: ids }));
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', ids.length + ' student(s)');
                this.classList.add('opacity-50');
            });
            row.addEventListener('dragend', function() { this.classList.remove('opacity-50'); });
        });
    }

    function filterBlockStudents() {
        const input = document.getElementById('block-search-input');
        const term = input ? input.value.trim() : '';
        const filtered = term ? currentBlockStudentsList.filter(s => studentMatchesSearch(s, term)) : currentBlockStudentsList;
        renderBlockStudentsRows(filtered);
    }

    function loadStudents(blockId) {
        const tbody = document.getElementById('students-tbody');
        const searchInput = document.getElementById('block-search-input');
        if (searchInput) searchInput.value = '';
        tbody.innerHTML = renderSkeletonRows(4);
        fetch(base + '/block-explorer/blocks/' + blockId + '/students?per_page=100').then(r => r.json()).then(data => {
            const rows = data.data || [];
            currentBlockStudentsList = rows;
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="6" class="py-10 px-4 text-gray-500 text-center font-data">No students.</td></tr>';
                var thShow = document.getElementById('transfer-hint');
                if (thShow && allowTransfer) thShow.classList.remove('hidden');
                var printAllBtn = document.getElementById('btn-print-all-cor');
                if (printAllBtn) { printAllBtn.classList.add('hidden'); }
                var printMasterListBtn = document.getElementById('btn-print-master-list');
                if (printMasterListBtn) {
                    printMasterListBtn.classList.remove('hidden');
                    printMasterListBtn.href = base + '/block-explorer/blocks/' + blockId + '/print-master-list';
                }
                return;
            }
            renderBlockStudentsRows(rows);
            var thShow2 = document.getElementById('transfer-hint');
            if (thShow2 && allowTransfer) thShow2.classList.remove('hidden');
        }).catch(() => { tbody.innerHTML = '<tr><td colspan="6" class="py-10 px-4 text-red-500 text-center font-data">Failed to load.</td></tr>'; toast('Failed to load block students.', 'error'); });
    }

    function getFilterParams(includePagination) {
        const q = {};
        const v = id => (document.getElementById(id) && document.getElementById(id).value) ? document.getElementById(id).value.trim() : '';
        if (v('filter-student-number')) q.student_number = v('filter-student-number');
        if (v('filter-program')) q.program = v('filter-program');
        if (v('filter-school-year')) q.school_year = v('filter-school-year');
        if (v('filter-first-name')) q.first_name = v('filter-first-name');
        if (v('filter-year-level')) q.year_level = v('filter-year-level');
        if (v('filter-last-name')) q.last_name = v('filter-last-name');
        if (v('filter-semester')) q.semester = v('filter-semester');
        if (v('filter-status')) q.status = v('filter-status');
        if (v('filter-folder')) q.folder = v('filter-folder');
        if (includePagination) {
            q.page = pagination.currentPage;
            q.per_page = pagination.perPage;
        }
        return q;
    }

    function renderPagination() {
        const wrap = document.getElementById('students-pagination');
        const countEl = document.getElementById('students-count-label');
        if (viewMode !== 'table') {
            if (wrap) { wrap.classList.add('hidden'); wrap.innerHTML = ''; }
            if (countEl) countEl.textContent = '';
            return;
        }
        const total = pagination.total;
        const page = pagination.currentPage;
        const lastPage = pagination.lastPage;
        const perPage = pagination.perPage;
        const from = total === 0 ? 0 : (page - 1) * perPage + 1;
        const to = Math.min(page * perPage, total);
        if (countEl) countEl.textContent = total === 0 ? '' : ' (' + from + '–' + to + ' of ' + total + ')';
        if (!wrap) return;
        if (lastPage <= 1 && total <= perPage) {
            wrap.classList.add('hidden');
            wrap.innerHTML = '';
            return;
        }
        wrap.classList.remove('hidden');
        let html = '<div class="flex items-center gap-2 flex-wrap">';
        html += '<span class="text-gray-600">Showing ' + from + '–' + to + ' of ' + total + '</span>';
        html += '<div class="flex items-center gap-1">';
        html += '<button type="button" class="pagination-prev px-3 py-1.5 rounded border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:ring-offset-1" ' + (page <= 1 ? 'disabled' : '') + '>Previous</button>';
        for (let i = 1; i <= lastPage; i++) {
            if (lastPage > 7 && (i > 2 && i < lastPage - 1 && Math.abs(i - page) > 1)) {
                if (i === 3) html += '<span class="px-2 text-gray-400">…</span>';
                if (i === page + 2) html += '<span class="px-2 text-gray-400">…</span>';
                continue;
            }
            const active = i === page;
            html += '<button type="button" class="pagination-page px-3 py-1.5 rounded text-sm font-medium ' + (active ? 'bg-[#1E40AF] text-white' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50') + ' focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:ring-offset-1" data-page="' + i + '">' + i + '</button>';
        }
        html += '<button type="button" class="pagination-next px-3 py-1.5 rounded border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:ring-offset-1" ' + (page >= lastPage ? 'disabled' : '') + '>Next</button>';
        html += '</div>';
        html += '<label class="text-gray-600">Per page:</label><select id="per-page-select" class="border border-gray-300 rounded px-2 py-1 text-sm bg-white">';
        [10, 25, 50, 100].forEach(function(n) { html += '<option value="' + n + '"' + (perPage === n ? ' selected' : '') + '>' + n + '</option>'; });
        html += '</select></div>';
        wrap.innerHTML = html;
        wrap.querySelector('.pagination-prev').addEventListener('click', function() { if (pagination.currentPage > 1) { pagination.currentPage--; loadAllStudents(); } });
        wrap.querySelector('.pagination-next').addEventListener('click', function() { if (pagination.currentPage < pagination.lastPage) { pagination.currentPage++; loadAllStudents(); } });
        wrap.querySelectorAll('.pagination-page').forEach(function(btn) {
            btn.addEventListener('click', function() { const p = parseInt(this.getAttribute('data-page'), 10); if (p !== pagination.currentPage) { pagination.currentPage = p; loadAllStudents(); } });
        });
        const perPageSelect = document.getElementById('per-page-select');
        if (perPageSelect) perPageSelect.addEventListener('change', function() { pagination.perPage = parseInt(this.value, 10); pagination.currentPage = 1; loadAllStudents(); });
    }

    function loadAllStudents() {
        const tbody = document.getElementById('students-tbody');
        const countEl = document.getElementById('students-count-label');
        if (countEl) countEl.textContent = '';
        tbody.innerHTML = renderSkeletonRows(5);
        const params = new URLSearchParams(getFilterParams(true));
        const url = studentsTableUrl + (params.toString() ? '?' + params.toString() : '');
        fetch(url).then(r => r.json()).then(data => {
            const rows = data.data || [];
            pagination.total = data.total ?? 0;
            pagination.lastPage = data.last_page ?? 1;
            pagination.currentPage = data.current_page ?? 1;
            pagination.perPage = data.per_page ?? pagination.perPage;
            if (!rows.length) {
                const clearFilters = '<button type="button" class="text-[#1E40AF] hover:underline font-semibold" id="empty-clear-filters">Clear filters</button>';
                tbody.innerHTML = '<tr><td colspan="6" class="py-10 px-4 text-gray-500 text-center font-data">No students found. ' + clearFilters + '</td></tr>';
                document.getElementById('empty-clear-filters') && document.getElementById('empty-clear-filters').addEventListener('click', function() {
                    document.getElementById('filter-reset').click();
                });
            } else {
                tbody.innerHTML = rows.map(s => {
                    const name = (s.last_name || '') + ', ' + (s.first_name || '') || s.name || '—';
                    const yearSem = (s.year_level || '—') + ' / ' + (s.semester || '—');
                    const status = statusBadge(s);
                    const program = escapeHtml(s.course || '—');
                    const payload = JSON.stringify({
                        id: s.id,
                        school_id: s.school_id,
                        first_name: s.first_name,
                        middle_name: s.middle_name,
                        last_name: s.last_name,
                        email: s.email,
                        course: s.course,
                        major: s.major,
                        year_level: s.year_level,
                        semester: s.semester,
                        school_year: s.school_year,
                        enrollment_school_year: s.enrollment_school_year,
                        block_id: s.block_id,
                        shift: s.shift,
                        student_type: s.student_type,
                        previous_program: s.previous_program,
                        phone: s.phone,
                        gender: s.gender
                    });
                    return '<tr class="border-t border-gray-100 student-row hover:bg-blue-50/50 transition-colors font-data" data-student-id="' + s.id + '">' +
                        '<td class="py-4 px-4">' + escapeHtml(name) + '</td><td class="py-4 px-4">' + escapeHtml(s.school_id || '—') + '</td><td class="py-4 px-4">' + program + '</td>' +
                        '<td class="py-4 px-4">' + escapeHtml(yearSem) + '</td><td class="py-4 px-4">' + status + '</td>' +
                        '<td class="py-4 px-4 text-right"><span class="inline-flex items-center gap-2 flex-wrap justify-end"><a href="' + base + '/students/' + s.id + '/cor" class="btn-action-cor" target="_blank" onclick="event.stopPropagation();">View/Print COR</a><button type="button" class="btn-action-edit" data-student="' + payload.replace(/"/g, '&quot;') + '" onclick="event.stopPropagation(); openStudentEdit(this);">View / Edit</button></span></td></tr>';
                }).join('');
            }
            currentStudentIds = rows.map(r => r.id);
            updateFilterSummary();
            renderPagination();
        }).catch(() => {
            tbody.innerHTML = '<tr><td colspan="6" class="py-10 px-4 text-red-500 text-center font-data">Failed to load.</td></tr>';
            toast('Failed to load students.', 'error');
        });
    }

    function toggleSelect(id) {
        if (selectedIds.has(id)) selectedIds.delete(id); else selectedIds.add(id);
        refreshRowHighlights();
        updateSelectionUI();
    }

    function selectRange(clickedId, allIds) {
        const idx = allIds.indexOf(clickedId);
        if (idx === -1) { selectedIds.add(clickedId); refreshRowHighlights(); updateSelectionUI(); return; }
        const first = document.querySelector('#students-tbody .student-row.selected-highlight');
        const firstIdx = first ? allIds.indexOf(parseInt(first.dataset.studentId, 10)) : idx;
        const low = Math.min(firstIdx, idx);
        const high = Math.max(firstIdx, idx);
        for (let i = low; i <= high; i++) selectedIds.add(allIds[i]);
        refreshRowHighlights();
        updateSelectionUI();
    }

    function refreshRowHighlights() {
        document.querySelectorAll('#students-tbody .student-row').forEach(row => {
            const id = parseInt(row.dataset.studentId, 10);
            if (selectedIds.has(id)) {
                row.classList.add('bg-[#EFF6FF]', 'border-l-4', 'border-l-[#1E40AF]', 'selected-highlight');
            } else {
                row.classList.remove('bg-[#EFF6FF]', 'border-l-4', 'border-l-[#1E40AF]', 'selected-highlight');
            }
        });
    }

    document.getElementById('select-all-btn').addEventListener('click', function(e) {
        e.stopPropagation();
        const rows = document.querySelectorAll('#students-tbody .student-row');
        if (selectedIds.size === rows.length) {
            rows.forEach(r => selectedIds.delete(parseInt(r.dataset.studentId, 10)));
        } else {
            rows.forEach(r => selectedIds.add(parseInt(r.dataset.studentId, 10)));
        }
        refreshRowHighlights();
        updateSelectionUI();
    });

    function updateSelectionUI() {
        const n = selectedIds.size;
        var sc = document.getElementById('selected-count');
        if (sc) sc.textContent = n + ' selected';
        var ta = document.getElementById('transfer-actions');
        if (ta) ta.classList.toggle('hidden', !allowTransfer || n === 0);
        var sab = document.getElementById('select-all-btn');
        if (sab) sab.textContent = n === document.querySelectorAll('#students-tbody .student-row').length && n > 0 ? 'Deselect all' : 'Select all';
    }

    function doTransfer(fromBlockId, toBlockId, studentIds, fromDrop) {
        if (!allowTransfer || !studentIds.length) return;
        const fromProg = blockProgramMap[fromBlockId];
        const toProg = blockProgramMap[toBlockId];
        if (fromProg != null && toProg != null && String(fromProg) !== String(toProg)) {
            if (!confirm('Transferring this student to a different program will turn their Student Type into Irregular. Continue?')) return;
        }
        fetch(transferUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
            body: JSON.stringify({ from_block_id: fromBlockId, to_block_id: toBlockId, student_ids: studentIds })
        }).then(r => r.json()).then(data => {
            if (data.success) {
                const n = data.moved ?? studentIds.length;
                selectedIds.clear();
                cutBuffer = null;
                refreshRowHighlights();
                updateSelectionUI();
                var ta = document.getElementById('transfer-actions');
                if (ta) ta.classList.add('hidden');
                if (currentBlockId === String(fromBlockId)) loadStudents(currentBlockId);
                else if (currentBlockId === String(toBlockId)) loadStudents(currentBlockId);
                fetchTree();
                if (n > 0) toast('Transferred ' + n + ' student(s).');
            } else {
                toast(data.message || (data.errors && data.errors.transfer ? data.errors.transfer[0] : 'Transfer failed.'), 'error');
            }
        }).catch(() => toast('Transfer failed.', 'error'));
    }

    document.addEventListener('keydown', function(e) {
        const tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
        if (tag === 'input' || tag === 'textarea' || tag === 'select') return;
        if (e.ctrlKey || e.metaKey) {
            const key = (e.key || '').toLowerCase();
            if (key === 'c') {
                e.preventDefault();
                e.stopPropagation();
                if (selectedIds.size && currentBlockId) {
                    cutBuffer = { fromBlockId: parseInt(currentBlockId, 10), studentIds: Array.from(selectedIds) };
                }
                return;
            }
            if (key === 'v') {
                e.preventDefault();
                e.stopPropagation();
                if (!allowTransfer) return;
                if (cutBuffer && cutBuffer.studentIds.length) {
                    let toId = lastClickedBlockId ? parseInt(lastClickedBlockId, 10) : null;
                    if (toId == null && currentBlockId) {
                        const cur = parseInt(currentBlockId, 10);
                        if (cur !== cutBuffer.fromBlockId) toId = cur;
                    }
                    if (toId != null && toId !== cutBuffer.fromBlockId) {
                        doTransfer(cutBuffer.fromBlockId, toId, cutBuffer.studentIds, false);
                    }
                }
                return;
            }
        }
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            if (currentStudentIds.length === 0) return;
            const shift = e.shiftKey;
            const anchor = currentFocusIndex >= 0 ? currentFocusIndex : -1;
            let next = e.key === 'ArrowDown' ? anchor + 1 : anchor - 1;
            next = Math.max(0, Math.min(currentStudentIds.length - 1, next));
            e.preventDefault();
            if (shift) {
                selectedIds.add(currentStudentIds[next]);
            } else {
                selectedIds.clear();
                selectedIds.add(currentStudentIds[next]);
            }
            currentFocusIndex = next;
            refreshRowHighlights();
            updateSelectionUI();
            const rowEl = document.querySelector('#students-tbody .student-row[data-student-id="' + currentStudentIds[next] + '"]');
            if (rowEl) rowEl.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            return;
        }
    }, true);

    if (allowTransfer) {
        var br = document.getElementById('btn-rebalance');
        if (br) br.addEventListener('click', function() {
            if (!currentBlockId) { toast('Select a block first.', 'error'); return; }
            if (!confirm('Run rebalance for this block?')) return;
            fetch(rebalanceUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }, body: JSON.stringify({ block_id: parseInt(currentBlockId, 10) }) })
                .then(r => r.json()).then(d => { toast('Moved ' + (d.moved || 0) + ' student(s).'); fetchTree(); if (currentBlockId) loadStudents(currentBlockId); });
        });
        var bp = document.getElementById('btn-promotion');
        if (bp) bp.addEventListener('click', function() {
            if (!confirm('Run promotion for all students? This advances year level and resets semester.')) return;
            fetch(promotionUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' } })
                .then(r => r.json()).then(d => { toast('Promoted ' + (d.promoted || 0) + ', created ' + (d.blocks_created || 0) + ' blocks.'); fetchTree(); });
        });
        var ltl = document.getElementById('link-transfer-log');
        if (ltl) ltl.href = base + '/blocks/transfer-log';
    }

    (function initTreePanelToggle() {
        var panel = document.getElementById('tree-panel');
        var storageKey = 'students-explorer-tree-panel-collapsed';
        function isCollapsed() { return panel.classList.contains('tree-panel--collapsed'); }
        function setCollapsed(collapsed) {
            if (collapsed) {
                panel.classList.add('tree-panel--collapsed');
                try { localStorage.setItem(storageKey, '1'); } catch (e) {}
            } else {
                panel.classList.remove('tree-panel--collapsed');
                try { localStorage.removeItem(storageKey); } catch (e) {}
            }
        }
        try {
            if (localStorage.getItem(storageKey) === '1') setCollapsed(true);
        } catch (e) {}
        document.getElementById('tree-panel-toggle').addEventListener('click', function() { setCollapsed(true); });
        document.getElementById('tree-panel-expand').addEventListener('click', function() { setCollapsed(false); });
    })();

    document.getElementById('nav-students-table').addEventListener('click', function(e) {
        e.preventDefault();
        switchToTableView();
    });

    document.getElementById('filter-apply').addEventListener('click', function() {
        pagination.currentPage = 1;
        loadAllStudents();
    });
    document.getElementById('filter-reset').addEventListener('click', function() {
        ['filter-student-number','filter-program','filter-first-name','filter-last-name','filter-folder'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        ['filter-school-year','filter-year-level','filter-semester','filter-status'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        pagination.currentPage = 1;
        loadAllStudents();
    });
    document.getElementById('filter-panel').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target && e.target.id && e.target.id.startsWith('filter-') && e.target.tagName !== 'BUTTON') {
            e.preventDefault();
            document.getElementById('filter-apply').click();
        }
    });

    document.getElementById('block-search-input').addEventListener('input', function() {
        if (viewMode === 'block') filterBlockStudents();
    });

    fetchTree();
    switchToTableView();
})();
    </script>
</body>
</html>
