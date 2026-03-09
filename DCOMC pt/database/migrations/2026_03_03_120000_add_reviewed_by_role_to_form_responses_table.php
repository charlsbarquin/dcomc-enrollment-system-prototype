<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Stores the acting role (e.g. staff vs registrar) when a response is approved/rejected.
     */
    public function up(): void
    {
        Schema::table('form_responses', function (Blueprint $table) {
            $table->string('reviewed_by_role', 32)->nullable()->after('reviewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_responses', function (Blueprint $table) {
            $table->dropColumn('reviewed_by_role');
        });
    }
};
