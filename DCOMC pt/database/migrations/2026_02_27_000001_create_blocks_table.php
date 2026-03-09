<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('blocks')) {
            Schema::create('blocks', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('program');
                $table->string('major')->nullable();
                $table->string('year_level');
                $table->string('semester');
                $table->enum('shift', ['day', 'night'])->default('day');
                $table->enum('gender_group', ['mixed', 'male', 'female'])->default('mixed');
                $table->unsignedInteger('capacity')->default(50);
                $table->unsignedInteger('current_size')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            return;
        }

        Schema::table('blocks', function (Blueprint $table) {
            if (! Schema::hasColumn('blocks', 'code')) {
                $table->string('code')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('blocks', 'program')) {
                $table->string('program')->nullable()->after('code');
            }
            if (! Schema::hasColumn('blocks', 'major')) {
                $table->string('major')->nullable()->after('program');
            }
            if (! Schema::hasColumn('blocks', 'year_level')) {
                $table->string('year_level')->nullable()->after('major');
            }
            if (! Schema::hasColumn('blocks', 'semester')) {
                $table->string('semester')->nullable()->after('year_level');
            }
            if (! Schema::hasColumn('blocks', 'shift')) {
                $table->enum('shift', ['day', 'night'])->default('day')->after('semester');
            }
            if (! Schema::hasColumn('blocks', 'gender_group')) {
                $table->enum('gender_group', ['mixed', 'male', 'female'])->default('mixed')->after('shift');
            }
            if (! Schema::hasColumn('blocks', 'capacity')) {
                $table->unsignedInteger('capacity')->default(50)->after('gender_group');
            }
            if (! Schema::hasColumn('blocks', 'current_size')) {
                $table->unsignedInteger('current_size')->default(0)->after('capacity');
            }
            if (! Schema::hasColumn('blocks', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('current_size');
            }
        });

        if (Schema::hasColumn('blocks', 'name') && Schema::hasColumn('blocks', 'code')) {
            DB::statement("UPDATE blocks SET code = name WHERE code IS NULL");
        }
        if (Schema::hasColumn('blocks', 'max_students') && Schema::hasColumn('blocks', 'capacity')) {
            DB::statement("UPDATE blocks SET capacity = max_students WHERE capacity = 50");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('blocks');
    }
};

