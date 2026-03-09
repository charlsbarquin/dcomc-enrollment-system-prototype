<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Allow subject-only slots (no day/time) for Program Schedule (registrar).
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE scope_schedule_slots MODIFY day_of_week TINYINT UNSIGNED NULL');
            DB::statement('ALTER TABLE scope_schedule_slots MODIFY start_time TIME NULL');
            DB::statement('ALTER TABLE scope_schedule_slots MODIFY end_time TIME NULL');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE scope_schedule_slots MODIFY day_of_week TINYINT UNSIGNED NOT NULL DEFAULT 1');
            DB::statement('ALTER TABLE scope_schedule_slots MODIFY start_time TIME NOT NULL');
            DB::statement('ALTER TABLE scope_schedule_slots MODIFY end_time TIME NOT NULL');
        }
    }
};
