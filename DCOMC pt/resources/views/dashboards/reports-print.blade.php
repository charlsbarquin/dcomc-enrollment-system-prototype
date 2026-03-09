<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Report - Daraga Community College</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcc-certification-styles')
    <style>
        .dcc-report-header .dcc-line-republic { font-family: 'Times New Roman', serif; font-size: 14px; color: #000; }
        .dcc-report-header .dcc-line-college { font-family: 'Times New Roman', serif; font-size: 22px; font-weight: bold; color: #1E40AF; }
        .dcc-report-header .dcc-line-address { font-family: 'Times New Roman', serif; font-size: 13px; color: #374151; }
        .dcc-report-header .dcc-office-registrar { font-family: 'Times New Roman', serif; font-size: 16px; font-style: italic; color: #1f2937; }
        .dcc-document-title { font-family: 'Times New Roman', serif; font-size: 16px; font-weight: bold; text-decoration: underline; text-align: center; margin: 0 0 8px; color: #1E40AF; }
        .dcc-meta-section { font-family: 'Times New Roman', serif; font-size: 12px; text-align: center; margin: 0 0 16px; color: #374151; }
        .dcc-cert-narrative { font-family: 'Times New Roman', serif; font-size: 12px; line-height: 1.5; color: #1f2937; margin-bottom: 16px; text-align: left; }
        .dcc-section-heading { font-family: 'Times New Roman', serif; font-size: 12px; font-weight: bold; margin: 16px 0 8px; color: #1f2937; }
        @media print {
            @page { size: auto; margin: 0.5in; }
            body { background: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            body * { box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-white text-gray-900">
    <div class="no-print" style="max-width: 21cm; margin: 0 auto; padding: 1rem;">
        <div class="flex items-center justify-between mb-4">
            <a href="{{ $backUrl ?? '#' }}" class="text-[#1E40AF] hover:underline" style="text-decoration: none;">← Back to System Reports</a>
            <button type="button" id="btn-print-report" class="px-4 py-2 bg-[#1E40AF] text-white rounded font-semibold border-0 cursor-pointer">Print Report</button>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var btn = document.getElementById('btn-print-report');
            if (btn) btn.addEventListener('click', function () { window.print(); });
        });
    </script>

    <div style="max-width: 21cm; margin: 0 auto; padding: 0 0.5in 0.5in;">
        @include('dashboards.partials.dcc-report-header', ['officeLine' => request()->routeIs('admin.reports.print') ? 'Office of the System Administrator' : (request()->routeIs('dean.reports.print') ? 'Office of the Dean' : 'Office of the College Registrar')])

        @php
            $reportTitle = match($reportType ?? 'enrollment_summary') {
                'enrollment_summary' => 'ENROLLMENT SUMMARY',
                'program_yearlevel' => 'SEMESTER ENROLLMENT BY PROGRAM AND YEAR LEVEL',
                'faculty_loading' => 'FACULTY TEACHING LOAD REPORT',
                'room_utilization' => 'ROOM UTILIZATION REPORT',
                'financial_summary' => 'FINANCIAL / UNIFAST ASSESSMENT SUMMARY',
                default => 'OFFICIAL REPORT',
            };
            $academicYear = $filters['academic_year'] ?? 'All';
            $semester = $filters['semester'] ?? 'All';
        @endphp

        <h1 class="dcc-document-title">{{ $reportTitle }}</h1>
        <div class="dcc-meta-section">
            Academic Year: {{ $academicYear }} &nbsp;&nbsp;|&nbsp;&nbsp; Semester: {{ $semester }}
        </div>
        <p class="dcc-meta-section" style="margin-top: 0; font-size: 11px;">Generated: {{ $generatedAt ?? now()->format('F j, Y g:i A') }}</p>

        @if(($reportType ?? '') === 'enrollment_summary')
            <p class="dcc-cert-narrative">On the basis of the records on file in this office, we hereby certify that the following enrollment summary is true and correct as of the date of generation.</p>
            <div class="dcc-table-wrap">
                <table>
                    <thead>
                        <tr><th style="text-align: left;">Summary</th><th style="text-align: right;">Count</th></tr>
                    </thead>
                    <tbody>
                        <tr><td style="font-weight: bold;">Total Enrollment (Applications)</td><td style="text-align: right;">{{ number_format($totalEnrollmentCount ?? 0) }}</td></tr>
                        <tr><td>Daraga (by address)</td><td style="text-align: right;">{{ number_format($daragaCount ?? 0) }}</td></tr>
                        <tr><td>Legazpi (by address)</td><td style="text-align: right;">{{ number_format($legazpiCount ?? 0) }}</td></tr>
                        <tr><td>Guinobatan (by address)</td><td style="text-align: right;">{{ number_format($guinobatanCount ?? 0) }}</td></tr>
                    </tbody>
                </table>
            </div>
            <p class="dcc-section-heading">By Program</p>
            <div class="dcc-table-wrap">
                <table>
                    <thead><tr><th style="text-align: left;">Program</th><th style="text-align: right;">Count</th></tr></thead>
                    <tbody>
                        @forelse($programBreakdown ?? [] as $r)
                            <tr><td>{{ $r->label ?: 'N/A' }}</td><td style="text-align: right;">{{ number_format($r->count) }}</td></tr>
                        @empty
                            <tr><td colspan="2" style="text-align: center;">No data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="dcc-section-heading">By Year Level</p>
            <div class="dcc-table-wrap">
                <table>
                    <thead><tr><th style="text-align: left;">Year Level</th><th style="text-align: right;">Count</th></tr></thead>
                    <tbody>
                        @forelse($enrollmentByYearLevel ?? [] as $r)
                            <tr><td>{{ $r->year_level ?: 'N/A' }}</td><td style="text-align: right;">{{ number_format($r->count) }}</td></tr>
                        @empty
                            <tr><td colspan="2" style="text-align: center;">No data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="dcc-section-heading">By Gender</p>
            <div class="dcc-table-wrap">
                <table>
                    <thead><tr><th style="text-align: left;">Gender</th><th style="text-align: right;">Count</th></tr></thead>
                    <tbody>
                        @forelse($genderBreakdown ?? [] as $r)
                            <tr><td>{{ $r->label ?: 'N/A' }}</td><td style="text-align: right;">{{ number_format($r->count) }}</td></tr>
                        @empty
                            <tr><td colspan="2" style="text-align: center;">No data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif(($reportType ?? '') === 'program_yearlevel')
            <p class="dcc-cert-narrative">On the basis of the records on file in this office, we hereby certify that the following enrollment by program and year level is true and correct.</p>
            <div class="dcc-table-wrap">
                <table>
                    <thead>
                        <tr><th style="text-align: left;">Program</th><th style="text-align: left;">Year Level</th><th style="text-align: right;">Count</th></tr>
                    </thead>
                    <tbody>
                        @forelse($enrollmentByProgramYearLevel ?? [] as $r)
                            <tr><td>{{ $r->program ?: 'N/A' }}</td><td>{{ $r->year_level ?: 'N/A' }}</td><td style="text-align: right;">{{ number_format($r->count) }}</td></tr>
                        @empty
                            <tr><td colspan="3" style="text-align: center;">No data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif(($reportType ?? '') === 'faculty_loading')
            <p class="dcc-cert-narrative">On the basis of the records on file in this office, we hereby certify that the following faculty teaching load report is true and correct.</p>
            <div class="dcc-table-wrap">
                <table>
                    <thead>
                        <tr><th style="text-align: left;">Faculty</th><th style="text-align: left;">Type</th><th style="text-align: right;">Assigned Units</th><th style="text-align: right;">Max Units</th><th style="text-align: center;">Overload</th></tr>
                    </thead>
                    <tbody>
                        @forelse($facultyRows ?? [] as $r)
                            <tr><td>{{ $r->name }}</td><td>{{ $r->faculty_type ?? 'N/A' }}</td><td style="text-align: right;">{{ $r->assigned_units }}</td><td style="text-align: right;">{{ $r->max_units }}</td><td style="text-align: center;">{{ $r->is_overload ? 'Yes' : 'No' }}</td></tr>
                        @empty
                            <tr><td colspan="5" style="text-align: center;">No data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif(($reportType ?? '') === 'room_utilization')
            <p class="dcc-cert-narrative">On the basis of the records on file in this office, we hereby certify that the following room utilization report is true and correct.</p>
            <div class="dcc-table-wrap">
                <table>
                    <thead>
                        <tr><th style="text-align: left;">Room</th><th style="text-align: left;">Code</th><th style="text-align: right;">Capacity</th><th style="text-align: right;">Schedules</th><th style="text-align: right;">Hours</th></tr>
                    </thead>
                    <tbody>
                        @forelse($roomRows ?? [] as $r)
                            <tr><td>{{ $r->name }}</td><td>{{ $r->code }}</td><td style="text-align: right;">{{ $r->capacity ?? 'N/A' }}</td><td style="text-align: right;">{{ $r->utilization_count }}</td><td style="text-align: right;">{{ $r->utilization_hours }}</td></tr>
                        @empty
                            <tr><td colspan="5" style="text-align: center;">No data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif(($reportType ?? '') === 'financial_summary')
            <p class="dcc-cert-narrative">On the basis of the records on file in this office, we hereby certify that the following financial / UniFAST assessment summary is true and correct.</p>
            <div class="dcc-table-wrap">
                <table>
                    <thead>
                        <tr><th style="text-align: left;">Student</th><th style="text-align: right;">Total Assessed</th><th style="text-align: left;">Income Class</th><th style="text-align: left;">Status</th><th style="text-align: center;">UniFAST Eligible</th></tr>
                    </thead>
                    <tbody>
                        @forelse($financialRows ?? [] as $r)
                            <tr><td>{{ $r->student?->name ?? 'N/A' }}</td><td style="text-align: right;">{{ $r->total_assessed ?? 'N/A' }}</td><td>{{ $r->income_classification ?? 'N/A' }}</td><td>{{ $r->assessment_status ?? 'N/A' }}</td><td style="text-align: center;">{{ $r->unifast_eligible ? 'Yes' : 'No' }}</td></tr>
                        @empty
                            <tr><td colspan="5" style="text-align: center;">No data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <p class="dcc-cert-narrative">Select a report type from the System Reporting page.</p>
        @endif

        {{-- Official Signatory Block (no stamp). Admin: Administrator only; Dean: Dean signatory; UniFAST: dual (Registrar + Admin); others: Registrar + Administrator. --}}
        @if(request()->routeIs('admin.reports.print'))
            @include('dashboards.partials.dcc-admin-signatory')
        @elseif(request()->routeIs('dean.reports.print'))
            @include('dashboards.partials.dcc-dean-signatory')
        @elseif(request()->routeIs('unifast.reports.print'))
            <div class="dcc-signatory-block" style="margin-top: 32px;">
                <div class="dcc-signatory-row" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 40px; max-width: 100%;">
                    <div class="dcc-signatory-item" style="flex: 1; min-width: 0;">
                        <p class="dcc-certified-label" style="font-family: 'Times New Roman', serif; font-size: 12px; margin: 0 0 24px; text-align: left;">Certified Correct:</p>
                        <div class="dcc-signature-line" style="border-bottom: 1px solid #000; height: 28px; margin-bottom: 4px;"></div>
                        <p class="dcc-signatory-name" style="font-family: 'Times New Roman', serif; font-size: 12px; font-weight: bold; margin: 0 0 2px; text-transform: uppercase;">JAY F. NACE, LPT</p>
                        <p class="dcc-signatory-title" style="font-family: 'Times New Roman', serif; font-size: 11px; margin: 0; color: #374151;">Registrar</p>
                    </div>
                    <div class="dcc-signatory-item" style="flex: 1; min-width: 0; text-align: right;">
                        <div class="dcc-signature-line" style="border-bottom: 1px solid #000; height: 28px; margin-bottom: 4px;"></div>
                        <p class="dcc-signatory-name" style="font-family: 'Times New Roman', serif; font-size: 12px; font-weight: bold; margin: 0 0 2px; text-transform: uppercase;">JOEY M. ZAMORA, EdD</p>
                        <p class="dcc-signatory-title" style="font-family: 'Times New Roman', serif; font-size: 11px; margin: 0; color: #374151;">Admin</p>
                    </div>
                </div>
            </div>
        @else
            <div class="dcc-signatory-block" style="margin-top: 32px;">
                <div class="dcc-signatory-row" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 40px; max-width: 100%;">
                    <div class="dcc-signatory-item" style="flex: 1; min-width: 0;">
                        <p class="dcc-certified-label" style="font-family: 'Times New Roman', serif; font-size: 12px; margin: 0 0 24px; text-align: left;">Certified Correct:</p>
                        <div class="dcc-signature-line" style="border-bottom: 1px solid #000; height: 28px; margin-bottom: 4px;"></div>
                        <p class="dcc-signatory-name" style="font-family: 'Times New Roman', serif; font-size: 12px; font-weight: bold; margin: 0 0 2px; text-transform: uppercase;">JAY F. NACE, LPT</p>
                        <p class="dcc-signatory-title" style="font-family: 'Times New Roman', serif; font-size: 11px; margin: 0; color: #374151;">College Registrar I</p>
                    </div>
                    <div class="dcc-signatory-item" style="flex: 1; min-width: 0; text-align: right;">
                        <div class="dcc-signature-line" style="border-bottom: 1px solid #000; height: 28px; margin-bottom: 4px;"></div>
                        <p class="dcc-signatory-name" style="font-family: 'Times New Roman', serif; font-size: 12px; font-weight: bold; margin: 0 0 2px; text-transform: uppercase;">JOEY M. ZAMORA, EdD</p>
                        <p class="dcc-signatory-title" style="font-family: 'Times New Roman', serif; font-size: 11px; margin: 0; color: #374151;">College Administrator</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Footer Motto --}}
        <div class="dcc-footer" style="margin-top: 40px; padding-top: 16px; border-top: 1px solid #e5e7eb; text-align: center;">
            <p class="dcc-motto" style="font-family: 'Times New Roman', serif; font-size: 13px; margin: 0; color: #374151;">Pandayan ng Pangarap</p>
        </div>
    </div>

    <div class="no-print" style="max-width: 21cm; margin: 0 auto; padding: 1rem; text-align: center;">
        <button type="button" id="btn-print-report-footer" class="px-4 py-2 bg-[#1E40AF] text-white rounded font-semibold border-0 cursor-pointer">Print Report</button>
    </div>
    <script>
        (function () {
            var f = document.getElementById('btn-print-report-footer');
            if (f) f.addEventListener('click', function () { window.print(); });
        })();
    </script>
</body>
</html>
