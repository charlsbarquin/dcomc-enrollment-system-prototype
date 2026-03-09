<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Masterlist - {{ $block->code ?? $block->name ?? 'Block' }} - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcc-certification-styles')
</head>
<body class="bg-gray-100">
    <div class="dcc-print-page max-w-5xl mx-auto p-6" style="background: #fff;">
        <div class="no-print flex flex-wrap items-center justify-between gap-4 mb-4">
            <h1 class="text-2xl font-bold text-gray-800">Class Masterlist — {{ $block->code ?? $block->name ?? 'Block' }}</h1>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ $back_url }}" class="px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-800">Back to Students Explorer</a>
                <button type="button" onclick="window.print()" class="px-4 py-2 bg-blue-700 text-white rounded hover:bg-blue-800">Print list</button>
            </div>
        </div>

        @php
            $programLabel = is_string($block->program ?? null) ? $block->program : ($block->program?->program_name ?? $block->getAttribute('program') ?? '—');
            $yearLevel = $block->year_level ?? '—';
            $semester = $block->semester ?? '—';
            $schoolYearLabel = $block->school_year_label ?? '—';
        @endphp

        @include('dashboards.partials.dcc-certification-header', ['documentTitle' => 'CLASS MASTERLIST'])

        <div class="dcc-cert-body">
            <p class="to-whom">To Whom It May Concern:</p>
            <p class="narrative">On the basis of the records on file in this office, we hereby certify that the following is the class masterlist for <strong>{{ $block->code ?? $block->name ?? 'Block' }}</strong> — {{ $programLabel }}, Year Level {{ $yearLevel }}, {{ $semester }}, Academic Year {{ $schoolYearLabel }}, to wit:</p>
        </div>

        <div class="dcc-table-wrap">
            <table class="dcc-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Program/Year</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $i => $s)
                        @php
                            $last = trim($s->last_name ?? '');
                            $first = trim($s->first_name ?? '');
                            $middle = trim($s->middle_name ?? '');
                            $midInitial = $middle !== '' ? mb_substr($middle, 0, 1) . '.' : '';
                            $fullName = $last !== '' ? $last . ', ' . $first . ($midInitial !== '' ? ' ' . $midInitial : '') : ($first . ($midInitial !== '' ? ' ' . $midInitial : ''));
                            if ($fullName === '') { $fullName = '—'; }
                            $programYear = ($s->course ?? $programLabel) . ' / ' . ($s->year_level ?? $yearLevel);
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $s->school_id ?? '—' }}</td>
                            <td>{{ $fullName }}</td>
                            <td>{{ $programYear }}</td>
                            <td></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding: 24px; text-align: center; color: #6b7280;">No students in this block.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="dcc-date-line">Issued this {{ now()->format('jS \d\a\y \o\f F Y') }} at Daraga, Albay.</p>

        @include('dashboards.partials.dcc-certification-signatory')
        @include('dashboards.partials.dcc-certification-footer')
    </div>
</body>
</html>
