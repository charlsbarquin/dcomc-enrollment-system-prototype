<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Block Explorer - DCOMC</title>
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
        .btn-primary { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1.25rem; border-radius: 0.5rem; background: #1E40AF; color: #fff; font-size: 0.875rem; font-weight: 600; transition: background-color 0.2s; border: none; cursor: pointer; font-family: 'Roboto', sans-serif; text-decoration: none; }
        .btn-primary:hover { background: #1D3A8A; }
        .btn-primary:focus { outline: none; box-shadow: 0 0 0 2px #fff, 0 0 0 4px #1E40AF; }
        .btn-secondary { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1rem; border-radius: 0.5rem; border: 2px solid #d1d5db; background: #fff; color: #374151; font-size: 0.875rem; font-weight: 600; transition: background-color 0.2s, border-color 0.2s; cursor: pointer; font-family: 'Roboto', sans-serif; text-decoration: none; }
        .btn-secondary:hover { background: #f9fafb; border-color: #9ca3af; }
        .btn-ghost-hero { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1rem; border-radius: 0.5rem; background: rgba(255,255,255,0.2); color: #fff; font-size: 0.875rem; font-weight: 500; transition: background-color 0.2s; border: none; cursor: pointer; font-family: 'Roboto', sans-serif; }
        .btn-ghost-hero:hover { background: rgba(255,255,255,0.3); }
        .btn-white-hero { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1rem; border-radius: 0.5rem; border: 2px solid #d1d5db; background: #fff; color: #1f2937; font-size: 0.875rem; font-weight: 600; transition: background-color 0.2s; text-decoration: none; cursor: pointer; font-family: 'Roboto', sans-serif; }
        .btn-white-hero:hover { background: #f9fafb; }
        .btn-select-all { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; border-radius: 0.5rem; border: 2px solid #d1d5db; background: #fff; color: #374151; font-size: 0.75rem; font-weight: 600; transition: background-color 0.2s; cursor: pointer; font-family: 'Roboto', sans-serif; }
        .btn-select-all:hover { background: #f9fafb; }
        .btn-select-all:focus { outline: none; box-shadow: 0 0 0 2px #1E40AF; }
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
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-data shadow-sm">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-data">
                        <ul class="list-disc pl-5 mb-0">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
                    </div>
                @endif

                {{-- Hero Banner (same as Students Explorer) --}}
                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Block Explorer</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Browse blocks by program and year level. View students in each block and manage assignments.</p>
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

                {{-- Block info bar (shown when a block is selected) --}}
                <div id="block-info-bar" class="hidden mb-4 space-y-2">
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

                {{-- Unified card: Program tree (left) + Students table (right) --}}
                <div class="flex gap-0 min-h-[480px] rounded-xl overflow-hidden border border-gray-200 shadow-2xl bg-white">
                    {{-- Tree sidebar (collapsible, same as Students Explorer) --}}
                    <div class="flex shrink-0 transition-[width] duration-200 ease-out w-72 bg-white border-r border-gray-200 flex flex-col" id="tree-panel">
                        <div id="tree-panel-expanded" class="flex flex-col h-full min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2 p-4 pb-2 shrink-0 border-b border-gray-200">
                                <p class="text-sm font-heading font-bold text-gray-700 mb-0 truncate">Programs</p>
                                <button type="button" id="tree-panel-toggle" class="shrink-0 p-1.5 rounded-lg hover:bg-gray-100 text-gray-600 hover:text-gray-800 transition text-lg leading-none" title="Collapse sidebar" aria-label="Collapse sidebar">‹</button>
                            </div>
                            <div class="flex-1 overflow-y-auto px-3 py-3 min-h-0" id="tree-panel-content">
                                <p class="text-xs text-gray-500 mb-2 px-1 font-data">📁 Programs → Year → Blocks</p>
                                <div id="tree-container" class="font-data">Loading...</div>
                            </div>
                        </div>
                        <div id="tree-panel-collapsed-bar" class="hidden items-center justify-start flex-col pt-4 bg-gray-50 border-r border-gray-200 w-12 flex-shrink-0 flex-1 min-h-0">
                            <button type="button" id="tree-panel-expand" class="p-2 rounded-lg hover:bg-gray-200 text-gray-600 hover:text-gray-800 transition text-lg font-medium" title="Expand sidebar" aria-label="Expand sidebar">›</button>
                        </div>
                    </div>

                    {{-- Students table: DCOMC blue header, gray thead, hover rows --}}
                    <div class="flex-1 flex flex-col min-w-0">
                        <div class="bg-[#1E40AF] px-6 py-4 shrink-0 flex items-center justify-between flex-wrap gap-2">
                            <h2 class="font-heading text-lg font-bold text-white">Students <span id="students-count-label" class="font-data font-normal text-white/90 text-base ml-1" aria-live="polite">Select a block</span></h2>
                        </div>
                        <div class="flex-1 overflow-auto min-h-0" id="students-wrap" tabindex="0">
                            <table class="w-full text-sm font-data transition-opacity duration-200" id="students-table" role="grid" aria-label="Students in block">
                                <thead class="bg-gray-50 border-b border-gray-200 sticky top-0 z-10">
                                    <tr>
                                        <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Name</th>
                                        <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">School ID</th>
                                        <th scope="col" class="py-3 px-4 text-left font-heading font-bold text-gray-700">Year / Semester</th>
                                        <th scope="col" class="py-3 px-4 text-right font-heading font-bold text-gray-700"><button type="button" id="select-all-btn" class="btn-select-all">Select all</button></th>
                                    </tr>
                                </thead>
                                <tbody id="students-tbody" class="divide-y divide-gray-100 bg-white">
                                    <tr><td colspan="4" class="py-10 px-4 text-gray-500 text-center font-data">Select a block to view students.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
(function () {
    const base = '{{ request()->routeIs("staff.*") ? "/staff" : "/registrar" }}';
    const allowTransfer = @json(!$isStaff);
    const treeUrl = base + '/block-explorer/tree';
    const transferUrl = base + '/blocks/transfer';
    const rebalanceUrl = base + '/blocks/rebalance';
    const promotionUrl = base + '/blocks/promotion';

    const SELECTED_CLASS = 'bg-[#1E40AF]/10 border-l-4 border-[#1E40AF]';

    let treeData = [];
    let currentBlockId = null;
    let selectedIds = new Set();
    let lastClickedBlockId = null;
    let lastClickedBlockCode = null;
    let cutBuffer = null;
    let currentStudentIds = [];
    let currentFocusIndex = -1;

    function csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function fetchTree() {
        fetch(treeUrl).then(r => r.json()).then(data => {
            treeData = data.tree || [];
            renderTree();
        }).catch(() => { document.getElementById('tree-container').innerHTML = '<p class="text-gray-500 font-data">Failed to load.</p>'; });
    }

    function escapeHtml(s) {
        if (!s) return '';
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    function renderTree() {
        const el = document.getElementById('tree-container');
        if (!treeData.length) { el.innerHTML = '<p class="text-gray-500 font-data">No blocks.</p>'; return; }
        let html = '';
        treeData.forEach(prog => {
            const progLabel = escapeHtml(prog.label || 'Program');
            html += '<details class="tree-program mb-2 rounded-lg border border-gray-200 bg-gray-50">';
            html += '<summary class="list-none cursor-pointer font-medium text-gray-700 py-2 px-3 hover:bg-gray-100 rounded-lg flex items-center justify-between font-data"><span>📁 ' + progLabel + '</span><span class="text-xs text-gray-400">▾</span></summary>';
            html += '<div class="pl-2 pb-2">';
            (prog.years || []).forEach(yr => {
                const yrLabel = escapeHtml(yr.label || '');
                html += '<details class="tree-year mb-1 rounded-lg border border-gray-100 bg-white ml-2">';
                html += '<summary class="list-none cursor-pointer text-gray-600 text-sm py-1.5 px-3 hover:bg-gray-50 rounded-lg flex items-center justify-between font-data"><span>📂 ' + yrLabel + '</span><span class="text-xs text-gray-400">▾</span></summary>';
                html += '<div class="pl-2 pb-1">';
                (yr.blocks || []).forEach(blk => {
                    const cap = blk.max_capacity || 50;
                    const size = blk.current_size || 0;
                    const pct = cap > 0 ? Math.min(100, (size / cap) * 100) : 0;
                    const blkLabel = escapeHtml(blk.label || blk.code || 'Block');
                    html += '<div class="ml-2 py-1"><a href="#" class="block-link drop-target text-sm py-1.5 px-2 rounded-lg flex items-center gap-2 font-data transition-colors ' + (String(blk.id) === String(currentBlockId) ? 'bg-[#1E40AF] text-white' : 'text-[#1E40AF] hover:bg-[#EFF6FF]') + '" data-block-id="' + blk.id + '" data-code="' + (blk.code || '') + '" data-size="' + size + '" data-cap="' + cap + '">';
                    html += '<span class="flex-1 truncate">📄 ' + blkLabel + '</span>';
                    html += '<span class="text-xs shrink-0">' + size + '/' + cap + '</span>';
                    html += '</a><div class="tree-capacity-bar mt-0.5 ml-2"><div class="tree-capacity-fill" style="width:' + pct + '%"></div></div></div>';
                });
                html += '</div></details>';
            });
            html += '</div></details>';
        });
        el.innerHTML = html;
        el.querySelectorAll('.block-link').forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                const id = link.dataset.blockId;
                const code = link.dataset.code;
                lastClickedBlockId = id;
                lastClickedBlockCode = code || ('Block ' + id);
                document.querySelectorAll('.drop-target').forEach(t => {
                    t.classList.remove('ring-2', 'ring-green-500', 'bg-green-50', 'bg-[#1E40AF]', 'text-white');
                    t.classList.add('text-[#1E40AF]');
                });
                link.classList.remove('text-[#1E40AF]');
                link.classList.add('ring-2', 'ring-green-500', 'bg-green-50');
                if (allowTransfer) {
                    var ph = document.getElementById('paste-target-hint');
                    if (ph) { ph.classList.remove('hidden'); }
                    var pl = document.getElementById('paste-target-label');
                    if (pl) { pl.textContent = lastClickedBlockCode; }
                }
                selectBlock(id, code, link.dataset.size, link.dataset.cap);
            });
            link.addEventListener('dragstart', e => e.preventDefault());
            link.addEventListener('dragover', e => { e.preventDefault(); e.dataTransfer.dropEffect = 'move'; link.classList.add('bg-amber-100', 'ring-2', 'ring-amber-400'); });
            link.addEventListener('dragleave', () => { link.classList.remove('bg-amber-100', 'ring-2', 'ring-amber-400'); });
            link.addEventListener('drop', function(e) {
                e.preventDefault();
                link.classList.remove('bg-amber-100', 'ring-2', 'ring-amber-400');
                if (!allowTransfer) return;
                const raw = e.dataTransfer.getData('application/json');
                if (!raw) return;
                try {
                    const { fromBlockId, studentIds } = JSON.parse(raw);
                    const toBlockId = parseInt(this.dataset.blockId, 10);
                    if (toBlockId === parseInt(fromBlockId, 10)) return;
                    doTransfer(parseInt(fromBlockId, 10), toBlockId, studentIds);
                } catch (err) {}
            });
        });
    }

    function selectBlock(id, code, size, cap) {
        currentBlockId = id;
        selectedIds.clear();
        document.getElementById('block-info-bar').classList.remove('hidden');
        document.getElementById('block-info').classList.remove('hidden');
        document.getElementById('block-title').textContent = code || 'Block ' + id;
        document.getElementById('block-meta').textContent = (size || 0) + ' / ' + (cap || 50) + ' students';
        var btnPrintCor = document.getElementById('btn-print-all-cor');
        var btnPrintList = document.getElementById('btn-print-master-list');
        if (btnPrintCor) { btnPrintCor.href = base + '/block-explorer/blocks/' + id + '/print-all-cor'; btnPrintCor.classList.remove('hidden'); }
        if (btnPrintList) { btnPrintList.href = base + '/block-explorer/blocks/' + id + '/print-master-list'; btnPrintList.classList.remove('hidden'); }
        var ta = document.getElementById('transfer-actions');
        if (ta) ta.classList.add('hidden');
        var th = document.getElementById('transfer-hint');
        if (th) th.classList.add('hidden');
        loadStudents(id);
        renderTree();
    }

    function loadStudents(blockId) {
        const tbody = document.getElementById('students-tbody');
        const countEl = document.getElementById('students-count-label');
        tbody.innerHTML = '<tr><td colspan="4" class="py-10 px-4 text-center font-data">Loading...</td></tr>';
        if (countEl) countEl.textContent = 'Loading...';
        fetch(base + '/block-explorer/blocks/' + blockId + '/students?per_page=100').then(r => r.json()).then(data => {
            const rows = data.data || [];
            if (countEl) countEl.textContent = rows.length + ' in this block';
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="4" class="py-10 px-4 text-gray-500 text-center font-data">No students in this block.</td></tr>';
                return;
            }
            tbody.innerHTML = rows.map(s => {
                const name = (s.last_name || '') + ', ' + (s.first_name || '') || s.name || '—';
                return '<tr class="student-row cursor-pointer select-none border-b border-gray-100 transition-colors duration-200 hover:bg-blue-50/50" data-student-id="' + s.id + '" draggable="true">' +
                    '<td class="py-3 px-4 font-data text-gray-900">' + escapeHtml(name) + '</td>' +
                    '<td class="py-3 px-4 font-data text-gray-700">' + escapeHtml(s.school_id || '—') + '</td>' +
                    '<td class="py-3 px-4 font-data text-gray-700">' + escapeHtml((s.year_level || '—') + ' / ' + (s.semester || '—')) + '</td>' +
                    '<td class="py-3 px-4 text-right"></td></tr>';
            }).join('');
            const allIds = rows.map(r => r.id);
            currentStudentIds = allIds;
            tbody.querySelectorAll('.student-row').forEach((row, i) => {
                const id = rows[i].id;
                const rowIndex = i;
                row.addEventListener('click', e => {
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
            if (allowTransfer && (th = document.getElementById('transfer-hint'))) th.classList.remove('hidden');
        }).catch(() => {
            tbody.innerHTML = '<tr><td colspan="4" class="py-10 px-4 text-red-500 text-center font-data">Failed to load.</td></tr>';
            if (countEl) countEl.textContent = 'Error';
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
                row.classList.add('selected-highlight', 'bg-[#1E40AF]/10', 'border-l-4', 'border-[#1E40AF]');
            } else {
                row.classList.remove('selected-highlight', 'bg-[#1E40AF]/10', 'border-l-4', 'border-[#1E40AF]');
            }
        });
    }

    document.getElementById('select-all-btn').addEventListener('click', function() {
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
        var rowCount = document.querySelectorAll('#students-tbody .student-row').length;
        if (sab) sab.textContent = (n === rowCount && n > 0) ? 'Deselect all' : 'Select all';
    }

    function doTransfer(fromBlockId, toBlockId, studentIds) {
        if (!allowTransfer || !studentIds.length) return;
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
                document.getElementById('transfer-actions').classList.add('hidden');
                if (currentBlockId === String(fromBlockId)) loadStudents(currentBlockId);
                else if (currentBlockId === String(toBlockId)) loadStudents(currentBlockId);
                fetchTree();
                if (n > 0) alert('Transferred ' + n + ' student(s).');
            } else {
                alert(data.message || (data.errors && data.errors.transfer ? data.errors.transfer[0] : 'Transfer failed.'));
            }
        }).catch(() => alert('Transfer failed.'));
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
                        doTransfer(cutBuffer.fromBlockId, toId, cutBuffer.studentIds);
                    }
                }
                return;
            }
        }
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            if (currentStudentIds.length === 0) return;
            const anchor = currentFocusIndex >= 0 ? currentFocusIndex : -1;
            let next = e.key === 'ArrowDown' ? anchor + 1 : anchor - 1;
            next = Math.max(0, Math.min(currentStudentIds.length - 1, next));
            e.preventDefault();
            if (e.shiftKey) {
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
        }
    }, true);

    (function initTreeCollapse() {
        var panel = document.getElementById('tree-panel');
        var expanded = document.getElementById('tree-panel-expanded');
        var collapsedBar = document.getElementById('tree-panel-collapsed-bar');
        var toggle = document.getElementById('tree-panel-toggle');
        var expandBtn = document.getElementById('tree-panel-expand');
        if (toggle) toggle.addEventListener('click', function() {
            panel.classList.add('tree-panel--collapsed');
        });
        if (expandBtn) expandBtn.addEventListener('click', function() {
            panel.classList.remove('tree-panel--collapsed');
        });
    })();

    if (allowTransfer) {
        var br = document.getElementById('btn-rebalance');
        if (br) br.addEventListener('click', function() {
            if (!currentBlockId) { alert('Select a block first.'); return; }
            if (!confirm('Run rebalance for this block?')) return;
            fetch(rebalanceUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }, body: JSON.stringify({ block_id: parseInt(currentBlockId, 10) }) })
                .then(r => r.json()).then(d => { alert('Moved ' + (d.moved || 0) + ' student(s).'); fetchTree(); if (currentBlockId) loadStudents(currentBlockId); });
        });
        var bp = document.getElementById('btn-promotion');
        if (bp) bp.addEventListener('click', function() {
            if (!confirm('Run promotion for all students? This advances year level and resets semester.')) return;
            fetch(promotionUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' } })
                .then(r => r.json()).then(d => { alert('Promoted ' + (d.promoted || 0) + ', created ' + (d.blocks_created || 0) + ' blocks.'); fetchTree(); });
        });
        var ltl = document.getElementById('link-transfer-log');
        if (ltl) ltl.href = base + '/blocks/transfer-log';
    }
    fetchTree();
})();
    </script>
</body>
</html>
