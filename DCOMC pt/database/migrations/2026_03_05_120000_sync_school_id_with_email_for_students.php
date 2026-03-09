<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Student ID and School ID are the same: sync school_id to email for existing students
     * so login (email) and displayed School ID match.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'student')
            ->whereRaw('(school_id IS NULL OR school_id != email)')
            ->update(['school_id' => DB::raw('email')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reliably restore previous school_id values
    }
};
