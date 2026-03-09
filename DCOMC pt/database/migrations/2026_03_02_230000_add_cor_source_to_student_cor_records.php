<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * COR source: 'schedule_by_program' (dean/regular) vs 'create_schedule' (registrar/shifters).
     * View COR shows create_schedule for shifters; regular/transferee/returnee see schedule_by_program.
     */
    public function up(): void
    {
        Schema::table('student_cor_records', function (Blueprint $table) {
            $table->string('cor_source', 50)->nullable()->after('school_year');
        });

        \Illuminate\Support\Facades\DB::table('student_cor_records')->update(['cor_source' => 'schedule_by_program']);
    }

    public function down(): void
    {
        Schema::table('student_cor_records', function (Blueprint $table) {
            $table->dropColumn('cor_source');
        });
    }
};
