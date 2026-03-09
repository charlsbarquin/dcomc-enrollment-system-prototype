@php
    $hasCorSubjects = !empty($corSubjects);
    $totalUnits = $hasCorSubjects ? collect($corSubjects)->sum(fn($row) => (int) ($row['subject']->units ?? 0)) : 0;
    $schoolYearDisplay = $schoolYear ?? $student->block?->school_year_label ?? 'N/A';
    $semesterDisplay = $semester ?? $student->semester ?? 'N/A';
    $yearLevelDisplay = $student->resolved_year_level ?? $student->year_level ?? 'N/A';
    $studentNameFormal = strtoupper(($student->last_name ?? '') . ', ' . ($student->first_name ?? '') . ($student->middle_name ? ' ' . substr($student->middle_name, 0, 1) . '.' : ''));
    if (trim($studentNameFormal) === ',' || $studentNameFormal === '') {
        $studentNameFormal = strtoupper($student->name ?? 'N/A');
    }
    $syShort = preg_match('/^(\d{4})/', (string)$schoolYearDisplay, $m) ? $m[1] : date('Y');
    $studentNo = $student->school_id ?? ('DComC - ' . $syShort . ' - ' . $student->id);
    $addressParts = array_filter([
        $student->street ?? $student->house_number ?? null,
        $student->barangay ?? null,
        $student->municipality ?? null,
        $student->province ?? null,
    ]);
    $addressLine = implode(' ', $addressParts) ?: '—';
    $blockMajor = $student->block?->name ?? $student->block?->code ?? $student->major ?? 'N/A';
@endphp
<div class="dcc-print-page cor-page" style="background: #fff; padding: 0.5in;">
    @include('dashboards.partials.cor-institutional-header')

    <div class="cor-info-box">
        <div class="cor-info-header">Student General Information</div>
        <div class="cor-info-grid">
            <div class="cor-info-col">
                <div class="cor-info-row"><span class="cor-info-label">Student No.:</span><span class="cor-info-value">{{ $studentNo }}</span></div>
                <div class="cor-info-row"><span class="cor-info-label">Name:</span><span class="cor-info-value">{{ $studentNameFormal }}</span></div>
                <div class="cor-info-row"><span class="cor-info-label">Gender:</span><span class="cor-info-value">{{ $student->gender ?? '—' }}</span></div>
                <div class="cor-info-row"><span class="cor-info-label">Address:</span><span class="cor-info-value">{{ $addressLine }}</span></div>
                <div class="cor-info-row"><span class="cor-info-label">Zip Code:</span><span class="cor-info-value">{{ $student->zip_code ?? '—' }}</span></div>
                <div class="cor-info-row"><span class="cor-info-label">Contact No.:</span><span class="cor-info-value">{{ $student->phone ?? '—' }}</span></div>
                <div class="cor-info-row"><span class="cor-info-label">Email Address:</span><span class="cor-info-value">{{ $student->email ?? '—' }}</span></div>
                <div class="cor-info-row"><span class="cor-info-label">Student Type:</span><span class="cor-info-value">{{ $student->student_type ?? 'Regular' }}</span></div>
                <div class="cor-info-row"><span class="cor-info-label">Semester:</span><span class="cor-info-value">{{ $semesterDisplay }}</span></div>
                <div class="cor-info-row"><span class="cor-info-label">Block/Major:</span><span class="cor-info-value">{{ $blockMajor }}</span></div>
                <div class="cor-info-row"><span class="cor-info-label">Father's Full Name:</span><span class="cor-info-value">{{ $student->father_name ?? '—' }}</span></div>
                <div class="cor-info-row"><span class="cor-info-label">Occupation:</span><span class="cor-info-value">{{ $student->father_occupation ?? '—' }}</span></div>
                <div class="cor-info-row"><span class="cor-info-label">Mother's Maiden Name:</span><span class="cor-info-value">{{ $student->mother_name ?? '—' }}</span></div>
                <div class="cor-info-row"><span class="cor-info-label">Occupation:</span><span class="cor-info-value">{{ $student->mother_occupation ?? '—' }}</span></div>
                <div class="cor-info-row"><span class="cor-info-label">Household's Monthly Income:</span><span class="cor-info-value">{{ $student->monthly_income ? number_format((float)$student->monthly_income) : '—' }}</span></div>
            </div>
            <div class="cor-info-col">
                <div class="cor-info-row"><span class="cor-info-label">Civil Status:</span><span class="cor-info-value">{{ $student->civil_status ?? '—' }}</span></div>
                <div class="cor-info-row"><span class="cor-info-label">Date of Birth:</span><span class="cor-info-value">{{ $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('m/d/Y') : '—' }}</span></div>
                <div class="cor-info-row"><span class="cor-info-label">Place of Birth:</span><span class="cor-info-value">{{ $student->place_of_birth ?? '—' }}</span></div>
                <div class="cor-info-row"><span class="cor-info-label">Citizenship:</span><span class="cor-info-value">{{ $student->citizenship ?? '—' }}</span></div>
                <div class="cor-info-row"><span class="cor-info-label">School Year:</span><span class="cor-info-value">{{ $schoolYearDisplay }}</span></div>
                <div class="cor-info-row"><span class="cor-info-label">Year Level:</span><span class="cor-info-value">{{ $yearLevelDisplay }}</span></div>
                <div class="cor-info-row"><span class="cor-info-label">Course:</span><span class="cor-info-value">{{ $student->resolved_program ?? $student->course ?? 'N/A' }}</span></div>
                <div class="cor-info-row"><span class="cor-info-label">Shift:</span><span class="cor-info-value">{{ $student->shift ?? 'N/A' }}</span></div>
                <div class="cor-info-row"><span class="cor-info-label">DSWD Household No.:</span><span class="cor-info-value">{{ $student->dswd_household_no ?? 'N/A' }}</span></div>
                <div class="cor-info-row"><span class="cor-info-label">No. of Family Members:</span><span class="cor-info-value">{{ $student->num_family_members ?? '—' }}</span></div>
            </div>
        </div>
    </div>

    <table class="cor-table" style="margin-bottom: 16px;">
        <thead>
            <tr>
                <th>Code</th>
                <th>Subject Title</th>
                <th>Units</th>
                <th>Schedule</th>
                <th>Faculty Signature</th>
            </tr>
        </thead>
        <tbody>
            @if($hasCorSubjects)
                @foreach($corSubjects as $row)
                    <tr>
                        <td>{{ $row['subject']->code ?? '' }}</td>
                        <td>{{ $row['subject']->title ?? 'N/A' }}</td>
                        <td>{{ isset($row['subject']->units) ? (strpos((string)$row['subject']->units, '.') !== false ? "({$row['subject']->units})" : $row['subject']->units) : 0 }}</td>
                        <td>{{ $row['schedule_text'] ?? 'TBA' }}</td>
                        <td></td>
                    </tr>
                @endforeach
                <tr style="font-weight: bold;">
                    <td colspan="2">Total Units</td>
                    <td>{{ $totalUnits }}</td>
                    <td colspan="2"></td>
                </tr>
            @else
                @forelse($schedules ?? [] as $schedule)
                    <tr>
                        <td>{{ $schedule->subject?->code ?? '' }}</td>
                        <td>{{ $schedule->subject?->title ?? 'N/A' }}</td>
                        <td>{{ $schedule->subject?->units ?? 0 }}</td>
                        <td>{{ $schedule->start_time && $schedule->end_time ? $schedule->start_time . '-' . $schedule->end_time : 'TBA' }}</td>
                        <td></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 24px; text-align: center; color: #6b7280;">
                            @if(!empty($noCorForSelectedYear) && !empty($selectedSchoolYear))
                                No COR on file for school year {{ $selectedSchoolYear }}.
                            @else
                                No subjects assigned yet.
                            @endif
                        </td>
                    </tr>
                @endforelse
            @endif
        </tbody>
    </table>

    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 24px; align-items: flex-start;">
        <div style="flex: 0 1 320px;">
            @if(!empty($corFees))
            <table class="cor-table">
                <thead>
                    <tr>
                        <th>Fee Type</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($corFees as $row)
                        <tr>
                            <td>{{ $row['name'] }}{{ !empty($row['category']) ? ' (' . $row['category'] . ')' : '' }}</td>
                            <td style="text-align: right;">{{ number_format($row['amount'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr style="font-weight: bold;">
                        <td>TOTAL</td>
                        <td style="text-align: right;">{{ number_format(collect($corFees)->sum('amount'), 2) }}</td>
                    </tr>
                </tbody>
            </table>
            @else
            <table class="cor-table">
                <thead><tr><th>Fee Type</th><th style="text-align: right;">Amount</th></tr></thead>
                <tbody><tr><td colspan="2" style="text-align: center; padding: 12px;">No fees assessed.</td></tr></tbody>
            </table>
            @endif
        </div>
        <div style="flex: 1; min-width: 280px;">
            @include('dashboards.partials.cor-signatory')
        </div>
    </div>

    @include('dashboards.partials.cor-footer', ['printed_by' => auth()->user()->name ?? '—', 'printed_date' => now()->format('M d, Y')])
</div>
