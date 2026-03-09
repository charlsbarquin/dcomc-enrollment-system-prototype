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
        Schema::table('users', function (Blueprint $table) {
            $table->string('citizenship')->nullable()->after('civil_status');
            $table->string('place_of_birth')->nullable()->after('date_of_birth');
            $table->string('purok_zone')->nullable()->after('house_number');
            $table->decimal('monthly_income', 10, 2)->nullable()->after('annual_income');
            $table->integer('num_family_members')->nullable()->after('num_siblings');
            $table->string('dswd_household_no')->nullable()->after('num_family_members');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['citizenship', 'place_of_birth', 'purok_zone', 'monthly_income', 'num_family_members', 'dswd_household_no']);
        });
    }
};
