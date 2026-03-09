<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fee_categories')) {
            Schema::create('fee_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('fees')) {
            if (! Schema::hasColumn('fees', 'fee_category_id')) {
                Schema::table('fees', function (Blueprint $table) {
                    $table->foreignId('fee_category_id')->nullable()->after('id')->constrained('fee_categories')->nullOnDelete();
                });
            }
            if (! Schema::hasColumn('fees', 'year_level')) {
                Schema::table('fees', function (Blueprint $table) {
                    $table->string('year_level')->nullable()->after('amount');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fees')) {
            if (Schema::hasColumn('fees', 'fee_category_id')) {
                Schema::table('fees', function (Blueprint $table) {
                    $table->dropForeign(['fee_category_id']);
                });
            }
            if (Schema::hasColumn('fees', 'year_level')) {
                Schema::table('fees', function (Blueprint $table) {
                    $table->dropColumn('year_level');
                });
            }
        }
        Schema::dropIfExists('fee_categories');
    }
};
