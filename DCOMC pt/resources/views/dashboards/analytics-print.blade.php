<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Analytics Summary - Daraga Community College</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700&family=Times+New+Roman&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 24px 32px;
            max-width: 210mm;
            margin-left: auto;
            margin-right: auto;
        }
        .no-print { display: block; }
        @media print {
            body { padding: 0; margin: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }

        /* DCC Header */
        .dcc-header { text-align: center; margin-bottom: 8px; }
        .dcc-logo { width: 80px; height: 80px; object-fit: contain; margin: 0 auto 6px; display: block; }
        .dcc-line-republic { font-size: 11pt; margin: 0 0 2px; }
        .dcc-line-college { font-size: 14pt; font-weight: bold; color: #1e40af; margin: 4px 0 2px; }
        .dcc-line-address { font-size: 10pt; margin: 0 0 16px; }
        .dcc-office { font-family: 'Cinzel Decorative', 'Times New Roman', serif; font-size: 13pt; margin: 12px 0 6px; }
        .dcc-title { font-size: 14pt; font-weight: bold; text-decoration: underline; text-align: center; margin: 8px 0 20px; }

        /* Metadata */
        .report-meta { margin-bottom: 16px; font-size: 11pt; }
        .report-meta p { margin: 4px 0; }

        /* Table (certification style) */
        .analytics-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
            margin-bottom: 20px;
        }
        .analytics-table th,
        .analytics-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: left;
        }
        .analytics-table th {
            background: #f3f4f6;
            font-weight: bold;
            text-align: center;
        }
        .analytics-table th.col-program,
        .analytics-table td.col-program { text-align: left; }
        .analytics-table th.col-num,
        .analytics-table td.col-num { text-align: center; }
        .analytics-table th.col-status,
        .analytics-table td.col-status { text-align: center; }

        /* Summary paragraph */
        .summary-para { margin: 20px 0 28px; text-align: left; line-height: 1.5; }

        /* Signatory block */
        .signatory-block { margin-top: 32px; display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; }
        .signatory { flex: 1; max-width: 220px; }
        .signatory-line { border-bottom: 1px solid #000; height: 28px; margin-bottom: 4px; }
        .signatory-name { font-weight: bold; font-size: 11pt; }
        .signatory-title { font-size: 10pt; }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 16px;">
        <a href="{{ $backUrl ?? '#' }}" style="color: #1e40af; text-decoration: none;">← Back</a>
        <button type="button" onclick="window.print();" style="margin-left: 12px; padding: 8px 16px; background: #1e40af; color: #fff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">Print Report</button>
    </div>

    {{-- 1. DCC Header (identical to certification) --}}
    <header class="dcc-header">
        <img src="{{ asset('images/logo.png') }}" alt="DCC" class="dcc-logo" onerror="this.style.display='none'">
        <p class="dcc-line-republic">Republic of the Philippines</p>
        <p class="dcc-line-college">DARAGA COMMUNITY COLLEGE</p>
        <p class="dcc-line-address">Salvacion, Daraga, Albay, 4501</p>
        <p class="dcc-office">Office of the College Registrar</p>
        <p class="dcc-title">ENROLLMENT ANALYTICS SUMMARY</p>
    </header>

    {{-- 2. Report Metadata --}}
    <div class="report-meta">
        <p><strong>Period Covered:</strong> {{ $academicYear ?? ($filters['academic_year'] ?? 'All') }} / {{ $semester ?? ($filters['semester'] ?? 'All') }}</p>
        <p><strong>Date Generated:</strong> {{ now()->format('F d, Y') }}</p>
    </div>

    {{-- 3. Analytics Data Table (certification style: thin black borders, 100% width) --}}
    <table class="analytics-table" role="grid">
        <thead>
            <tr>
                <th scope="col" class="col-program">Program/Course</th>
                <th scope="col" class="col-num">Year Level</th>
                <th scope="col" class="col-num">Male Count</th>
                <th scope="col" class="col-num">Female Count</th>
                <th scope="col" class="col-num">Total Enrolled</th>
                <th scope="col" class="col-status">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($enrollmentSummaryRows ?? [] as $row)
                <tr>
                    <td class="col-program">{{ $row->program ?? 'N/A' }}</td>
                    <td class="col-num">{{ $row->year_level ?? '—' }}</td>
                    <td class="col-num">{{ (int) ($row->male_count ?? 0) }}</td>
                    <td class="col-num">{{ (int) ($row->female_count ?? 0) }}</td>
                    <td class="col-num">{{ (int) ($row->total_enrolled ?? 0) }}</td>
                    <td class="col-status">{{ $row->status_text ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 12px;">No enrollment data for the selected period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- 4. Summary Paragraph --}}
    <p class="summary-para">
        This document serves to certify the total enrollment figures for the specified term. Based on system records, the institution has attained a total of <strong>{{ number_format($totalEnrolled ?? $totalStudents ?? 0) }}</strong> students for the period covered above. This enrollment analytics summary is issued for official reference and reporting purposes.
    </p>

    {{-- 5. Official Signatory Block (mirror certification positions) --}}
    <div class="signatory-block">
        <div class="signatory" style="text-align: left;">
            <div class="signatory-line"></div>
            <p class="signatory-name">JAY F. NACE, LPT</p>
            <p class="signatory-title">College Registrar I</p>
        </div>
        <div class="signatory" style="text-align: left;">
            <div class="signatory-line"></div>
            <p class="signatory-name">JOEY M. ZAMORA, EdD</p>
            <p class="signatory-title">College Administrator</p>
        </div>
    </div>

    <div class="no-print" style="margin-top: 24px;">
        <button type="button" onclick="window.print();" style="padding: 8px 16px; background: #1e40af; color: #fff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">Print Report</button>
    </div>
</body>
</html>
