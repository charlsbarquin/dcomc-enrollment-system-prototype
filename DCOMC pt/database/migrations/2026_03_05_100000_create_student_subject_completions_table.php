<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Subject completion history: passed, failed, dropped, credited per (student, subject, term).
     * Used to prevent irregular students from retaking subjects they already completed (passed/credited).
     */
    public function up(): void
    {
        if (Schema::hasTable('student_subject_completions')) {
            return;
        }

        Schema::create('student_subject_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('school_year', 100)->nullable();
            $table->string('semester', 100)->nullable();
            $table->string('status', 50)->comment('passed, failed, dropped, credited, withdrawn');
            $table->decimal('grade', 5, 2)->nullable();
            $table->string('credited_from', 255)->nullable()->comment('e.g. transfer institution');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(
                ['student_id', 'subject_id', 'school_year', 'semester'],
                'student_subject_completions_stu_sub_sy_sem_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_subject_completions');
    }
};
