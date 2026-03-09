<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Admin - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
    <style>
        [x-cloak] { display: none !important; }
        .pill-priority-high { background: #dc2626; color: #fff; }
        .pill-priority-mid { background: #d97706; color: #fff; }
        .pill-priority-low { background: #6b7280; color: #fff; }
    </style>
</head>
<body class="dashboard-wrap bg-[#F1F5F9] min-h-screen overflow-x-hidden text-gray-800 font-data" x-data="{ open: false, active: null }">
    @php use Illuminate\Support\Str; @endphp
    @include('dashboards.partials.admin-sidebar')
    @include('dashboards.partials.admin-loading-bar')

    <main class="dashboard-main flex flex-col min-w-0 overflow-hidden">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-data shadow-sm">{{ session('success') }}</div>
                @endif

                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Feedback</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Review and manage feedback from users across roles.</p>
                        </div>
                        <a href="{{ route('admin.feedback.create') }}" class="btn-white-hero shrink-0 whitespace-nowrap">Send feedback</a>
                    </div>
                </section>

                <form method="get" action="{{ route('admin.feedback.index') }}" class="bg-white shadow-2xl rounded-xl border border-gray-200 px-6 py-5 mb-6 card-dcomc-top">
                    <div class="flex flex-wrap items-end gap-4">
                        <div>
                            <label for="priority" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Priority</label>
                            <select name="priority" id="priority" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white font-data input-dcomc-focus min-w-[180px]">
                                <option value="">All</option>
                                <option value="high" {{ $priorityFilter === 'high' ? 'selected' : '' }}>Very important (4–5)</option>
                                <option value="medium" {{ $priorityFilter === 'medium' ? 'selected' : '' }}>Medium (3)</option>
                                <option value="low" {{ $priorityFilter === 'low' ? 'selected' : '' }}>Least important (1–2)</option>
                            </select>
                        </div>
                        <div>
                            <label for="role" class="block text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1.5">Sender role</label>
                            <select name="role" id="role" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white font-data input-dcomc-focus min-w-[140px]">
                                <option value="">All</option>
                                <option value="student" {{ $roleFilter === 'student' ? 'selected' : '' }}>Student</option>
                                <option value="registrar" {{ $roleFilter === 'registrar' ? 'selected' : '' }}>Registrar</option>
                                <option value="staff" {{ $roleFilter === 'staff' ? 'selected' : '' }}>Staff</option>
                                <option value="dean" {{ $roleFilter === 'dean' ? 'selected' : '' }}>Dean</option>
                                <option value="unifast" {{ $roleFilter === 'unifast' ? 'selected' : '' }}>UniFast</option>
                                <option value="admin" {{ $roleFilter === 'admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-primary">Filter</button>
                    </div>
                </form>

                <div class="rounded-xl overflow-hidden border border-gray-200 shadow-2xl bg-white">
                    <div class="bg-[#1E40AF] px-6 py-4">
                        <h2 class="font-heading text-lg font-bold text-white">Feedback entries</h2>
                    </div>
                    @if($feedback->isEmpty())
                        @include('dashboards.partials.admin-empty-state', ['title' => 'No feedback yet', 'text' => 'Use the filter above to adjust criteria, or send feedback from the sidebar.'])
                    @else
                        <div class="overflow-x-auto admin-table-wrap">
                            <table class="table table-hover mb-0 w-100 text-sm font-data">
                                <thead class="table-header-dcomc">
                                    <tr>
                                        <th class="py-3 px-4 text-left border-0 text-white">Date</th>
                                        <th class="py-3 px-4 text-left border-0 text-white">Sender</th>
                                        <th class="py-3 px-4 text-left border-0 text-white">Role</th>
                                        <th class="py-3 px-4 text-left border-0 text-white">Priority</th>
                                        <th class="py-3 px-4 text-left border-0 text-white">Subject</th>
                                        <th class="py-3 px-4 text-right border-0 text-white">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach($feedback as $item)
                                        <tr class="hover:bg-blue-50/50 transition-colors font-data">
                                            <td class="py-4 px-4 text-gray-700 whitespace-nowrap">{{ $item->created_at->format('M j, Y H:i') }}</td>
                                            <td class="py-4 px-4">{{ $item->user->name ?? '—' }}</td>
                                            <td class="py-4 px-4">{{ ucfirst($item->role) }}</td>
                                            <td class="py-4 px-4">
                                                @php
                                                    $p = $item->priority;
                                                    $pillClass = $p >= 4 ? 'pill-priority-high' : ($p >= 3 ? 'pill-priority-mid' : 'pill-priority-low');
                                                @endphp
                                                <span class="pill {{ $pillClass }}">{{ $p }} — {{ \App\Models\Feedback::priorityLabel($p) }}</span>
                                            </td>
                                            <td class="py-4 px-4 text-gray-700 max-w-[280px] truncate" title="{{ $item->subject ?? '' }}">{{ Str::limit($item->subject ?? '', 80) }}</td>
                                            <td class="py-4 px-4 text-right">
                                                <button type="button" class="btn-action-readonly mr-1"
                                                    @click="
                                                        open = true;
                                                        active = {
                                                            id: {{ $item->id }},
                                                            date: @js($item->created_at->format('M j, Y H:i')),
                                                            sender: @js($item->user->name ?? '—'),
                                                            role: @js(ucfirst($item->role)),
                                                            priority: @js($item->priority),
                                                            priorityLabel: @js(\App\Models\Feedback::priorityLabel((int) $item->priority)),
                                                            subject: @js($item->subject ?? ''),
                                                            message: @js($item->message ?? ''),
                                                        };
                                                    ">
                                                    View
                                                </button>
                                                <form method="POST" action="{{ route('admin.feedback.destroy', $item) }}" class="inline" onsubmit="return confirm('Delete this feedback?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-action-edit">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="admin-pagination px-6 py-3 border-t border-gray-200">
                            {{ $feedback->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>

    <div x-show="open" x-cloak class="fixed inset-0 z-[1050]">
        <div class="absolute inset-0 bg-black/50" @click="open=false; active=null"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-[#1E40AF] text-white flex items-start justify-between gap-4">
                    <div>
                        <h2 class="font-heading font-bold text-lg" x-text="active?.subject || 'Feedback'"></h2>
                        <p class="text-white/90 text-sm mt-1 font-data" x-text="(active?.date ? active.date + ' • ' : '') + (active?.sender ? active.sender + ' • ' : '') + (active?.role ? active.role : '')"></p>
                    </div>
                    <button type="button" class="text-white hover:text-white/80 text-2xl leading-none focus:outline-none" aria-label="Close" @click="open=false; active=null">&times;</button>
                </div>
                <div class="p-6">
                    <div class="flex flex-wrap gap-2 mb-3">
                        <span class="pill pill-neutral" x-text="'Role: ' + (active?.role ?? '—')"></span>
                        <span class="pill pill-neutral" x-text="'Priority: ' + (active?.priority ?? '—') + ' — ' + (active?.priorityLabel ?? '')"></span>
                    </div>
                    <p class="text-xs font-heading font-bold text-gray-600 uppercase tracking-wide mb-1">Description</p>
                    <div class="border border-gray-200 rounded-lg p-3 bg-gray-50 text-sm font-data whitespace-pre-wrap break-words" x-text="active?.message || ''"></div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <button type="button" class="btn-secondary" @click="open=false; active=null">Done</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
