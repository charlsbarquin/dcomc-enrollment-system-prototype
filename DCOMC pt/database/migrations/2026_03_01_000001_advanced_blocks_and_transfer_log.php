<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blocks', function (Blueprint $table) {
            if (! Schema::hasColumn('blocks', 'program_id')) {
                $table->unsignedBigInteger('program_id')->nullable()->after('id');
            }
            if (! Schema::hasColumn('blocks', 'section_name')) {
                $table->string('section_name', 50)->nullable()->after('year_level');
            }
            if (! Schema::hasColumn('blocks', 'max_capacity')) {
                $table->unsignedInteger('max_capacity')->default(50)->after('capacity');
            }
        });

        if (Schema::hasTable('programs')) {
            Schema::table('blocks', function (Blueprint $table) {
                if (Schema::hasColumn('blocks', 'program_id')) {
                    $table->foreign('program_id')->references('id')->on('programs')->nullOnDelete();
                }
            });
        }

        if (Schema::hasColumn('blocks', 'max_capacity')) {
            \Illuminate\Support\Facades\DB::table('blocks')->whereNull('max_capacity')->orWhere('max_capacity', 0)->update(['max_capacity' => \Illuminate\Support\Facades\DB::raw('COALESCE(capacity, 50)')]);
        }

        Schema::create('block_transfer_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('from_block_id')->nullable()->constrained('blocks')->nullOnDelete();
            $table->foreignId('to_block_id')->constrained('blocks')->cascadeOnDelete();
            $table->enum('transfer_type', ['manual', 'auto_rebalance', 'promotion', 'shift_out', 'admin_correction']);
            $table->unsignedBigInteger('initiated_by')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('initiated_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['student_id', 'created_at']);
            $table->index(['from_block_id', 'created_at']);
            $table->index(['to_block_id', 'created_at']);
            $table->index('transfer_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_transfer_logs');

        Schema::table('blocks', function (Blueprint $table) {
            if (Schema::hasColumn('blocks', 'program_id')) {
                $table->dropForeign(['program_id']);
            }
            if (Schema::hasColumn('blocks', 'section_name')) {
                $table->dropColumn('section_name');
            }
            if (Schema::hasColumn('blocks', 'max_capacity')) {
                $table->dropColumn('max_capacity');
            }
        });
    }
};
