<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cor_scopes')) {
            Schema::create('cor_scopes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
                $table->foreignId('academic_year_level_id')->constrained('academic_year_levels')->restrictOnDelete();
                $table->string('semester', 100);
                $table->string('school_year', 100);
                $table->string('major', 255)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->unique(
                    ['program_id', 'academic_year_level_id', 'semester', 'school_year', 'major'],
                    'cor_scopes_scope_unique'
                );
            });
        }

        if (! Schema::hasTable('cor_scope_subjects')) {
            Schema::create('cor_scope_subjects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cor_scope_id')->constrained('cor_scopes')->cascadeOnDelete();
                $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['cor_scope_id', 'subject_id'], 'cor_scope_subjects_unique');
            });
        }

        if (! Schema::hasTable('cor_scope_fees')) {
            Schema::create('cor_scope_fees', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cor_scope_id')->constrained('cor_scopes')->cascadeOnDelete();
                $table->foreignId('fee_id')->constrained('fees')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['cor_scope_id', 'fee_id'], 'cor_scope_fees_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cor_scope_fees');
        Schema::dropIfExists('cor_scope_subjects');
        Schema::dropIfExists('cor_scopes');
    }
};
