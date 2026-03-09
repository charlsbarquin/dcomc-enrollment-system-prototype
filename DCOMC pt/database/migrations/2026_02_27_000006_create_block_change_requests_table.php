<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('block_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('current_block_id')->nullable()->constrained('blocks')->nullOnDelete();
            $table->foreignId('requested_block_id')->nullable()->constrained('blocks')->nullOnDelete();
            $table->foreignId('replacement_student_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('requested_shift', ['day', 'night'])->nullable();
            $table->text('reason');
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_change_requests');
    }
};

