<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn('enrollment_forms', 'lock_course_major')) {
            DB::table('enrollment_forms')->update(['lock_course_major' => false]);
        }
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn('enrollment_forms', 'lock_course_major')) {
            DB::table('enrollment_forms')->update(['lock_course_major' => true]);
        }
    }
};
