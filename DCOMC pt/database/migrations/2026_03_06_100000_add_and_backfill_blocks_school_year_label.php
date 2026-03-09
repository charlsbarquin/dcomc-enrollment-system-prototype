<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add school_year_label to blocks if missing and backfill so existing blocks
 * belong to the active (or latest) school year. This restores visibility of
 * 2025-2026 (and other past) data when the user selects that year.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('blocks', 'school_year_label')) {
            Schema::table('blocks', function (Blueprint $table) {
                $table->string('school_year_label', 50)->nullable()->after('semester');
            });
        }

        $label = $this->getDefaultSchoolYearLabel();
        if ($label !== null && $label !== '') {
            DB::table('blocks')
                ->where(function ($q) {
                    $q->whereNull('school_year_label')->orWhere('school_year_label', '');
                })
                ->update(['school_year_label' => $label]);
        }
        // Backfill students (users with role=student) so past years show data.
        if ($label !== null && $label !== '' && Schema::hasColumn('users', 'school_year')) {
            DB::table('users')
                ->where('role', 'student')
                ->where(function ($q) {
                    $q->whereNull('school_year')->orWhere('school_year', '');
                })
                ->update(['school_year' => $label]);
        }
    }

    private function getDefaultSchoolYearLabel(): ?string
    {
        $activeId = DB::table('academic_calendar_settings')->value('active_school_year_id');
        if ($activeId !== null) {
            $label = DB::table('school_years')->where('id', $activeId)->value('label');
            if ($label !== null && $label !== '') {
                return $label;
            }
        }
        return DB::table('school_years')->orderByDesc('start_year')->value('label');
    }

    public function down(): void
    {
        if (Schema::hasColumn('blocks', 'school_year_label')) {
            Schema::table('blocks', function (Blueprint $table) {
                $table->dropColumn('school_year_label');
            });
        }
    }
};
