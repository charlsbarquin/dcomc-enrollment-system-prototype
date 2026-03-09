<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add scope index if table exists but index was missing (e.g. after long name fix).
     */
    public function up(): void
    {
        if (!Schema::hasTable('scope_schedule_slots')) {
            return;
        }
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            $hasIndex = DB::select("SHOW INDEX FROM scope_schedule_slots WHERE Key_name = 'scope_sched_slots_scope_idx'");
            if (empty($hasIndex)) {
                Schema::table('scope_schedule_slots', function (Blueprint $table) {
                    $table->index(['program_id', 'academic_year_level_id', 'semester'], 'scope_sched_slots_scope_idx');
                });
            }
        } else {
            Schema::table('scope_schedule_slots', function (Blueprint $table) {
                $table->index(['program_id', 'academic_year_level_id', 'semester'], 'scope_sched_slots_scope_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('scope_schedule_slots')) {
            Schema::table('scope_schedule_slots', function (Blueprint $table) {
                $table->dropIndex('scope_sched_slots_scope_idx');
            });
        }
    }
};
