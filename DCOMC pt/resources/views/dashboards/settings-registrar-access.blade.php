<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Registrar Access Settings - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
</head>
<body class="dashboard-wrap bg-[#F1F5F9] min-h-screen h-screen overflow-hidden text-gray-800 font-data">
    <div class="w-full h-full flex min-w-0">
        @include('dashboards.partials.admin-sidebar')

        <main class="dashboard-main flex-1 flex flex-col min-w-0 overflow-hidden">
            <div class="flex-1 overflow-y-auto p-6 md:p-8">
                <div class="w-full max-w-3xl mx-auto">
                    @if(session('success'))
                        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-data shadow-sm">{{ session('success') }}</div>
                    @endif

                    {{-- Hero --}}
                    <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                            <div>
                                <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Registrar access</h1>
                                <p class="text-white/90 text-sm sm:text-base font-data">Toggle which modules the <strong>Registrar</strong> role can access. Changes apply to all registrar accounts.</p>
                            </div>
                            <a href="{{ route('admin.dashboard') }}" class="btn-back-hero shrink-0 whitespace-nowrap no-underline">← Back to Dashboard</a>
                        </div>
                    </section>

                    {{-- Google Forms–inspired card stack: each group in a white card with 10px solid blue top border --}}
                    <div class="space-y-6">
                        @foreach($featureGroups as $groupKey => $group)
                            @php
                                $groupFeatures = $group['features'] ?? [];
                                if (empty($groupFeatures)) {
                                    continue;
                                }
                            @endphp
                            <div class="bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden border-t-[10px] border-t-[#1E40AF]">
                                <div class="px-6 py-4 border-b border-gray-100">
                                    <h2 class="font-heading text-lg font-bold text-gray-800">{{ $group['label'] ?? ucfirst(str_replace('_',' ',$groupKey)) }}</h2>
                                    <p class="text-xs text-gray-500 font-data mt-0.5">Module</p>
                                </div>
                                <div class="divide-y divide-gray-100">
                                    @foreach($groupFeatures as $featureKey => $meta)
                                        @php
                                            $enabled = $states[$featureKey] ?? true;
                                        @endphp
                                        <div class="flex items-center justify-between px-6 py-4 hover:bg-gray-50/50 transition-colors">
                                            <div>
                                                <p class="font-medium text-gray-800 font-data">{{ $meta['label'] }}</p>
                                                @if(!empty($meta['description']))
                                                    <p class="text-xs text-gray-500 mt-1 font-data">{{ $meta['description'] }}</p>
                                                @endif
                                            </div>
                                            <label class="inline-flex items-center cursor-pointer shrink-0">
                                                <span class="sr-only">Toggle {{ $meta['label'] }}</span>
                                                <input type="checkbox"
                                                       class="peer sr-only"
                                                       data-feature="{{ $featureKey }}"
                                                       @checked($enabled)>
                                                <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-[#1E40AF] rounded-full peer peer-checked:bg-[#1E40AF] transition-colors duration-200">
                                                    <div class="w-5 h-5 bg-white rounded-full shadow transform translate-x-0.5 mt-0.5 ml-0.5 peer-checked:translate-x-5 transition-transform duration-200"></div>
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.querySelectorAll('input[type="checkbox"][data-feature]').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                var feature = this.getAttribute('data-feature');
                var enabled = this.checked ? 1 : 0;

                fetch("{{ route('admin.settings.registrar-access.update') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ feature: feature, enabled: !!enabled })
                }).catch(function () {
                    checkbox.checked = !checkbox.checked;
                });
            });
        });
    </script>
</body>
</html>
