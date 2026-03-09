<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\BlockChangeRequest;
use App\Models\ClassSchedule;
use App\Models\Fee;
use App\Models\FormResponse;
use App\Models\ScheduleTemplate;
use App\Models\SchoolYear;
use App\Models\StudentCorRecord;
use App\Models\Subject;
use App\Services\CorViewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentServicesController extends Controller
{
    public function requestBlockChange(Request $request): RedirectResponse
    {
        $student = $request->user();

        $validated = $request->validate([
            'requested_block_id' => ['nullable', 'exists:blocks,id'],
            'replacement_student_id' => ['required', 'exists:users,id'],
            'requested_shift' => ['nullable', 'in:day,night'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $pendingExists = BlockChangeRequest::query()
            ->where('student_id', $student->id)
            ->where('status', 'pending')
            ->exists();

        if ($pendingExists) {
            return back()->withErrors(['request' => 'You already have a pending block/shift change request.']);
        }

        if (! $validated['requested_block_id'] && ! $validated['requested_shift']) {
            return back()->withErrors(['request' => 'Select a requested block or requested shift.']);
        }

        if ((int) $validated['replacement_student_id'] === (int) $student->id) {
            return back()->withErrors(['request' => 'Replacement student cannot be yourself.']);
        }

        $replacementStudent = \App\Models\User::find($validated['replacement_student_id']);
        if (! $replacementStudent || $replacementStudent->role !== \App\Models\User::ROLE_STUDENT) {
            return back()->withErrors(['request' => 'Replacement must be a valid student account.']);
        }

        if ($validated['requested_block_id']) {
            $targetBlock = Block::find($validated['requested_block_id']);
            if (! $targetBlock) {
                return back()->withErrors(['request' => 'Requested block not found.']);
            }

            if (
                $student->year_level &&
                $student->semester &&
                ($targetBlock->year_level !== $student->year_level || $targetBlock->semester !== $student->semester)
            ) {
                return back()->withErrors(['request' => 'You can only request blocks that match your year and semester.']);
            }

            if ((int) ($replacementStudent->block_id ?? 0) !== (int) $targetBlock->id) {
                return back()->withErrors(['request' => 'Replacement student must come from the requested target block.']);
            }
        }

        BlockChangeRequest::create([
            'student_id' => $student->id,
            'current_block_id' => $student->block_id,
            'requested_block_id' => $validated['requested_block_id'] ?? null,
            'replacement_student_id' => $validated['replacement_student_id'] ?? null,
            'requested_shift' => $validated['requested_shift'] ?? null,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return back()->with('success', 'Block/shift change request submitted. Please wait for registrar approval.');
    }

    public function cor(Request $request): View|RedirectResponse
    {
        $student = auth()->user();
        if (! $student->block_id) {
            return redirect()->route('student.dashboard')->with('error', 'No block assigned yet. COR is not available.');
        }
        $requestedSchoolYear = trim($request->string('school_year')->toString());
        $data = app(CorViewService::class)->buildCorData(
            $student,
            $requestedSchoolYear !== '' ? $requestedSchoolYear : null
        );
        if (isset($data['deployedTemplate']) && $data['deployedTemplate'] === null && empty($data['corSubjects']) && ! $data['noCorForSelectedYear']) {
            $student->load('block');
            $block = $student->block;
            if ($block && ! $student->isIrregularType()) {
                return redirect()->route('student.dashboard')->with(
                    'error',
                    'No schedule has been deployed for your cohort (Program / Year / Semester / Block). COR is not available until the dean deploys a schedule for your block from Schedule by Program.'
                );
            }
        }
        return view('dashboards.student-cor', $data);
    }
}

