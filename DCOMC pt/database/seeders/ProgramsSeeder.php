<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramsSeeder extends Seeder
{
    /** Program name => code (single source for abbreviations). */
    private static array $nameToCode = [
        'Bachelor of Elementary Education' => 'BEED',
        'Bachelor of Secondary Education' => 'BSED',
        'Bachelor of Secondary Education Major in English' => 'BSED-ENG',
        'Bachelor of Secondary Education Major in Filipino' => 'BSED-FIL',
        'Bachelor of Secondary Education Major in Mathematics' => 'BSED-MATH',
        'Bachelor of Secondary Education Major in Science' => 'BSED-SCI',
        'Bachelor of Secondary Education Major in Social Studies' => 'BSED-SS',
        'Bachelor of Secondary Education Major in Values Education' => 'BSED-VE',
        'Bachelor of Culture and Arts Education' => 'BCAED',
        'Bachelor of Physical Education' => 'BPED',
        'Bachelor of Technical-Vocational Teacher Education' => 'BTVTED',
        'Bachelor of Technical-Vocational Teacher Education Major in Food Service Management' => 'BTVTED-FSM',
        'Bachelor of Technical-Vocational Teacher Education Major in Garments Fashion and Design' => 'BTVTED-GFD',
        'Bachelor of Science in Entrepreneurship' => 'BSE',
        'Teacher Certificate Program' => 'TCP',
    ];

    public function run(): void
    {
        $names = config('fee_programs.programs', []);
        foreach ($names as $name) {
            $program = Program::firstOrCreate(
                ['program_name' => $name],
                ['program_name' => $name]
            );
            if (($program->code === null || $program->code === '') && isset(self::$nameToCode[$name])) {
                $program->update(['code' => self::$nameToCode[$name]]);
            }
        }
    }
}
