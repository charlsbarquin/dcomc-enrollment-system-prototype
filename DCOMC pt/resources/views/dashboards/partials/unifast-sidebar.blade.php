<aside class="dashboard-sidebar no-print unifast-sidebar-card w-72 h-screen sticky top-0 left-0 flex-shrink-0 bg-white flex flex-col border-r border-gray-200" role="navigation" aria-label="UNIFAST portal navigation">
    <div class="px-4 pt-4 pb-3 flex flex-col flex-1 min-h-0">
        <div class="border-b border-gray-200 pb-3 mb-10 flex items-center gap-3 shrink-0">
            <img src="{{ asset('images/logo.png') }}" alt="DCOMC" class="h-16 w-auto object-contain shrink-0" onerror="this.style.display='none'">
            <span class="font-bold text-[#1E40AF] text-base leading-tight" style="font-family: 'Figtree', sans-serif;">DCOMC UNIFAST Portal</span>
        </div>
        <div class="flex-1 min-h-0 overflow-y-auto">
            <nav class="flex flex-col space-y-1 pt-8">
                <a href="{{ route('unifast.dashboard') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1E40AF] focus-visible:ring-offset-2 {{ request()->routeIs('unifast.dashboard') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>
                <a href="{{ route('unifast.assessments') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1E40AF] focus-visible:ring-offset-2 {{ request()->routeIs('unifast.assessments*') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">
                    <svg class="w-4 h-4 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Assessment Monitoring
                </a>
                <a href="{{ route('unifast.analytics') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1E40AF] focus-visible:ring-offset-2 {{ request()->routeIs('unifast.analytics*') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">
                    <svg class="w-4 h-4 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Analytics
                </a>
                @php
                    $unifastUser = Auth::user();
                    $canFees = \App\Models\UnifastFeatureAccess::isEnabledForUser($unifastUser, 'unifast_fees');
                    $canReports = \App\Models\UnifastFeatureAccess::isEnabledForUser($unifastUser, 'unifast_reports');
                @endphp
                @if($canFees)
                <a href="{{ route('unifast.settings.fees') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1E40AF] focus-visible:ring-offset-2 {{ request()->routeIs('unifast.settings.fees*') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">
                    <svg class="w-4 h-4 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Fee Settings
                </a>
                @endif
                @if($canReports)
                <a href="{{ route('unifast.reports') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1E40AF] focus-visible:ring-offset-2 {{ request()->routeIs('unifast.reports*') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">
                    <svg class="w-4 h-4 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Reports
                </a>
                @endif
            </nav>
        </div>
        <div class="mt-auto pt-3 border-t border-gray-200 shrink-0">
            <a href="{{ route('unifast.feedback') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1E40AF] focus-visible:ring-offset-2" style="font-family: 'Roboto', sans-serif;">
                <svg class="w-4 h-4 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                Feedback
            </a>
            <form method="POST" action="{{ route('logout') }}" class="mt-1.5">
                @csrf
                <button type="submit" class="w-full py-2.5 px-3 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2" style="font-family: 'Roboto', sans-serif;">Log Out</button>
            </form>
        </div>
    </div>
</aside>
