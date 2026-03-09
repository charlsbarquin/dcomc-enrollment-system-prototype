<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\BlockChangeRequest;
use App\Models\User;
use App\Models\ClassSchedule;
use App\Services\AcademicCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrarOperationsController extends Controller
{
    public function blocks(Request $request): View
    {
        $selectedLabel = AcademicCalendarService::getSelectedSchoolYearLabel();
        $blocksQuery = Block::query()
            ->where('is_active', true)
            ->orderBy('program')
            ->orderBy('year_level')
            ->orderBy('semester')
            ->orderBy('code');
        if ($selectedLabel !== null && $selectedLabel !== '') {
            $activeLabel = AcademicCalendarService::getActiveSchoolYearLabel();
            $blocksQuery->where(function ($q) use ($selectedLabel, $activeLabel) {
                $q->where('school_year_label', $selectedLabel);
                if ($activeLabel !== null && $activeLabel === $selectedLabel) {
                    $q->orWhereNull('school_year_label')->orWhere('school_year_label', '');
                }
            });
        }
        if ($request->filled('program')) {
            $blocksQuery->where('program', $request->program);
        }
        if ($request->filled('year_level')) {
            $blocksQuery->where('year_level', $request->year_level);
        }
        if ($request->filled('semester')) {
            $blocksQuery->where('semester', $request->semester);
        }
        $perPage = (int) $request->get('per_page', 15);
        $perPage = $perPage >= 5 && $perPage <= 100 ? $perPage : 15;
        $blocks = $blocksQuery->paginate($perPage)->withQueryString();

        $blockIds = $blocks->pluck('id')->all();
        $currentCountsByBlock = Block::currentCountsByBlockForSchoolYear($blockIds, $selectedLabel);
        foreach ($blocks as $block) {
            $block->current_count_for_year = $currentCountsByBlock[(int) $block->id] ?? 0;
        }

        $baseQuery = Block::query()->where('is_active', true);
        if ($selectedLabel !== null && $selectedLabel !== '') {
            $activeLabel = AcademicCalendarService::getActiveSchoolYearLabel();
            $baseQuery->where(function ($q) use ($selectedLabel, $activeLabel) {
                $q->where('school_year_label', $selectedLabel);
                if ($activeLabel !== null && $activeLabel === $selectedLabel) {
                    $q->orWhereNull('school_year_label')->orWhere('school_year_label', '');
                }
            });
        }
        $programs = (clone $baseQuery)->whereNotNull('program')->distinct()->pluck('program')->filter()->sort()->values();
        $yearLevels = (clone $baseQuery)->whereNotNull('year_level')->distinct()->pluck('year_level')->filter()->sort()->values();
        $semesters = (clone $baseQuery)->whereNotNull('semester')->distinct()->pluck('semester')->filter()->sort()->values();

        return view('dashboards.registrar-blocks', compact('blocks', 'programs', 'yearLevels', 'semesters'));
    }

    public function blockChangeRequests(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 15);
        $perPage = $perPage >= 5 && $perPage <= 100 ? $perPage : 15;
        $requests = BlockChangeRequest::query()
            ->with(['student', 'currentBlock', 'requestedBlock', 'replacementStudent', 'reviewer'])
            ->latest()
            ->paginate($perPage)->withQueryString();

        return view('dashboards.registrar-block-change-requests', compact('requests'));
    }

    public function approveBlockChange(Request $request, int $id): RedirectResponse
    {
        if (auth()->user()?->effectiveRole() === User::ROLE_STAFF) {
            abort(403, 'Staff cannot approve block change requests. Only registrar or admin can transfer or move students.');
        }
        $request->validate([
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $changeRequest = BlockChangeRequest::with(['student', 'currentBlock', 'requestedBlock', 'replacementStudent'])->findOrFail($id);

        if ($changeRequest->status !== 'pending') {
            return back()->withErrors(['request' => 'This request has already been processed.']);
        }

        if (! $changeRequest->student) {
            return back()->withErrors(['request' => 'Student account not found for this request.']);
        }

        if (! $changeRequest->replacementStudent) {
            return back()->withErrors(['request' => 'Replacement student is required and must be valid.']);
        }

        $oldBlock = $changeRequest->currentBlock;
        $student = $changeRequest->student;
        $replacementStudent = $changeRequest->replacementStudent;

        $targetBlock = $changeRequest->requestedBlock;
        if (! $targetBlock && $changeRequest->requested_shift) {
            $program = $student->block?->program ?? $student->course;
            $yearLevel = $student->block?->year_level ?? $student->year_level;
            $semester = $student->block?->semester ?? $student->semester;
            $selectedLabel = AcademicCalendarService::getSelectedSchoolYearLabel();
            $targetBlockQuery = Block::query()
                ->where('is_active', true)
                ->where('year_level', $yearLevel)
                ->where('semester', $semester)
                ->where('shift', $changeRequest->requested_shift)
                ->where(function ($query) use ($program) {
                    $query->where('program', $program)->orWhereNull('program');
                });
            if ($selectedLabel !== null && $selectedLabel !== '') {
                $targetBlockQuery->where('school_year_label', $selectedLabel);
            }
            $targetBlock = $targetBlockQuery
                ->whereRaw('COALESCE(current_size, 0) < COALESCE(capacity, COALESCE(max_students, 50))')
                ->orderBy('id')
                ->first();
        }

        if (! $targetBlock) {
            return back()->withErrors(['request' => 'No valid target block found for this request.']);
        }

        if ((int) ($replacementStudent->block_id ?? 0) !== (int) $targetBlock->id) {
            return back()->withErrors(['request' => 'Replacement student must currently belong to the requested target block.']);
        }

        if ($replacementStudent->id === $student->id || $replacementStudent->role !== User::ROLE_STUDENT) {
            return back()->withErrors(['request' => 'Replacement must be a different valid student account.']);
        }

        $student->update([
            'block_id' => $targetBlock->id,
            'shift' => $changeRequest->requested_shift ?: $student->shift,
            'course' => $targetBlock->program ?? $student->course,
            'year_level' => $targetBlock->year_level ?? $student->year_level,
            'semester' => $targetBlock->semester ?? $student->semester,
        ]);

        if ($oldBlock) {
            $replacementStudent->update([
                'block_id' => $oldBlock->id,
                'shift' => $oldBlock->shift ?: $replacementStudent->shift,
            ]);
        }

        // Swap keeps both block counts stable. Recompute a safe baseline.
        if ($oldBlock) {
            $oldBlock->update([
                'current_size' => User::query()->where('block_id', $oldBlock->id)->count(),
            ]);
        }
        $targetBlock->update([
            'current_size' => User::query()->where('block_id', $targetBlock->id)->count(),
        ]);

        $changeRequest->update([
            'status' => 'approved',
            'review_notes' => $request->input('review_notes'),
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Block/shift change request approved successfully.');
    }

    public function rejectBlockChange(Request $request, int $id): RedirectResponse
    {
        if (auth()->user()?->effectiveRole() === User::ROLE_STAFF) {
            abort(403, 'Staff cannot reject block change requests. Only registrar or admin can transfer or move students.');
        }
        $request->validate([
            'review_notes' => ['required', 'string', 'max:1000'],
        ]);

        $changeRequest = BlockChangeRequest::findOrFail($id);

        if ($changeRequest->status !== 'pending') {
            return back()->withErrors(['request' => 'This request has already been processed.']);
        }

        $changeRequest->update([
            'status' => 'rejected',
            'review_notes' => $request->input('review_notes'),
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Block/shift change request rejected.');
    }

    public function deleteBlock(int $id)
    {
        $block = Block::findOrFail($id);

        $hasStudents = User::query()->where('block_id', $block->id)->exists();
        if ($hasStudents) {
            return back()->withErrors(['block' => 'Cannot remove this block because it still has students assigned.']);
        }

        $hasSchedules = ClassSchedule::query()->where('block_id', $block->id)->exists();
        if ($hasSchedules) {
            return back()->withErrors(['block' => 'Cannot remove this block because it is still used in class schedules.']);
        }

        $block->delete();

        return back()->with('success', 'Block removed successfully.');
    }
}

