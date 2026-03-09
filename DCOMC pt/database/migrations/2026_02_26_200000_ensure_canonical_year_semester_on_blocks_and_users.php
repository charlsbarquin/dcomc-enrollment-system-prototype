<?php

use App\Services\AcademicNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure blocks and users use only canonical year_level and semester
     * (1st–4th Year, First/Second Semester) so the tree and data stay consistent.
     */
    public function up(): void
    {
        if (Schema::hasTable('blocks')) {
            $blocks = DB::table('blocks')->get(['id', 'year_level', 'semester']);
            foreach ($blocks as $b) {
                $yl = AcademicNormalizer::normalizeYearLevel($b->year_level);
                $sm = AcademicNormalizer::normalizeSemester($b->semester);
                $up = [];
                if ($yl !== null && $b->year_level !== $yl) {
                    $up['year_level'] = $yl;
                }
                if ($sm !== null && $b->semester !== $sm) {
                    $up['semester'] = $sm;
                }
                if (! empty($up)) {
                    DB::table('blocks')->where('id', $b->id)->update($up);
                }
            }
        }

        if (Schema::hasTable('users')) {
            $users = DB::table('users')->get(['id', 'year_level', 'semester']);
            foreach ($users as $u) {
                $yl = AcademicNormalizer::normalizeYearLevel($u->year_level);
                $sm = AcademicNormalizer::normalizeSemester($u->semester);
                $up = [];
                if ($yl !== null && $u->year_level !== $yl) {
                    $up['year_level'] = $yl;
                }
                if ($sm !== null && $u->semester !== $sm) {
                    $up['semester'] = $sm;
                }
                if (! empty($up)) {
                    DB::table('users')->where('id', $u->id)->update($up);
                }
            }
        }
    }

    public function down(): void
    {
        // Cannot safely reverse normalization
    }
};
