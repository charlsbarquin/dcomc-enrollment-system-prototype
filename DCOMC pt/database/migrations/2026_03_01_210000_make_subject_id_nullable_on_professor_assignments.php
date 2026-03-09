<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professor_subject_assignments', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
        });

        DB::statement('ALTER TABLE professor_subject_assignments MODIFY subject_id BIGINT UNSIGNED NULL');

        Schema::table('professor_subject_assignments', function (Blueprint $table) {
            $table->foreign('subject_id')->references('id')->on('subjects')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('professor_subject_assignments', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
        });

        DB::statement('ALTER TABLE professor_subject_assignments MODIFY subject_id BIGINT UNSIGNED NOT NULL');

        Schema::table('professor_subject_assignments', function (Blueprint $table) {
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
        });
    }
};
