<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_responses', function (Blueprint $table) {
            if (! Schema::hasColumn('form_responses', 'preferred_block_id')) {
                $table->foreignId('preferred_block_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('blocks')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('form_responses', 'assigned_block_id')) {
                $table->foreignId('assigned_block_id')
                    ->nullable()
                    ->after('preferred_block_id')
                    ->constrained('blocks')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_responses', function (Blueprint $table) {
            if (Schema::hasColumn('form_responses', 'assigned_block_id')) {
                $table->dropConstrainedForeignId('assigned_block_id');
            }
            if (Schema::hasColumn('form_responses', 'preferred_block_id')) {
                $table->dropConstrainedForeignId('preferred_block_id');
            }
        });
    }
};

