<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('rooms', 'department_id')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->foreignId('department_id')->nullable()->after('id')->constrained('departments')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            if (Schema::hasColumn('rooms', 'department_id')) {
                $table->dropConstrainedForeignId('department_id');
            }
        });
    }
};
