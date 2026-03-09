<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $folder->title }} - Form Responses - DCOMC</title>
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
</head>
<body class="bg-[#F1F5F9] min-h-screen flex overflow-x-hidden text-gray-800 font-data">

    @php
        $user = auth()->user();
        $isStaff = request()->routeIs('staff.*');
        $manualRoute = $isStaff ? route('staff.registration.manual') : route('registrar.registration.manual');
        $builderRoute = $isStaff ? route('staff.registration.builder') : route('registrar.registration.builder');
        $responsesRoute = $isStaff ? route('staff.registration.responses') : route('registrar.registration.responses');
        $registrationBase = $isStaff ? '/staff/registration' : '/registrar/registration';
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

    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden" x-data="{ globalEnrollment: {{ $globalEnrollmentActive ? 'true' : 'false' }} }">
        {{-- Sub-nav (match Form Responses page) --}}
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
                <button type="button" @click="globalEnrollment = !globalEnrollment; fetch('{{ $isStaff ? '/staff/registration/toggle-global' : '/registrar/registration/toggle-global' }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ is_active: globalEnrollment }) }).then(r=>r.json()).then(d=>alert(d.message))" role="switch" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:ring-offset-2" :class="globalEnrollment ? 'bg-[#1E40AF]' : 'bg-gray-300'">
                    <span class="pointer-events-none absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition duration-200" :class="globalEnrollment ? 'left-5 translate-x-0' : 'left-0.5 translate-x-0'"></span>
                </button>
                @endunless
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 md:p-8 forms-canvas">
            <div class="w-full">
                {{-- Back link --}}
                <div class="mb-4">
                    <a href="{{ $responsesRoute }}" class="inline-flex items-center gap-1 text-sm font-medium text-[#1E40AF] hover:text-[#1D3A8A] no-underline font-data">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Back to folders
                    </a>
                </div>

                {{-- Hero banner: folder title + assignment + count --}}
                <section class="w-full hero-gradient rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold mb-2">{{ $folder->title }}</h1>
                            <p class="text-white/90 text-sm sm:text-base font-data">
                                @if($folder->assigned_year && $folder->assigned_semester)
                                    {{ $folder->assigned_year }} – {{ $folder->assigned_semester }}
                                @else
                                    No assignment
                                @endif
                                <span class="text-white/70"> · </span>
                                <span>{{ $responses->count() }} response(s)</span>
                            </p>
                        </div>
                        <div class="shrink-0">
                            <span class="inline-flex items-center px-4 py-2 rounded-lg bg-white/20 text-white text-sm font-bold font-data">Total: {{ $responses->count() }}</span>
                        </div>
                    </div>
                </section>

                {{-- Search card --}}
                <div class="bg-white shadow-2xl rounded-xl border border-gray-200 px-5 py-5 mb-6">
                    <label for="folder-response-search" class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2 font-heading">Search in this folder</label>
                    <div class="relative max-w-xl">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input type="text" id="folder-response-search" placeholder="Search by student name, email, form title, or destination..." class="w-full font-data text-sm border border-gray-300 rounded-lg pl-10 pr-4 py-2.5 bg-white input-dcomc-focus transition" oninput="filterFolderResponses()">
                    </div>
                </div>

                {{-- Table: DCOMC blue header, pills, Eye/Check/X actions --}}
                <div class="w-full bg-white shadow-2xl rounded-xl overflow-hidden border border-gray-200">
                    <div class="bg-[#1E40AF] px-6 py-4 flex flex-wrap items-center justify-between gap-2">
                        <h2 class="font-heading text-lg font-bold text-white">Responses in this folder</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full font-data text-sm" id="folder-responses-table" role="grid">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="text-left py-3 px-4 font-heading font-semibold text-gray-700">Student</th>
                                    <th scope="col" class="text-left py-3 px-4 font-heading font-semibold text-gray-700">Current Level</th>
                                    <th scope="col" class="text-left py-3 px-4 font-heading font-semibold text-gray-700">Form</th>
                                    <th scope="col" class="text-left py-3 px-4 font-heading font-semibold text-gray-700">Destination</th>
                                    <th scope="col" class="text-left py-3 px-4 font-heading font-semibold text-gray-700">Block Preference</th>
                                    <th scope="col" class="text-left py-3 px-4 font-heading font-semibold text-gray-700">Status</th>
                                    <th scope="col" class="text-right py-3 px-4 font-heading font-semibold text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @forelse($responses as $response)
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
                                    <tr class="hover:bg-[#1E40AF]/5 transition-colors duration-200 folder-response-row">
                                        <td class="py-3 px-4 text-gray-900 font-data">
                                            {{ $response->user?->name ?? 'Unknown' }}
                                            @if($response->user?->email)
                                                <p class="text-xs text-gray-500">{{ $response->user->email }}</p>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-gray-700 font-data">{{ $response->user?->year_level ?? 'N/A' }} – {{ $response->user?->semester ?? 'N/A' }}</td>
                                        <td class="py-3 px-4 text-gray-700 font-data">{{ $response->enrollmentForm?->title ?? 'N/A' }}</td>
                                        <td class="py-3 px-4 text-gray-700 font-data">
                                            @if($response->enrollmentForm?->incoming_year_level && $response->enrollmentForm?->incoming_semester)
                                                {{ $response->enrollmentForm->incoming_year_level }} – {{ $response->enrollmentForm->incoming_semester }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-gray-700 font-data">
                                            <p>Preferred: {{ $response->preferredBlock?->code ?? $response->preferredBlock?->name ?? 'Auto' }}</p>
                                            <p class="text-xs text-gray-500">Assigned: {{ $response->assignedBlock?->code ?? $response->assignedBlock?->name ?? 'Pending' }}</p>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold {{ $badgeClass }}">{{ $badge }}</span>
                                        </td>
                                        <td class="py-3 px-4 text-right">
                                            <div class="inline-flex items-center gap-2">
                                                <button type="button" data-response='@json($responsePayload)' onclick="openFolderResponseDetails(this)" class="inline-flex items-center justify-center w-10 h-10 rounded-full border-2 border-[#1E40AF] text-[#1E40AF] bg-transparent hover:bg-[#1E40AF]/10 transition-colors focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/50" title="View" aria-label="View">
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
                                        <td colspan="7" class="py-16 px-6 text-center">
                                            <svg class="w-14 h-14 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <p class="font-heading font-semibold text-gray-600 mb-1">No responses in this folder yet</p>
                                            <p class="text-sm text-gray-500 font-data">Student submissions will appear here once the form is deployed and the portal is open.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    {{-- Response Details Modal (DCOMC blue header) --}}
    <div id="folderResponseModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden border border-gray-200">
            <div class="px-6 py-4 bg-[#1E40AF] text-white flex justify-between items-center">
                <h3 class="font-heading font-semibold text-lg">Response Details</h3>
                <button type="button" onclick="closeFolderResponseDetails()" class="text-white hover:text-white/80 text-2xl leading-none focus:outline-none" aria-label="Close">&times;</button>
            </div>
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                <div class="space-y-6">
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <div class="grid grid-cols-2 gap-4 text-sm font-data">
                            <div><span class="font-bold text-gray-600">Student:</span> <span id="folderRespStudentName">Unknown</span></div>
                            <div><span class="font-bold text-gray-600">Email:</span> <span id="folderRespStudentEmail">N/A</span></div>
                            <div><span class="font-bold text-gray-600">Form:</span> <span id="folderRespFormTitle">N/A</span></div>
                            <div><span class="font-bold text-gray-600">Submitted:</span> <span id="folderRespSubmittedAt">N/A</span></div>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-heading font-bold text-gray-700 mb-4 text-lg">Answers</h4>
                        <div id="folderRespAnswersContainer" class="space-y-4"></div>
                    </div>
                    <div id="folderRespActionsContainer" class="border-t border-gray-200 pt-4 flex justify-end gap-2">
                        <form id="folderRespRejectForm" method="POST" action="">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="px-4 py-2 text-sm font-semibold bg-red-600 text-white hover:bg-red-700 rounded-lg transition font-data">Reject</button>
                        </form>
                        <form id="folderRespEnrollForm" method="POST" action="">
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
        function filterFolderResponses() {
            const input = document.getElementById('folder-response-search');
            if (!input) return;
            const query = input.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#folder-responses-table tbody tr.folder-response-row');
            rows.forEach(row => {
                const text = Array.from(row.children).slice(0, 4).map(c => c?.innerText || '').join(' ').toLowerCase();
                row.classList.toggle('hidden', !!query && !text.includes(query));
            });
        }

        function closeFolderResponseDetails() {
            const modal = document.getElementById('folderResponseModal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function openFolderResponseDetails(button) {
            const raw = button.getAttribute('data-response');
            if (!raw) return;
            let response;
            try {
                response = JSON.parse(raw);
            } catch (e) {
                return;
            }

            document.getElementById('folderRespStudentName').textContent = response.user?.name || 'Unknown';
            document.getElementById('folderRespStudentEmail').textContent = response.user?.email || 'N/A';
            document.getElementById('folderRespFormTitle').textContent = response.enrollment_form?.title || 'N/A';
            document.getElementById('folderRespSubmittedAt').textContent = response.created_at ? new Date(response.created_at).toLocaleString() : 'N/A';

            const answersContainer = document.getElementById('folderRespAnswersContainer');
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

            document.getElementById('folderRespRejectForm').action = '{{ $registrationBase }}/responses/' + response.id + '/reject';
            document.getElementById('folderRespEnrollForm').action = '{{ $registrationBase }}/responses/' + response.id + '/approve';

            const actions = document.getElementById('folderRespActionsContainer');
            if (!response.approval_status || response.approval_status === 'pending') {
                actions.classList.remove('hidden');
            } else {
                actions.classList.add('hidden');
            }

            const modal = document.getElementById('folderResponseModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    </script>
</body>
</html>
