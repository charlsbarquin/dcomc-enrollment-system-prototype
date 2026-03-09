<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'schedule_selection_limit')) {
                $table->unsignedTinyInteger('schedule_selection_limit')->nullable()->after('max_units');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'schedule_selection_limit')) {
                $table->dropColumn('schedule_selection_limit');
            }
        });
    }
};
