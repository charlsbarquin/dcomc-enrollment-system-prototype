<aside class="dashboard-sidebar no-print dean-sidebar-card w-72 h-screen sticky top-0 left-0 flex-shrink-0 bg-white flex flex-col border-r border-gray-200" role="navigation" aria-label="Dean portal navigation">
    <div class="px-4 pt-4 pb-3 flex flex-col flex-1 min-h-0">
        <div class="border-b border-gray-200 pb-3 mb-10 flex items-center gap-3 shrink-0">
            <img src="{{ asset('images/logo.png') }}" alt="DCOMC" class="h-16 w-auto object-contain shrink-0" onerror="this.style.display='none'">
            <span class="font-bold text-[#1E40AF] text-base leading-tight" style="font-family: 'Figtree', sans-serif;">DCOMC Dean Portal</span>
        </div>
        <div class="flex-1 min-h-0 overflow-y-auto">
            <nav class="flex flex-col space-y-1 pt-8">
                <a href="{{ route('dean.dashboard') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('dean.dashboard') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>

                <div class="mt-6">
                    <p class="px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-gray-500 mb-0.5" style="font-family: 'Figtree', sans-serif;">Schedule</p>
                    <details class="rounded" {{ request()->routeIs('dean.schedule.*') || request()->routeIs('cor.archive.*') ? 'open' : '' }}>
                        <summary class="block flex items-center gap-2 px-4 py-2.5 rounded-lg cursor-pointer list-none text-gray-700 hover:bg-blue-50 border-l-4 border-transparent hover:border-[#1E40AF] transition-colors text-sm" style="font-family: 'Roboto', sans-serif;">
                            <svg class="w-4 h-4 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="flex-1">Schedule</span><svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="space-y-1 pl-2 mt-0.5">
                            <a href="{{ route('dean.schedule.by-scope') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('dean.schedule.by-scope') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Schedule by Program</a>
                            <a href="{{ route('cor.archive.index') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('cor.archive.*') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">COR Archive</a>
                        </div>
                    </details>
                </div>

                <div class="mt-3">
                    <a href="{{ route('dean.room-utilization') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('dean.room-utilization') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">
                        <svg class="w-4 h-4 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Room Utilization
                    </a>
                </div>

                <div class="mt-3">
                    <a href="{{ route('dean.manage-professor.index') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('dean.manage-professor.*') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">
                        <svg class="w-4 h-4 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Manage Professor
                    </a>
                </div>

                <div class="mt-3">
                    <p class="px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-gray-500 mb-0.5" style="font-family: 'Figtree', sans-serif;">Reports</p>
                    <details class="rounded" {{ request()->routeIs('dean.analytics*') || request()->routeIs('dean.reports') ? 'open' : '' }}>
                        <summary class="block flex items-center gap-2 px-4 py-2.5 rounded-lg cursor-pointer list-none text-gray-700 hover:bg-blue-50 border-l-4 border-transparent hover:border-[#1E40AF] transition-colors text-sm" style="font-family: 'Roboto', sans-serif;">
                            <svg class="w-4 h-4 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            <span class="flex-1">Reports</span><svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="space-y-1 pl-2 mt-0.5">
                            <a href="{{ route('dean.analytics') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('dean.analytics*') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Analytics</a>
                            <a href="{{ route('dean.reports') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('dean.reports') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Reports</a>
                        </div>
                    </details>
                </div>

                <div class="mt-6">
                    <p class="px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-gray-500 mb-0.5" style="font-family: 'Figtree', sans-serif;">Settings</p>
                    <details class="rounded" {{ request()->routeIs('dean.settings.*') ? 'open' : '' }}>
                        <summary class="block flex items-center gap-2 px-4 py-2.5 rounded-lg cursor-pointer list-none text-gray-700 hover:bg-blue-50 border-l-4 border-transparent hover:border-[#1E40AF] transition-colors text-sm" style="font-family: 'Roboto', sans-serif;">
                            <svg class="w-4 h-4 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span class="flex-1">Settings</span><svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="space-y-1 pl-2 mt-0.5">
                            <a href="{{ route('dean.settings.professors') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('dean.settings.professors') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Professors</a>
                            <a href="{{ route('dean.settings.rooms') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('dean.settings.rooms') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Rooms</a>
                        </div>
                    </details>
                </div>
            </nav>
        </div>
        <div class="mt-auto pt-3 border-t border-gray-200 shrink-0">
            <p class="px-4 py-1 text-xs text-gray-500 font-data truncate" title="{{ Auth::user()->department?->name ?? 'No department' }}">Dept: {{ Auth::user()->department?->name ?? '—' }}</p>
            <a href="{{ route('dean.feedback') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]" style="font-family: 'Roboto', sans-serif;">
                <svg class="w-4 h-4 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                Feedback
            </a>
            <form method="POST" action="{{ route('logout') }}" class="mt-1.5">
                @csrf
                <button type="submit" class="w-full py-2.5 px-3 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-colors" style="font-family: 'Roboto', sans-serif;">Log Out</button>
            </form>
        </div>
    </div>
</aside>
