<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Student Records - DCOMC</title>
    @include('layouts.partials.offline-assets')
    <style>
        .dropdown-content { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; }
        .dropdown-content.open { max-height: 500px; }
    </style>
</head>
<body class="dashboard-wrap" x-data="{
    openDropdown: '',
    showModal: false,
    selectedStudent: null,
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
        if (Array.isArray(answer)) {
            return answer.join(', ');
        }
        if (answer === null || answer === undefined || answer === '') {
            return 'No answer';
        }
        return String(answer);
    }
}">

    @include('dashboards.partials.registrar-sidebar')

    <main class="dashboard-main d-flex flex-column overflow-hidden">
        <header class="bg-white border-bottom shadow-sm px-4 py-3">
            <h2 class="h5 fw-bold text-primary mb-0">Student Records</h2>
        </header>

        <div class="p-4 flex-grow-1 overflow-auto">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('registrar.students') }}">
                        <div class="row g-3 mb-3">
                            <div class="col-6 col-md-3">
                                <label class="form-label small text-uppercase fw-bold mb-0">Student Number</label>
                                <input type="text" name="student_number" value="{{ request('student_number') }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small text-uppercase fw-bold mb-0">Program</label>
                                <input type="text" name="program" value="{{ request('program') }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small text-uppercase fw-bold mb-0">School Year</label>
                                <select name="school_year" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    @foreach($schoolYears as $schoolYear)
                                        <option value="{{ $schoolYear }}" {{ request('school_year') == $schoolYear ? 'selected' : '' }}>{{ $schoolYear }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small text-uppercase fw-bold mb-0">First Name</label>
                                <input type="text" name="first_name" value="{{ request('first_name') }}" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <label class="form-label small text-uppercase fw-bold mb-0">Year Level</label>
                                <select name="year_level" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    @foreach($yearLevels as $yearLevel)
                                        <option value="{{ $yearLevel }}" {{ request('year_level') == $yearLevel ? 'selected' : '' }}>{{ $yearLevel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small text-uppercase fw-bold mb-0">Last Name</label>
                                <input type="text" name="last_name" value="{{ request('last_name') }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small text-uppercase fw-bold mb-0">Semester</label>
                                <select name="semester" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    @foreach($semesters as $semester)
                                        <option value="{{ $semester }}" {{ request('semester') == $semester ? 'selected' : '' }}>{{ $semester }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small text-uppercase fw-bold mb-0">Status</label>
                                <select name="status" class="form-select form-select-sm">
                                        <option value="">All</option>
                                        <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="Enrolled" {{ request('status') == 'Enrolled' ? 'selected' : '' }}>Enrolled</option>
                                        <option value="Rejected" {{ request('status') == 'Rejected' ? 'selected' : '' }}>Rejected</option>
                                    </select>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2 mt-3">
                                <a href="{{ route('registrar.students') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                            </div>
                        </form>
                </div>
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student Number</th>
                            <th>Name</th>
                            <th>Program</th>
                            <th>Year</th>
                            <th>Semester</th>
                            <th>School Year</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                        @php
                            $latestResponse = $student->formResponses->sortByDesc('created_at')->first();
                        @endphp
                        <tr>
                            <td class="text-nowrap fw-medium">{{ $student->id }}</td>
                            <td class="text-nowrap">{{ trim(($student->last_name ?? '') . ', ' . ($student->first_name ?? '') . ' ' . ($student->middle_name ?? '')) ?: $student->name }}</td>
                            <td class="text-nowrap">{{ $student->course ?? 'N/A' }}</td>
                            <td class="text-nowrap">{{ $student->year_level ?? 'N/A' }}</td>
                            <td class="text-nowrap">{{ $student->semester ?? 'N/A' }}</td>
                            <td class="text-nowrap">{{ $currentSchoolYear ?? 'N/A' }}</td>
                            <td class="text-nowrap">
                                @if($latestResponse)
                                    @if($latestResponse->approval_status === 'approved')
                                        <span class="badge bg-success">Enrolled</span>
                                    @elseif($latestResponse->approval_status === 'rejected')
                                        <span class="badge bg-danger">Rejected</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                @else
                                    <span class="badge bg-secondary">No Response</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if($latestResponse)
                                    <button type="button" @click="selectedStudent = {{ $student->toJson() }}; selectedStudent.responses = {{ $student->formResponses->toJson() }}; showModal = true" class="btn btn-link btn-sm p-0">View Details</button>
                                @else
                                    <span class="text-muted">No Action</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">No students found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($students->hasPages())
                <div class="card-footer bg-white">{{ $students->withQueryString()->links() }}</div>
            @endif
            </div>
        </div>
    </main>

    <div x-show="showModal" class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center p-3 bg-dark bg-opacity-50" style="z-index: 1050; display: none;" x-transition x-cloak>
        <div class="card shadow-lg w-100 flex-grow-1" style="max-width: 56rem; max-height: 90vh;" @click.away="showModal = false">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h3 class="h6 fw-bold mb-0">Student Enrollment Details</h3>
                <button type="button" class="btn-close btn-close-white" aria-label="Close" @click="showModal = false"></button>
            </div>
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-180px)]">
                <template x-if="selectedStudent">
                    <div class="space-y-6">
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <h4 class="font-bold text-gray-700 mb-3">Student Information</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div><span class="font-bold text-gray-600">Name:</span> <span x-text="(selectedStudent.last_name || '') + ', ' + (selectedStudent.first_name || '') + ' ' + (selectedStudent.middle_name || '')"></span></div>
                                <div><span class="font-bold text-gray-600">Email:</span> <span x-text="selectedStudent.email"></span></div>
                                <div><span class="font-bold text-gray-600">Program:</span> <span x-text="selectedStudent.course || 'N/A'"></span></div>
                                <div><span class="font-bold text-gray-600">Year Level:</span> <span x-text="selectedStudent.year_level || 'N/A'"></span></div>
                                <div><span class="font-bold text-gray-600">Semester:</span> <span x-text="selectedStudent.semester || 'N/A'"></span></div>
                                <div><span class="font-bold text-gray-600">Phone:</span> <span x-text="selectedStudent.phone || 'N/A'"></span></div>
                            </div>
                        </div>

                        <div>
                            <h4 class="font-bold text-gray-700 mb-3">Enrollment Form Responses</h4>
                            <template x-if="selectedStudent.responses && selectedStudent.responses.length > 0">
                                <div class="space-y-4">
                                    <template x-for="response in selectedStudent.responses" :key="response.id">
                                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                                            <div class="flex justify-between items-center mb-3 pb-2 border-b">
                                                <div>
                                                    <span class="font-medium text-gray-800" x-text="response.enrollment_form ? response.enrollment_form.title : 'Form'"></span>
                                                    <p class="text-xs text-gray-500 mt-1" x-text="response.enrollment_form && response.enrollment_form.incoming_year_level ? ('Destination: ' + response.enrollment_form.incoming_year_level + ' - ' + response.enrollment_form.incoming_semester) : 'Destination not set'"></p>
                                                </div>
                                                <span class="text-xs text-gray-500" x-text="new Date(response.created_at).toLocaleString()"></span>
                                            </div>
                                            <div class="mb-3">
                                                <template x-if="response.approval_status === 'approved'">
                                                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">Enrolled</span>
                                                </template>
                                                <template x-if="response.approval_status === 'rejected'">
                                                    <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-bold">Rejected</span>
                                                </template>
                                                <template x-if="!response.approval_status || response.approval_status === 'pending'">
                                                    <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs font-bold">Pending</span>
                                                </template>
                                            </div>
                                            <div class="space-y-3">
                                                <template x-for="(answer, key) in response.answers" :key="key">
                                                    <div class="text-sm">
                                                        <div class="font-medium text-gray-700 mb-1" x-text="getQuestionLabel(response, key)"></div>
                                                        <div class="text-gray-600 pl-4" x-text="formatAnswer(answer)"></div>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="mt-4 pt-3 border-t border-gray-100 flex justify-end gap-2" x-show="!response.approval_status || response.approval_status === 'pending'">
                                                <form method="POST" :action="'/registrar/registration/responses/' + response.id + '/reject'">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="px-3 py-2 text-xs font-medium bg-red-600 text-white hover:bg-red-700 rounded transition">Reject</button>
                                                </form>
                                                <form method="POST" :action="'/registrar/registration/responses/' + response.id + '/approve'">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="px-3 py-2 text-xs font-medium bg-green-600 text-white hover:bg-green-700 rounded transition">Approve & Promote</button>
                                                </form>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 border-t border-gray-200">
                <button @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-200 rounded transition">Close</button>
            </div>
        </div>
    </div>

</body>
</html>
