<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Manual Registration - DCOMC</title>
    @include('layouts.partials.offline-assets')
    <style>
        .font-heading { font-family: 'Figtree', sans-serif; }
        .font-data { font-family: 'Roboto', sans-serif; }
        .hero-gradient { background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 50%, #60A5FA 100%); }
        .required-asterisk { color: #d93025; }
        .dropdown-content { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; }
        .dropdown-content.open { max-height: 500px; }
        .folder-card { border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; overflow: hidden; transition: all 0.2s ease; }
        .folder-card:hover { box-shadow: 0 4px 12px rgba(30, 64, 175, 0.12); border-color: #1E40AF; transform: translateY(-1px); }
        .folder-preview { height: 110px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f8fafc, #eff6ff); border-bottom: 1px solid #eef2f7; }
        .section-nav { display: flex; flex-wrap: wrap; gap: 0.5rem 1rem; margin-bottom: 1rem; padding: 0.5rem 0; border-bottom: 1px solid #e8eaed; }
        .section-nav a { font-size: 0.8125rem; color: #1E40AF; text-decoration: none; font-weight: 500; }
        .section-nav a:hover { text-decoration: underline; }
        .success-sticky { position: sticky; top: 0; z-index: 20; }
        .form-section { background: #fff; border-radius: 8px; border: 1px solid #dadce0; padding: 1.5rem 1.75rem; margin-bottom: 1rem; box-shadow: 0 1px 2px rgba(60, 64, 67, 0.06); }
        .form-section-title { font-family: 'Figtree', sans-serif; font-size: 1rem; font-weight: 700; color: #202124; padding-bottom: 0.5rem; border-bottom: 1px solid #e8eaed; margin-bottom: 1.25rem; }
        .input-dcomc { border: 1px solid #dadce0; border-radius: 0.25rem; padding: 0.625rem 0.75rem; font-size: 0.875rem; width: 100%; transition: border-color 0.2s, box-shadow 0.2s; background: #fff; }
        .input-dcomc:focus { outline: none; border-color: #1E40AF; box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.2); }

        /* Google Forms–style (Detailed form only) */
        .forms-canvas { background: #f3f4f6; }
        .forms-header-card { background: #fff; border-top: 10px solid #1E40AF; border-radius: 8px; box-shadow: 0 1px 3px rgba(60, 64, 67, 0.08); padding: 1.5rem 1.75rem; margin-bottom: 0.75rem; }
        .forms-section-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(60, 64, 67, 0.08); padding: 1.5rem 1.75rem; margin-bottom: 0.75rem; border-left: 4px solid transparent; transition: border-left-color 0.2s ease; }
        .forms-section-card.active { border-left-color: #1E40AF; }
        .forms-section-title { font-family: 'Figtree', sans-serif; font-size: 1rem; font-weight: 700; color: #202124; padding-bottom: 0.5rem; border-bottom: 1px solid #e8eaed; margin-bottom: 1.25rem; }
        .form-block { margin-bottom: 1.5rem; }
        .form-block:last-child { margin-bottom: 0; }
        .label-dcomc { font-family: 'Figtree', sans-serif; font-weight: 700; color: #202124; font-size: 0.875rem; }
        /* Underlined inputs (Google Forms look) */
        .input-forms-underline { border: none; border-bottom: 1px solid #dadce0; border-radius: 0; padding: 0.5rem 0; font-size: 0.875rem; width: 100%; background: transparent; transition: border-color 0.2s, border-width 0.2s; font-family: 'Roboto', sans-serif; }
        .input-forms-underline:focus { outline: none; border-bottom-width: 2px; border-bottom-color: #1E40AF; }
        .input-forms-underline::placeholder { color: #9ca3af; }
        .select-forms-underline { border: none; border-bottom: 1px solid #dadce0; border-radius: 0; padding: 0.5rem 0; font-size: 0.875rem; width: 100%; background: #fff; transition: border-color 0.2s, border-width 0.2s; font-family: 'Roboto', sans-serif; cursor: pointer; }
        .select-forms-underline:focus { outline: none; border-bottom-width: 2px; border-bottom-color: #1E40AF; }
        .select-forms-underline.select-has-indicator { padding-right: 2rem; -webkit-appearance: none; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%235b5b5b' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 0 center; background-size: 1.25rem; }
        .select-forms-underline.select-has-indicator:focus { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%231E40AF' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E"); }
        .textarea-forms-underline { border: none; border-bottom: 1px solid #dadce0; border-radius: 0; padding: 0.5rem 0; font-size: 0.875rem; width: 100%; background: #fff; transition: border-color 0.2s, border-width 0.2s; font-family: 'Roboto', sans-serif; min-height: 2.5rem; resize: vertical; }
        .textarea-forms-underline:focus { outline: none; border-bottom-width: 2px; border-bottom-color: #1E40AF; }
        /* Radios & checkboxes: DCOMC blue selected state, always show circle/box */
        .input-forms-radio { accent-color: #1E40AF; -webkit-appearance: radio; appearance: radio; width: 1.125rem; height: 1.125rem; flex-shrink: 0; }
        .input-forms-checkbox { accent-color: #1E40AF; -webkit-appearance: checkbox; appearance: checkbox; width: 1.125rem; height: 1.125rem; flex-shrink: 0; }
    </style>
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden" x-data="manualRegistrationApp()">
    @php
        $user = auth()->user();
        $isStaff = request()->routeIs('staff.*');
        $manualRoute = $isStaff ? route('staff.registration.manual') : route('registrar.registration.manual');
        $builderRoute = $isStaff ? route('staff.registration.builder') : route('registrar.registration.builder');
        $responsesRoute = $isStaff ? route('staff.registration.responses') : route('registrar.registration.responses');
        $manualStoreRoute = $isStaff ? route('staff.registration.manual.store') : route('registrar.registration.manual.store');
        $manualImportRoute = $isStaff ? route('staff.registration.manual.import') : route('registrar.registration.manual.import');
        $manualImportTemplateRoute = $isStaff ? route('staff.registration.manual.import.template') : route('registrar.registration.manual.import.template');
        $dashboardRoute = $isStaff ? route('staff.dashboard') : route('registrar.dashboard');
        if ($isStaff && $user) {
            $canBuilderTab = \App\Models\StaffFeatureAccess::isEnabledForUser($user, 'staff_admission_form_builder');
            $canResponsesTab = \App\Models\StaffFeatureAccess::isEnabledForUser($user, 'staff_admission_responses');
        } elseif ($user) {
            $canBuilderTab = \App\Models\RegistrarFeatureAccess::isEnabledForUser($user, 'registrar_admission_form_builder');
            $canResponsesTab = \App\Models\RegistrarFeatureAccess::isEnabledForUser($user, 'registrar_admission_responses');
        } else {
            $canBuilderTab = false;
            $canResponsesTab = false;
        }
    @endphp
    @include('dashboards.partials.role-sidebar')

    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
        {{-- Sub-nav tabs (Manual / Form Builder / Responses) --}}
        <div class="bg-white border-b border-gray-200 px-6 py-2 flex items-center justify-between gap-4 shrink-0">
            <div class="flex items-center gap-1">
                <a href="{{ $manualRoute }}" class="px-4 py-2.5 text-sm font-semibold text-[#1E40AF] border-b-2 border-[#1E40AF] font-heading">Manual Registration</a>
                @if(!empty($canBuilderTab))
                    <a href="{{ $builderRoute }}" class="px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 rounded-t font-heading">Form Builder</a>
                @endif
                @if(!empty($canResponsesTab))
                    <a href="{{ $responsesRoute }}" class="px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 rounded-t font-heading flex items-center gap-2">Responses <span class="bg-gray-200 text-gray-800 text-xs font-bold px-2 py-0.5 rounded-full">{{ $totalResponsesCount }}</span></a>
                @endif
            </div>
            <div class="flex items-center gap-3">
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

        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas" x-data="{ successDismissed: false }">
            <div class="w-full mx-auto" :class="(manualRegMode === 'detailed' || manualRegMode === 'formBased') ? 'max-w-3xl' : 'max-w-6xl'">
                @if(session('success'))
                    <div x-show="!successDismissed" x-transition class="success-sticky mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm font-data flex items-center justify-between gap-4 shadow-sm">
                        <span>{{ session('success') }}</span>
                        <button type="button" @click="successDismissed = true" class="shrink-0 p-1 rounded hover:bg-green-100 text-green-700 focus:outline-none focus:ring-2 focus:ring-green-400" aria-label="Dismiss">✕</button>
                    </div>
                @endif

                {{-- Hero Banner (choice / form-based / batch only; detailed uses header card below) --}}
                <section x-show="manualRegMode !== 'detailed' && manualRegMode !== 'formBased'" class="hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Manual Student Registration</h1>
                            <p class="text-white/90 text-sm sm:text-base">Encode walk-in applications and official student records manually.</p>
                        </div>
                        <a href="{{ $dashboardRoute }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-white/20 hover:bg-white/30 text-white text-sm font-medium transition-colors no-underline shrink-0 font-data">← Back to Dashboard</a>
                    </div>
                </section>

                <div x-show="manualRegMode === 'choice'">
                    <p class="text-sm text-gray-600 mb-6 font-data">Choose a registration mode to continue.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <button type="button" @click="manualRegMode = 'formBased'" class="folder-card text-left group">
                            <div class="folder-preview">
                                <svg class="w-14 h-14 text-[#1E40AF]/70 group-hover:text-[#1E40AF] transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                            </div>
                            <div class="p-4">
                                <h3 class="font-medium text-gray-900 mb-1 font-heading">Form-Based</h3>
                                <p class="text-xs text-gray-500 font-data">Use deployed templates from Form Builder.</p>
                            </div>
                        </button>

                        <button type="button" @click="goToDetailed()" class="folder-card text-left group">
                            <div class="folder-preview">
                                <svg class="w-14 h-14 text-[#1E40AF]/80 group-hover:text-[#1E40AF] transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2zm3 4h4m-4 4h4m-4 4h4"/></svg>
                            </div>
                            <div class="p-4">
                                <h3 class="font-medium text-gray-900 mb-1 font-heading">Detailed</h3>
                                <p class="text-xs text-gray-500 font-data">Encode student details from scratch.</p>
                            </div>
                        </button>

                        <button type="button" @click="manualRegMode = 'batchImport'" class="folder-card text-left group">
                            <div class="folder-preview">
                                <svg class="w-14 h-14 text-[#1E40AF]/70 group-hover:text-[#1E40AF] transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            </div>
                            <div class="p-4">
                                <h3 class="font-medium text-gray-900 mb-1 font-heading">Batch Import</h3>
                                <p class="text-xs text-gray-500 font-data">Upload CSV to add many students at once.</p>
                            </div>
                        </button>
                    </div>
                </div>

                <div x-show="manualRegMode === 'formBased'" style="display: none;">
                    {{-- Google Forms–style header card (same as Detailed) --}}
                    <div class="forms-header-card">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                            <div>
                                <h1 class="font-heading text-2xl font-bold text-gray-900 mb-1">Form-Based Registration</h1>
                                <p class="text-sm text-gray-600 font-data">Use a deployed form template from Form Builder. Select a template below, then fill out the questions. Required fields are marked with <span class="required-asterisk">*</span>.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 shrink-0">
                                <a href="{{ $dashboardRoute }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold transition-colors no-underline font-data focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                    Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mb-4">
                        <button type="button" @click="manualRegMode = 'choice'" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold transition-colors font-data focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2">
                            Cancel
                        </button>
                    </div>

                    {{-- Deployed Templates card (floating section with active state) --}}
                    <div class="forms-section-card" @focusin="activeSectionFormBased = 'templates'; activeQuestionIndex = null" :class="{ 'active': activeSectionFormBased === 'templates' }">
                        <h2 class="forms-section-title font-heading">Deployed Templates</h2>
                        <p class="text-xs text-gray-500 mb-3 font-data" x-text="deployedForms.length + ' template(s) available'"></p>
                        <template x-if="deployedForms.length > 0">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <template x-for="template in deployedForms" :key="template.id">
                                    <button type="button" @click="selectTemplateById(template.id)" class="folder-card text-left group">
                                        <div class="folder-preview">
                                            <svg class="w-12 h-12 transition"
                                                :class="selectedTemplate && selectedTemplate.id === template.id ? 'text-[#1E40AF]' : 'text-[#1E40AF]/70 group-hover:text-[#1E40AF]'"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                                        </div>
                                        <div class="p-3">
                                            <h4 class="text-sm font-medium text-gray-900 truncate" x-text="template.title"></h4>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <span x-text="template.assigned_year"></span> – <span x-text="template.assigned_semester"></span>
                                            </p>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>

                    <template x-if="deployedForms.length === 0">
                        <div class="forms-section-card mt-4 text-center py-8">
                            <h3 class="font-heading text-lg font-bold text-gray-800 mb-2">No Deployed Templates</h3>
                            <p class="text-sm text-gray-600 mb-4 font-data">Deploy a form in Form Builder so it appears here for manual registration.</p>
                            @if(!empty($canBuilderTab))
                            <a href="{{ $builderRoute }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-[#1E40AF] hover:bg-[#1D3A8A] text-white text-sm font-semibold transition-colors no-underline font-data">Go to Form Builder</a>
                            @endif
                        </div>
                    </template>

                    <template x-if="deployedForms.length > 0 && !selectedTemplate">
                        <div class="forms-section-card mt-4 text-center py-8">
                            <h3 class="font-heading text-lg font-bold text-gray-800 mb-2">Select a Template to Start</h3>
                            <p class="text-sm text-gray-600 font-data">Choose a deployed form above to process manual registration using that template.</p>
                        </div>
                    </template>

                    <template x-if="selectedTemplate">
                        <div class="mt-4 flex-1 overflow-y-auto space-y-4">
                            <div class="forms-section-card border-l-4 border-[#1E40AF]" @focusin="activeSectionFormBased = 'info'; activeQuestionIndex = null" :class="{ 'active': activeSectionFormBased === 'info' }">
                                <h3 class="forms-section-title font-heading" x-text="selectedTemplate.title"></h3>
                                <p class="text-sm text-gray-600 mt-1 font-data" x-text="selectedTemplate.description || 'No description'"></p>
                                <p class="text-xs text-gray-500 mt-2 font-data">
                                    Target: <span class="font-medium" x-text="selectedTemplate.assigned_year"></span> –
                                    <span class="font-medium" x-text="selectedTemplate.assigned_semester"></span>
                                </p>
                            </div>

                            <template x-for="(question, index) in (selectedTemplate.questions || [])" :key="index">
                                <div class="forms-section-card" @focusin="activeQuestionIndex = index; activeSectionFormBased = null" :class="{ 'active': activeQuestionIndex === index }">
                                    <template x-if="question.type === 'section'">
                                        <div>
                                            <h4 class="forms-section-title font-heading" x-text="question.title || 'Section'"></h4>
                                            <p class="text-sm text-gray-600 mt-1 font-data" x-text="question.description || ''"></p>
                                        </div>
                                    </template>

                                    <template x-if="question.type === 'description'">
                                        <div>
                                            <h4 class="font-heading font-bold text-gray-800" x-text="question.title || 'Description'"></h4>
                                            <p class="text-sm text-gray-600 mt-1 whitespace-pre-line font-data" x-text="question.description || ''"></p>
                                        </div>
                                    </template>

                                    <template x-if="question.type === 'question'">
                                        <div class="form-block">
                                            <label class="label-dcomc block mb-2">
                                                <span x-text="question.questionText || 'Untitled Question'"></span>
                                                <span x-show="question.required" class="required-asterisk">*</span>
                                            </label>

                                            <template x-if="question.questionType === 'short'">
                                                <input type="text" x-model="formBasedAnswers[index]" class="input-forms-underline font-data">
                                            </template>

                                            <template x-if="question.questionType === 'paragraph'">
                                                <textarea rows="3" x-model="formBasedAnswers[index]" class="textarea-forms-underline font-data"></textarea>
                                            </template>

                                            <template x-if="question.questionType === 'dropdown'">
                                                <select x-model="formBasedAnswers[index]" class="select-forms-underline select-has-indicator font-data">
                                                    <option value="">— Select —</option>
                                                    <template x-for="(opt, optIndex) in (question.options || [])" :key="optIndex">
                                                        <option :value="opt" x-text="opt"></option>
                                                    </template>
                                                </select>
                                            </template>

                                            <template x-if="question.questionType === 'radio'">
                                                <div class="space-y-2">
                                                    <template x-for="(opt, optIndex) in (question.options || [])" :key="optIndex">
                                                        <label class="flex items-center gap-2 cursor-pointer font-data text-sm text-gray-700">
                                                            <input type="radio" :name="'fbq-' + index" :value="opt" x-model="formBasedAnswers[index]" class="input-forms-radio">
                                                            <span x-text="opt"></span>
                                                        </label>
                                                    </template>
                                                </div>
                                            </template>

                                            <template x-if="question.questionType === 'checkbox'">
                                                <div class="space-y-2">
                                                    <template x-for="(opt, optIndex) in (question.options || [])" :key="optIndex">
                                                        <label class="flex items-center gap-2 cursor-pointer font-data text-sm text-gray-700">
                                                            <input type="checkbox" :value="opt" @change="toggleCheckbox(index, opt, $event.target.checked)" class="input-forms-checkbox">
                                                            <span x-text="opt"></span>
                                                        </label>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <div x-show="manualRegMode === 'detailed'" style="display: none;">
                    {{-- Google Forms–style header card (thick DCOMC blue top border) --}}
                    <div class="forms-header-card">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                            <div>
                                <h1 class="font-heading text-2xl font-bold text-gray-900 mb-1">Manual Student Registration</h1>
                                <p class="text-sm text-gray-600 font-data">Encode walk-in applications and official student records manually. Required fields are marked with <span class="required-asterisk">*</span>.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 shrink-0">
                                <a href="{{ $dashboardRoute }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold transition-colors no-underline font-data focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                    Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mb-4">
                        <button type="button" @click="manualRegMode = 'choice'" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold transition-colors font-data focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2">
                            Cancel
                        </button>
                    </div>

                    <nav class="section-nav font-data" aria-label="Jump to section">
                        <a href="#section-personal">Personal Information</a>
                        <a href="#section-academic">Academic Profile</a>
                        <a href="#section-documents">Document Checklist</a>
                    </nav>

                    <form method="POST" action="{{ $manualStoreRoute }}" id="manual-reg-form" class="space-y-4" @submit="submitting = true">
                        @csrf
                        @if($errors->any())
                            <div class="forms-section-card border-red-200 bg-red-50 p-4">
                                <p class="font-semibold text-red-800 mb-1 font-data">Please fix the following:</p>
                                <ul class="list-disc pl-5 text-red-700 text-sm font-data">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                            </div>
                        @endif

                        {{-- Card 1: Personal Information --}}
                        <div id="section-personal" class="forms-section-card" data-section="personal" @focusin="activeSection = 'personal'" style="scroll-margin-top: 1rem;" :class="{ 'active': activeSection === 'personal' }">
                            <h2 class="forms-section-title font-heading">Personal Information</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="form-block">
                                    <label for="first_name" class="label-dcomc block mb-1">Full Name (First) <span class="required-asterisk">*</span></label>
                                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required class="input-forms-underline font-data" :disabled="submitting">
                                    @error('first_name')<p class="text-red-600 text-sm mt-1 font-data">{{ $message }}</p>@enderror
                                </div>
                                <div class="form-block">
                                    <label for="middle_name" class="label-dcomc block mb-1">Middle Name</label>
                                    <input type="text" id="middle_name" name="middle_name" value="{{ old('middle_name') }}" class="input-forms-underline font-data" :disabled="submitting">
                                </div>
                                <div class="form-block">
                                    <label for="last_name" class="label-dcomc block mb-1">Last Name <span class="required-asterisk">*</span></label>
                                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required class="input-forms-underline font-data" :disabled="submitting">
                                    @error('last_name')<p class="text-red-600 text-sm mt-1 font-data">{{ $message }}</p>@enderror
                                </div>
                                <div class="form-block">
                                    <label for="date_of_birth" class="label-dcomc block mb-1">Date of Birth</label>
                                    <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}" class="input-forms-underline font-data" :disabled="submitting">
                                </div>
                                <div class="form-block">
                                    <label for="gender" class="label-dcomc block mb-1">Gender <span class="required-asterisk">*</span></label>
                                    <select id="gender" name="gender" required class="select-forms-underline font-data" :disabled="submitting">
                                        <option value="Male" {{ old('gender') === 'Male' ? 'selected' : '' }}>Male</option>
                                        <option value="Female" {{ old('gender') === 'Female' ? 'selected' : '' }}>Female</option>
                                    </select>
                                    @error('gender')<p class="text-red-600 text-sm mt-1 font-data">{{ $message }}</p>@enderror
                                </div>
                                <div class="form-block">
                                    <label for="civil_status" class="label-dcomc block mb-1">Civil Status</label>
                                    <select id="civil_status" name="civil_status" class="select-forms-underline font-data" :disabled="submitting">
                                        <option value="Single" {{ old('civil_status', 'Single') === 'Single' ? 'selected' : '' }}>Single</option>
                                        <option value="Married" {{ old('civil_status') === 'Married' ? 'selected' : '' }}>Married</option>
                                        <option value="Widowed" {{ old('civil_status') === 'Widowed' ? 'selected' : '' }}>Widowed</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2 form-block">
                                    <p class="label-dcomc block mb-2">Address</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div><label for="house_number" class="text-xs font-medium text-gray-600 block mb-1 font-data">House No.</label><input type="text" id="house_number" name="house_number" value="{{ old('house_number') }}" class="input-forms-underline font-data" :disabled="submitting"></div>
                                        <div><label for="street" class="text-xs font-medium text-gray-600 block mb-1 font-data">Street / Subdivision</label><input type="text" id="street" name="street" value="{{ old('street') }}" class="input-forms-underline font-data" :disabled="submitting"></div>
                                        <div><label for="purok_zone" class="text-xs font-medium text-gray-600 block mb-1 font-data">Purok / Zone</label><input type="text" id="purok_zone" name="purok_zone" value="{{ old('purok_zone') }}" class="input-forms-underline font-data" :disabled="submitting"></div>
                                        <div><label for="barangay" class="text-xs font-medium text-gray-600 block mb-1 font-data">Barangay</label><input type="text" id="barangay" name="barangay" value="{{ old('barangay') }}" class="input-forms-underline font-data" :disabled="submitting"></div>
                                        <div><label for="municipality" class="text-xs font-medium text-gray-600 block mb-1 font-data">City / Municipality</label><input type="text" id="municipality" name="municipality" value="{{ old('municipality') }}" class="input-forms-underline font-data" :disabled="submitting"></div>
                                        <div><label for="province" class="text-xs font-medium text-gray-600 block mb-1 font-data">Province</label><input type="text" id="province" name="province" value="{{ old('province') }}" class="input-forms-underline font-data" :disabled="submitting"></div>
                                        <div><label for="zip_code" class="text-xs font-medium text-gray-600 block mb-1 font-data">Zip Code</label><input type="text" id="zip_code" name="zip_code" value="{{ old('zip_code') }}" class="input-forms-underline font-data" :disabled="submitting"></div>
                                    </div>
                                </div>
                                <div class="form-block">
                                    <label for="place_of_birth" class="label-dcomc block mb-1">Place of Birth</label>
                                    <input type="text" id="place_of_birth" name="place_of_birth" value="{{ old('place_of_birth') }}" class="input-forms-underline font-data" :disabled="submitting">
                                </div>
                                <div class="form-block">
                                    <label for="citizenship" class="label-dcomc block mb-1">Citizenship</label>
                                    <input type="text" id="citizenship" name="citizenship" value="{{ old('citizenship', 'Filipino') }}" class="input-forms-underline font-data" :disabled="submitting">
                                </div>
                                <div class="form-block">
                                    <label for="phone" class="label-dcomc block mb-1">Contact No.</label>
                                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}" placeholder="09xxxxxxxxx" class="input-forms-underline font-data" :disabled="submitting">
                                </div>
                                <div class="form-block">
                                    <label for="email" class="label-dcomc block mb-1">Email (optional)</label>
                                    <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Leave blank to auto-generate" class="input-forms-underline font-data" :disabled="submitting">
                                </div>
                                <div class="md:col-span-2 form-block border-t border-gray-200 pt-4 mt-2">
                                    <p class="label-dcomc text-sm mb-3">Family / Guardian</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div><label for="father_name" class="text-xs font-medium text-gray-600 block mb-1 font-data">Father's Full Name</label><input type="text" id="father_name" name="father_name" value="{{ old('father_name') }}" class="input-forms-underline font-data" :disabled="submitting"></div>
                                        <div><label for="father_occupation" class="text-xs font-medium text-gray-600 block mb-1 font-data">Occupation (Father)</label><input type="text" id="father_occupation" name="father_occupation" value="{{ old('father_occupation') }}" class="input-forms-underline font-data" :disabled="submitting"></div>
                                        <div><label for="mother_name" class="text-xs font-medium text-gray-600 block mb-1 font-data">Mother's Maiden Name</label><input type="text" id="mother_name" name="mother_name" value="{{ old('mother_name') }}" class="input-forms-underline font-data" :disabled="submitting"></div>
                                        <div><label for="mother_occupation" class="text-xs font-medium text-gray-600 block mb-1 font-data">Occupation (Mother)</label><input type="text" id="mother_occupation" name="mother_occupation" value="{{ old('mother_occupation') }}" class="input-forms-underline font-data" :disabled="submitting"></div>
                                        <div><label for="monthly_income" class="text-xs font-medium text-gray-600 block mb-1 font-data">Monthly Income</label><input type="text" id="monthly_income" name="monthly_income" value="{{ old('monthly_income') }}" placeholder="e.g. 15000 or N/A" class="input-forms-underline font-data" :disabled="submitting"></div>
                                        <div><label for="dswd_household_no" class="text-xs font-medium text-gray-600 block mb-1 font-data">DSWD Household No.</label><input type="text" id="dswd_household_no" name="dswd_household_no" value="{{ old('dswd_household_no') }}" class="input-forms-underline font-data" :disabled="submitting"></div>
                                        <div><label for="emergency_contact_name" class="text-xs font-medium text-gray-600 block mb-1 font-data">Emergency Contact Name</label><input type="text" id="emergency_contact_name" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" class="input-forms-underline font-data" :disabled="submitting"></div>
                                        <div><label for="emergency_contact_phone" class="text-xs font-medium text-gray-600 block mb-1 font-data">Emergency Contact Number</label><input type="text" id="emergency_contact_phone" name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}" class="input-forms-underline font-data" :disabled="submitting"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Card 2: Academic Profile --}}
                        <div id="section-academic" class="forms-section-card" data-section="academic" @focusin="activeSection = 'academic'" style="scroll-margin-top: 1rem;" :class="{ 'active': activeSection === 'academic' }">
                            <h2 class="forms-section-title font-heading">Academic Profile</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="form-block">
                                    <label for="course" class="label-dcomc block mb-1">Course / Program <span class="required-asterisk">*</span></label>
                                    <select id="course" name="course" required x-model="selectedProgram" @change="syncMajorsByProgram()" class="select-forms-underline select-has-indicator font-data" :disabled="submitting">
                                        <option value="">— Select Program —</option>
                                        @foreach($programOptions as $prog)
                                            <option value="{{ $prog }}" {{ old('course') === $prog ? 'selected' : '' }}>{{ $prog }}</option>
                                        @endforeach
                                    </select>
                                    @error('course')<p class="text-red-600 text-sm mt-1 font-data">{{ $message }}</p>@enderror
                                </div>
                                <div class="form-block">
                                    <label for="major" class="label-dcomc block mb-1">Major (optional)</label>
                                    <select id="major" name="major" x-model="selectedMajor" class="select-forms-underline select-has-indicator font-data" :disabled="availableMajors.length === 0 || submitting">
                                        <option value="">— Select Major —</option>
                                        <template x-for="major in availableMajors" :key="major">
                                            <option :value="major" x-text="major"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="form-block">
                                    <label for="year_level" class="label-dcomc block mb-1">Year Level <span class="required-asterisk">*</span></label>
                                    <select id="year_level" name="year_level" required class="select-forms-underline font-data" :disabled="submitting">
                                        @forelse($yearLevels as $yearLevel)
                                            <option value="{{ $yearLevel }}" {{ old('year_level') === $yearLevel ? 'selected' : '' }}>{{ $yearLevel }}</option>
                                        @empty
                                            <option>No year level saved</option>
                                        @endforelse
                                    </select>
                                    @error('year_level')<p class="text-red-600 text-sm mt-1 font-data">{{ $message }}</p>@enderror
                                </div>
                                <div class="form-block">
                                    <label for="semester" class="label-dcomc block mb-1">Semester <span class="required-asterisk">*</span></label>
                                    <select id="semester" name="semester" required class="select-forms-underline font-data" :disabled="submitting">
                                        @forelse($semesters as $semester)
                                            <option value="{{ $semester }}" {{ old('semester') === $semester ? 'selected' : '' }}>{{ $semester }}</option>
                                        @empty
                                            <option>No semester saved</option>
                                        @endforelse
                                    </select>
                                    @error('semester')<p class="text-red-600 text-sm mt-1 font-data">{{ $message }}</p>@enderror
                                </div>
                                <div class="form-block">
                                    <label for="school_year" class="label-dcomc block mb-1">School Year</label>
                                    <select id="school_year" name="school_year" class="select-forms-underline font-data" :disabled="submitting">
                                        @forelse($schoolYears ?? collect() as $sy)
                                            <option value="{{ $sy }}" {{ old('school_year', $defaultSchoolYear ?? optional($schoolYears)->first() ?? '') === $sy ? 'selected' : '' }}>{{ $sy }}</option>
                                        @empty
                                            @php $current = now()->month >= 6 ? now()->year . '-' . (now()->year + 1) : (now()->year - 1) . '-' . now()->year; @endphp
                                            <option value="{{ $current }}" selected>{{ $current }}</option>
                                        @endforelse
                                    </select>
                                </div>
                                <div class="form-block">
                                    <label for="shift" class="label-dcomc block mb-1">Shift <span class="required-asterisk">*</span></label>
                                    <select id="shift" name="shift" required class="select-forms-underline font-data" :disabled="submitting">
                                        <option value="day" {{ old('shift', 'day') === 'day' ? 'selected' : '' }}>Day</option>
                                        <option value="night" {{ old('shift') === 'night' ? 'selected' : '' }}>Night</option>
                                    </select>
                                    @error('shift')<p class="text-red-600 text-sm mt-1 font-data">{{ $message }}</p>@enderror
                                </div>
                                <div class="md:col-span-2 form-block">
                                    <p class="label-dcomc block mb-2">Student Type</p>
                                    <div class="flex flex-wrap gap-4">
                                        <label class="flex items-center gap-2 cursor-pointer font-data text-sm text-gray-700">
                                            <input type="radio" name="student_type" value="Regular" {{ old('student_type', 'Regular') === 'Regular' ? 'checked' : '' }} class="input-forms-radio" :disabled="submitting"> New / Regular
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer font-data text-sm text-gray-700">
                                            <input type="radio" name="student_type" value="Transferee" {{ old('student_type') === 'Transferee' ? 'checked' : '' }} class="input-forms-radio" :disabled="submitting"> Transferee
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer font-data text-sm text-gray-700">
                                            <input type="radio" name="student_type" value="Returnee" {{ old('student_type') === 'Returnee' ? 'checked' : '' }} class="input-forms-radio" :disabled="submitting"> Returnee / Old
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer font-data text-sm text-gray-700">
                                            <input type="radio" name="student_type" value="Irregular" {{ old('student_type') === 'Irregular' ? 'checked' : '' }} class="input-forms-radio" :disabled="submitting"> Irregular
                                        </label>
                                    </div>
                                </div>
                                <div class="form-block">
                                    <label for="high_school" class="label-dcomc block mb-1">Last School Attended</label>
                                    <input type="text" id="high_school" name="high_school" value="{{ old('high_school') }}" class="input-forms-underline font-data" :disabled="submitting">
                                </div>
                                <div class="form-block">
                                    <label for="hs_graduation_date" class="label-dcomc block mb-1">Year Graduated</label>
                                    <input type="text" id="hs_graduation_date" name="hs_graduation_date" value="{{ old('hs_graduation_date') }}" class="input-forms-underline font-data" :disabled="submitting">
                                </div>
                                <div class="form-block">
                                    <label for="lrn" class="label-dcomc block mb-1">LRN</label>
                                    <input type="text" id="lrn" name="lrn" value="{{ old('lrn') }}" placeholder="Learner Reference Number" class="input-forms-underline font-data" :disabled="submitting">
                                </div>
                                <div class="form-block">
                                    <label for="num_family_members" class="label-dcomc block mb-1">No. of Family Members</label>
                                    <input type="number" id="num_family_members" name="num_family_members" value="{{ old('num_family_members') }}" min="0" max="99" placeholder="N/A or number" class="input-forms-underline font-data" :disabled="submitting">
                                </div>
                            </div>
                        </div>

                        {{-- Card 3: Document Checklist (Requirements) --}}
                        <div id="section-documents" class="forms-section-card" data-section="documents" @focusin="activeSection = 'documents'" style="scroll-margin-top: 1rem;" :class="{ 'active': activeSection === 'documents' }">
                            <h2 class="forms-section-title font-heading">Requirements</h2>
                            <p class="text-sm text-gray-600 mb-4 font-data">Check requirements submitted by the student (for office use).</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                <label class="flex items-center gap-2 cursor-pointer font-data text-sm text-gray-700">
                                    <input type="checkbox" name="document_birth_certificate" value="1" {{ old('document_birth_certificate') ? 'checked' : '' }} class="input-forms-checkbox" :disabled="submitting"> Birth Certificate
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-data text-sm text-gray-700">
                                    <input type="checkbox" name="document_form_138" value="1" {{ old('document_form_138') ? 'checked' : '' }} class="input-forms-checkbox" :disabled="submitting"> Form 138 / SF9
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-data text-sm text-gray-700">
                                    <input type="checkbox" name="document_good_moral" value="1" {{ old('document_good_moral') ? 'checked' : '' }} class="input-forms-checkbox" :disabled="submitting"> Good Moral Certificate
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-data text-sm text-gray-700">
                                    <input type="checkbox" name="document_photos" value="1" {{ old('document_photos') ? 'checked' : '' }} class="input-forms-checkbox" :disabled="submitting"> 2x2 ID Photos
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-data text-sm text-gray-700">
                                    <input type="checkbox" name="document_medical" value="1" {{ old('document_medical') ? 'checked' : '' }} class="input-forms-checkbox" :disabled="submitting"> Medical Certificate
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-data text-sm text-gray-700">
                                    <input type="checkbox" name="document_other" value="1" {{ old('document_other') ? 'checked' : '' }} class="input-forms-checkbox" :disabled="submitting"> Other (specify in remarks)
                                </label>
                            </div>
                            <div class="form-block mt-4 pt-4 border-t border-gray-200">
                                <label for="registration_remarks" class="label-dcomc block mb-1">Remarks</label>
                                <textarea id="registration_remarks" name="registration_remarks" rows="2" class="textarea-forms-underline font-data" placeholder="N/A or additional notes" :disabled="submitting">{{ old('registration_remarks') }}</textarea>
                            </div>
                        </div>

                        {{-- Action Footer: Submit & Clear Form --}}
                        <div class="forms-section-card mt-6 flex flex-col sm:flex-row items-end sm:items-center justify-end gap-3 py-4">
                            <button type="submit" :disabled="submitting" class="order-2 sm:order-1 inline-flex items-center justify-center gap-1.5 px-5 py-2 rounded-lg text-xs font-semibold text-white bg-[#1E40AF] hover:bg-[#1D3A8A] transition-colors font-data focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:ring-offset-2 disabled:opacity-70 disabled:cursor-not-allowed shadow-sm">
                                <span x-show="!submitting">Submit</span>
                                <span x-show="submitting" class="inline-flex items-center gap-1.5">
                                    <svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Submitting…
                                </span>
                            </button>
                            <button type="button" @click="clearFormConfirm()" :disabled="submitting" class="order-1 sm:order-2 inline-flex items-center justify-center px-5 py-2 rounded-lg text-xs font-semibold text-gray-600 bg-white border-2 border-gray-300 hover:bg-gray-50 transition-colors font-data focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 disabled:opacity-70 disabled:cursor-not-allowed">
                                Clear Form
                            </button>
                        </div>
                    </form>
                </div>

                <div x-show="manualRegMode === 'batchImport'" class="form-section flex flex-col min-h-[400px]" style="display: none;">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="form-section-title accent font-heading">Batch Import</h2>
                        <button type="button" @click="manualRegMode = 'choice'" class="text-sm bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg text-gray-700 transition font-medium font-data">← Back</button>
                    </div>
                    <div class="max-w-2xl">
                        <p class="text-sm text-gray-600 mb-4 font-data">Upload a CSV file to create multiple student accounts at once. Each row becomes one account.</p>
                        <ul class="text-sm text-gray-600 list-disc pl-5 mb-4 space-y-1 font-data">
                            <li><strong>Required column:</strong> <code class="bg-gray-100 px-1.5 py-0.5 rounded text-[#1E40AF]">student_id</code> or <code class="bg-gray-100 px-1.5 py-0.5 rounded text-[#1E40AF]">school_id</code> — used as <strong>School ID / login</strong> (do not use "email").</li>
                            <li><strong>Password for all imported accounts:</strong> <code class="bg-gray-100 px-1.5 py-0.5 rounded">Student12345</code></li>
                            <li><strong>student_type</strong> must be one of: <code class="bg-gray-100 px-1.5 py-0.5 rounded">Regular</code>, <code class="bg-gray-100 px-1.5 py-0.5 rounded">Transferee</code>, <code class="bg-gray-100 px-1.5 py-0.5 rounded">Returnee</code>, <code class="bg-gray-100 px-1.5 py-0.5 rounded">Irregular</code>. Default: Regular.</li>
                            <li>Optional columns: <code class="bg-gray-100 px-1.5 py-0.5 rounded">first_name</code>, <code class="bg-gray-100 px-1.5 py-0.5 rounded">last_name</code>, <code class="bg-gray-100 px-1.5 py-0.5 rounded">middle_name</code>, <code class="bg-gray-100 px-1.5 py-0.5 rounded">name</code>, <code class="bg-gray-100 px-1.5 py-0.5 rounded">course</code>, <code class="bg-gray-100 px-1.5 py-0.5 rounded">year_level</code>, <code class="bg-gray-100 px-1.5 py-0.5 rounded">semester</code>, <code class="bg-gray-100 px-1.5 py-0.5 rounded">school_year</code>, <code class="bg-gray-100 px-1.5 py-0.5 rounded">gender</code>, <code class="bg-gray-100 px-1.5 py-0.5 rounded">phone</code>, <code class="bg-gray-100 px-1.5 py-0.5 rounded">major</code>, <code class="bg-gray-100 px-1.5 py-0.5 rounded">municipality</code>, etc.</li>
                        </ul>
                        <div class="flex flex-wrap items-center gap-3 mb-4">
                            <a href="{{ $manualImportTemplateRoute }}" class="inline-flex items-center gap-2 bg-[#1E40AF] hover:bg-[#1D3A8A] text-white px-4 py-2.5 rounded-lg font-semibold text-sm transition no-underline font-data">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0L8 8m4-4v12"/></svg>
                                Download CSV template
                            </a>
                            <span class="text-xs text-gray-500 font-data">Template includes column headers, one example row, and blank rows.</span>
                        </div>
                        <form method="POST" action="{{ $manualImportRoute }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            @if($errors->any())
                                <div class="form-section border-red-200 bg-red-50 p-4">
                                    <p class="font-semibold text-red-800 mb-1 font-data">Please fix the following:</p>
                                    <ul class="list-disc pl-5 text-red-700 text-sm font-data">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                                </div>
                            @endif
                            <div class="form-block">
                                <label for="csv_file" class="label-dcomc block mb-2">CSV file <span class="required-asterisk">*</span></label>
                                <input type="file" id="csv_file" name="csv_file" accept=".csv,.txt" required class="block w-full text-sm text-gray-600 font-data file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:bg-[#1E40AF] file:text-white file:font-semibold hover:file:bg-[#1D3A8A] file:transition-colors">
                            </div>
                            <button type="submit" class="px-6 py-2.5 rounded-lg text-sm font-semibold text-white bg-[#1E40AF] hover:bg-[#1D3A8A] transition-colors font-data focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:ring-offset-2">
                                Import students
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
        function manualRegistrationApp() {
            return {
                openDropdown: 'registrationMenu',
                globalEnrollment: {{ $globalEnrollmentActive ? 'true' : 'false' }},
                manualRegMode: 'choice',
                currentTab: 'basic',
                submitting: false,
                activeSection: null,
                activeSectionFormBased: null,
                activeQuestionIndex: null,
                deployedForms: @json($savedForms->filter(fn($form) => !empty($form->assigned_year) && !empty($form->assigned_semester))->values()),
                programOptions: @json(($programOptions ?? collect())->values()),
                majorsByProgram: @json($majorsByProgram ?? []),
                selectedProgram: '',
                selectedMajor: '',
                availableMajors: [],
                selectedTemplateId: '',
                selectedTemplate: null,
                formBasedAnswers: {},

                goToDetailed() {
                    this.manualRegMode = 'detailed';
                    this.$nextTick(() => {
                        const el = document.getElementById('first_name');
                        if (el) el.focus();
                    });
                },
                clearFormConfirm() {
                    const form = document.getElementById('manual-reg-form');
                    if (!form) return;
                    const inputs = form.querySelectorAll('input:not([type="hidden"]), select, textarea');
                    const hasValue = Array.from(inputs).some(i => (i.type === 'checkbox' || i.type === 'radio') ? i.checked : (i.value && i.value.trim() !== ''));
                    if (hasValue && !confirm('Clear form? All entered data will be lost.')) return;
                    form.reset();
                },
                toggleDropdown(menu) { this.openDropdown = this.openDropdown === menu ? '' : menu; },
                syncMajorsByProgram() {
                    const majors = this.majorsByProgram[this.selectedProgram] || [];
                    this.availableMajors = [...majors];
                    if (!this.availableMajors.includes(this.selectedMajor)) {
                        this.selectedMajor = '';
                    }
                },
                selectTemplate() {
                    const id = Number(this.selectedTemplateId);
                    this.selectedTemplate = this.deployedForms.find(form => form.id === id) || null;
                    this.formBasedAnswers = {};
                },
                selectTemplateById(id) {
                    this.selectedTemplateId = String(id);
                    this.selectTemplate();
                },
                toggleCheckbox(questionIndex, optionValue, checked) {
                    if (!Array.isArray(this.formBasedAnswers[questionIndex])) {
                        this.formBasedAnswers[questionIndex] = [];
                    }
                    if (checked) {
                        if (!this.formBasedAnswers[questionIndex].includes(optionValue)) {
                            this.formBasedAnswers[questionIndex].push(optionValue);
                        }
                        return;
                    }
                    this.formBasedAnswers[questionIndex] = this.formBasedAnswers[questionIndex].filter(
                        value => value !== optionValue
                    );
                },
                toggleGlobal() { 
                    this.globalEnrollment = !this.globalEnrollment;
                    fetch('{{ $isStaff ? '/staff/registration/toggle-global' : '/registrar/registration/toggle-global' }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ is_active: this.globalEnrollment })
                    })
                    .then(res => res.json())
                    .then(data => {
                        alert(data.message);
                    });
                }
            }
        }
    </script>
</body>
</html>