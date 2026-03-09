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
            if (! Schema::hasColumn('schedule_templates', 'major')) {
                $table->string('major')->nullable()->after('program');
            }
            if (! Schema::hasColumn('schedule_templates', 'block_id')) {
                $table->unsignedBigInteger('block_id')->nullable()->after('semester');
                $table->foreign('block_id')->references('id')->on('blocks')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('schedule_templates')) {
            return;
        }
        Schema::table('schedule_templates', function (Blueprint $table) {
            if (Schema::hasColumn('schedule_templates', 'block_id')) {
                $table->dropForeign(['block_id']);
                $table->dropColumn('block_id');
            }
            if (Schema::hasColumn('schedule_templates', 'major')) {
                $table->dropColumn('major');
            }
        });
    }
};
