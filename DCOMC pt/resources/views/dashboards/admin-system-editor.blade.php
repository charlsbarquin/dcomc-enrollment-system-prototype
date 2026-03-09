<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Code Editor - Admin - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
    <style>
        .editor-shell { height: calc(100vh - 0px); }
        .monaco-host { height: calc(100vh - 110px); }
    </style>
</head>
<body class="dashboard-wrap bg-[#F1F5F9] min-h-screen h-screen overflow-hidden">
    @include('dashboards.partials.admin-loading-bar')
    <div class="w-full h-full flex min-w-0">
        @include('dashboards.partials.admin-sidebar')
        <main class="dashboard-main flex-1 flex flex-col min-w-0 min-h-0 overflow-hidden">
        <section class="hero-gradient rounded-t-none sm:rounded-t-2xl shadow-lg p-4 sm:p-6 text-white shrink-0">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="font-heading text-xl sm:text-2xl font-bold">Code Editor</h1>
                    <p class="text-white/80 text-xs sm:text-sm font-data mt-0.5">Admin-only (restricted folders)</p>
                </div>
                <a href="{{ route('admin.system.overview') }}" class="btn-back-hero shrink-0 whitespace-nowrap no-underline text-sm">← Back to System Overview</a>
            </div>
        </section>

        <div class="flex-1 min-h-0 flex flex-col bg-white border border-gray-200 rounded-b-xl shadow-2xl overflow-hidden mx-4 mb-4">
            <div class="flex-1 min-h-0 flex">
                <aside class="w-72 bg-gray-50 border-r border-gray-200 flex flex-col min-h-0 shrink-0">
                    <div class="p-3 border-b border-gray-200">
                        <input id="treeSearch" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus" placeholder="Search files/folders">
                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach($roots as $r)
                                <button class="rootBtn text-xs px-2 py-1 rounded-lg border border-gray-300 hover:bg-blue-50 hover:border-[#1E40AF] font-data transition-colors" data-root="{{ $r }}">{{ $r }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div id="tree" class="flex-1 overflow-auto p-2 text-sm font-mono"></div>
                </aside>

                <section class="flex-1 min-w-0 flex flex-col min-h-0">
                    <div class="p-3 bg-white border-b border-gray-200 flex flex-wrap items-center gap-2">
                        <span class="text-xs text-gray-500 font-data">File:</span>
                        <span id="currentPath" class="text-xs font-mono text-gray-800 break-all">—</span>
                        <span id="dirtyBadge" class="hidden text-xs px-2 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-200 font-data">Unsaved</span>
                        <div class="ml-auto flex flex-wrap items-center gap-2">
                            <button id="btnSave" class="btn-primary text-xs py-1.5 px-3 disabled:opacity-50" disabled>Save</button>
                            <button id="btnNewFile" class="px-3 py-2 bg-[#1E40AF] hover:bg-[#1D3A8A] text-white rounded-lg text-xs font-semibold transition font-data">New file</button>
                            <button id="btnNewFolder" class="px-3 py-2 bg-gray-700 hover:bg-gray-800 text-white rounded-lg text-xs font-semibold transition font-data">New folder</button>
                            <button id="btnRename" class="btn-secondary text-xs py-1.5 px-3 disabled:opacity-50" disabled>Rename</button>
                            <button id="btnDelete" class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-semibold transition font-data disabled:opacity-50" disabled>Delete</button>
                        </div>
                    </div>

                    <div class="flex-1 min-h-0 relative">
                        <div id="admin-editor-loading" class="absolute inset-0 flex items-center justify-center bg-gray-50 text-gray-600 font-data text-sm z-10">
                            <span>Loading editor…</span>
                        </div>
                        <div id="monacoMissing" class="hidden p-6 text-sm text-gray-700 font-data">
                            Monaco editor assets are missing. Run <span class="font-mono">npm install</span> once (it will copy Monaco to <span class="font-mono">public/monaco</span>).
                        </div>
                        <div id="editor" class="monaco-host"></div>
                    </div>

                    <div class="px-3 py-2 bg-white border-t border-gray-200 text-xs text-gray-600 flex flex-wrap items-center gap-2 font-data">
                        <span id="status">Ready.</span>
                        <span class="ml-auto text-gray-400">Blocked: <span class="font-mono">.env</span>, <span class="font-mono">vendor</span>, <span class="font-mono">storage</span>, <span class="font-mono">node_modules</span></span>
                    </div>
                </section>
            </div>
        </div>
        </main>
    </div>

    <script>
        window.__DCOMC_EDITOR__ = {
            api: {
                tree: @json(route('admin.system.editor.api.tree')),
                file: @json(route('admin.system.editor.api.file')),
                write: @json(route('admin.system.editor.api.file.write')),
                create: @json(route('admin.system.editor.api.create')),
                rename: @json(route('admin.system.editor.api.rename')),
                del: @json(route('admin.system.editor.api.delete')),
            },
            monaco: {
                loader: @json(asset('monaco/vs/loader.js')),
                vsPath: @json(asset('monaco/vs')),
            }
        };
    </script>
    <script src="{{ asset('admin-code-editor.js') }}"></script>
</body>
</html>
