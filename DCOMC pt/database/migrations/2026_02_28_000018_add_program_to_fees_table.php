<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fees') && ! Schema::hasColumn('fees', 'program')) {
            Schema::table('fees', function (Blueprint $table) {
                $table->string('program')->nullable()->after('year_level');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fees') && Schema::hasColumn('fees', 'program')) {
            Schema::table('fees', function (Blueprint $table) {
                $table->dropColumn('program');
            });
        }
    }
};
