<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_responses', function (Blueprint $table) {
            if (! Schema::hasColumn('form_responses', 'process_status')) {
                $table->string('process_status')->default('pending')->after('approval_status');
            }
            if (! Schema::hasColumn('form_responses', 'process_notes')) {
                $table->text('process_notes')->nullable()->after('process_status');
            }
        });

        if (Schema::hasColumn('form_responses', 'approval_status') && Schema::hasColumn('form_responses', 'process_status')) {
            DB::table('form_responses')
                ->where('approval_status', 'approved')
                ->update(['process_status' => 'approved']);

            DB::table('form_responses')
                ->where('approval_status', 'rejected')
                ->update(['process_status' => 'rejected']);
        }
    }

    public function down(): void
    {
        Schema::table('form_responses', function (Blueprint $table) {
            if (Schema::hasColumn('form_responses', 'process_notes')) {
                $table->dropColumn('process_notes');
            }
            if (Schema::hasColumn('form_responses', 'process_status')) {
                $table->dropColumn('process_status');
            }
        });
    }
};

