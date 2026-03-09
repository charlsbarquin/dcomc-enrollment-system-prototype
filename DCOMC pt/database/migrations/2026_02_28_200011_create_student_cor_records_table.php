<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Immutable COR deployment snapshot per student per subject.
     * Archive must not change when schedule/professor/room are edited later.
     */
    public function up(): void
    {
        if (!Schema::hasTable('student_cor_records')) {
            Schema::create('student_cor_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
                $table->string('professor_name_snapshot', 255)->nullable();
                $table->string('room_name_snapshot', 255)->nullable();
                $table->string('days_snapshot', 255)->nullable();
                $table->time('start_time_snapshot')->nullable();
                $table->time('end_time_snapshot')->nullable();
                $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
                $table->string('year_level', 100)->nullable();
                $table->foreignId('block_id')->nullable()->constrained('blocks')->nullOnDelete();
                $table->string('shift', 50)->nullable();
                $table->string('semester', 100)->nullable();
                $table->string('school_year', 100)->nullable();
                $table->foreignId('deployed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('deployed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_cor_records');
    }
};
