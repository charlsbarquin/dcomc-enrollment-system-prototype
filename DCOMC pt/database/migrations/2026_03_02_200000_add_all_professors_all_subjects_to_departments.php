<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (!Schema::hasColumn('departments', 'all_professors_all_subjects')) {
                $table->boolean('all_professors_all_subjects')->default(false)->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'all_professors_all_subjects')) {
                $table->dropColumn('all_professors_all_subjects');
            }
        });
    }
};
