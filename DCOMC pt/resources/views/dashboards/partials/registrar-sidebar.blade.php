<aside class="dashboard-sidebar no-print registrar-sidebar-card w-72 h-screen sticky top-0 left-0 flex-shrink-0 bg-white flex flex-col border-r border-gray-200" role="navigation" aria-label="Registrar portal navigation">
    <div class="px-4 pt-4 pb-3 flex flex-col flex-1 min-h-0">
        <div class="border-b border-gray-200 pb-3 mb-10 flex items-center gap-3 shrink-0">
            <img src="{{ asset('images/logo.png') }}" alt="DCOMC" class="h-16 w-auto object-contain shrink-0" onerror="this.style.display='none'">
            <span class="font-bold text-[#1E40AF] text-base leading-tight" style="font-family: 'Figtree', sans-serif;">DCOMC Registrar Portal</span>
        </div>
        <div class="flex-1 min-h-0 overflow-y-auto">
            <nav class="flex flex-col space-y-1 pt-8">
                <a href="{{ route('registrar.dashboard') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.dashboard') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>
        @php
            $registrarUser = Auth::user();
            $canRegManual = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_admission_manual_register');
            $canRegFormBuilder = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_admission_form_builder');
            $canRegResponses = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_admission_responses');

            $canRegStudentsExplorer = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_student_records_students_explorer');
            $canRegIrregularities = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_student_records_irregularities');
            $canRegBlocks = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_student_records_blocks');
            $canRegBlockRequests = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_student_records_block_requests');
            $canRegCorArchive = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_student_records_cor_archive');
            $canRegIrregularCorArchive = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_student_records_irregular_cor_archive');

            $canRegProgramSchedule = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_schedule_program_schedule');
            $canRegCreateSchedule = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_schedule_create_schedule');

            $canRegAnalytics = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_reports_analytics');
            $canRegReports = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_reports_reports');

            $canRegSettingsSchoolYears = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_settings_school_years');
            $canRegSettingsSemesters = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_settings_semesters');
            $canRegSettingsYearLevels = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_settings_year_levels');
            $canRegSettingsBlocks = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_settings_blocks');
            $canRegSettingsSubjects = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_settings_subjects');
            $canRegSettingsFees = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_settings_fees');
            $canRegSettingsProfessors = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_settings_professors');
            $canRegSettingsRooms = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_settings_rooms');
            $canRegSettingsCorScopes = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_settings_cor_scopes');
            $canRegSettingsStaffAccess = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_settings_staff_access');
            $canRegSettingsUnifastAccess = \App\Models\RegistrarFeatureAccess::isEnabledForUser($registrarUser, 'registrar_settings_unifast_access');
        @endphp

        @if($canRegManual || $canRegFormBuilder || $canRegResponses)
        <div class="mt-6">
            <p class="px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-gray-500 mb-0.5" style="font-family: 'Figtree', sans-serif;">Admission</p>
            <details class="rounded" {{ request()->routeIs('registrar.registration.*') ? 'open' : '' }}>
                <summary class="block flex items-center gap-2 px-4 py-2.5 rounded-lg cursor-pointer list-none text-gray-700 hover:bg-blue-50 border-l-4 border-transparent hover:border-[#1E40AF] transition-colors text-sm" style="font-family: 'Roboto', sans-serif;">
                    <svg class="w-4 h-4 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                    <span class="flex-1">Admission</span><svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="space-y-1 pl-2 mt-0.5">
                    @if($canRegManual)
                        <a href="{{ route('registrar.registration.manual') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.registration.manual') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Manual Register</a>
                    @endif
                    @if($canRegFormBuilder)
                        <a href="{{ route('registrar.registration.builder') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.registration.builder') || request()->routeIs('registrar.registration.builder.*') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Form Builder</a>
                    @endif
                    @if($canRegResponses)
                        <a href="{{ route('registrar.registration.responses') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.registration.responses') || request()->routeIs('registrar.registration.responses.*') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Responses</a>
                    @endif
                </div>
            </details>
        </div>
        @endif

        @if($canRegStudentsExplorer || $canRegIrregularities || $canRegBlocks || $canRegBlockRequests || $canRegCorArchive || $canRegIrregularCorArchive)
        <div class="mt-6">
            <p class="px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-gray-500 mb-0.5" style="font-family: 'Figtree', sans-serif;">Records</p>
            <details class="rounded" {{ request()->routeIs('registrar.students-explorer') || request()->routeIs('registrar.blocks') || request()->routeIs('registrar.block-change-requests*') || request()->routeIs('registrar.irregularities') || request()->routeIs('registrar.irregularities.schedule') || request()->routeIs('registrar.schedule.templates.edit') || request()->routeIs('registrar.cor.archive.*') || request()->routeIs('registrar.irregular-cor-archive.*') ? 'open' : '' }}>
                <summary class="block flex items-center gap-2 px-4 py-2.5 rounded-lg cursor-pointer list-none text-gray-700 hover:bg-blue-50 border-l-4 border-transparent hover:border-[#1E40AF] transition-colors text-sm" style="font-family: 'Roboto', sans-serif;">
                    <svg class="w-4 h-4 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                    <span class="flex-1">Student Records</span><svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="space-y-1 pl-2 mt-0.5">
                    <a href="{{ route('registrar.student-status') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.student-status') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Student Status</a>
                    @if($canRegStudentsExplorer)
                        <a href="{{ route('registrar.students-explorer') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.students-explorer') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Students Explorer</a>
                    @endif
                    @if($canRegIrregularities)
                        <a href="{{ route('registrar.irregularities') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.irregularities') || request()->routeIs('registrar.irregularities.schedule') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Irregular Students</a>
                    @endif
                    @if($canRegBlocks)
                        <a href="{{ route('registrar.blocks') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.blocks') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Blocks</a>
                    @endif
                    @if($canRegBlockRequests)
                        <a href="{{ route('registrar.block-change-requests') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.block-change-requests*') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Block Requests</a>
                    @endif
                    @if($canRegCorArchive)
                        <a href="{{ route('registrar.cor.archive.index') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.cor.archive.*') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">COR Archive</a>
                    @endif
                    @if($canRegIrregularCorArchive)
                        <a href="{{ route('registrar.irregular-cor-archive.index') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.irregular-cor-archive.*') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Irregular COR Archive</a>
                    @endif
                </div>
            </details>
        </div>
        @endif

        @if($canRegProgramSchedule)
        <div class="mt-3">
            <p class="px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-gray-500 mb-0.5" style="font-family: 'Figtree', sans-serif;">Schedule</p>
            <details class="rounded" {{ request()->routeIs('registrar.program-schedule*') ? 'open' : '' }}>
                <summary class="block flex items-center gap-2 px-4 py-2.5 rounded-lg cursor-pointer list-none text-gray-700 hover:bg-blue-50 border-l-4 border-transparent hover:border-[#1E40AF] transition-colors text-sm" style="font-family: 'Roboto', sans-serif;">
                    <svg class="w-4 h-4 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span class="flex-1">Schedule</span><svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="space-y-1 pl-2 mt-0.5">
                    <a href="{{ route('registrar.program-schedule.index') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.program-schedule*') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Program Schedule</a>
                </div>
            </details>
        </div>
        @endif

        @if($canRegAnalytics || $canRegReports)
        <div class="mt-6">
            <p class="px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-gray-500 mb-0.5" style="font-family: 'Figtree', sans-serif;">Reports</p>
            <details class="rounded" {{ request()->routeIs('registrar.analytics') || request()->routeIs('registrar.reports') ? 'open' : '' }}>
                <summary class="block flex items-center gap-2 px-4 py-2.5 rounded-lg cursor-pointer list-none text-gray-700 hover:bg-blue-50 border-l-4 border-transparent hover:border-[#1E40AF] transition-colors text-sm" style="font-family: 'Roboto', sans-serif;">
                    <svg class="w-4 h-4 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span class="flex-1">Reports</span><svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="space-y-1 pl-2 mt-0.5">
                    @if($canRegAnalytics)
                        <a href="{{ route('registrar.analytics') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.analytics') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Analytics</a>
                    @endif
                    @if($canRegReports)
                        <a href="{{ route('registrar.reports') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.reports') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Reports</a>
                    @endif
                </div>
            </details>
        </div>
        @endif

        @if($canRegSettingsSchoolYears || $canRegSettingsSemesters || $canRegSettingsYearLevels || $canRegSettingsBlocks || $canRegSettingsSubjects || $canRegSettingsFees || $canRegSettingsProfessors || $canRegSettingsRooms || $canRegSettingsCorScopes || $canRegSettingsStaffAccess || $canRegSettingsUnifastAccess)
        <div class="mt-6">
            <p class="px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-gray-500 mb-0.5" style="font-family: 'Figtree', sans-serif;">Settings</p>
            <details class="rounded" {{ request()->routeIs('registrar.settings.*') || request()->routeIs('registrar.cor-scopes.*') ? 'open' : '' }}>
                <summary class="block flex items-center gap-2 px-4 py-2.5 rounded-lg cursor-pointer list-none text-gray-700 hover:bg-blue-50 border-l-4 border-transparent hover:border-[#1E40AF] transition-colors text-sm" style="font-family: 'Roboto', sans-serif;">
                    <svg class="w-4 h-4 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="flex-1">Settings</span><svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="space-y-1 pl-2 mt-0.5">
                    @if($canRegSettingsSchoolYears)
                        <a href="{{ route('registrar.settings.school-years') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.settings.school-years') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">School Year</a>
                    @endif
                    @if($canRegSettingsSemesters)
                        <a href="{{ route('registrar.settings.semesters') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.settings.semesters') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Semester</a>
                    @endif
                    @if($canRegSettingsYearLevels)
                        <a href="{{ route('registrar.settings.year-levels') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.settings.year-levels') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Year Level</a>
                    @endif
                    @if($canRegSettingsBlocks)
                        <a href="{{ route('registrar.settings.blocks') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.settings.blocks') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Blocks</a>
                    @endif
                    @if($canRegSettingsSubjects)
                        <a href="{{ route('registrar.settings.subjects') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.settings.subjects') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Subjects</a>
                    @endif
                    @if($canRegSettingsFees)
                        <a href="{{ route('registrar.settings.fees') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.settings.fees') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Fees</a>
                    @endif
                    @if($canRegSettingsProfessors)
                        <a href="{{ route('registrar.settings.professors') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.settings.professors') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Professors</a>
                    @endif
                    @if($canRegSettingsRooms)
                        <a href="{{ route('registrar.settings.rooms') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.settings.rooms') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Rooms</a>
                    @endif
                    @if($canRegSettingsCorScopes)
                        <a href="{{ route('registrar.cor-scopes.index') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.cor-scopes.*') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">COR Scope Templates</a>
                    @endif
                    @if($canRegSettingsStaffAccess)
                        <a href="{{ route('registrar.settings.staff-access') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.settings.staff-access') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Staff access</a>
                    @endif
                    @if($canRegSettingsUnifastAccess)
                        <a href="{{ route('registrar.settings.unifast-access') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors {{ request()->routeIs('registrar.settings.unifast-access') ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]' }}" style="font-family: 'Roboto', sans-serif;">Unifast access</a>
                    @endif
                </div>
            </details>
        </div>
        @endif
            </nav>
        </div>
        <div class="mt-auto pt-3 border-t border-gray-200 shrink-0">
            <a href="{{ route('registrar.feedback') }}" class="block flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm no-underline border-l-4 border-transparent transition-colors text-gray-700 hover:bg-blue-50 hover:border-[#1E40AF]" style="font-family: 'Roboto', sans-serif;">
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
