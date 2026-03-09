<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Schedule by Program: scope slots per Program, Year Level, Block, Shift, Semester, School Year.
     */
    public function up(): void
    {
        Schema::table('scope_schedule_slots', function (Blueprint $table) {
            if (!Schema::hasColumn('scope_schedule_slots', 'block_id')) {
                $table->foreignId('block_id')->nullable()->after('academic_year_level_id')->constrained('blocks')->nullOnDelete();
            }
            if (!Schema::hasColumn('scope_schedule_slots', 'shift')) {
                $table->string('shift', 50)->nullable()->after('block_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('scope_schedule_slots', function (Blueprint $table) {
            if (Schema::hasColumn('scope_schedule_slots', 'block_id')) {
                $table->dropForeign(['block_id']);
                $table->dropColumn('block_id');
            }
            if (Schema::hasColumn('scope_schedule_slots', 'shift')) {
                $table->dropColumn('shift');
            }
        });
    }
};
