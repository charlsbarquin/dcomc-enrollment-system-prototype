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
        .forms-header-card { background: #fff; border-top: 10px solid #1E40AF; border-radius: 8px; box-shadow: 0 1px 3px rgba(60, 64, 67, 0.08); padding: 1.5rem 1.75rem; margin-bottom: 0.75rem; }
        .forms-section-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(60, 64, 67, 0.08); padding: 1.5rem 1.75rem; margin-bottom: 0.75rem; border-left: 4px solid transparent; transition: border-left-color 0.2s ease; }
        .forms-section-card.active { border-left-color: #1E40AF; }
        .builder-input { border: none; border-bottom: 1px solid #dadce0; border-radius: 0; padding: 0.5rem 0; font-size: 0.875rem; width: 100%; background: transparent; transition: border-color 0.2s, border-width 0.2s; font-family: 'Roboto', sans-serif; }
        .builder-input:focus { outline: none; border-bottom-width: 2px; border-bottom-color: #1E40AF; }
        .builder-select { border: none; border-bottom: 1px solid #dadce0; border-radius: 0; padding: 0.5rem 0; font-size: 0.875rem; width: 100%; background: #fff; cursor: pointer; transition: border-color 0.2s, border-width 0.2s; }
        .builder-select:focus { outline: none; border-bottom-width: 2px; border-bottom-color: #1E40AF; }
        .dropdown-content { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; }
        .dropdown-content.open { max-height: 500px; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    </style>
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data" x-data="formBuilderApp()">

    @php
        $user = auth()->user();
        $isStaff = request()->routeIs('staff.*');
        $manualRoute = $isStaff ? route('staff.registration.manual') : route('registrar.registration.manual');
        $builderRoute = $isStaff ? route('staff.registration.builder') : route('registrar.registration.builder');
        $responsesRoute = $isStaff ? route('staff.registration.responses') : route('registrar.registration.responses');
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

    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden relative">
        {{-- Sub-nav (same as Manual Registration / Form Library) --}}
        <div class="bg-white border-b border-gray-200 px-6 py-2 flex items-center justify-between gap-4 shrink-0">
            <div class="flex items-center gap-1">
                <a href="{{ $manualRoute }}" class="px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 rounded-t font-heading">Manual Registration</a>
                @if(!empty($canBuilderTab))
                    <a href="{{ $builderRoute }}" class="px-4 py-2.5 text-sm font-semibold text-[#1E40AF] border-b-2 border-[#1E40AF] font-heading">Form Builder</a>
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

        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full max-w-3xl mx-auto relative flex gap-4">
                <div class="flex-1 flex flex-col space-y-4 pb-32 min-w-0">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-sm font-medium font-data" :class="hasUnsavedChanges ? 'text-orange-500' : 'text-gray-500'" x-text="hasUnsavedChanges ? 'Unsaved changes' : 'All changes saved'"></span>
                    <a href="{{ $builderRoute }}" class="text-sm text-gray-600 hover:text-gray-800 font-data inline-flex items-center gap-1 no-underline">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Back to Library
                    </a>
                </div>

                <div @click="setActive('header')" class="forms-section-card cursor-pointer" :class="{ 'active': activeBlockId === 'header' }">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 bg-[#EFF6FF] border border-[#1E40AF]/20 rounded-lg p-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1 uppercase font-heading">Incoming Year Level (Destination)</label>
                            <select x-model="incomingYearLevel" @change="hasUnsavedChanges = true" class="builder-select w-full font-data">
                                <option value="">Select year level</option>
                                @foreach($yearLevels as $yearLevel)
                                    <option value="{{ $yearLevel }}">{{ $yearLevel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1 uppercase font-heading">Incoming Semester (Destination)</label>
                            <select x-model="incomingSemester" @change="hasUnsavedChanges = true" class="builder-select w-full font-data">
                                <option value="">Select semester</option>
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester }}">{{ $semester }}</option>
                                @endforeach
                            </select>
                        </div>
                        <p class="md:col-span-2 text-xs text-gray-500 font-data">Set where approved students will be promoted after enrollment approval.</p>
                        <div class="md:col-span-2 flex items-start gap-3 pt-2 border-t border-[#1E40AF]/20">
                            <div class="flex items-center h-6 shrink-0" @click="lockCourseMajor = !lockCourseMajor; hasUnsavedChanges = true">
                                <input type="checkbox" x-model="lockCourseMajor" class="w-4 h-4 rounded border-gray-300 text-[#1E40AF] focus:ring-[#1E40AF] cursor-pointer">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 font-heading cursor-pointer" @click="lockCourseMajor = !lockCourseMajor; hasUnsavedChanges = true">Lock Course and Major/BLOCK for students</label>
                                <p class="text-xs text-gray-500 font-data mt-0.5">When checked, students cannot change Course or Major/BLOCK (pre-filled from profile). Uncheck to allow students to edit these fields.</p>
                            </div>
                        </div>
                    </div>
                    <input type="text" x-model="formTitle" @input="hasUnsavedChanges = true" class="builder-input text-xl font-heading font-normal text-gray-900 mb-2" placeholder="Form Title">
                    <input type="text" x-model="formDesc" @input="hasUnsavedChanges = true" placeholder="Form description" class="builder-input text-sm text-gray-600 font-data">
                </div>

                    <template x-for="(block, index) in blocks" :key="block.id">
                        <div @click="setActive(block.id)" class="forms-section-card cursor-pointer mt-4" :class="{ 'active': activeBlockId === block.id }">
                            
                            <template x-if="block.type === 'question'">
                                <div>
                                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-4 gap-4">
                                        <div class="flex-1 w-full">
                                            <input type="text" x-model="block.questionText" @input="hasUnsavedChanges = true" placeholder="Question" class="builder-input text-base text-gray-800 font-data">
                                        </div>
                                        <select x-model="block.questionType" @change="hasUnsavedChanges = true" class="builder-select w-full md:w-48 font-data">
                                            <option value="short">Short answer</option>
                                            <option value="paragraph">Paragraph</option>
                                            <option value="radio">Multiple choice</option>
                                            <option value="checkbox">Checkboxes</option>
                                            <option value="dropdown">Dropdown</option>
                                        </select>
                                    </div>

                                    <div class="space-y-3 mb-6">
                                        <template x-if="block.questionType === 'short'">
                                            <div class="border-b border-gray-300 border-dotted w-1/2 pb-1 text-sm text-gray-400">Short answer text</div>
                                        </template>
                                        <template x-if="block.questionType === 'paragraph'">
                                            <div class="border-b border-gray-300 border-dotted w-full pb-1 text-sm text-gray-400 mt-2">Long answer text</div>
                                        </template>

                                        <template x-if="['radio', 'checkbox', 'dropdown'].includes(block.questionType)">
                                            <div>
                                                <template x-for="(opt, optIndex) in block.options" :key="optIndex">
                                                    <div class="flex items-center group mb-2">
                                                        <template x-if="block.questionType === 'radio'"><div class="w-4 h-4 rounded-full border-2 border-gray-400 mr-3 shrink-0"></div></template>
                                                        <template x-if="block.questionType === 'checkbox'"><div class="w-4 h-4 rounded border-2 border-gray-400 mr-3 shrink-0"></div></template>
                                                        <template x-if="block.questionType === 'dropdown'"><div class="mr-3 text-gray-500 font-medium shrink-0" x-text="optIndex + 1 + '.'"></div></template>
                                                        
                                                        <input type="text" x-model="block.options[optIndex]" @input="hasUnsavedChanges = true" class="builder-input flex-1 text-gray-800 font-data" placeholder="Option text">
                                                        
                                                        <button @click.stop="removeOption(index, optIndex)" x-show="block.options.length > 1" type="button" class="ml-2 text-gray-400 hover:text-red-500 transition opacity-0 group-hover:opacity-100">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                        </button>
                                                    </div>
                                                </template>
                                                <div class="flex items-center mt-3 text-sm">
                                                    <button @click.stop="addOption(index)" type="button" class="text-gray-500 hover:border-b hover:border-gray-400 pb-0.5 ml-7">Add option</button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>

                                    <hr class="border-gray-200 mb-3">
                                    
                                    <div class="flex justify-end items-center space-x-4 text-gray-500">
                                        <button @click.stop="duplicateBlock(index)" type="button" class="hover:bg-gray-100 p-2 rounded-full transition" title="Duplicate">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                        </button>
                                        <button @click.stop="deleteBlock(index)" type="button" class="hover:bg-gray-100 p-2 rounded-full transition hover:text-red-500" title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                        <div class="w-px h-6 bg-gray-300"></div>
                                        <div class="flex items-center space-x-2 cursor-pointer" @click.stop="block.required = !block.required; hasUnsavedChanges = true">
                                            <span class="text-sm select-none">Required</span>
                                            <div class="w-8 h-4 rounded-full relative transition-colors duration-200" :class="block.required ? 'bg-[#1E40AF]' : 'bg-gray-300'">
                                                <div class="w-3 h-3 bg-white rounded-full absolute top-0.5 shadow-sm transition-transform duration-200" :class="block.required ? 'left-4' : 'left-0.5'"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template x-if="block.type === 'description'">
                                <div>
                                    <input type="text" x-model="block.title" @input="hasUnsavedChanges = true" class="builder-input text-xl font-medium text-gray-900 mb-2 font-heading" placeholder="Untitled Title">
                                    <input type="text" x-model="block.description" @input="hasUnsavedChanges = true" placeholder="Description (optional)" class="builder-input text-sm text-gray-600 mb-4 font-data">
                                    <div class="flex justify-end border-t border-gray-100 pt-3">
                                        <button @click.stop="deleteBlock(index)" type="button" class="text-gray-400 hover:text-red-500 p-1 rounded-full"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                    </div>
                                </div>
                            </template>

                            <template x-if="block.type === 'section'">
                                <div>
                                    <div class="bg-[#1E40AF] text-white text-xs font-bold px-3 py-1 rounded-t-md inline-block absolute top-0 left-0 rounded-tl-none rounded-br-md font-heading">Section</div>
                                    <div class="mt-4">
                                        <input type="text" x-model="block.title" @input="hasUnsavedChanges = true" class="builder-input text-2xl font-normal text-gray-900 mb-2 font-heading" placeholder="Untitled Section">
                                        <input type="text" x-model="block.description" @input="hasUnsavedChanges = true" placeholder="Description (optional)" class="builder-input text-sm text-gray-600 mb-4 font-data">
                                    </div>
                                    <div class="flex justify-end border-t border-gray-100 pt-3">
                                        <button @click.stop="deleteBlock(index)" type="button" class="text-gray-400 hover:text-red-500 p-1 rounded-full"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                    </div>
                                </div>
                            </template>
                            
                        </div>
                    </template>
                </div>

                <div class="w-14 shrink-0 relative hidden md:block">
                    <div class="sticky top-4 bg-white shadow-md border border-gray-200 rounded-lg py-3 flex flex-col items-center space-y-3 z-10">
                        <button @click="addBlock('question')" class="p-2 text-gray-600 hover:bg-gray-100 rounded-full transition" title="Add question">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </button>
                        <button @click="addBlock('description')" class="p-2 text-gray-600 hover:bg-gray-100 rounded-full transition" title="Add title and description">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M5 4v3h5.5v12h3V7H19V4H5z"/></svg>
                        </button>
                        <button @click="addBlock('section')" class="p-2 text-gray-600 hover:bg-gray-100 rounded-full transition" title="Add section">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path></svg>
                        </button>
                        <div class="w-8 h-px bg-gray-200 my-2"></div>
                        <button @click="saveForm()" class="p-2 text-[#1E40AF] hover:bg-[#1E40AF]/10 rounded-full transition" title="Save Draft">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                        </button>
                        <button @click="openDeployModal()" class="p-2 text-green-600 hover:bg-green-50 rounded-full transition" title="Deploy Form">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div x-show="showDeployModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center" style="display: none;" x-transition>
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden" @click.away="closeDeployModal()">
            <div class="px-6 py-4 bg-[#1E40AF] text-white flex justify-between items-center">
                <h3 class="font-medium text-lg flex items-center"><svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg> Deploy Form</h3>
                <button @click="closeDeployModal()" class="text-white hover:text-gray-200">&times;</button>
            </div>
            <div class="p-6 space-y-4">
                <template x-if="yearLevelOptions.length === 0 || semesterOptions.length === 0">
                    <div class="text-xs text-red-700 bg-red-50 border border-red-200 rounded p-3">
                        Add Year Levels and Semesters in Settings first before deploying forms.
                    </div>
                </template>
                <p class="text-sm text-gray-600 mb-4">Assign this form to current student cohort. Approved students will be promoted to the incoming target set on this form.</p>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1 uppercase">Current Year Level (Who Sees This Form)</label>
                    <select x-model="deployYear" class="w-full border border-gray-300 rounded p-2 text-sm focus:border-[#1E40AF] focus:outline-none">
                        <template x-for="year in yearLevelOptions" :key="year">
                            <option :value="year" x-text="year"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1 uppercase">Current Semester (Who Sees This Form)</label>
                    <select x-model="deploySem" class="w-full border border-gray-300 rounded p-2 text-sm focus:border-[#1E40AF] focus:outline-none">
                        <template x-for="semester in semesterOptions" :key="semester">
                            <option :value="semester" x-text="semester"></option>
                        </template>
                    </select>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 border-t border-gray-200">
                <button @click="closeDeployModal()" class="px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded transition">Cancel</button>
                <button @click="confirmDeploy()" class="px-4 py-2 text-sm font-medium bg-green-600 text-white hover:bg-green-700 rounded shadow transition">Confirm & Deploy</button>
            </div>
        </div>
    </div>

    <script>
        function formBuilderApp() {
            return {
                openDropdown: 'registrationMenu',
                globalEnrollment: {{ $globalEnrollmentActive ? 'true' : 'false' }},
                
                // Form Builder Core Data
                formTitle: {!! json_encode($form->title ?? 'Untitled Form') !!},
                formDesc: {!! json_encode($form->description ?? '') !!},
                blocks: {!! json_encode($form->questions ?? [['id' => 1, 'type' => 'question', 'questionText' => 'Untitled Question', 'questionType' => 'radio', 'options' => ['Option 1'], 'required' => false]]) !!},
                activeBlockId: null,
                hasUnsavedChanges: false,
                currentFormId: {{ $form->id ?? 'null' }},
                incomingYearLevel: {!! json_encode($form->incoming_year_level ?? '') !!},
                incomingSemester: {!! json_encode($form->incoming_semester ?? '') !!},
                lockCourseMajor: {{ isset($form->lock_course_major) ? ($form->lock_course_major ? 'true' : 'false') : 'true' }},
                
                // Deploy Modal Data
                showDeployModal: false,
                yearLevelOptions: @json($yearLevels ?? []),
                semesterOptions: @json($semesters ?? []),
                deployYear: '',
                deploySem: '',

                // Basic Nav functions
                toggleDropdown(menu) { this.openDropdown = this.openDropdown === menu ? '' : menu; },
                toggleGlobal() { 
                    this.globalEnrollment = !this.globalEnrollment;
                    fetch('{{ $isStaff ? '/staff/registration/toggle-global' : '/registrar/registration/toggle-global' }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ is_active: this.globalEnrollment })
                    }).then(res => res.json()).then(data => alert(data.message));
                },
                
                // Builder functions
                setActive(id) {
                    this.activeBlockId = id;
                },
                addBlock(type) {
                    const newId = Date.now();
                    let newBlock = { id: newId, type: type };
                    if (type === 'question') {
                        newBlock = { ...newBlock, questionText: '', questionType: 'radio', options: ['Option 1'], required: false };
                    } else if (type === 'description' || type === 'section') {
                        newBlock = { ...newBlock, title: '', description: '' };
                    }
                    this.blocks.push(newBlock);
                    this.setActive(newId);
                    this.hasUnsavedChanges = true;
                    setTimeout(() => { window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' }); }, 50);
                },
                duplicateBlock(index) {
                    const blockToCopy = JSON.parse(JSON.stringify(this.blocks[index]));
                    blockToCopy.id = Date.now(); 
                    this.blocks.splice(index + 1, 0, blockToCopy);
                    this.setActive(blockToCopy.id);
                    this.hasUnsavedChanges = true;
                },
                deleteBlock(index) {
                    this.blocks.splice(index, 1);
                    this.hasUnsavedChanges = true;
                },
                addOption(blockIndex) {
                    const currentLen = this.blocks[blockIndex].options.length;
                    this.blocks[blockIndex].options.push(`Option ${currentLen + 1}`);
                    this.hasUnsavedChanges = true;
                },
                removeOption(blockIndex, optionIndex) {
                    this.blocks[blockIndex].options.splice(optionIndex, 1);
                    this.hasUnsavedChanges = true;
                },
                
                // Actions
                saveForm() {
                    const regBase = '{{ $isStaff ? '/staff/registration' : '/registrar/registration' }}';
                    if (!this.incomingYearLevel || !this.incomingSemester) {
                        alert('Please set Incoming Year Level and Incoming Semester first.');
                        return;
                    }
                    fetch(regBase + '/save-form', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({
                            title: this.formTitle,
                            description: this.formDesc,
                            questions: this.blocks,
                            form_id: this.currentFormId,
                            incoming_year_level: this.incomingYearLevel,
                            incoming_semester: this.incomingSemester,
                            lock_course_major: this.lockCourseMajor
                        })
                    }).then(res => res.json()).then(data => {
                        if (data.success) {
                            this.hasUnsavedChanges = false;
                            this.currentFormId = data.form_id;
                            alert(data.message);
                        }
                    });
                },
                openDeployModal() {
                    if (!this.deployYear && this.yearLevelOptions.length > 0) {
                        this.deployYear = this.yearLevelOptions[0];
                    }
                    if (!this.deploySem && this.semesterOptions.length > 0) {
                        this.deploySem = this.semesterOptions[0];
                    }
                    this.showDeployModal = true;
                },
                closeDeployModal() { this.showDeployModal = false; },
                confirmDeploy() {
                    const regBase = '{{ $isStaff ? '/staff/registration' : '/registrar/registration' }}';
                    if (!this.currentFormId) {
                        alert('Please save the form first before deploying.');
                        return;
                    }
                    if (!this.incomingYearLevel || !this.incomingSemester) {
                        alert('Please set Incoming Year Level and Incoming Semester first.');
                        return;
                    }
                    if (this.yearLevelOptions.length === 0 || this.semesterOptions.length === 0) {
                        alert('Please add Year Levels and Semesters in Settings first.');
                        return;
                    }
                    fetch(regBase + '/deploy-form', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({ form_id: this.currentFormId, assigned_year: this.deployYear, assigned_semester: this.deploySem })
                    }).then(res => res.json()).then(data => {
                        if (data.success) {
                            this.hasUnsavedChanges = false;
                            this.closeDeployModal();
                            alert(data.message);
                        }
                    });
                }
            }
        }
    </script>
</body>
</html>