<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rooms')) {
            Schema::create('rooms', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->unsignedInteger('capacity')->default(50);
                $table->string('building')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            return;
        }

        Schema::table('rooms', function (Blueprint $table) {
            if (! Schema::hasColumn('rooms', 'code')) {
                $table->string('code')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('rooms', 'building')) {
                $table->string('building')->nullable()->after('capacity');
            }
            if (! Schema::hasColumn('rooms', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('building');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};

