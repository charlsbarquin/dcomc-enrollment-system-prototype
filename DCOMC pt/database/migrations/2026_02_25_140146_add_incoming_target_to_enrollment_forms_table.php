<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('enrollment_forms', function (Blueprint $table) {
            $table->string('incoming_year_level')->nullable()->after('assigned_semester');
            $table->string('incoming_semester')->nullable()->after('incoming_year_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollment_forms', function (Blueprint $table) {
            $table->dropColumn(['incoming_year_level', 'incoming_semester']);
        });
    }
};
