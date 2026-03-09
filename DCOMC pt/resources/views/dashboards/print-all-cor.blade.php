<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print All COR - {{ $block->code ?? 'Block' }} - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcc-certification-styles')
    <style>
        .cor-page { page-break-after: always; }
        .cor-page:last-child { page-break-after: auto; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="max-w-5xl mx-auto p-6">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4 no-print">
            <h1 class="text-2xl font-bold text-gray-800">Print All COR — {{ $block->code ?? $block->name ?? 'Block' }}</h1>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ $back_url }}" class="px-4 py-2 bg-gray-700 text-white rounded">Back to Students Explorer</a>
                <button type="button" onclick="window.print()" class="px-4 py-2 bg-green-700 text-white rounded">Print all {{ count($corDataList) }} COR(s)</button>
            </div>
        </div>

        @foreach($corDataList as $data)
            @php
                $student = $data['student'];
                $corSubjects = $data['corSubjects'] ?? [];
                $corFees = $data['corFees'] ?? [];
                $schoolYear = $data['schoolYear'] ?? 'N/A';
                $semester = $data['semester'] ?? 'N/A';
                $schedules = $data['schedules'] ?? collect();
                $noCorForSelectedYear = $data['noCorForSelectedYear'] ?? false;
                $selectedSchoolYear = $data['selectedSchoolYear'] ?? null;
                $corFromRegistrar = $data['corFromRegistrar'] ?? false;
            @endphp
            <div class="cor-page max-w-5xl mt-6">
                @include('dashboards.partials.cor-card')
            </div>
        @endforeach
    </div>
</body>
</html>
