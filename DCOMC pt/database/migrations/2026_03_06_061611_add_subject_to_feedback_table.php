<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('feedback')) {
            return;
        }
        if (Schema::hasColumn('feedback', 'subject')) {
            return;
        }

        Schema::table('feedback', function (Blueprint $table) {
            $table->string('subject', 160)->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('feedback')) {
            return;
        }
        if (! Schema::hasColumn('feedback', 'subject')) {
            return;
        }

        Schema::table('feedback', function (Blueprint $table) {
            $table->dropColumn('subject');
        });
    }
};
