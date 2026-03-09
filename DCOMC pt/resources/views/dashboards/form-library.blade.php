<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Form Builder - DCOMC</title>
    @include('layouts.partials.offline-assets')
    <style>
        .font-heading { font-family: 'Figtree', sans-serif; }
        .font-data { font-family: 'Roboto', sans-serif; }
        .forms-canvas { background: #f3f4f6; }
        .hero-gradient { background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 50%, #60A5FA 100%); }
        .folder-card { border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; overflow: hidden; transition: all 0.2s ease; }
        .folder-card:hover { box-shadow: 0 4px 12px rgba(30, 64, 175, 0.12); border-color: #1E40AF; transform: translateY(-1px); }
        .folder-preview { height: 110px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f8fafc, #eff6ff); border-bottom: 1px solid #eef2f7; }
        .dropdown-content { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; }
        .dropdown-content.open { max-height: 500px; }
    </style>
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden" x-data="formLibraryApp()">

    @php
        $user = auth()->user();
        $isStaff = request()->routeIs('staff.*');
        $manualRoute = $isStaff ? route('staff.registration.manual') : route('registrar.registration.manual');
        $builderRoute = $isStaff ? route('staff.registration.builder') : route('registrar.registration.builder');
        $responsesRoute = $isStaff ? route('staff.registration.responses') : route('registrar.registration.responses');
        $builderNewRoute = $isStaff ? route('staff.registration.create-form') : route('registrar.registration.create-form');
        $dashboardRoute = $isStaff ? route('staff.dashboard') : route('registrar.dashboard');
        if ($isStaff && $user) {
            $canBuilderTab = \App\Models\StaffFeatureAccess::isEnabledForUser($user, 'staff_admission_form_builder');
            $canResponsesTab = \App\Models\StaffFeatureAccess::isEnabledForUser($user, 'staff_admission_responses');
        } elseif ($user) {
            $canBuilderTab = \App\Models\RegistrarFeatureAccess::isEnabledForUser($user, 'registrar_admission_form_builder');
            $canResponsesTab = \App\Models\RegistrarFeatureAccess::isEnabledForUser($user, 'registrar_admission_responses');
        } else {
            $canBuilderTab = true;
            $canResponsesTab = true;
        }
    @endphp
    @include('dashboards.partials.role-sidebar')

    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
        {{-- Sub-nav (same as Manual Registration) --}}
        <div class="bg-white border-b border-gray-200 px-6 py-2 flex items-center justify-between gap-4 shrink-0 w-full">
            <div class="flex items-center gap-1">
                <a href="{{ $manualRoute }}" class="px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 rounded-t font-heading">Manual Registration</a>
                @if(!empty($canBuilderTab))
                    <a href="{{ $builderRoute }}" class="px-4 py-2.5 text-sm font-semibold text-[#1E40AF] border-b-2 border-[#1E40AF] font-heading">Form Builder</a>
                @endif
                @if(!empty($canResponsesTab))
                    <a href="{{ $responsesRoute }}" class="px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 rounded-t font-heading flex items-center gap-2">Responses <span class="bg-gray-200 text-gray-800 text-xs font-bold px-2 py-0.5 rounded-full">{{ $totalResponsesCount }}</span></a>
                @endif
            </div>
            <div class="flex items-center gap-3 ml-auto">
                <span class="text-xs font-semibold text-gray-600 font-data">Enrollment</span>
                <span class="text-xs font-medium" :class="globalEnrollment ? 'text-green-700' : 'text-gray-500'" x-text="globalEnrollment ? 'Open' : 'Closed'"></span>
                @unless($isStaff)
                <button type="button" @click="toggleGlobal()" role="switch" :aria-checked="globalEnrollment" aria-label="Turn enrollment portal on or off" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:ring-offset-2" :class="globalEnrollment ? 'bg-[#1E40AF]' : 'bg-gray-300'">
                    <span class="sr-only" x-text="globalEnrollment ? 'Portal open' : 'Portal closed'"></span>
                    <span class="pointer-events-none absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition duration-200" :class="globalEnrollment ? 'left-5 translate-x-0' : 'left-0.5 translate-x-0'"></span>
                </button>
                @endunless
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full">
                {{-- Hero banner: full width like Registrar Control Panel --}}
                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Enrollment Forms</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Create and manage enrollment form templates. Deploy a form to make it available to students and for manual registration.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <a href="{{ $dashboardRoute }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-white/20 hover:bg-white/30 text-white text-sm font-medium transition-colors no-underline font-data">← Back to Dashboard</a>
                            <a href="{{ $builderNewRoute }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-white text-[#1E40AF] hover:bg-white/95 text-sm font-semibold transition-colors no-underline font-data focus:outline-none focus:ring-2 focus:ring-white/50 focus:ring-offset-2 focus:ring-offset-transparent">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Create New Form
                            </a>
                        </div>
                    </div>
                </section>

                <p class="text-sm text-gray-600 mb-6 font-data">Choose a form to edit or create a new one.</p>

                @if($savedForms->count() === 0)
                    <div class="text-center py-12 bg-white rounded-xl border border-gray-200 shadow-sm">
                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <h3 class="font-heading text-lg font-bold text-gray-800 mb-2">No Forms Yet</h3>
                        <p class="text-sm text-gray-600 font-data mb-4">Create your first enrollment form to get started.</p>
                        <a href="{{ $builderNewRoute }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-[#1E40AF] hover:bg-[#1D3A8A] text-white text-sm font-semibold transition-colors no-underline font-data">Create New Form</a>
                    </div>
                @else
                    <p class="text-sm text-gray-500 font-data mb-4">{{ $savedForms->count() }} form(s)</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        @foreach($savedForms as $form)
                        <a href="{{ $isStaff ? route('staff.registration.edit-form', $form->id) : route('registrar.registration.edit-form', $form->id) }}" class="folder-card text-left group block no-underline text-inherit">
                            <div class="folder-preview">
                                <svg class="w-14 h-14 text-[#1E40AF]/70 group-hover:text-[#1E40AF] transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div class="p-4">
                                <h3 class="font-medium text-gray-900 mb-1 truncate font-heading group-hover:text-[#1E40AF] transition">{{ $form->title }}</h3>
                                <p class="text-xs text-gray-500 font-data mb-2">{{ $form->created_at->diffForHumans() }}</p>
                                <div class="flex items-center justify-between text-xs font-data flex-wrap gap-1">
                                    @if($form->is_active)
                                        <span class="bg-green-100 text-green-700 px-2 py-1 rounded font-medium">Active</span>
                                    @else
                                        <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded">Draft</span>
                                    @endif
                                    @if($form->assigned_year && $form->assigned_semester)
                                        <span class="text-gray-600">{{ $form->assigned_year }}</span>
                                    @endif
                                </div>
                                @if($form->incoming_year_level && $form->incoming_semester)
                                    <p class="text-[11px] text-gray-500 font-data mt-2">Destination: {{ $form->incoming_year_level }} – {{ $form->incoming_semester }}</p>
                                @endif
                            </div>
                        </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </main>

    <script>
        function formLibraryApp() {
            return {
                openDropdown: 'registrationMenu',
                globalEnrollment: {{ $globalEnrollmentActive ? 'true' : 'false' }},
                toggleDropdown(menu) { this.openDropdown = this.openDropdown === menu ? '' : menu; },
                toggleGlobal() {
                    this.globalEnrollment = !this.globalEnrollment;
                    fetch('{{ $isStaff ? '/staff/registration/toggle-global' : '/registrar/registration/toggle-global' }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({ is_active: this.globalEnrollment })
                    }).then(res => res.json()).then(data => alert(data.message));
                }
            };
        }
    </script>
</body>
</html>
