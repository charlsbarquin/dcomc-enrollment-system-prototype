<?php

namespace App\Console\Commands;

use App\Models\Block;
use App\Models\User;
use Illuminate\Console\Command;

class SyncStudentCohortFromBlocks extends Command
{
    protected $signature = 'students:sync-cohort-from-blocks';

    protected $description = 'Sync course, year_level, semester on users from their assigned block so stored data matches block (run once or after data fixes).';

    public function handle(): int
    {
        $students = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->whereNotNull('block_id')
            ->with('block')
            ->get();

        $updated = 0;
        foreach ($students as $student) {
            $block = $student->block;
            if (! $block) {
                continue;
            }
            $updates = [];
            if ($block->program !== null && $block->program !== '' && $student->course !== $block->program) {
                $updates['course'] = $block->program;
            }
            if ($block->year_level !== null && $block->year_level !== '' && $student->year_level !== $block->year_level) {
                $updates['year_level'] = $block->year_level;
            }
            if ($block->semester !== null && $block->semester !== '' && $student->semester !== $block->semester) {
                $updates['semester'] = $block->semester;
            }
            if (! empty($updates)) {
                $student->update($updates);
                $updated++;
            }
        }

        $this->info("Synced cohort data from blocks for {$updated} student(s).");
        return self::SUCCESS;
    }
}
