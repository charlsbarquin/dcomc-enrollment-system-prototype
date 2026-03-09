<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Philippine calendar: 1st Sem Aug–Dec, 2nd Sem Jan–May, Midyear Jun–Jul (optional).
     * Active SY controls who is considered "Enrolled"; when SY changes, students are reset to Not Enrolled.
     */
    public function up(): void
    {
        Schema::create('academic_calendar_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('active_school_year_id')->nullable()->constrained('school_years')->nullOnDelete();
            $table->unsignedTinyInteger('first_semester_start_month')->default(8);   // August
            $table->unsignedTinyInteger('first_semester_end_month')->default(12);    // December
            $table->unsignedTinyInteger('second_semester_start_month')->default(1); // January
            $table->unsignedTinyInteger('second_semester_end_month')->default(5);    // May
            $table->unsignedTinyInteger('midyear_start_month')->nullable();          // June
            $table->unsignedTinyInteger('midyear_end_month')->nullable();           // July
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_calendar_settings');
    }
};
