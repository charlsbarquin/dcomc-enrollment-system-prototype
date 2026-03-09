<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Enrollment Form - DCOMC</title>
    @include('layouts.partials.offline-assets')
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { 'dcomc-blue': '#1E40AF' },
                    fontFamily: { figtree: ['Figtree', 'sans-serif'], roboto: ['Roboto', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        .font-heading { font-family: 'Figtree', sans-serif; }
        .font-data { font-family: 'Roboto', sans-serif; }
        .enrollment-hero-card { background: #fff; border-top: 10px solid #1E40AF; border-radius: 0.75rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); padding: 1.5rem 1.75rem; margin-bottom: 1rem; }
        .enrollment-section-card { background: #fff; border-radius: 0.75rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); padding: 1.5rem 1.75rem; margin-bottom: 1rem; border-left: 4px solid transparent; transition: border-left-color 0.2s ease; }
        .enrollment-section-card.active { border-left-color: #1E40AF; }
        .enrollment-input { width: 100%; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.625rem 0.75rem; font-size: 0.875rem; background: #fff; font-family: 'Roboto', sans-serif; transition: border-color 0.2s, box-shadow 0.2s; }
        .enrollment-input:focus { outline: none; border-color: #1E40AF; box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.2); }
        /* Dropdown indicator for selects (COURSE, MAJOR/BLOCK, Preferred Block) */
        select.enrollment-input { appearance: none; -webkit-appearance: none; padding-right: 2.25rem; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%235b5b5b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M19 9l-7 7-7-7'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 0.5rem center; background-size: 1.25rem; }
        select.enrollment-input:focus { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%231E40AF' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M19 9l-7 7-7-7'/%3E%3C/svg%3E"); }
        .enrollment-upload-slot { border: 2px dashed #94a3b8; border-radius: 0.75rem; padding: 1.5rem; text-align: center; background: #f8fafc; transition: border-color 0.2s, background 0.2s; }
        .enrollment-upload-slot:hover, .enrollment-upload-slot:focus-within { border-color: #1E40AF; background: #eff6ff; }
        .enrollment-upload-slot input[type="file"] { font-family: 'Roboto', sans-serif; }
        .enrollment-upload-icon { color: #1E40AF; }
        /* Ensure radio and checkbox circles/boxes are always visible (Tailwind preflight can hide them) */
        input[type="radio"] { -webkit-appearance: radio; appearance: radio; width: 1.125rem; height: 1.125rem; flex-shrink: 0; accent-color: #1E40AF; }
        input[type="checkbox"] { -webkit-appearance: checkbox; appearance: checkbox; width: 1.125rem; height: 1.125rem; flex-shrink: 0; accent-color: #1E40AF; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen font-data antialiased" style="background-color: #F8FAFC;" x-data="{ navOpen: false }">

    {{-- Same header as Student Dashboard --}}
    <header class="sticky top-0 z-50 bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between min-h-[4.5rem] py-3">
                <a href="{{ route('student.dashboard') }}" onclick="return confirm('Are you sure you want to leave? Your enrollment form progress may not be saved.');" class="flex items-center gap-4 shrink-0 no-underline hover:no-underline pl-1">
                    <img src="{{ asset('images/logo.png') }}" alt="DCOMC" class="h-10 w-auto object-contain flex-shrink-0 pl-4" onerror="this.style.display='none'">
                    <span class="font-heading font-semibold text-[#1E40AF] no-underline">DCOMC Student Portal</span>
                </a>
                <nav class="hidden md:flex items-center gap-3 flex-wrap justify-end">
                    <a href="{{ route('student.dashboard') }}" onclick="return confirm('Are you sure you want to leave? Your enrollment form progress may not be saved.');" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg border-2 border-[#1E40AF] text-[#1E40AF] bg-transparent hover:bg-[#1E40AF]/15 no-underline transition-colors">← Back to Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2.5 text-sm font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors">Log Out</button>
                    </form>
                </nav>
                <button type="button" class="md:hidden flex items-center justify-center w-10 h-10 rounded-lg text-gray-600 hover:bg-gray-100"
                        @click="navOpen = !navOpen" :aria-expanded="navOpen" aria-label="Toggle menu">
                    <svg x-show="!navOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="navOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L6 6M18 6v12M18 6L6 18"/></svg>
                </button>
            </div>
        </div>
        <div x-show="navOpen" x-cloak x-transition class="md:hidden border-t border-gray-200 bg-gray-50">
            <div class="max-w-6xl mx-auto px-4 py-3 space-y-1">
                <a href="{{ route('student.dashboard') }}" onclick="return confirm('Are you sure you want to leave? Your enrollment form progress may not be saved.');" class="block px-4 py-2.5 text-sm font-medium text-[#1E40AF] no-underline">← Back to Dashboard</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="block w-full text-left px-4 py-2.5 text-sm font-medium text-red-600">Log Out</button></form>
            </div>
        </div>
    </header>

    <div class="flex-1 p-6 md:p-8 overflow-y-auto bg-gray-100">
        <div class="max-w-4xl mx-auto">
            @php
                $existingAnswers = $existingApplication?->answers ?? [];
            @endphp
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6 font-data">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('student.submit-enrollment') }}" id="enrollmentForm">
                @csrf
                <input type="hidden" name="form_id" value="{{ $form->id }}">
                @if(isset($existingApplication) && $existingApplication)
                    <input type="hidden" name="response_id" value="{{ $existingApplication->id }}">
                @endif
                
                {{-- Hero: 10px DCOMC Blue top border, title Official Enrollment Form --}}
                <div class="enrollment-hero-card" tabindex="-1">
                    <h1 class="font-heading text-2xl font-bold text-gray-900 mb-2">Official Enrollment Form</h1>
                    @if($form->title && $form->title !== 'Official Enrollment Form')
                        <p class="font-data text-gray-600 mb-2">{{ $form->title }}</p>
                    @endif
                    @if($form->description)
                        <p class="font-data text-gray-600 text-sm">{{ $form->description }}</p>
                    @endif
                    @if(isset($existingApplication) && $existingApplication)
                        <p class="font-data text-sm text-orange-700 bg-orange-50 border border-orange-200 rounded-lg px-3 py-2 mt-3">Your previous submission was marked as needing correction. Update the required fields and resubmit.</p>
                    @endif
                    <p class="font-data text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mt-3">Your profile information has been pre-filled. Fields marked with 🔒 are locked by the registrar.</p>
                </div>

                <div class="enrollment-section-card" tabindex="-1" @focusin="$el.classList.add('active')" @focusout="$el.classList.remove('active')">
                    <label class="font-heading block text-sm font-bold text-gray-900 mb-2">Preferred Block (optional)</label>
                    <select name="preferred_block_id" class="enrollment-input">
                        <option value="">Auto-assign me to available block</option>
                        @foreach(($availableBlocks ?? collect()) as $block)
                            <option value="{{ $block->id }}" {{ (string) ($existingApplication?->preferred_block_id ?? '') === (string) $block->id ? 'selected' : '' }}>
                                {{ $block->code ?? $block->name }} - {{ strtoupper($block->shift ?? 'day') }} ({{ $block->current_size ?? 0 }}/{{ $block->capacity ?? $block->max_students ?? 50 }})
                            </option>
                        @endforeach
                    </select>
                    <p class="font-data text-xs text-gray-500 mt-2">If selected block is already full at approval time, the system automatically assigns you to another available block.</p>
                </div>

                @if($form->questions)
                    @foreach($form->questions as $index => $question)
                        <div class="enrollment-section-card" tabindex="-1" @focusin="$el.classList.add('active')" @focusout="$el.classList.remove('active')">
                            @if($question['type'] === 'question')
                                @php
                                    $questionText = strtolower($question['questionText']);
                                    $lockCourseMajor = isset($form->lock_course_major) ? (bool) $form->lock_course_major : false;
                                    $isCourseOrMajor = (str_contains($questionText, 'course') && !str_contains($questionText, 'major'))
                                        || str_contains($questionText, 'degree') || str_contains($questionText, 'program')
                                        || str_contains($questionText, 'major');
                                    $isLocked = str_contains($questionText, 'school id') ||
                                                str_contains($questionText, 'student number') ||
                                                ($lockCourseMajor && $isCourseOrMajor) ||
                                                str_contains($questionText, 'year level') ||
                                                str_contains($questionText, 'student status');
                                    $isFileType = ($question['questionType'] ?? '') === 'file';
                                @endphp
                                <label class="font-heading block text-sm font-bold text-gray-900 mb-2">
                                    {{ $question['questionText'] }}
                                    @if($question['required'] ?? false)
                                        <span class="text-red-500">*</span>
                                    @endif
                                    @if($isLocked)
                                        <span class="text-gray-500 text-sm font-normal">🔒 Locked</span>
                                    @endif
                                </label>
                                @if($question['description'] ?? false)
                                    <p class="font-data text-sm text-gray-500 mb-2">{{ $question['description'] }}</p>
                                @endif

                                @if($question['questionType'] === 'short')
                                    <input type="text" name="answers[{{ $index }}]" id="field_{{ $index }}" value="{{ old('answers.'.$index, $existingAnswers[$index] ?? '') }}" class="enrollment-input" {{ ($question['required'] ?? false) ? 'required' : '' }} {{ $isLocked ? 'readonly' : '' }} style="{{ $isLocked ? 'background-color: #f3f4f6; cursor: not-allowed;' : '' }}">
                                @elseif($question['questionType'] === 'paragraph')
                                    <textarea name="answers[{{ $index }}]" id="field_{{ $index }}" rows="4" class="enrollment-input" {{ ($question['required'] ?? false) ? 'required' : '' }} {{ $isLocked ? 'readonly' : '' }} style="{{ $isLocked ? 'background-color: #f3f4f6; cursor: not-allowed;' : '' }}">{{ old('answers.'.$index, $existingAnswers[$index] ?? '') }}</textarea>
                                @elseif($question['questionType'] === 'radio')
                                    <div class="space-y-2">
                                        @foreach($question['options'] as $option)
                                            <label class="flex items-center cursor-pointer font-data">
                                                <input type="radio" name="answers[{{ $index }}]" value="{{ $option }}" class="mr-3" style="accent-color: #1E40AF;" {{ ($question['required'] ?? false) ? 'required' : '' }} {{ $isLocked ? 'disabled' : '' }} {{ (string) old('answers.'.$index, $existingAnswers[$index] ?? '') === (string) $option ? 'checked' : '' }}>
                                                <span class="text-gray-700">{{ $option }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @elseif($question['questionType'] === 'checkbox')
                                    <div class="space-y-2">
                                        @foreach($question['options'] as $optIndex => $option)
                                            <label class="flex items-center cursor-pointer font-data">
                                                <input type="checkbox" name="answers[{{ $index }}][]" value="{{ $option }}" class="mr-3" style="accent-color: #1E40AF;" {{ $isLocked ? 'disabled' : '' }} {{ in_array($option, old('answers.'.$index, is_array($existingAnswers[$index] ?? null) ? $existingAnswers[$index] : []), true) ? 'checked' : '' }}>
                                                <span class="text-gray-700">{{ $option }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @elseif($question['questionType'] === 'dropdown')
                                    @php
                                        $dropdownValue = old('answers.'.$index, $existingAnswers[$index] ?? '');
                                        if ($isLocked && $dropdownValue === '' && str_contains($questionText, 'course') && !str_contains($questionText, 'major')) {
                                            $dropdownValue = Auth::user()->course ?? '';
                                        }
                                        if ($isLocked && $dropdownValue === '' && str_contains($questionText, 'major')) {
                                            $dropdownValue = Auth::user()->major ?? '';
                                        }
                                    @endphp
                                    <select name="answers[{{ $index }}]" id="field_{{ $index }}" class="enrollment-input" {{ (!$isLocked && ($question['required'] ?? false)) ? 'required' : '' }} {{ $isLocked ? 'disabled' : '' }} style="{{ $isLocked ? 'background-color: #f3f4f6; cursor: not-allowed;' : '' }}" @if(str_contains(strtolower($question['questionText']), 'course')) onchange="updateMajorOptions(this.value)" @endif>
                                        <option value="">Choose</option>
                                        @foreach($question['options'] as $option)
                                            <option value="{{ $option }}" {{ (string) $dropdownValue === (string) $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    @if($isLocked)
                                        <input type="hidden" name="answers[{{ $index }}]" id="field_{{ $index }}_hidden" value="{{ $dropdownValue }}" {{ ($question['required'] ?? false) ? 'required' : '' }}>
                                    @endif
                                @elseif($isFileType)
                                    <div class="enrollment-upload-slot">
                                        <svg class="enrollment-upload-icon w-10 h-10 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                        <input type="file" name="answers[{{ $index }}]" id="field_{{ $index }}" class="block w-full text-sm font-data text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#1E40AF] file:text-white hover:file:bg-[#1D3A8A]" {{ ($question['required'] ?? false) ? 'required' : '' }}>
                                    </div>
                                @endif
                            @elseif($question['type'] === 'section')
                                <div class="border-t-4 border-[#1E40AF] pt-4">
                                    <h4 class="font-heading text-xl font-bold text-gray-900 mb-2">{{ $question['title'] ?? 'Section' }}</h4>
                                    @if($question['description'] ?? false)
                                        <p class="font-data text-gray-600">{{ $question['description'] }}</p>
                                    @endif
                                </div>
                            @elseif($question['type'] === 'description')
                                <h4 class="font-heading text-lg font-bold text-gray-900 mb-2">{{ $question['title'] ?? '' }}</h4>
                                @if($question['description'] ?? false)
                                    <p class="font-data text-gray-600">{{ $question['description'] }}</p>
                                @endif
                            @endif
                        </div>
                    @endforeach
                @endif

                <div class="flex flex-wrap justify-between items-center gap-4 mt-8 pt-6">
                    <p class="font-data text-sm text-gray-500">* Required fields</p>
                    <button type="submit" class="font-heading w-full sm:w-auto min-w-[200px] px-8 py-3.5 text-base font-bold rounded-xl text-white no-underline transition-colors focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:ring-offset-2" style="background-color: #1E40AF;">
                        Submit Enrollment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const studentData = @json(Auth::user());
        const autosaveKey = 'enrollment_form_draft_{{ $form->id }}_user_{{ Auth::id() }}';
        const enrollmentQuestions = @json($form->questions);
        window.majorsByCourse = @json($majorsByProgram ?? []);
        window.courseFieldIndex = null;
        window.majorFieldIndex = null;

        function normalizeProgram(value) {
            const raw = String(value || '').trim();
            const normalized = raw.toLowerCase();

            const aliases = {
                'beed': 'Bachelor of Elementary Education',
                'bachelor of elementary education': 'Bachelor of Elementary Education',
                'bsed': 'Bachelor of Secondary Education',
                'bachelor of secondary education': 'Bachelor of Secondary Education',
                'bcaed': 'Bachelor of Culture and Arts Education',
                'bachelor of culture and arts education': 'Bachelor of Culture and Arts Education',
                'bped': 'Bachelor of Physical Education',
                'bachelor of physical education': 'Bachelor of Physical Education',
                'btvted': 'Bachelor of Technical-Vocational Teacher Education',
                'bachelor of technical-vocational teacher education': 'Bachelor of Technical-Vocational Teacher Education',
                'bsentrep': 'Bachelor of Science in Entrepreneurship',
                'bs entrepreneurship': 'Bachelor of Science in Entrepreneurship',
                'bachelor of science in entrepreneurship': 'Bachelor of Science in Entrepreneurship',
                'teacher certificate program': 'Teacher Certificate Program',
            };

            return aliases[normalized] || raw;
        }

        function locateProgramAndMajorFields() {
            if (window.courseFieldIndex !== null && window.majorFieldIndex !== null) return;

            enrollmentQuestions.forEach((question, index) => {
                if (!question || question.type !== 'question') return;
                const text = String(question.questionText || '').toLowerCase();
                if (window.courseFieldIndex === null && (text.includes('course') || text.includes('degree') || text.includes('program')) && !text.includes('major')) {
                    window.courseFieldIndex = index;
                }
                if (window.majorFieldIndex === null && text.includes('major')) {
                    window.majorFieldIndex = index;
                }
            });
        }

        function updateMajorOptions(course) {
            locateProgramAndMajorFields();
            if (!window.majorsByCourse || window.majorFieldIndex === null) return;
            
            const majorField = document.getElementById('field_' + window.majorFieldIndex);
            if (!majorField) return;
            
            majorField.innerHTML = '<option value="">Choose</option>';
            
            const normalizedProgram = normalizeProgram(course);
            const majors = window.majorsByCourse[normalizedProgram] || [];
            majors.forEach(major => {
                const option = document.createElement('option');
                option.value = major;
                option.textContent = major;
                majorField.appendChild(option);
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            autoFillForm();
            restoreDraft();
            bindAutoSave();
        });

        function autoFillForm() {
            locateProgramAndMajorFields();

            enrollmentQuestions.forEach((question, index) => {
                if (question.type !== 'question') return;
                
                const questionText = question.questionText.toLowerCase();
                const field = document.getElementById('field_' + index);
                const radios = document.querySelectorAll(`input[name="answers[${index}]"]`);
                
                let value = null;
                
                if (questionText.includes('student number') || questionText.includes('school id')) {
                    value = studentData.school_id || '';
                } else if (questionText.includes('last name') || questionText.includes('surname')) {
                    value = studentData.last_name || '';
                } else if (questionText.includes('first name')) {
                    value = studentData.first_name || '';
                } else if (questionText.includes('middle name')) {
                    value = studentData.middle_name || '';
                } else if (questionText.includes('email')) {
                    value = studentData.email || '';
                } else if (questionText.includes('contact number') || questionText.includes('phone') || questionText.includes('mobile')) {
                    value = studentData.phone || '';
                } else if (questionText.includes('citizenship')) {
                    value = studentData.citizenship || '';
                } else if (questionText.includes('place of birth')) {
                    value = studentData.place_of_birth || '';
                } else if (questionText.includes('date of birth') || questionText.includes('birthday') || questionText.includes('birthdate')) {
                    value = studentData.date_of_birth || '';
                } else if (questionText.includes('gender') || questionText.includes('sex')) {
                    value = studentData.gender || '';
                } else if (questionText.includes('civil status') || questionText.includes('marital status')) {
                    value = studentData.civil_status || '';
                } else if (questionText.includes('purok') || questionText.includes('zone')) {
                    value = studentData.purok_zone || '';
                } else if (questionText.includes('barangay')) {
                    value = studentData.barangay || '';
                } else if (questionText.includes('municipality') || questionText.includes('city')) {
                    value = studentData.municipality || '';
                } else if (questionText.includes('province')) {
                    value = studentData.province || '';
                } else if (questionText.includes('zip') || questionText.includes('postal')) {
                    value = studentData.zip_code || '';
                } else if ((questionText.includes('course') || questionText.includes('degree') || questionText.includes('program')) && !questionText.includes('major')) {
                    value = studentData.course || '';
                    value = normalizeProgram(value);
                } else if (questionText.includes('major')) {
                    value = studentData.major || '';
                } else if (questionText.includes('year level')) {
                    value = studentData.year_level || '';
                } else if (questionText.includes('semester')) {
                    value = studentData.semester || '';
                } else if (questionText.includes('student status') || questionText.includes('student type')) {
                    value = studentData.student_type || studentData.student_status || '';
                } else if (questionText.includes('father') && questionText.includes('name')) {
                    value = studentData.father_name || '';
                } else if (questionText.includes('father') && questionText.includes('occupation')) {
                    value = studentData.father_occupation || '';
                } else if (questionText.includes('mother') && questionText.includes('name')) {
                    value = studentData.mother_name || '';
                } else if (questionText.includes('mother') && questionText.includes('occupation')) {
                    value = studentData.mother_occupation || '';
                } else if (questionText.includes('household') && questionText.includes('monthly income')) {
                    value = studentData.monthly_income || '';
                } else if (questionText.includes('number of family members')) {
                    value = studentData.num_family_members || '';
                } else if (questionText.includes('dswd household')) {
                    value = studentData.dswd_household_no || '';
                }
                
                if (value) {
                    if (question.questionType === 'dropdown') {
                        if (field) {
                            field.value = value;
                            const hiddenField = document.getElementById('field_' + index + '_hidden');
                            if (hiddenField) hiddenField.value = value;
                            // If this dropdown is the course field, update majors immediately
                            const qText = (question.questionText || '').toLowerCase();
                            if (qText.includes('course') && !qText.includes('major')) {
                                updateMajorOptions(value);
                                if (studentData.major && window.majorFieldIndex !== null) {
                                    const majorField = document.getElementById('field_' + window.majorFieldIndex);
                                    if (majorField) {
                                        majorField.value = studentData.major;
                                    }
                                }
                            }
                        }
                    } else if (question.questionType === 'radio') {
                        radios.forEach(radio => {
                            if (radio.value.toLowerCase() === value.toLowerCase()) {
                                radio.checked = true;
                            }
                        });
                    } else if (field) {
                        field.value = value;
                    }
                }
            });
        }

        function bindAutoSave() {
            const form = document.getElementById('enrollmentForm');
            if (!form) return;

            form.addEventListener('input', saveDraft);
            form.addEventListener('change', saveDraft);
            form.addEventListener('submit', function() {
                localStorage.removeItem(autosaveKey);
            });
        }

        function saveDraft() {
            const form = document.getElementById('enrollmentForm');
            if (!form) return;

            const data = {};
            const fields = form.querySelectorAll('input[name^="answers["], textarea[name^="answers["], select[name^="answers["]');
            fields.forEach(field => {
                if (field.type === 'radio') {
                    if (field.checked) data[field.name] = field.value;
                    return;
                }
                if (field.type === 'checkbox') {
                    if (!Array.isArray(data[field.name])) data[field.name] = [];
                    if (field.checked) data[field.name].push(field.value);
                    return;
                }
                data[field.name] = field.value;
            });

            localStorage.setItem(autosaveKey, JSON.stringify(data));
        }

        function restoreDraft() {
            const raw = localStorage.getItem(autosaveKey);
            if (!raw) return;

            let data = {};
            try {
                data = JSON.parse(raw) || {};
            } catch (e) {
                return;
            }

            Object.entries(data).forEach(([name, value]) => {
                const fields = document.querySelectorAll(`[name="${name.replace(/"/g, '\\"')}"]`);
                if (!fields.length) return;

                const first = fields[0];
                if (first.type === 'radio') {
                    fields.forEach(radio => {
                        if (radio.value === value) radio.checked = true;
                    });
                    return;
                }
                if (first.type === 'checkbox') {
                    fields.forEach(box => {
                        box.checked = Array.isArray(value) ? value.includes(box.value) : false;
                    });
                    return;
                }
                first.value = value ?? '';
            });
        }
    </script>

</body>
</html>
