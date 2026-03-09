<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * department_scope: "all" | "education" | "entrepreneurship" for professor visibility.
     * created_by_*: audit when created via Settings > Add Professor.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'department_scope')) {
                $table->string('department_scope', 32)->nullable()->after('department_id');
            }
            if (!Schema::hasColumn('users', 'created_by_role')) {
                $table->string('created_by_role', 32)->nullable()->after('department_scope');
            }
            if (!Schema::hasColumn('users', 'created_by_user_id')) {
                $table->unsignedBigInteger('created_by_user_id')->nullable()->after('created_by_role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'department_scope')) {
                $table->dropColumn('department_scope');
            }
            if (Schema::hasColumn('users', 'created_by_role')) {
                $table->dropColumn('created_by_role');
            }
            if (Schema::hasColumn('users', 'created_by_user_id')) {
                $table->dropColumn('created_by_user_id');
            }
        });
    }
};
