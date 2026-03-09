<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Program;
use App\Models\AcademicYearLevel;
use App\Models\Block;
use App\Models\ScopeScheduleSlot;
use App\Models\Subject;
use App\Models\StudentCorRecord;

class CorArchiveFetchTest extends TestCase
{
    use RefreshDatabase;

    public function test_dean_can_fetch_archive_and_archive_records_appear_in_cor_archive()
    {
        // Create basic data
        $deptId = 1;
        $program = Program::create(['program_name' => 'Test Program', 'code' => 'TP', 'department_id' => $deptId]);
        $dean = User::factory()->create(['role' => 'dean', 'department_id' => $deptId]);

        $yearLevel = AcademicYearLevel::create(['name' => '1st Year', 'is_active' => true]);

        $block = Block::create([
            'program_id' => $program->id,
            'code' => 'TP 1 - 1',
            'name' => 'TP 1 - 1',
            'year_level' => $yearLevel->name,
            'semester' => 'First Semester',
            'shift' => 'day',
            'is_active' => true,
        ]);

        $subject = Subject::create(['code' => 'SUBJ1', 'title' => 'Subject One', 'units' => 3, 'is_active' => true]);

        ScopeScheduleSlot::create([
            'program_id' => $program->id,
            'academic_year_level_id' => $yearLevel->id,
            'semester' => 'First Semester',
            'school_year' => '2025-2026',
            'subject_id' => $subject->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room_id' => null,
            'professor_id' => null,
        ]);

        $this->actingAs($dean);

        $response = $this->post(route('dean.schedule.fetch-cor'), [
            'program_id' => $program->id,
            'academic_year_level_id' => $yearLevel->id,
            'block_id' => $block->id,
            'shift' => $block->shift,
            'semester' => 'First Semester',
            'school_year' => '2025-2026',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('student_cor_records', [
            'block_id' => $block->id,
            'program_id' => $program->id,
            'semester' => 'First Semester',
            'school_year' => '2025-2026',
        ]);

        $showResp = $this->get(route('cor.archive.show', ['programId' => $program->id, 'yearLevel' => $yearLevel->name, 'semester' => 'First Semester', 'deployedBlock' => $block->id]));
        $showResp->assertStatus(200);
        $showResp->assertSee('SUBJ1');
    }
}
