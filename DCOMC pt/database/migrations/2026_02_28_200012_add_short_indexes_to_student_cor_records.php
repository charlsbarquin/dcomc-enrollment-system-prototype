<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_cor_records', function (Blueprint $table) {
            $table->index(['student_id', 'program_id', 'semester', 'school_year'], 'stucor_stu_prog_sem_sy');
            $table->index(['program_id', 'year_level', 'block_id', 'shift', 'semester', 'school_year'], 'stucor_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::table('student_cor_records', function (Blueprint $table) {
            $table->dropIndex('stucor_stu_prog_sem_sy');
            $table->dropIndex('stucor_scope_idx');
        });
    }
};
