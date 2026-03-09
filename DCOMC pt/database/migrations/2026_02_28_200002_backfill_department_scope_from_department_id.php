<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backfill department_scope from department_id for existing rows.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'department_scope')) {
            return;
        }
        $educationId = DB::table('departments')->where('name', 'Education')->value('id');
        $entrepId = DB::table('departments')->where('name', 'Entrepreneurship')->value('id');

        if ($educationId) {
            DB::table('users')->where('department_id', $educationId)->whereNull('department_scope')->update(['department_scope' => 'education']);
        }
        if ($entrepId) {
            DB::table('users')->where('department_id', $entrepId)->whereNull('department_scope')->update(['department_scope' => 'entrepreneurship']);
        }
        DB::table('users')->whereNull('department_id')->whereNull('department_scope')->whereNotNull('faculty_type')->update(['department_scope' => 'all']);

        if (!Schema::hasColumn('rooms', 'department_scope')) {
            return;
        }
        if ($educationId) {
            DB::table('rooms')->where('department_id', $educationId)->whereNull('department_scope')->update(['department_scope' => 'education']);
        }
        if ($entrepId) {
            DB::table('rooms')->where('department_id', $entrepId)->whereNull('department_scope')->update(['department_scope' => 'entrepreneurship']);
        }
        DB::table('rooms')->whereNull('department_id')->whereNull('department_scope')->update(['department_scope' => 'all']);
    }

    public function down(): void
    {
        // no-op
    }
};
