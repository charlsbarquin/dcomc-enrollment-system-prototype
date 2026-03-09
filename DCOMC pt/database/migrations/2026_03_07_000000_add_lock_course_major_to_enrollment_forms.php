<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_forms', function (Blueprint $table) {
            if (! Schema::hasColumn('enrollment_forms', 'lock_course_major')) {
                $table->boolean('lock_course_major')->default(true)->after('incoming_semester');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_forms', function (Blueprint $table) {
            if (Schema::hasColumn('enrollment_forms', 'lock_course_major')) {
                $table->dropColumn('lock_course_major');
            }
        });
    }
};
