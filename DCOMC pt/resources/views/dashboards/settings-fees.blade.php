@php
    $effectiveRole = request()->routeIs('admin.settings.*') ? 'admin' : (request()->routeIs('unifast.*') ? 'unifast' : (request()->routeIs('staff.*') ? 'staff' : 'registrar'));
    $user = auth()->user();
    $feesRouteName = $feesRouteName ?? (request()->routeIs('admin.settings.*') ? 'admin.settings.fees' : (request()->routeIs('staff.*') ? 'staff.settings.fees' : (request()->routeIs('unifast.*') ? 'unifast.settings.fees' : 'registrar.settings.fees')));
    $isAdminFees = $feesRouteName === 'admin.settings.fees';
    $feesTableRouteName = $isAdminFees ? 'admin.settings.fees.table' : (($feesRouteName === 'unifast.settings.fees') ? 'unifast.settings.fees.table' : 'registrar.settings.fees.table');
    $feesStoreRouteName = $isAdminFees ? 'admin.settings.fees.store' : (($feesRouteName === 'unifast.settings.fees') ? 'unifast.settings.fees.store' : 'registrar.settings.fees.store');
    $feesToggleRouteName = $isAdminFees ? 'admin.settings.fees.toggle' : (($feesRouteName === 'unifast.settings.fees') ? 'unifast.settings.fees.toggle' : 'registrar.settings.fees.toggle');
    $rawFeesStoreRouteName = $isAdminFees ? 'admin.settings.raw-fees.store' : (($feesRouteName === 'unifast.settings.fees') ? 'unifast.settings.raw-fees.store' : 'registrar.settings.raw-fees.store');
    $feesDestroyRouteName = $isAdminFees ? 'admin.settings.fees.destroy' : (($feesRouteName === 'unifast.settings.fees') ? 'unifast.settings.fees.destroy' : 'registrar.settings.fees.destroy');
    $feesCopyFromRawRouteName = $isAdminFees ? 'admin.settings.fees.copy-from-raw' : (($feesRouteName === 'unifast.settings.fees') ? 'unifast.settings.fees.copy-from-raw' : 'registrar.settings.fees.copy-from-raw');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Fees - DCOMC</title>
    @include('layouts.partials.offline-assets')
    <style>.card-dcomc-top { border-top: 10px solid #1E40AF !important; } .font-heading { font-family: 'Figtree', sans-serif; } .font-data { font-family: 'Roboto', sans-serif; } .hero-gradient { background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 50%, #60A5FA 100%); } .input-dcomc-focus:focus { outline: none; border-color: #1E40AF; box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.2); } .btn-back-hero { display: inline-flex; align-items: center; padding: 0.625rem 1rem; border-radius: 0.5rem; background: rgba(255,255,255,0.2); color: #fff; font-size: 0.875rem; font-weight: 500; transition: background-color 0.2s; border: none; cursor: pointer; font-family: 'Roboto', sans-serif; text-decoration: none; } .btn-back-hero:hover { background: rgba(255,255,255,0.3); }</style>
    @if($effectiveRole === 'admin')
    @include('dashboards.partials.dcomc-redesign-styles')
    @endif
    @if($effectiveRole === 'unifast')
    @include('dashboards.partials.unifast-styles')
    @endif
</head>
<body class="{{ $effectiveRole === 'admin' ? 'dashboard-wrap bg-[#F1F5F9] min-h-screen h-screen overflow-hidden' : ($effectiveRole === 'unifast' ? 'dashboard-wrap' : 'bg-gray-100 flex h-screen') }}">
    @if($effectiveRole === 'admin')
    <div class="w-full h-full flex min-w-0">
    @endif
    @include('dashboards.partials.role-sidebar')
    <main class="{{ $effectiveRole === 'admin' ? 'dashboard-main flex-1 flex flex-col min-w-0 overflow-hidden' : ($effectiveRole === 'unifast' ? 'dashboard-main d-flex flex-column overflow-hidden' : 'flex-1 p-8 overflow-y-auto') }}">
        <div class="{{ $effectiveRole === 'unifast' ? 'p-4 flex-grow-1 overflow-auto' : ($effectiveRole === 'admin' ? 'flex-1 overflow-y-auto p-6 md:p-8' : '') }}">
        <div class="max-w-5xl mx-auto">
            @if($effectiveRole === 'unifast' || $effectiveRole === 'admin')
                <section class="w-full hero-gradient rounded-2xl shadow-2xl p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Fee Settings</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Configure fees by program and year level. Use <strong>Raw fees</strong> to manage all records in one list, or <strong>Arrange fees</strong> to edit by program and year.</p>
                        </div>
                        @if($effectiveRole === 'admin')
                        <a href="{{ route('admin.dashboard') }}" class="btn-back-hero shrink-0 whitespace-nowrap no-underline">← Back to Dashboard</a>
                        @else
                        <a href="{{ route('unifast.dashboard') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-base font-bold text-[#1E40AF] bg-white hover:bg-gray-100 no-underline transition-colors shrink-0 font-heading">Back to Dashboard</a>
                        @endif
                    </div>
                </section>
            @else
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Fee Settings</h1>
            @endif
            @if($effectiveRole === 'registrar' || $effectiveRole === 'unifast' || $effectiveRole === 'admin')
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <span class="text-sm text-gray-600">Show:</span>
                    <a href="{{ route($feesRouteName, ['mode' => 'raw']) }}" class="px-4 py-2 rounded-lg text-sm font-medium no-underline {{ ($feeMode ?? 'arrange') === 'raw' ? 'text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}" style="{{ ($feeMode ?? 'arrange') === 'raw' ? 'background: #1E40AF;' : '' }}">Raw fees</a>
                    <a href="{{ route($feesRouteName) }}" class="px-4 py-2 rounded-lg text-sm font-medium no-underline {{ ($feeMode ?? 'arrange') === 'arrange' ? 'text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}" style="{{ ($feeMode ?? 'arrange') === 'arrange' ? 'background: #1E40AF;' : '' }}">Arrange fees</a>
                </div>
            @endif
            @if(($feeMode ?? 'arrange') === 'raw')
                <p class="text-sm text-gray-600 mb-4">All fee records in one list—easier to integrate with other roles and spot duplicate fees (same category, program, and year level).</p>
            @else
                <p class="text-sm text-gray-600 mb-4">Fees are organized by program and year level. Open a program, then a year, to view or edit the fee table.</p>
            @endif

            @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl shadow-sm font-data" role="alert">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl shadow-sm font-data" role="alert">
                    <ul class="list-disc pl-5 text-sm mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Breadcrumb (Fee Settings > Program > Year when drilling) --}}
            @if(!empty($breadcrumb) && ($feeMode ?? 'arrange') !== 'raw')
                <nav class="flex items-center gap-2 text-sm text-gray-600 mb-4 font-data" aria-label="Breadcrumb">
                    @foreach($breadcrumb as $i => $item)
                        @if($i > 0)<span class="text-gray-400">/</span>@endif
                        <a href="{{ $item['url'] }}" class="hover:text-[#1E40AF] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1E40AF] focus-visible:ring-offset-1 rounded">{{ $item['label'] }}</a>
                    @endforeach
                </nav>
            @endif

            {{-- Raw fees: single list (registrar, unifast, admin) — blue header when admin/unifast --}}
            @if(($feeMode ?? '') === 'raw')
                <div class="bg-white border border-gray-200 rounded-xl shadow-2xl overflow-hidden mb-6 {{ ($effectiveRole === 'unifast' || $effectiveRole === 'admin') ? 'card-dcomc-top' : '' }}">
                    @if($effectiveRole === 'admin' || $effectiveRole === 'unifast')
                    <div class="bg-[#1E40AF] px-6 py-4">
                        <h2 class="font-heading text-lg font-bold text-white">Raw fees</h2>
                    </div>
                    <div class="p-5">
                    @else
                    <div class="p-5">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Raw fees</h2>
                    @endif
                    <form method="POST" action="{{ route($rawFeesStoreRouteName) }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end text-sm mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        @csrf
                        <div class="md:col-span-3">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Category</label>
                            <select id="raw-fee-category-select" class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
                                <option value="">— Choose or type below —</option>
                                @foreach($feeCategories ?? [] as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="fee_category_id" id="raw-fee-category-id" value="">
                            <input type="text" name="category_name" id="raw-fee-category-name" value="{{ old('category_name') }}" placeholder="Or type new category name" class="mt-1 w-full border border-gray-300 rounded px-3 py-2 text-sm" maxlength="255">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Program</label>
                            <select name="program" class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
                                <option value="">— Optional —</option>
                                @foreach($rawFeePrograms ?? [] as $prog)
                                    <option value="{{ $prog }}" {{ old('program') === $prog ? 'selected' : '' }}>{{ ($rawFeeDisplayLabels ?? [])[$prog] ?? $prog }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Year level</label>
                            <select name="year_level" class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
                                <option value="">— Optional —</option>
                                @foreach($rawFeeYearLevels ?? [] as $yr)
                                    <option value="{{ $yr }}" {{ old('year_level') === $yr ? 'selected' : '' }}>{{ $yr }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Amount (₱)</label>
                            <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount', '0') }}" class="w-full border border-gray-300 rounded px-3 py-2" required>
                        </div>
                        <div>
                            <button type="submit" class="w-full md:w-auto px-4 py-2 text-white rounded-lg text-sm font-semibold border-0" style="background: #1E40AF;">Add fee</button>
                        </div>
                    </form>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var sel = document.getElementById('raw-fee-category-select');
                            var hid = document.getElementById('raw-fee-category-id');
                            var nameInp = document.getElementById('raw-fee-category-name');
                            if (sel && hid && nameInp) {
                                function sync() {
                                    if (sel.value) {
                                        hid.value = sel.value;
                                        hid.setAttribute('name', 'fee_category_id');
                                        nameInp.removeAttribute('name');
                                    } else if (nameInp.value.trim()) {
                                        hid.removeAttribute('name');
                                        hid.value = '';
                                        nameInp.setAttribute('name', 'category_name');
                                    } else {
                                        hid.value = '';
                                        hid.removeAttribute('name');
                                        nameInp.removeAttribute('name');
                                    }
                                }
                                sel.addEventListener('change', function() {
                                    if (this.value) nameInp.value = '';
                                    sync();
                                });
                                nameInp.addEventListener('input', function() {
                                    if (this.value.trim()) sel.value = '';
                                    sync();
                                });
                                sync();
                            }
                        });
                    </script>
                    @if(($rawFees ?? collect())->isEmpty())
                        <p class="text-sm text-gray-500 font-data">No fee records yet. Add one using the form above.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm font-data {{ $effectiveRole === 'admin' ? 'admin-table-wrap' : '' }}">
                                <thead class="{{ $effectiveRole === 'admin' ? 'table-header-dcomc' : 'bg-gray-100' }}">
                                    <tr>
                                        <th class="p-3 text-left {{ $effectiveRole === 'admin' ? 'text-white font-heading' : '' }}">Category</th>
                                        <th class="p-3 text-left {{ $effectiveRole === 'admin' ? 'text-white font-heading' : '' }}">Program</th>
                                        <th class="p-3 text-left {{ $effectiveRole === 'admin' ? 'text-white font-heading' : '' }}">Year level</th>
                                        <th class="p-3 text-right {{ $effectiveRole === 'admin' ? 'text-white font-heading' : '' }}">Amount (₱)</th>
                                        <th class="p-3 text-center {{ $effectiveRole === 'admin' ? 'text-white font-heading' : '' }}">Active</th>
                                        <th class="p-3 text-center w-20 {{ $effectiveRole === 'admin' ? 'text-white font-heading' : '' }}">Remove</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rawFees as $fee)
                                        <tr class="border-t border-gray-200 {{ $effectiveRole === 'admin' ? 'hover:bg-blue-50/50 transition-colors' : '' }}">
                                            <td class="p-2 font-medium">{{ $fee->feeCategory?->name ?? $fee->name ?? '—' }}</td>
                                            <td class="p-2">{{ $fee->program ?? '—' }}</td>
                                            <td class="p-2">{{ $fee->year_level ?? '—' }}</td>
                                            <td class="p-2 text-right">{{ number_format((float) $fee->amount, 2) }}</td>
                                            <td class="p-2 text-center">{{ $fee->is_active ? 'Yes' : 'No' }}</td>
                                            <td class="p-2 text-center">
                                                <form method="POST" action="{{ route($feesDestroyRouteName, $fee->id) }}" class="inline" onsubmit="return confirm('Remove this fee from the raw list?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-gray-500 mt-3">Duplicate = same category + program + year level. Remove duplicates above or edit in <strong>Arrange fees</strong>.</p>
                        @if(method_exists($rawFees, 'links'))
                        <div class="admin-pagination mt-4 pt-4 border-t border-gray-200">
                            {{ $rawFees->withQueryString()->links() }}
                        </div>
                        @endif
                    @endif
                    </div>
                </div>
            @endif

            @if(($feeMode ?? 'arrange') === 'arrange' && ($viewMode ?? 'programs') === 'programs')
                {{-- Program folders (main page) --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-lg p-5 {{ $effectiveRole === 'unifast' ? 'card-dcomc-top' : '' }}">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Programs / Courses</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @forelse($programs ?? [] as $prog)
                            @php $label = ($displayLabels ?? [])[$prog] ?? $prog; @endphp
                            <a href="{{ route($feesRouteName) }}?program={{ urlencode($prog) }}" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-blue-50 hover:border-blue-300 transition">
                                <svg class="w-8 h-8 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                                <span class="font-medium text-gray-800">{{ $label }}</span>
                            </a>
                        @empty
                            <p class="text-sm text-gray-500 col-span-2">No programs defined. Check config/fee_programs.php.</p>
                        @endforelse
                    </div>
                </div>
            @endif

            @if(($feeMode ?? 'arrange') === 'arrange' && ($viewMode ?? '') === 'years')
                {{-- Year folders (inside a program) --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-lg p-5 {{ $effectiveRole === 'unifast' ? 'card-dcomc-top' : '' }}">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Year levels</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach($yearLevels ?? [] as $yr)
                            <a href="{{ route($feesRouteName) }}?program={{ urlencode($program) }}&year={{ urlencode($yr) }}" class="flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:bg-blue-50 hover:border-blue-300 transition">
                                <svg class="w-8 h-8 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                                <span class="font-medium text-gray-800">{{ $yr }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(($feeMode ?? 'arrange') === 'arrange' && ($viewMode ?? '') === 'table')
                {{-- Fee table for one program + year --}}
                @php
                    $showTryButton = ($effectiveRole === 'unifast');
                    $isUnifastTable = $isUnifastFeesTable ?? false;
                    $testCorFeeRows = $showTryButton ? array_map(fn($r) => ['name' => $r['category']->name, 'category' => $r['category']->name ?? '', 'amount' => (float) ($r['amount'] ?? 0)], $rows ?? []) : [];
                    $unifastRowsData = $isUnifastTable ? array_map(fn($r) => ['category_id' => $r['category']->id, 'category_name' => $r['category']->name, 'fee_id' => $r['fee_id'] ?? null, 'amount' => (float) ($r['amount'] ?? 0)], $rows ?? []) : [];
                @endphp
                <div class="bg-white border border-gray-200 rounded-xl shadow-lg p-5 {{ $effectiveRole === 'unifast' ? 'card-dcomc-top' : '' }}" @if($showTryButton || $isUnifastTable) x-data='{
                    showTestCor: false,
                    feeRows: @json($testCorFeeRows),
                    sortedRows: @json($unifastRowsData),
                    refreshFeesFromInputs() {
                        const inputs = document.querySelectorAll("input.fee-amount-input");
                        inputs.forEach((el, i) => {
                            if (this.feeRows[i] !== undefined) { this.feeRows[i].amount = parseFloat(el.value) || 0; }
                        });
                    },
                    syncFeeRowsFromSorted() {
                        this.feeRows = this.sortedRows.map(r => ({ name: r.category_name, category: r.category_name, amount: r.amount }));
                    },
                    moveRow(fromIndex, toIndex) {
                        if (fromIndex === toIndex || toIndex < 0 || toIndex >= this.sortedRows.length) return;
                        const item = this.sortedRows.splice(fromIndex, 1)[0];
                        const insertAt = fromIndex < toIndex ? toIndex - 1 : toIndex;
                        this.sortedRows.splice(insertAt, 0, item);
                        this.syncFeeRowsFromSorted();
                    },
                    get totalFees() {
                        return this.sortedRows.length ? this.sortedRows.reduce((s, r) => s + (Number(r.amount) || 0), 0) : this.feeRows.reduce((s, r) => s + (Number(r.amount) || 0), 0);
                    }
                }' @endif>
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Fees — {{ $programLabel ?? $program }} — {{ $year }}</h2>
                    @if((($feesRouteName === 'registrar.settings.fees' || $feesRouteName === 'admin.settings.fees') && !$isUnifastTable) || $isUnifastTable)
                        <div class="mb-4 flex flex-wrap items-center gap-3">
                            <form method="POST" action="{{ route($feesCopyFromRawRouteName) }}" class="flex flex-wrap items-end gap-2 p-3 bg-blue-50 rounded-lg border border-blue-200">
                                @csrf
                                <input type="hidden" name="program" value="{{ $program }}">
                                <input type="hidden" name="year_level" value="{{ $year }}">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-0.5">Add fee from raw list</label>
                                    <select name="fee_id" required class="border border-gray-300 rounded px-2 py-1.5 text-sm bg-white min-w-[220px]">
                                        <option value="">— Select a fee —</option>
                                        @foreach($rawFeesForDropdown ?? [] as $rf)
                                            <option value="{{ $rf->id }}">{{ $rf->feeCategory?->name ?? $rf->name }} ({{ $rf->program ?? '—' }} / {{ $rf->year_level ?? '—' }}): ₱{{ number_format((float)$rf->amount, 2) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="px-3 py-1.5 text-white rounded-lg text-sm font-semibold border-0" style="background: #1E40AF;">Add</button>
                            </form>
                            @if(empty($rawFeesForDropdown) || $rawFeesForDropdown->isEmpty())
                                <span class="text-xs text-gray-500">Add fees in <strong>Raw fees</strong> first, then select one here.</span>
                            @endif
                        </div>
                    @endif
                    @if($showTryButton)
                        <div class="mb-4 flex items-center gap-2">
                            <button type="button" @click="showTestCor = !showTestCor; if(showTestCor) refreshFeesFromInputs();" class="px-4 py-2 rounded-lg text-sm font-semibold border-0 text-white" style="background: #1E40AF;">
                                Try
                            </button>
                            <span class="text-sm text-gray-500">Preview how fee changes will look on a sample COR (updates as you type).</span>
                        </div>
                    @endif
                    <form method="POST" action="{{ route($feesTableRouteName) }}" @if($showTryButton || $isUnifastTable) @input.debounce.50ms="refreshFeesFromInputs()" @endif>
                        @csrf
                        <input type="hidden" name="program" value="{{ old('program', $program) }}">
                        <input type="hidden" name="year_level" value="{{ old('year_level', $year) }}">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        @if($isUnifastTable)<th class="p-2 w-10 text-left"></th>@endif
                                        <th class="p-2 text-left">Fee</th>
                                        <th class="p-2 text-right w-40">Amount (₱)</th>
                                        @if($isUnifastTable)<th class="p-2 w-16 text-center">Remove</th>@endif
                                        @if(($feesRouteName === 'registrar.settings.fees' || $feesRouteName === 'admin.settings.fees') && !$isUnifastTable)<th class="p-2 w-16 text-center">Remove</th>@endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($isUnifastTable)
                                        <template x-for="(row, index) in sortedRows" :key="row.fee_id || row.category_id + '-' + index">
                                            <tr class="border-t border-gray-200 hover:bg-gray-50" draggable="true"
                                                @dragstart="$event.dataTransfer.setData('text/plain', index); $event.dataTransfer.effectAllowed = 'move';"
                                                @dragover.prevent="$event.dataTransfer.dropEffect = 'move';"
                                                @drop="moveRow(parseInt($event.dataTransfer.getData('text/plain'), 10), index);">
                                                <td class="p-2 text-gray-400 cursor-grab active:cursor-grabbing" title="Drag to reorder">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M7 2a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V4a2 2 0 012-2h2zm8 0a2 2 0 012 2v12a2 2 0 01-2 2h-2a2 2 0 01-2-2V4a2 2 0 012-2h2zM7 6a1 1 0 100 2 1 1 0 000-2zm0 4a1 1 0 100 2 1 1 0 000-2zm0 4a1 1 0 100 2 1 1 0 000-2zm8-8a1 1 0 100 2 1 1 0 000-2zm0 4a1 1 0 100 2 1 1 0 000-2zm0 4a1 1 0 100 2 1 1 0 000-2z"/></svg>
                                                </td>
                                                <td class="p-2 font-medium" x-text="row.category_name"></td>
                                                <td class="p-2 text-right">
                                                    <input type="hidden" :name="'fees['+index+'][fee_category_id]'" :value="row.category_id">
                                                    <input type="number" step="0.01" min="0" :name="'fees['+index+'][amount]'" x-model.number="row.amount" class="w-full border border-gray-300 rounded px-2 py-1 text-right fee-amount-input" :data-index="index">
                                                </td>
                                                <td class="p-2 text-center">
                                                    <template x-if="row.fee_id">
                                                        <form :action="'{{ route($feesDestroyRouteName, ['id' => '__ID__']) }}'.replace('__ID__', row.fee_id)" method="POST" class="inline" @submit="if(!confirm('Remove this fee from the table?')) $event.preventDefault();">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Delete</button>
                                                        </form>
                                                    </template>
                                                </td>
                                            </tr>
                                        </template>
                                    @else
                                        @foreach($rows ?? [] as $index => $row)
                                            <tr class="border-t">
                                                <td class="p-2 font-medium">{{ $row['category']->name }}</td>
                                                <td class="p-2 text-right">
                                                    <input type="hidden" name="fees[{{ $index }}][fee_category_id]" value="{{ $row['category']->id }}">
                                                    <input type="number" step="0.01" min="0" name="fees[{{ $index }}][amount]" value="{{ old('fees.'.$index.'.amount', $row['amount']) }}" class="w-full border border-gray-300 rounded px-2 py-1 text-right">
                                                </td>
                                                @if(($feesRouteName === 'registrar.settings.fees' || $feesRouteName === 'admin.settings.fees') && !$isUnifastTable)
                                                <td class="p-2 text-center">
                                                    @if(!empty($row['fee_id']))
                                                        <form method="POST" action="{{ route($feesRouteName . '.destroy', $row['fee_id']) }}" class="inline" onsubmit="return confirm('Remove this fee from this program/year?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Remove</button>
                                                        </form>
                                                    @endif
                                                </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                                <tfoot class="bg-gray-50 border-t-2">
                                    <tr>
                                        @if($isUnifastTable)<td class="p-2"></td>@endif
                                        <td class="p-2 font-semibold">Total</td>
                                        <td class="p-2 text-right font-semibold">@if($isUnifastTable)<span x-text="'₱'+totalFees.toLocaleString('en-PH',{minimumFractionDigits:2})"></span>@else ₱{{ number_format($total ?? 0, 2) }} @endif</td>
                                        @if($isUnifastTable)<td class="p-2"></td>@endif
                                        @if(($feesRouteName === 'registrar.settings.fees' || $feesRouteName === 'admin.settings.fees') && !$isUnifastTable)<td class="p-2"></td>@endif
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        @if($isUnifastTable)
                            <p class="text-xs text-gray-500 mt-2">Drag rows to reorder. Order is saved when you click Save.</p>
                        @endif
                        <p class="text-xs text-gray-500 mt-2">Total above is from current saved values. After saving, the total will update.</p>
                        <div class="mt-4 flex gap-2">
                            <button type="submit" class="text-white px-4 py-2 rounded-lg text-sm font-semibold border-0" style="background: #1E40AF;">Save fees for this year</button>
                            <a href="{{ route($feesRouteName) }}?program={{ urlencode($program) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded text-sm font-semibold">Back to years</a>
                        </div>
                    </form>

                    @if($showTryButton)
                        {{-- Test COR preview (real-time fees) --}}
                        <div x-show="showTestCor" x-cloak class="mt-6 border-2 rounded-xl overflow-hidden bg-white shadow-lg" style="border-color: #1E40AF;" x-transition>
                            <div class="text-white px-4 py-2 text-sm font-semibold" style="background: #1E40AF;">Test COR — {{ $programLabel ?? $program }} / {{ $year }} (sample data)</div>
                            <div class="p-4 max-h-[70vh] overflow-y-auto">
                                <div class="text-center mb-4">
                                    <h2 class="text-lg font-bold text-blue-800">Republic of the Philippines</h2>
                                    <h2 class="text-xl font-bold text-blue-800">DARAGA COMMUNITY COLLEGE</h2>
                                    <p class="text-gray-600 text-xs mt-1">Salvacion, Daraga, Albay</p>
                                    <h3 class="text-base font-bold mt-2">CERTIFICATE OF REGISTRATION (Preview)</h3>
                                </div>
                                <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs mb-4">
                                    <p><span class="font-semibold">Student No.:</span> DComC-TEST-001</p>
                                    <p><span class="font-semibold">Name:</span> SAMPLE, STUDENT</p>
                                    <p><span class="font-semibold">Course:</span> {{ $programLabel ?? $program }}</p>
                                    <p><span class="font-semibold">Year Level:</span> {{ $year }}</p>
                                    <p><span class="font-semibold">Semester:</span> 1st</p>
                                    <p><span class="font-semibold">School Year:</span> 2025-2026</p>
                                </div>
                                <h3 class="font-semibold text-gray-800 text-sm mt-2 mb-1">Subjects Enrolled (sample)</h3>
                                <div class="border border-gray-300 overflow-hidden mb-4">
                                    <table class="w-full text-xs">
                                        <thead class="bg-gray-200">
                                            <tr>
                                                <th class="p-1 text-left border-r">Code</th>
                                                <th class="p-1 text-left border-r">Subject Title</th>
                                                <th class="p-1 text-center border-r">Units</th>
                                                <th class="p-1 text-left">Schedule</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="border-t"><td class="p-1 border-r">GE 101</td><td class="p-1 border-r">Understanding the Self</td><td class="p-1 text-center border-r">3</td><td class="p-1">M 8:00-9:30 Rm 101</td></tr>
                                            <tr class="border-t"><td class="p-1 border-r">GE 102</td><td class="p-1 border-r">Readings in Phil. History</td><td class="p-1 text-center border-r">3</td><td class="p-1">W 9:30-11:00 Rm 102</td></tr>
                                            <tr class="border-t"><td class="p-1 border-r">CC 101</td><td class="p-1 border-r">Intro to Computing</td><td class="p-1 text-center border-r">3</td><td class="p-1">F 1:00-2:30 Rm 201</td></tr>
                                            <tr class="border-t-2 bg-gray-50 font-semibold"><td class="p-1 border-r" colspan="2">Total</td><td class="p-1 text-center border-r">9</td><td class="p-1"></td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <h3 class="font-semibold text-gray-800 text-sm mt-2 mb-1">ASSESSED FEES</h3>
                                <div class="border border-gray-300 overflow-hidden">
                                    <table class="w-full text-xs">
                                        <thead class="bg-gray-200">
                                            <tr>
                                                <th class="p-1 text-left">Fee</th>
                                                <th class="p-1 text-right">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(row, i) in feeRows" :key="i">
                                                <tr class="border-t border-gray-200">
                                                    <td class="p-1" x-text="row.name + (row.category ? ' (' + row.category + ')' : '')"></td>
                                                    <td class="p-1 text-right" x-text="Number(row.amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })"></td>
                                                </tr>
                                            </template>
                                            <tr class="border-t-2 border-gray-300 font-semibold bg-gray-100">
                                                <td class="p-1">TOTAL</td>
                                                <td class="p-1 text-right" x-text="totalFees.toLocaleString('en-PH', { minimumFractionDigits: 2 })"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="border-t border-gray-200 px-4 py-2 flex justify-end">
                                <button type="button" @click="showTestCor = false" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded">Close</button>
                            </div>
                        </div>
                        <style>[x-cloak]{display:none!important}</style>
                    @endif
                </div>
            @endif
        </div>
        @if($effectiveRole === 'unifast')</div>@endif
    </main>
    @if($effectiveRole === 'admin')
    </div>
    @endif
</body>
</html>
