<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\FormResponse;
use App\Models\ClassSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class FinanceMonitoringController extends Controller
{
    public function staffIndex(Request $request): View
    {
        $filters = $this->extractFilters($request);
        $assessments = $this->filteredAssessmentQuery($filters)
            ->with('student')
            ->latest()
            ->get();

        return view('dashboards.staff-assessments', compact('assessments', 'filters'));
    }

    public function unifastIndex(Request $request): View
    {
        $filters = $this->extractFilters($request);
        $assessments = $this->filteredAssessmentQuery($filters)
            ->with('student')
            ->latest()
            ->get();

        return view('dashboards.unifast-assessments', compact('assessments', 'filters'));
    }

    public function exportStaffCsv(Request $request): StreamedResponse
    {
        $filters = $this->extractFilters($request);
        $assessments = $this->filteredAssessmentQuery($filters)->with('student')->latest()->get();

        return $this->downloadCsv($assessments, 'staff-assessments.csv');
    }

    public function exportUnifastCsv(Request $request): StreamedResponse
    {
        $filters = $this->extractFilters($request);
        $assessments = $this->filteredAssessmentQuery($filters)->with('student')->latest()->get();

        return $this->downloadCsv($assessments, 'unifast-assessments.csv');
    }

    public function updateAssessmentStatus(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'assessment_status' => ['required', 'in:pending,reviewed,approved,rejected'],
            'income_classification' => ['nullable', 'string', 'max:100'],
        ]);

        $assessment = Assessment::findOrFail($id);
        $assessment->update([
            'assessment_status' => $validated['assessment_status'],
            'income_classification' => $validated['income_classification'] ?? $assessment->income_classification,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        if ($validated['assessment_status'] === 'approved') {
            $hasSchedule = ClassSchedule::query()
                ->whereHas('block', function ($query) use ($assessment) {
                    $query->whereHas('students', fn ($studentQuery) => $studentQuery->where('id', $assessment->user_id));
                })
                ->exists();

            if ($hasSchedule) {
                FormResponse::query()
                    ->where('user_id', $assessment->user_id)
                    ->whereIn('process_status', ['approved', 'scheduled'])
                    ->latest()
                    ->limit(1)
                    ->update([
                        'process_status' => 'completed',
                        'process_notes' => 'Completed after assessment approval.',
                    ]);
            }
        }

        return back()->with('success', 'Assessment status updated.');
    }

    public function updateUnifastEligibility(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'unifast_eligible' => ['required', 'in:0,1'],
        ]);

        $assessment = Assessment::findOrFail($id);
        $assessment->update([
            'unifast_eligible' => (bool) $validated['unifast_eligible'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        // When UniFAST sets a student as Eligible, complete enrollment so the student dashboard shows Enrolled (green) and COR becomes available.
        if ($validated['unifast_eligible']) {
            $hasSchedule = ClassSchedule::query()
                ->whereHas('block', function ($query) use ($assessment) {
                    $query->whereHas('students', fn ($studentQuery) => $studentQuery->where('id', $assessment->user_id));
                })
                ->exists();

            if ($hasSchedule) {
                FormResponse::query()
                    ->where('user_id', $assessment->user_id)
                    ->whereIn('process_status', ['approved', 'scheduled'])
                    ->latest()
                    ->limit(1)
                    ->update([
                        'process_status' => 'completed',
                        'process_notes' => 'Completed after UniFAST eligibility approval.',
                    ]);
            }
        }

        return back()->with('success', 'UniFAST eligibility updated.');
    }

    private function extractFilters(Request $request): array
    {
        $request->validate([
            'status' => ['nullable', 'in:pending,reviewed,approved,rejected'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        return [
            'status' => $request->string('status')->toString(),
            'from_date' => $request->string('from_date')->toString(),
            'to_date' => $request->string('to_date')->toString(),
            'search' => trim($request->string('search')->toString()),
        ];
    }

    private function filteredAssessmentQuery(array $filters)
    {
        return Assessment::query()
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('assessment_status', $value))
            ->when($filters['from_date'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '>=', $value))
            ->when($filters['to_date'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '<=', $value))
            ->when(! empty($filters['search'] ?? null), function ($q) use ($filters) {
                $term = '%' . $filters['search'] . '%';
                $q->whereHas('student', function ($uq) use ($term) {
                    $uq->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            });
    }

    private function downloadCsv($assessments, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($assessments) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Student Name', 'Email', 'Total Assessed', 'Income Classification',
                'Assessment Status', 'UniFAST Eligible', 'Reviewed At',
            ]);

            foreach ($assessments as $assessment) {
                fputcsv($out, [
                    $assessment->student?->name ?? 'N/A',
                    $assessment->student?->email ?? 'N/A',
                    $assessment->total_assessed,
                    $assessment->income_classification,
                    $assessment->assessment_status,
                    $assessment->unifast_eligible ? 'Yes' : 'No',
                    optional($assessment->reviewed_at)->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}

