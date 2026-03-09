<aside class="dashboard-sidebar no-print staff-sidebar-card w-72 h-screen sticky top-0 left-0 flex-shrink-0 bg-white flex flex-col border-r border-gray-200" role="navigation" aria-label="Staff portal navigation">
    <div class="px-4 pt-4 pb-3 flex flex-col flex-1 min-h-0">
        <div class="border-b border-gray-200 pb-3 mb-10 flex items-center gap-3 shrink-0">
            <img src="{{ asset('images/logo.png') }}" alt="DCOMC" class="h-16 w-auto object-contain shrink-0" onerror="this.style.display='none'">
            <span class="font-bold text-[#1E40AF] text-base leading-tight font-heading">DCOMC Staff Portal</span>
        </div>
        <div class="flex-1 min-h-0 overflow-y-auto">
            <nav class="flex flex-col space-y-1 pt-8">
                <a href="{{ route('staff.dashboard') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1E40AF] focus-visible:ring-offset-1 {{ request()->routeIs('staff.dashboard') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }} font-data">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>

                @php
                    $staffUser = Auth::user();
                    $canManual = \App\Models\StaffFeatureAccess::isEnabledForUser($staffUser, 'staff_admission_manual_register');
                    $canBuilder = \App\Models\StaffFeatureAccess::isEnabledForUser($staffUser, 'staff_admission_form_builder');
                    $canResponses = \App\Models\StaffFeatureAccess::isEnabledForUser($staffUser, 'staff_admission_responses');
                    $canStudentsExplorer = \App\Models\StaffFeatureAccess::isEnabledForUser($staffUser, 'staff_student_records_students_explorer');
                    $canIrregularities = \App\Models\StaffFeatureAccess::isEnabledForUser($staffUser, 'staff_student_records_irregularities');
                    $canBlocks = \App\Models\StaffFeatureAccess::isEnabledForUser($staffUser, 'staff_student_records_blocks');
                    $canBlockRequests = \App\Models\StaffFeatureAccess::isEnabledForUser($staffUser, 'staff_student_records_block_requests');
                    $canCorArchiveIrregular = \App\Models\StaffFeatureAccess::isEnabledForUser($staffUser, 'staff_student_records_cor_archive_irregular');
                    $canCorArchive = \App\Models\StaffFeatureAccess::isEnabledForUser($staffUser, 'staff_student_records_cor_archive');
                    $canProgramSchedule = \App\Models\StaffFeatureAccess::isEnabledForUser($staffUser, 'staff_schedule_program_schedule');
                    $canAnalytics = \App\Models\StaffFeatureAccess::isEnabledForUser($staffUser, 'staff_reports_analytics');
                    $canReports = \App\Models\StaffFeatureAccess::isEnabledForUser($staffUser, 'staff_reports_reports');
                    $canFeeSettings = \App\Models\StaffFeatureAccess::isEnabledForUser($staffUser, 'staff_settings_fee_settings');
                @endphp

                @if($canManual || $canBuilder || $canResponses)
                <div class="mt-6">
                    <p class="px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-gray-500 mb-0.5 font-heading">Admission</p>
                    <details class="rounded" {{ request()->routeIs('staff.registration.*') ? 'open' : '' }}>
                        <summary class="block flex items-center gap-2 px-4 py-2.5 rounded-lg cursor-pointer list-none text-gray-700 hover:bg-blue-50 border-l-4 border-transparent hover:border-[#1E40AF] transition-colors text-sm font-data">
                            <svg class="w-4 h-4 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                            <span class="flex-1">Admission</span><svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="space-y-1 pl-2 mt-0.5">
                            @if($canManual)<a href="{{ route('staff.registration.manual') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('staff.registration.manual') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }} font-data">Manual Register</a>@endif
                            @if($canBuilder)<a href="{{ route('staff.registration.builder') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('staff.registration.builder') || request()->routeIs('staff.registration.builder.*') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }} font-data">Form Builder</a>@endif
                            @if($canResponses)<a href="{{ route('staff.registration.responses') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('staff.registration.responses') || request()->routeIs('staff.registration.responses.*') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }} font-data">Responses</a>@endif
                        </div>
                    </details>
                </div>
                @endif

                @if($canStudentsExplorer || $canIrregularities || $canBlocks || $canBlockRequests || $canCorArchiveIrregular || $canCorArchive)
                @php $staffRecordsActive = request()->routeIs('staff.students-explorer*') || request()->routeIs('staff.student-status*') || request()->routeIs('staff.irregularities*') || request()->routeIs('staff.blocks*') || request()->routeIs('staff.block-change-requests*') || request()->routeIs('staff.block-explorer*') || request()->routeIs('staff.cor.archive.*') || request()->routeIs('staff.irregular-cor-archive.*'); @endphp
                <div class="mt-6 {{ $staffRecordsActive ? 'bg-blue-50/40 rounded-lg -mx-1 px-1' : '' }}">
                    <p class="px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-gray-500 mb-0.5 font-heading">Records</p>
                    <details class="rounded" {{ $staffRecordsActive ? 'open' : '' }}>
                        <summary class="block flex items-center gap-2 px-4 py-2.5 rounded-lg cursor-pointer list-none text-gray-700 hover:bg-blue-50 border-l-4 transition-colors text-sm font-data {{ $staffRecordsActive ? 'border-[#1E40AF] bg-blue-50/50' : 'border-transparent hover:border-[#1E40AF]' }} focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1E40AF] focus-visible:ring-offset-1">
                            <svg class="w-4 h-4 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                            <span class="flex-1">Student Records</span><svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="space-y-1 pl-2 mt-0.5">
                            @if($canStudentsExplorer)<a href="{{ route('staff.students-explorer') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('staff.students-explorer*') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }} font-data">Students Explorer</a>@endif
                            <a href="{{ route('staff.student-status') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('staff.student-status*') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }} font-data">Student Status</a>
                            @if($canBlocks)<a href="{{ route('staff.block-explorer') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('staff.block-explorer*') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }} font-data"><svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>Block Explorer</a>@endif
                            @if($canBlocks)<a href="{{ route('staff.blocks') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('staff.blocks') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }} font-data">Blocks</a>@endif
                            @if($canBlockRequests)<a href="{{ route('staff.block-change-requests') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('staff.block-change-requests*') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }} font-data">Block Requests</a>@endif
                            @if($canCorArchiveIrregular)<a href="{{ route('staff.irregular-cor-archive.index') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('staff.irregular-cor-archive.*') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }} font-data">COR Archive Irregular</a>@endif
                            @if($canCorArchive)<a href="{{ route('staff.cor.archive.index') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('staff.cor.archive.*') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }} font-data">COR Archive</a>@endif
                        </div>
                    </details>
                </div>
                @endif

                @if($canProgramSchedule)
                <div class="mt-3">
                    <p class="px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-gray-500 mb-0.5 font-heading">Schedule</p>
                    <details class="rounded" {{ request()->routeIs('staff.program-schedule*') ? 'open' : '' }}>
                        <summary class="block flex items-center gap-2 px-4 py-2.5 rounded-lg cursor-pointer list-none text-gray-700 hover:bg-blue-50 border-l-4 border-transparent hover:border-[#1E40AF] transition-colors text-sm font-data">
                            <svg class="w-4 h-4 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="flex-1">Schedule</span><svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="space-y-1 pl-2 mt-0.5">
                            <a href="{{ route('staff.program-schedule.index') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('staff.program-schedule*') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }} font-data">Program Schedule</a>
                        </div>
                    </details>
                </div>
                @endif

                @if($canAnalytics || $canReports)
                <div class="mt-6">
                    <p class="px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-gray-500 mb-0.5 font-heading">Reports</p>
                    <details class="rounded" {{ request()->routeIs('staff.analytics') || request()->routeIs('staff.reports') ? 'open' : '' }}>
                        <summary class="block flex items-center gap-2 px-4 py-2.5 rounded-lg cursor-pointer list-none text-gray-700 hover:bg-blue-50 border-l-4 border-transparent hover:border-[#1E40AF] transition-colors text-sm font-data">
                            <svg class="w-4 h-4 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            <span class="flex-1">Reports</span><svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="space-y-1 pl-2 mt-0.5">
                            @if($canAnalytics)<a href="{{ route('staff.analytics') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('staff.analytics') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }} font-data">Analytics</a>@endif
                            @if($canReports)<a href="{{ route('staff.reports') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('staff.reports') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }} font-data">Reports</a>@endif
                        </div>
                    </details>
                </div>
                @endif

            </nav>
        </div>
        <div class="mt-auto pt-3 border-t border-gray-200 shrink-0">
            <a href="{{ route('staff.feedback') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF] font-data focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1E40AF] focus-visible:ring-offset-1">
                <svg class="w-4 h-4 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                Feedback
            </a>
            <form method="POST" action="{{ route('logout') }}" class="mt-1.5">
                @csrf
                <button type="submit" class="w-full py-2.5 px-3 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-colors font-data focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1E40AF] focus-visible:ring-offset-1">Log Out</button>
            </form>
        </div>
    </div>
</aside>
