<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $activeId = DB::table('academic_calendar_settings')->value('active_school_year_id');
        if ($activeId === null) {
            $activeId = DB::table('school_years')->orderByDesc('start_year')->value('id');
        }
        if ($activeId !== null) {
            DB::table('enrollment_forms')->whereNull('school_year_id')->update(['school_year_id' => $activeId]);
        }
    }

    public function down(): void
    {
        // Optional: set back to null. Leave as-is for safety.
    }
};
