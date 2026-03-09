<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add professor_id to link deployed COR records to professors for View Profile.
     */
    public function up(): void
    {
        Schema::table('student_cor_records', function (Blueprint $table) {
            if (!Schema::hasColumn('student_cor_records', 'professor_id')) {
                $table->foreignId('professor_id')->nullable()->after('subject_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('student_cor_records', 'is_overload')) {
                $table->boolean('is_overload')->default(false)->after('professor_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_cor_records', function (Blueprint $table) {
            if (Schema::hasColumn('student_cor_records', 'professor_id')) {
                $table->dropForeign(['professor_id']);
            }
            if (Schema::hasColumn('student_cor_records', 'is_overload')) {
                $table->dropColumn('is_overload');
            }
        });
    }
};
