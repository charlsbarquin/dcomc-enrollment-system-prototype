<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Scope-level schedule: one template per (program, year_level, semester).
     * Registrar edits here; can be applied to blocks (class_schedules) later.
     * Same folder structure as Subject Settings (program → year → semester).
     */
    public function up(): void
    {
        Schema::create('scope_schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->foreignId('academic_year_level_id')->constrained('academic_year_levels')->cascadeOnDelete();
            $table->string('semester', 100);
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 1=Mon .. 7=Sun
            $table->time('start_time');
            $table->time('end_time');
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->foreignId('professor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('school_year', 50)->nullable();
            $table->timestamps();

            $table->index(['program_id', 'academic_year_level_id', 'semester'], 'scope_sched_slots_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scope_schedule_slots');
    }
};
