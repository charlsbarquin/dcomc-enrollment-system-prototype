<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Normalize users.course: where value matches a program code (e.g. BEED), set to program_name.
     * Keeps program name as single canonical value; code is for display only.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('programs')) {
            return;
        }

        $programs = DB::table('programs')->whereNotNull('code')->get(['id', 'program_name', 'code']);
        foreach ($programs as $p) {
            DB::table('users')
                ->where('course', $p->code)
                ->orWhere('course', trim($p->code))
                ->update(['course' => $p->program_name]);
        }
    }

    public function down(): void
    {
        // Cannot safely reverse
    }
};
