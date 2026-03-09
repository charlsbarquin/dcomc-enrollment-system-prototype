<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * First-Year Student Information Form: remarks and emergency contact.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('registration_remarks')->nullable()->after('dswd_household_no');
            $table->string('emergency_contact_name')->nullable()->after('registration_remarks');
            $table->string('emergency_contact_phone', 50)->nullable()->after('emergency_contact_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['registration_remarks', 'emergency_contact_name', 'emergency_contact_phone']);
        });
    }
};
