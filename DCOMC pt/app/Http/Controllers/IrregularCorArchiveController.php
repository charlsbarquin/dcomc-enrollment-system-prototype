<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\StudentCorRecord;
use App\Models\User;
use App\Services\AcademicCalendarService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Irregular COR Archive: read-only list of deployed schedules from the
 * Irregularities → Create Schedule tab (cor_source = create_schedule).
 * Separate from the dean's COR Archive (schedule_by_program).
 */
class IrregularCorArchiveController extends Controller
{
    /**
     * List deployments (batches) of irregular COR. Group by deployed_at + deployed_by.
     */
    public function index(Request $request): View
    {
        $selectedLabel = AcademicCalendarService::getSelectedSchoolYearLabel();
        $batchesQuery = StudentCorRecord::query()
            ->where('cor_source', StudentCorRecord::COR_SOURCE_CREATE_SCHEDULE)
            ->selectRaw('DATE(deployed_at) as deploy_date, deployed_by, COUNT(*) as record_count, COUNT(DISTINCT student_id) as student_count, MIN(program_id) as program_id, MIN(year_level) as year_level, MIN(semester) as semester')
            ->whereNotNull('deployed_at')
            ->whereNotNull('deployed_by');
        if ($selectedLabel !== null && $selectedLabel !== '') {
            $batchesQuery->where('school_year', $selectedLabel);
        }
        $batches = $batchesQuery->groupByRaw('DATE(deployed_at), deployed_by')
            ->orderByRaw('DATE(deployed_at) DESC, deployed_by DESC')
            ->get();

        $programIds = $batches->pluck('program_id')->filter()->unique()->values()->all();
        $programs = $programIds ? Program::whereIn('id', $programIds)->get()->keyBy('id') : collect();
        $deployerIds = $batches->pluck('deployed_by')->filter()->unique()->values()->all();
        $deployers = $deployerIds ? User::whereIn('id', $deployerIds)->get()->keyBy('id') : collect();

        return view('dashboards.irregular-cor-archive-index', [
            'batches' => $batches,
            'programs' => $programs,
            'deployers' => $deployers,
        ]);
    }

    /**
     * Show one deployment batch: records (student, subject, slot) for that deploy.
     */
    public function show(Request $request, string $date, int $deployedBy): View
    {
        $selectedLabel = AcademicCalendarService::getSelectedSchoolYearLabel();
        $recordsQuery = StudentCorRecord::query()
            ->where('cor_source', StudentCorRecord::COR_SOURCE_CREATE_SCHEDULE)
            ->whereRaw('DATE(deployed_at) = ?', [$date])
            ->where('deployed_by', $deployedBy)
            ->with(['student', 'subject', 'block'])
            ->orderBy('student_id')
            ->orderBy('subject_id');
        if ($selectedLabel !== null && $selectedLabel !== '') {
            $recordsQuery->where('school_year', $selectedLabel);
        }
        $records = $recordsQuery->get();

        $deployer = User::find($deployedBy);

        return view('dashboards.irregular-cor-archive-show', [
            'date' => $date,
            'deployedBy' => $deployedBy,
            'deployer' => $deployer,
            'records' => $records,
        ]);
    }
}
