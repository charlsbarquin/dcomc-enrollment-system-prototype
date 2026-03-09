<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add semester and school_year for Schedule by Program professor eligibility.
     * Professors appear in dropdown only when professor_subject_assignments matches
     * (subject_id, semester, school_year). Null = eligible for any.
     */
    public function up(): void
    {
        Schema::table('professor_subject_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('professor_subject_assignments', 'semester')) {
                $table->string('semester', 100)->nullable()->after('subject_id');
            }
            if (!Schema::hasColumn('professor_subject_assignments', 'school_year')) {
                $table->string('school_year', 100)->nullable()->after('semester');
            }
        });
    }

    public function down(): void
    {
        Schema::table('professor_subject_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('professor_subject_assignments', 'semester')) {
                $table->dropColumn('semester');
            }
            if (Schema::hasColumn('professor_subject_assignments', 'school_year')) {
                $table->dropColumn('school_year');
            }
        });
    }
};
