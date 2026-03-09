<?php

namespace Database\Seeders;

use App\Models\Block;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds blocks (BEED, ENGLISH, FILIPINO, etc.) and 2 test students per block.
 * Students: student3@dcomc.edu.ph through student90@dcomc.edu.ph, password 123456.
 * For testing with dynamic block size, set BLOCKS_STRICT_50=false in .env.
 */
class BlocksAndTestStudentsSeeder extends Seeder
{
    protected string $yearLevel = '1st Year';

    protected string $semester = 'First Semester';

    /** Block code => [program name, shift] */
    protected function blockSpecs(): array
    {
        $beed = 'Bachelor of Elementary Education';
        $english = 'Bachelor of Secondary Education Major in English';
        $filipino = 'Bachelor of Secondary Education Major in Filipino';
        $math = 'Bachelor of Secondary Education Major in Mathematics';
        $science = 'Bachelor of Secondary Education Major in Science';
        $socstud = 'Bachelor of Secondary Education Major in Social Studies';
        $values = 'Bachelor of Secondary Education Major in Values Education';
        $fsm = 'Bachelor of Technical-Vocational Teacher Education Major in Food Service Management';
        $gfd = 'Bachelor of Technical-Vocational Teacher Education Major in Garments Fashion and Design';
        $entrep = 'Bachelor of Science in Entrepreneurship';
        $bcaed = 'Bachelor of Culture and Arts Education';

        $day = 'day';
        $night = 'night';

        return [
            // BEED 1 - 1 through 1 - 10 day; 1 - 11, 1 - 12 night
            'BEED 1 - 1' => [$beed, $day],
            'BEED 1 - 2' => [$beed, $day],
            'BEED 1 - 3' => [$beed, $day],
            'BEED 1 - 4' => [$beed, $day],
            'BEED 1 - 5' => [$beed, $day],
            'BEED 1 - 6' => [$beed, $day],
            'BEED 1 - 7' => [$beed, $day],
            'BEED 1 - 8' => [$beed, $day],
            'BEED 1 - 9' => [$beed, $day],
            'BEED 1 - 10' => [$beed, $day],
            'BEED 1 - 11' => [$beed, $night],
            'BEED 1 - 12' => [$beed, $night],
            // ENGLISH
            'ENGLISH 1 - A' => [$english, $day],
            'ENGLISH 1 - B' => [$english, $day],
            'ENGLISH 1 - C' => [$english, $day],
            // FILIPINO
            'FILIPINO 1 - A' => [$filipino, $day],
            'FILIPINO 1 - B' => [$filipino, $day],
            'FILIPINO 1 - C' => [$filipino, $day],
            // MATH
            'MATH 1 - A' => [$math, $day],
            'MATH 1 - B' => [$math, $day],
            'MATH 1 - C' => [$math, $day],
            // SCIENCE
            'SCIENCE 1 - A' => [$science, $day],
            'SCIENCE 1 - B' => [$science, $day],
            'SCIENCE 1 - C' => [$science, $day],
            // SOCIAL STUDIES
            'SOCIAL STUDIES 1 - A' => [$socstud, $day],
            'SOCIAL STUDIES 1 - B' => [$socstud, $day],
            'SOCIAL STUDIES 1 - C' => [$socstud, $day],
            // VALUES EDUCATION
            'VALUES EDUCATION 1 - A' => [$values, $day],
            'VALUES EDUCATION 1 - B' => [$values, $day],
            'VALUES EDUCATION 1 - C' => [$values, $day],
            'VALUES EDUCATION 1 - D' => [$values, $day],
            'VALUES EDUCATION 1 - E' => [$values, $day],
            // FSM
            'FSM 1 - A' => [$fsm, $day],
            'FSM 1 - B' => [$fsm, $day],
            'FSM 1 - C' => [$fsm, $day],
            // GFD
            'GFD 1 - A' => [$gfd, $day],
            'GFD 1 - B' => [$gfd, $day],
            'GFD 1 - C' => [$gfd, $day],
            // ENTREP
            'ENTREP 1 - A' => [$entrep, $day],
            'ENTREP 1 - B' => [$entrep, $day],
            'ENTREP 1 - C' => [$entrep, $day],
            // BCAED
            'BCAED 1 - A' => [$bcaed, $day],
            'BCAED 1 - B' => [$bcaed, $day],
            'BCAED 1 - C' => [$bcaed, $day],
        ];
    }

    public function run(): void
    {
        $specs = $this->blockSpecs();
        $blocks = [];
        foreach ($specs as $code => [$program, $shift]) {
            $attrs = [
                'program' => $program,
                'year_level' => $this->yearLevel,
                'semester' => $this->semester,
                'shift' => $shift,
                'capacity' => 50,
                'current_size' => 0,
                'is_active' => true,
            ];
            if (Schema::hasColumn('blocks', 'name')) {
                $attrs['name'] = $code;
            }
            if (Schema::hasColumn('blocks', 'max_students')) {
                $attrs['max_students'] = 50;
            }
            $block = Block::firstOrCreate(['code' => $code], $attrs);
            $blocks[] = $block;
        }

        $password = Hash::make('123456');
        $studentIndex = 3; // student3@dcomc.edu.ph

        foreach ($blocks as $block) {
            for ($i = 0; $i < 2; $i++) {
                $email = "student{$studentIndex}@dcomc.edu.ph";
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => "Test Student {$studentIndex}",
                        'password' => $password,
                        'role' => User::ROLE_STUDENT,
                        'profile_completed' => true,
                        'school_id' => 'DCOMC-2025-' . str_pad((string) $studentIndex, 4, '0', STR_PAD_LEFT),
                        'first_name' => 'Test',
                        'last_name' => "Student{$studentIndex}",
                        'course' => $block->program,
                        'year_level' => $block->year_level,
                        'semester' => $block->semester,
                        'block_id' => $block->id,
                        'shift' => $block->shift,
                        'student_status' => 'Regular',
                        'gender' => 'Male',
                    ]
                );
                if (! $user->wasRecentlyCreated && $user->block_id !== $block->id) {
                    $user->update([
                        'block_id' => $block->id,
                        'course' => $block->program,
                        'year_level' => $block->year_level,
                        'semester' => $block->semester,
                        'shift' => $block->shift,
                    ]);
                }
                $studentIndex++;
            }
        }

        // Recalculate current_size for each block
        foreach ($blocks as $block) {
            $block->update([
                'current_size' => User::where('block_id', $block->id)->count(),
            ]);
        }

        $this->command->info('Blocks and test students (2 per block) seeded. Use BLOCKS_STRICT_50=false in .env for dynamic block size.');
    }
}
