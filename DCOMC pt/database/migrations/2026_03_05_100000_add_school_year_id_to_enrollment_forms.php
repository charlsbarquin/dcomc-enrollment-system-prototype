<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_forms', function (Blueprint $table) {
            if (! Schema::hasColumn('enrollment_forms', 'school_year_id')) {
                $table->foreignId('school_year_id')->nullable()->after('id')->constrained('school_years')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_forms', function (Blueprint $table) {
            if (Schema::hasColumn('enrollment_forms', 'school_year_id')) {
                $table->dropForeign(['school_year_id']);
            }
        });
    }
};
