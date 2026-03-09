<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('enrollment_forms', function (Blueprint $table) {
            $table->string('assigned_year')->nullable();
            $table->string('assigned_semester')->nullable();
        });
    }

    public function down()
    {
        Schema::table('enrollment_forms', function (Blueprint $table) {
            $table->dropColumn(['assigned_year', 'assigned_semester']);
        });
    }
};