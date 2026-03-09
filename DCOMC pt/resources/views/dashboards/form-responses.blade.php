<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Form Responses - DCOMC</title>
    @include('layouts.partials.offline-assets')
    <style>
        .font-heading { font-family: 'Figtree', sans-serif; }
        .font-data { font-family: 'Roboto', sans-serif; }
        .forms-canvas { background: #f3f4f6; }
        .hero-gradient { background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 50%, #60A5FA 100%); }
        .dropdown-content { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; }
        .dropdown-content.open { max-height: 500px; }
        .input-dcomc-focus:focus { outline: none; border-color: #1E40AF; box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.2); }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    </style>
    <script>
        function responsesData() {
            return {
                openDropdown: 'registrationMenu',
                globalEnrollment: {{ $globalEnrollmentActive ? 'true' : 'false' }},
                showResponseModal: false,
                selectedResponse: null,
                toggleDropdown(menu) {
                    this.openDropdown = this.openDropdown === menu ? '' : menu;
                },
                openResponseModal(response) {
                    this.selectedResponse = response;
                    this.showResponseModal = true;
                },
                getQuestionLabel(response, key) {
                    const index = Number(key);
                    if (!response || !response.enrollment_form || !Array.isArray(response.enrollment_form.questions) || Number.isNaN(index)) {
                        return `Question ${index + 1}`;
                    }
                    const question = response.enrollment_form.questions[index];
                    if (!question || question.type !== 'question') {
                        return `Question ${index + 1}`;
                    }
                    return question.questionText || `Question ${index + 1}`;
                },
                formatAnswer(answer) {
                    if (Array.isArray(answer)) return answer.join(', ');
                    if (answer === null || answer === undefined || answer === '') return 'No answer';
                    return String(answer);
                },
                toggleGlobal() {
                    this.globalEnrollment = !this.globalEnrollment;
                    fetch('{{ auth()->user()?->effectiveRole() === 'staff' ? '/staff/registration/toggle-global' : '/registrar/registration/toggle-global' }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ is_active: this.globalEnrollment })
                    }).then(res => res.json()).then(data => alert(data.message));
                }
            };
        }
    </script>
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data" x-data="responsesData()">

    @php
        $user = auth()->user();
        $isStaff = request()->routeIs('staff.*');
        $manualRoute = $isStaff ? route('staff.registration.manual') : route('registrar.registration.manual');
        $builderRoute = $isStaff ? route('staff.registration.builder') : route('registrar.registration.builder');
        $responsesRoute = $isStaff ? route('staff.registration.responses') : route('registrar.registration.responses');
        $registrationBase = $isStaff ? '/staff/registration' : '/registrar/registration';
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
        $filteredResponses = $filteredResponses ?? collect();
        $showTableResults = $showTableResults ?? false;
        $statusApproved = $showTableResults ? $filteredResponses->where('approval_status', 'approved')->count() : 0;
        $statusPending = $showTableResults ? $filteredResponses->whereIn('approval_status', [null, 'pending'])->count() : 0;
        $statusRejected = $showTableResults ? $filteredResponses->where('approval_status', 'rejected')->count() : 0;
        $statusTotal = $statusApproved + $statusPending + $statusRejected;
        $programBreakdown = $showTableResults && $filteredResponses->isNotEmpty()
            ? $filteredResponses->groupBy(fn ($r) => $r->user?->course ?? 'N/A')->map->count()->sortDesc()->take(3) : collect();
        $courseOptions = $showTableResults ? $filteredResponses->pluck('user.course')->unique()->filter()->sort()->values() : collect();
    @endphp
    @include('dashboards.partials.role-sidebar')

    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
        {{-- Sub-nav (match Form Builder / Manual Registration) --}}
        <div class="bg-white border-b border-gray-200 px-6 py-2 flex items-center justify-between gap-4 shrink-0 w-full">
            <div class="flex items-center gap-1">
                <a href="{{ $manualRoute }}" class="px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 rounded-t font-heading">Manual Registration</a>
                @if(!empty($canBuilderTab))
                    <a href="{{ $builderRoute }}" class="px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 rounded-t font-heading">Form Builder</a>
                @endif
                @if(!empty($canResponsesTab))
                    <a href="{{ $responsesRoute }}" class="px-4 py-2.5 text-sm font-semibold text-[#1E40AF] border-b-2 border-[#1E40AF] font-heading flex items-center gap-2">Responses <span class="bg-gray-200 text-gray-800 text-xs font-bold px-2 py-0.5 rounded-full">{{ $totalResponsesCount }}</span></a>
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
                {{-- Hero banner: full width, DCOMC blue --}}
                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">Form Responses</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">Review and manage student submissions from the online enrollment portal.</p>
                        </div>
                        <div class="shrink-0">
                            <span class="inline-flex items-center px-4 py-2 rounded-lg bg-white/20 text-white text-sm font-bold font-data">Total: {{ $totalResponsesCount }}</span>
                        </div>
                    </div>
                </section>

                {{-- Filter & Search bar: show always so user can apply filters to see table --}}
                <div class="bg-white shadow-2xl rounded-xl border border-gray-200 px-5 py-5 mb-6">
                    <form method="GET" action="{{ $responsesRoute }}" class="flex flex-col sm:flex-row sm:items-end gap-4">
                        <input type="hidden" name="student_number" value="{{ $filters['student_number'] ?? '' }}">
                        <input type="hidden" name="school_year" value="{{ $filters['school_year'] ?? '' }}">
                        <input type="hidden" name="last_name" value="{{ $filters['last_name'] ?? '' }}">
                        <input type="hidden" name="year" value="{{ $filters['year'] ?? '' }}">
                        <input type="hidden" name="semester" value="{{ $filters['semester'] ?? '' }}">
                        <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
                        <input type="hidden" name="folder" value="{{ $filters['folder'] ?? '' }}">
                        <div class="flex-1 min-w-0 max-w-md">
                            <label for="responses-search" class="sr-only">Search</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </span>
                                <input type="text" id="responses-search" name="first_name" value="{{ $filters['first_name'] ?? '' }}" placeholder="Search by name or ID..." class="w-full font-data text-sm border border-gray-300 rounded-lg pl-10 pr-4 py-2.5 bg-white input-dcomc-focus transition">
                            </div>
                        </div>
                        <div class="min-w-[180px]">
                            <label for="responses-course" class="block text-xs font-bold text-gray-600 mb-1 uppercase font-heading">Filter by Course</label>
                            <select id="responses-course" name="program" class="w-full font-data text-sm border border-gray-300 rounded-lg px-4 py-2.5 bg-white input-dcomc-focus transition cursor-pointer">
                                <option value="">All</option>
                                @foreach($courseOptions as $course)
                                    <option value="{{ $course }}" {{ ($filters['program'] ?? '') === $course ? 'selected' : '' }}>{{ $course }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ $responsesRoute }}" class="inline-flex items-center px-4 py-2.5 rounded-lg border-2 border-gray-300 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50 transition font-data">Reset</a>
                            <button type="submit" class="inline-flex items-center px-4 py-2.5 rounded-lg bg-[#1E40AF] hover:bg-[#1D3A8A] text-white text-sm font-semibold transition font-data focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:ring-offset-2">Apply</button>
                        </div>
                    </form>
                </div>

                @if(!$showTableResults)
                    {{-- Response Folders (no filters applied) --}}
                    <div class="w-full bg-white shadow-2xl rounded-xl overflow-hidden border border-gray-200">
                        <div class="bg-[#1E40AF] px-6 py-4">
                            <h2 class="font-heading text-lg font-bold text-white">Response Folders</h2>
                            <p class="text-white/80 text-sm font-data mt-0.5">Google Drive style folders grouped by active forms</p>
                        </div>
                        @if($activeFormFolders->isEmpty())
                            <div class="p-12 text-center">
                                <svg class="w-20 h-20 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"></path></svg>
                                <h3 class="font-heading text-xl font-medium text-gray-600 mb-2">No Active Form Folders</h3>
                                <p class="text-gray-500 font-data">Deploy or activate forms first to create response folders.</p>
                            </div>
                        @else
                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                                    @foreach($activeFormFolders as $folder)
                                        <a href="{{ $isStaff ? route('staff.registration.responses.folder', $folder->id) : route('registrar.registration.responses.folder', $folder->id) }}" class="block text-left border border-gray-200 rounded-xl p-4 transition hover:shadow-lg bg-white hover:border-[#1E40AF] no-underline text-inherit">
                                            <div class="flex items-center justify-between mb-2">
                                                <svg class="w-10 h-10 text-[#1E40AF]/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                                                <span class="text-xs font-bold px-2 py-1 rounded bg-[#1E40AF]/10 text-[#1E40AF]">{{ $folder->form_responses_count }} responses</span>
                                            </div>
                                            <p class="font-semibold text-gray-900 truncate font-heading">{{ $folder->title }}</p>
                                            <p class="text-xs text-gray-500 mt-1 font-data">{{ ($folder->assigned_year && $folder->assigned_semester) ? $folder->assigned_year . ' - ' . $folder->assigned_semester : 'No assignment' }}</p>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    {{-- Summary cards (Status Overview + Program Breakdown) --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                        <div class="bg-white shadow-2xl rounded-xl border border-gray-200 p-5">
                            <h3 class="font-heading text-sm font-bold text-gray-700 uppercase tracking-wide mb-3">Status Overview</h3>
                            @if($statusTotal > 0)
                                <div class="space-y-3">
                                    <div>
                                        <div class="flex justify-between text-xs font-data mb-1"><span class="text-gray-600">Approved</span><span class="font-semibold text-green-700">{{ $statusApproved }}</span></div>
                                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden"><div class="h-full bg-green-600 rounded-full" style="width: {{ $statusTotal ? round($statusApproved / $statusTotal * 100) : 0 }}%"></div></div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between text-xs font-data mb-1"><span class="text-gray-600">Pending</span><span class="font-semibold text-[#F97316]">{{ $statusPending }}</span></div>
                                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden"><div class="h-full bg-[#F97316] rounded-full" style="width: {{ $statusTotal ? round($statusPending / $statusTotal * 100) : 0 }}%"></div></div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between text-xs font-data mb-1"><span class="text-gray-600">Rejected</span><span class="font-semibold text-red-700">{{ $statusRejected }}</span></div>
                                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden"><div class="h-full bg-red-600 rounded-full" style="width: {{ $statusTotal ? round($statusRejected / $statusTotal * 100) : 0 }}%"></div></div>
                                    </div>
                                </div>
                            @else
                                <p class="text-sm text-gray-500 font-data">No responses in this set.</p>
                            @endif
                        </div>
                        <div class="bg-white shadow-2xl rounded-xl border border-gray-200 p-5">
                            <h3 class="font-heading text-sm font-bold text-gray-700 uppercase tracking-wide mb-3">Program Breakdown</h3>
                            @if($programBreakdown->isNotEmpty())
                                <ul class="space-y-2 font-data text-sm">
                                    @foreach($programBreakdown as $course => $count)
                                        <li class="flex justify-between"><span class="text-gray-700 truncate pr-2">{{ $course }}</span><span class="font-semibold text-[#1E40AF] shrink-0">{{ $count }}</span></li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-sm text-gray-500 font-data">No program data in this set.</p>
                            @endif
                        </div>
                    </div>

                    {{-- Detailed Response Table (DCOMC standard: blue header, pills, Eye/Check/X) --}}
                    <div class="w-full bg-white shadow-2xl rounded-xl overflow-hidden border border-gray-200">
                        <div class="bg-[#1E40AF] px-6 py-4 flex flex-wrap items-center justify-between gap-2">
                            <h2 class="font-heading text-lg font-bold text-white">Form Responses</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full font-data text-sm" role="grid">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th scope="col" class="text-left py-3 px-4 font-heading font-semibold text-gray-700">Student Name</th>
                                        <th scope="col" class="text-left py-3 px-4 font-heading font-semibold text-gray-700">Course</th>
                                        <th scope="col" class="text-left py-3 px-4 font-heading font-semibold text-gray-700">Year Level</th>
                                        <th scope="col" class="text-left py-3 px-4 font-heading font-semibold text-gray-700">Submission Date</th>
                                        <th scope="col" class="text-left py-3 px-4 font-heading font-semibold text-gray-700">Status</th>
                                        <th scope="col" class="text-right py-3 px-4 font-heading font-semibold text-gray-700">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    @forelse($filteredResponses as $response)
                                        @php
                                            $status = $response->approval_status ?? 'pending';
                                            $badge = 'Pending';
                                            $badgeClass = 'bg-[#F97316] text-white';
                                            if ($status === 'approved') { $badge = 'Approved'; $badgeClass = 'bg-green-600 text-white'; }
                                            elseif ($status === 'rejected') { $badge = 'Rejected'; $badgeClass = 'bg-red-600 text-white'; }
                                            $responsePayload = [
                                                'id' => $response->id,
                                                'approval_status' => $response->approval_status,
                                                'created_at' => optional($response->created_at)?->toIso8601String(),
                                                'answers' => $response->answers,
                                                'user' => [
                                                    'name' => $response->user?->name,
                                                    'email' => $response->user?->email,
                                                ],
                                                'enrollment_form' => [
                                                    'title' => $response->enrollmentForm?->title,
                                                    'questions' => $response->enrollmentForm?->questions,
                                                ],
                                            ];
                                        @endphp
                                        <tr class="hover:bg-[#1E40AF]/5 transition-colors duration-200">
                                            <td class="py-3 px-4 text-gray-900 font-data">
                                                {{ trim(($response->user?->last_name ?? '') . ', ' . ($response->user?->first_name ?? '') . ' ' . ($response->user?->middle_name ?? '')) ?: ($response->user?->name ?? 'N/A') }}
                                                @if($response->user?->email)
                                                    <p class="text-xs text-gray-500">{{ $response->user->email }}</p>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4 text-gray-700 font-data">{{ $response->user?->course ?? 'N/A' }}</td>
                                            <td class="py-3 px-4 text-gray-700 font-data">{{ $response->user?->year_level ?? 'N/A' }}</td>
                                            <td class="py-3 px-4 text-gray-700 font-data">{{ $response->created_at ? $response->created_at->format('M j, Y g:i A') : 'N/A' }}</td>
                                            <td class="py-3 px-4">
                                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold {{ $badgeClass }}">{{ $badge }}</span>
                                            </td>
                                            <td class="py-3 px-4 text-right">
                                                <div class="inline-flex items-center gap-2">
                                                    <button type="button" data-response='@json($responsePayload)' onclick="openResponseDetails(this)" class="inline-flex items-center justify-center w-10 h-10 rounded-full border-2 border-[#1E40AF] text-[#1E40AF] bg-transparent hover:bg-[#1E40AF]/10 transition-colors focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/50" title="View" aria-label="View">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    </button>
                                                    @if($status !== 'approved')
                                                    <form method="POST" action="{{ $registrationBase }}/responses/{{ $response->id }}/approve" class="inline-block" onsubmit="return confirm('Approve and enroll this student?');">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="inline-flex items-center justify-center w-10 h-10 rounded-full border-2 border-[#10B981] text-[#10B981] bg-transparent hover:bg-[#10B981]/10 transition-colors focus:outline-none focus:ring-2 focus:ring-[#10B981]/50" title="Approve" aria-label="Approve">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                        </button>
                                                    </form>
                                                    @endif
                                                    @if($status !== 'rejected')
                                                    <form method="POST" action="{{ $registrationBase }}/responses/{{ $response->id }}/reject" class="inline-block" onsubmit="return confirm('Reject this application?');">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="inline-flex items-center justify-center w-10 h-10 rounded-full border-2 border-[#EF4444] text-[#EF4444] bg-transparent hover:bg-[#EF4444]/10 transition-colors focus:outline-none focus:ring-2 focus:ring-[#EF4444]/50" title="Reject" aria-label="Reject">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L6 6M18 6v12M18 6L6 18"/></svg>
                                                        </button>
                                                    </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="py-10 px-4 text-center font-data text-sm text-gray-500">No matching records found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if($yearLevels->isEmpty() || $semesters->isEmpty())
                    <div class="mt-6 px-6 py-4 border border-amber-200 rounded-xl bg-amber-50 text-amber-800 text-sm font-data">Settings reminder: add at least one Year Level and Semester in Settings to keep deployment and response workflows fully available.</div>
                @endif
            </div>
        </div>
    </main>

    {{-- Response Details Modal (DCOMC blue header) --}}
    <div id="responseDetailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden border border-gray-200">
            <div class="px-6 py-4 bg-[#1E40AF] text-white flex justify-between items-center">
                <h3 class="font-heading font-semibold text-lg">Response Details</h3>
                <button type="button" onclick="closeResponseDetails()" class="text-white hover:text-white/80 text-2xl leading-none focus:outline-none" aria-label="Close">&times;</button>
            </div>
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                <div class="space-y-6">
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <div class="grid grid-cols-2 gap-4 text-sm font-data">
                            <div><span class="font-bold text-gray-600">Student:</span> <span id="respStudentName">Unknown</span></div>
                            <div><span class="font-bold text-gray-600">Email:</span> <span id="respStudentEmail">N/A</span></div>
                            <div><span class="font-bold text-gray-600">Form:</span> <span id="respFormTitle">N/A</span></div>
                            <div><span class="font-bold text-gray-600">Submitted:</span> <span id="respSubmittedAt">N/A</span></div>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-heading font-bold text-gray-700 mb-4 text-lg">Answers</h4>
                        <div id="respAnswersContainer" class="space-y-4"></div>
                    </div>
                    <div id="respActionsContainer" class="border-t border-gray-200 pt-4 flex justify-end gap-2">
                        <form id="respRejectForm" method="POST" action="">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="px-4 py-2 text-sm font-semibold bg-red-600 text-white hover:bg-red-700 rounded-lg transition font-data">Reject</button>
                        </form>
                        <form id="respEnrollForm" method="POST" action="">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="px-4 py-2 text-sm font-semibold bg-green-600 text-white hover:bg-green-700 rounded-lg transition font-data">Enroll</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function closeResponseDetails() {
            const modal = document.getElementById('responseDetailsModal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function openResponseDetails(button) {
            const raw = button.getAttribute('data-response');
            if (!raw) return;
            let response;
            try {
                response = JSON.parse(raw);
            } catch (e) {
                return;
            }

            document.getElementById('respStudentName').textContent = response.user?.name || 'Unknown';
            document.getElementById('respStudentEmail').textContent = response.user?.email || 'N/A';
            document.getElementById('respFormTitle').textContent = response.enrollment_form?.title || 'N/A';
            document.getElementById('respSubmittedAt').textContent = response.created_at ? new Date(response.created_at).toLocaleString() : 'N/A';

            const answersContainer = document.getElementById('respAnswersContainer');
            answersContainer.innerHTML = '';
            const questions = Array.isArray(response.enrollment_form?.questions) ? response.enrollment_form.questions : [];
            const answers = response.answers || {};

            Object.keys(answers).forEach((key) => {
                const idx = Number(key);
                const q = questions[idx];
                const label = q && q.type === 'question' ? (q.questionText || `Question ${idx + 1}`) : `Question ${idx + 1}`;
                const answer = answers[key];
                const value = Array.isArray(answer) ? answer.join(', ') : ((answer === null || answer === undefined || answer === '') ? 'No answer' : String(answer));

                const card = document.createElement('div');
                card.className = 'bg-white border border-gray-200 rounded-lg p-4';
                card.innerHTML = '<div class="font-medium text-gray-800 mb-2"></div><div class="text-gray-600 pl-4"></div>';
                card.children[0].textContent = label;
                card.children[1].textContent = value;
                answersContainer.appendChild(card);
            });

            const rejectForm = document.getElementById('respRejectForm');
            const enrollForm = document.getElementById('respEnrollForm');
            rejectForm.action = '{{ $registrationBase }}/responses/' + response.id + '/reject';
            enrollForm.action = '{{ $registrationBase }}/responses/' + response.id + '/approve';

            const actions = document.getElementById('respActionsContainer');
            if (!response.approval_status || response.approval_status === 'pending') {
                actions.classList.remove('hidden');
            } else {
                actions.classList.add('hidden');
            }

            const modal = document.getElementById('responseDetailsModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    </script>
</body>
</html>
