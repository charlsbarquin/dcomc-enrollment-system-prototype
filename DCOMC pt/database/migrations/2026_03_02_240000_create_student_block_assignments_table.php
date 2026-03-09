<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Irregular students can be assigned to multiple blocks (e.g. 1st year and 2nd year).
     * One row per (user_id, block_id). Users with block_id set are "regular" (single block);
     * irregulars may have block_id null and use this table for their block roster presence.
     */
    public function up(): void
    {
        Schema::create('student_block_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('block_id')->constrained('blocks')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'block_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_block_assignments');
    }
};
