<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Unifast Access - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">
    @php $set = request()->routeIs('admin.settings.*') ? 'admin.settings' : 'registrar.settings'; $isAdmin = request()->routeIs('admin.*'); $dashRoute = $isAdmin ? 'admin.dashboard' : 'registrar.dashboard'; @endphp
    @include('dashboards.partials.role-sidebar')

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full max-w-5xl mx-auto">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-data shadow-sm">{{ session('success') }}</div>
                @endif

                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Unifast Access</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Toggle which unifast features are available globally, then override access for specific unifast accounts if needed.</p>
                        </div>
                        <a href="{{ route($dashRoute) }}" class="btn-back-hero shrink-0 whitespace-nowrap">← Back to Dashboard</a>
                    </div>
                </section>

                <div class="bg-white shadow-2xl rounded-xl border border-gray-200 mb-6 overflow-hidden card-dcomc-top">
                @foreach($featureGroups as $groupKey => $group)
                    @php
                        $groupFeatures = $group['features'] ?? [];
                        if (empty($groupFeatures)) continue;
                    @endphp
                    <details class="border-b border-gray-200 last:border-b-0" open>
                        <summary class="list-none cursor-pointer px-4 py-3 flex items-center justify-between hover:bg-gray-50">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-gray-800">{{ $group['label'] ?? ucfirst(str_replace('_',' ',$groupKey)) }}</span>
                                <span class="text-xs text-gray-400">module</span>
                            </div>
                            <span class="text-xs text-gray-500">▾</span>
                        </summary>
                        <div class="divide-y divide-gray-100">
                            @foreach($groupFeatures as $featureKey => $meta)
                                @php $enabled = $states[$featureKey] ?? true; @endphp
                                <div class="flex items-center justify-between px-4 py-3">
                                    <div>
                                        <p class="font-medium text-gray-800">{{ $meta['label'] }}</p>
                                        @if(!empty($meta['description']))
                                            <p class="text-xs text-gray-500 mt-1">{{ $meta['description'] }}</p>
                                        @endif
                                    </div>
                                    <label class="inline-flex items-center cursor-pointer">
                                        <span class="sr-only">Toggle {{ $meta['label'] }}</span>
                                        <input type="checkbox" class="peer sr-only" data-feature="{{ $featureKey }}" @checked($enabled)>
                                        <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500 rounded-full peer peer-checked:bg-blue-600 transition-colors duration-200">
                                            <div class="w-5 h-5 bg-white rounded-full shadow transform translate-x-0.5 mt-0.5 ml-0.5 peer-checked:translate-x-5 transition-transform duration-200"></div>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endforeach
                </div>

                <div class="rounded-xl overflow-hidden border border-gray-200 shadow-2xl bg-white">
                    <div class="bg-[#1E40AF] px-6 py-4 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 class="font-heading text-lg font-bold text-white">Per-unifast overrides</h2>
                            <p class="text-white/90 text-sm font-data">Search unifast accounts and configure access for individual users.</p>
                        </div>
                        <form method="GET" class="flex items-center gap-2">
                            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search by name or email..." class="border border-white/50 rounded-lg px-3 py-2 text-sm bg-white/15 text-white placeholder-white/70 font-data focus:outline-none focus:ring-2 focus:ring-white/50 w-64">
                            <button type="submit" class="btn-white-hero">Search</button>
                        </form>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm font-data">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Name</th>
                                    <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Email</th>
                                    <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Role</th>
                                    <th class="py-3 px-4 text-right font-heading font-bold text-gray-700">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse($unifastUsers ?? [] as $u)
                                    <tr class="hover:bg-blue-50/50 transition-colors font-data">
                                        <td class="py-4 px-4">{{ $u->name }}</td>
                                        <td class="py-4 px-4 text-gray-700">{{ $u->email }}</td>
                                        <td class="py-4 px-4 text-gray-500 uppercase text-xs">{{ $u->role }}</td>
                                        <td class="py-4 px-4 text-right">
                                        <button type="button"
                                            class="inline-flex items-center px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded text-xs font-semibold"
                                            data-unifast-id="{{ $u->id }}"
                                            data-unifast-name="{{ $u->name }}"
                                            data-unifast-email="{{ $u->email }}">
                                            Manage access
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-8 px-4 text-center text-gray-500 text-sm font-data">No unifast accounts found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        window.UNIFAST_FEATURE_META = @json($features);
        window.UNIFAST_FEATURE_GROUPS = @json($featureGroups);

        document.querySelectorAll('input[type="checkbox"][data-feature]').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                var feature = this.getAttribute('data-feature');
                var enabled = this.checked ? 1 : 0;
                fetch("{{ route($set . '.unifast-access.update') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ feature: feature, enabled: !!enabled })
                }).catch(function () { checkbox.checked = !checkbox.checked; });
            });
        });

        (function () {
            var modal, overlay, activeUserId;

            function ensureModal() {
                if (modal) return;
                overlay = document.createElement('div');
                overlay.className = 'fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-40 hidden';
                modal = document.createElement('div');
                modal.className = 'bg-white rounded-lg shadow-xl max-w-lg w-full mx-4';
                modal.innerHTML =
                    '<div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">' +
                        '<div><h2 class="text-lg font-semibold text-gray-800" id="unifast-modal-title">Unifast access</h2>' +
                        '<p class="text-xs text-gray-500 mt-1" id="unifast-modal-subtitle"></p></div>' +
                        '<button type="button" id="unifast-modal-close" class="text-gray-500 hover:text-gray-700 text-xl leading-none">×</button>' +
                    '</div><div class="px-5 py-4" id="unifast-modal-body">Loading…</div>';
                overlay.appendChild(modal);
                document.body.appendChild(overlay);
                overlay.addEventListener('click', function (e) {
                    if (e.target === overlay) { overlay.classList.add('hidden'); activeUserId = null; }
                });
                modal.querySelector('#unifast-modal-close').addEventListener('click', function () {
                    overlay.classList.add('hidden'); activeUserId = null;
                });
            }

            function openUnifastModal(userId, name, email) {
                ensureModal();
                activeUserId = userId;
                overlay.classList.remove('hidden');
                modal.querySelector('#unifast-modal-title').textContent = 'Unifast access — ' + name;
                modal.querySelector('#unifast-modal-subtitle').textContent = email;
                var bodyEl = modal.querySelector('#unifast-modal-body');
                bodyEl.textContent = 'Loading…';

                var url = "{{ route($set . '.unifast-access.user', ['user' => 'USER_ID']) }}".replace('USER_ID', String(userId));
                fetch(url, { headers: { 'Accept': 'application/json' } }).then(function (res) { return res.json(); }).then(function (data) {
                    if (!data || !data.features) {
                        bodyEl.textContent = 'No feature data available.';
                        return;
                    }
                    var featureMeta = window.UNIFAST_FEATURE_META || {};
                    var groups = window.UNIFAST_FEATURE_GROUPS || {};
                    var html = '<div class="space-y-3 text-sm">';
                    Object.keys(groups).forEach(function (groupKey) {
                        var group = groups[groupKey] || {};
                        var groupLabel = group.label || groupKey.replace(/_/g, ' ');
                        var groupFeatures = group.features || {};
                        var featureKeys = Object.keys(groupFeatures).filter(function (fk) { return Object.prototype.hasOwnProperty.call(data.features, fk); });
                        if (!featureKeys.length) return;
                        html += '<details class="border border-gray-100 rounded-lg mb-2" open>';
                        html += '<summary class="list-none cursor-pointer px-3 py-2 flex items-center justify-between bg-gray-50 hover:bg-gray-100 rounded-t-lg"><span class="font-semibold text-gray-800">' + groupLabel + '</span><span class="text-xs text-gray-500">▾</span></summary>';
                        html += '<div class="divide-y divide-gray-100">';
                        featureKeys.forEach(function (key) {
                            var f = data.features[key];
                            var effective = !!(f.effective);
                            var meta = featureMeta[key] || {};
                            var label = meta.label || key.replace(/_/g, ' ');
                            var desc = meta.description || '';
                            html += '<div class="flex items-center justify-between px-3 py-2">';
                            html += '<div><p class="font-medium text-gray-800">' + label + '</p><p class="text-xs text-gray-500">Effective: ' + (effective ? 'Enabled' : 'Disabled') + '</p>';
                            if (desc) html += '<p class="text-xs text-gray-400 mt-0.5">' + desc + '</p>';
                            html += '</div>';
                            html += '<label class="inline-flex items-center cursor-pointer">';
                            html += '<input type="checkbox" class="sr-only unifast-feature-toggle" data-feature="' + key + '" ' + (effective ? 'checked' : '') + '>';
                            html += '<div class="w-11 h-6 bg-gray-300 rounded-full relative"><div class="knob w-5 h-5 bg-white rounded-full shadow absolute top-0.5 left-0.5 transition-transform duration-200 ' + (effective ? 'translate-x-5' : '') + '"></div></div>';
                            html += '</label></div>';
                        });
                        html += '</div></details>';
                    });
                    html += '</div>';
                    bodyEl.innerHTML = html;

                    bodyEl.querySelectorAll('.unifast-feature-toggle').forEach(function (checkbox) {
                        checkbox.addEventListener('change', function () {
                            var feature = this.getAttribute('data-feature');
                            var enabled = this.checked;
                            var knob = this.parentElement.querySelector('.knob');
                            if (knob) knob.classList.toggle('translate-x-5', enabled);
                            var updateUrl = "{{ route($set . '.unifast-access.user.update', ['user' => 'USER_ID']) }}".replace('USER_ID', String(activeUserId));
                            fetch(updateUrl, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}', 'Accept': 'application/json' },
                                body: JSON.stringify({ feature: feature, enabled: !!enabled })
                            }).catch(function () {
                                checkbox.checked = !checkbox.checked;
                                if (knob) knob.classList.toggle('translate-x-5', checkbox.checked);
                            });
                        });
                    });
                }).catch(function () { bodyEl.textContent = 'Failed to load unifast access details.'; });
            }

            document.querySelectorAll('button[data-unifast-id]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    openUnifastModal(this.getAttribute('data-unifast-id'), this.getAttribute('data-unifast-name') || '', this.getAttribute('data-unifast-email') || '');
                });
            });
        })();
    </script>
</body>
</html>
