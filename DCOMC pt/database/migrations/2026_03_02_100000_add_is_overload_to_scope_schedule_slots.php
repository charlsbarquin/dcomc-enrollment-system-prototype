<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scope_schedule_slots', function (Blueprint $table) {
            if (!Schema::hasColumn('scope_schedule_slots', 'is_overload')) {
                $table->boolean('is_overload')->default(false)->after('professor_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('scope_schedule_slots', function (Blueprint $table) {
            if (Schema::hasColumn('scope_schedule_slots', 'is_overload')) {
                $table->dropColumn('is_overload');
            }
        });
    }
};
