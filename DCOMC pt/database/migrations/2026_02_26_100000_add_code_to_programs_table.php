<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add program code (e.g. BEED, BSED) as single source for abbreviations.
     * Program name remains the canonical full name; code is for display and block naming.
     */
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->string('code', 32)->nullable()->unique()->after('program_name');
        });

        $nameToCode = [
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

        foreach ($nameToCode as $name => $code) {
            DB::table('programs')->where('program_name', $name)->update(['code' => $code]);
        }

        // Abbreviate any remaining (e.g. first letters) so code is set
        $programs = DB::table('programs')->whereNull('code')->get(['id', 'program_name']);
        foreach ($programs as $p) {
            $words = preg_split('/\s+/', trim($p->program_name));
            $abbr = '';
            foreach ($words as $w) {
                $clean = preg_replace('/[^A-Za-z]/', '', $w);
                if ($clean !== '') {
                    $abbr .= strtoupper(substr($clean, 0, 1));
                }
            }
            if ($abbr !== '') {
                $candidate = $abbr;
                $n = 0;
                while (DB::table('programs')->where('code', $candidate)->exists()) {
                    $candidate = $abbr . (++$n);
                }
                DB::table('programs')->where('id', $p->id)->update(['code' => $candidate]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
