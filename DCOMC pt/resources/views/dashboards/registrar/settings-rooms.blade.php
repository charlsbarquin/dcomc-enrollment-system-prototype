<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">
    @include('dashboards.partials.registrar-sidebar')

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
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Rooms</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Manage room inventory and department scope for scheduling.</p>
                        </div>
                        <a href="{{ route('registrar.dashboard') }}" class="btn-back-hero shrink-0 whitespace-nowrap">← Back to Dashboard</a>
                    </div>
                </section>

                <div class="bg-white shadow-2xl rounded-xl border border-gray-200 px-6 py-5 mb-6">
                    <h2 class="font-heading text-lg font-bold text-gray-800 mb-4">Add Room</h2>
                    <form method="POST" action="{{ route('registrar.settings.rooms.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Room name</label>
                            <input type="text" name="name" value="{{ old('name') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus" placeholder="e.g. Room 101">
                        </div>
                        <div>
                            <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Code</label>
                            <input type="text" name="code" value="{{ old('code') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus" placeholder="e.g. R101">
                        </div>
                        <div>
                            <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Capacity</label>
                            <input type="number" name="capacity" value="{{ old('capacity', 50) }}" min="1" max="500" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus">
                        </div>
                        <div>
                            <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Building</label>
                            <input type="text" name="building" value="{{ old('building') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-data input-dcomc-focus" placeholder="Optional">
                        </div>
                        <div>
                            <label class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Department scope</label>
                            <select name="department_scope" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white font-data input-dcomc-focus">
                                @foreach($allowedScopes ?? [] as $s)
                                    <option value="{{ $s }}" {{ old('department_scope') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="btn-primary">Add Room</button>
                        </div>
                    </form>
                </div>

                <div class="rounded-xl overflow-hidden border border-gray-200 shadow-2xl bg-white">
                    <div class="bg-[#1E40AF] px-6 py-4">
                        <h2 class="font-heading text-lg font-bold text-white">Existing rooms</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm font-data">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Name</th>
                                    <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Code</th>
                                    <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Capacity</th>
                                    <th class="py-3 px-4 text-left font-heading font-bold text-gray-700">Scope</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach($rooms as $r)
                                    <tr class="hover:bg-blue-50/50 transition-colors font-data">
                                        <td class="py-4 px-4">{{ $r->name }}</td>
                                        <td class="py-4 px-4">{{ $r->code }}</td>
                                        <td class="py-4 px-4">{{ $r->capacity }}</td>
                                        <td class="py-4 px-4">{{ $r->department_scope ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
