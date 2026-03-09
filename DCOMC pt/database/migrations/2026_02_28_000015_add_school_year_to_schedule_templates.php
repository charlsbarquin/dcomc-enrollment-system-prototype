<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('schedule_templates')) {
            return;
        }

        Schema::table('schedule_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('schedule_templates', 'school_year')) {
                $table->string('school_year')->nullable()->after('semester');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('schedule_templates')) {
            return;
        }

        Schema::table('schedule_templates', function (Blueprint $table) {
            if (Schema::hasColumn('schedule_templates', 'school_year')) {
                $table->dropColumn('school_year');
            }
        });
    }
};

