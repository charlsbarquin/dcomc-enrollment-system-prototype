<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('schedule_templates')) {
            return;
        }
        if (! Schema::hasTable('programs') || ! Schema::hasTable('academic_year_levels')) {
            return;
        }

        // Add columns only if missing (e.g. previous failed run may have added them)
        if (! Schema::hasColumn('schedule_templates', 'program_id')) {
            Schema::table('schedule_templates', function (Blueprint $table) {
                $table->unsignedBigInteger('program_id')->nullable()->after('program');
            });
        }
        if (! Schema::hasColumn('schedule_templates', 'academic_year_level_id')) {
            Schema::table('schedule_templates', function (Blueprint $table) {
                $table->unsignedBigInteger('academic_year_level_id')->nullable()->after('year_level');
            });
        }

        $programMap = DB::table('programs')->pluck('id', 'program_name');
        $yearLevelMap = DB::table('academic_year_levels')->pluck('id', 'name');

        DB::table('schedule_templates')->orderBy('id')->chunk(100, function ($templates) use ($programMap, $yearLevelMap) {
            foreach ($templates as $row) {
                $programId = $programMap->get(trim($row->program ?? ''));
                $yearLevelId = $yearLevelMap->get(trim($row->year_level ?? ''));
                if ($programId !== null || $yearLevelId !== null) {
                    DB::table('schedule_templates')->where('id', $row->id)->update([
                        'program_id' => $programId,
                        'academic_year_level_id' => $yearLevelId,
                    ]);
                }
            }
        });

        // Add foreign keys only if they don't exist (e.g. previous run added columns but failed before FKs)
        $driver = Schema::getConnection()->getDriverName();
        $fkNames = [];
        if ($driver === 'mysql') {
            $fkNames = DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'schedule_templates' AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
            );
            $fkNames = array_column($fkNames, 'CONSTRAINT_NAME');
        }
        $needsProgramFk = ! in_array('schedule_templates_program_id_foreign', $fkNames, true);
        $needsYearLevelFk = ! in_array('schedule_templates_academic_year_level_id_foreign', $fkNames, true);

        if ($needsProgramFk || $needsYearLevelFk) {
            Schema::table('schedule_templates', function (Blueprint $table) use ($needsProgramFk, $needsYearLevelFk) {
                if ($needsProgramFk) {
                    $table->foreign('program_id')->references('id')->on('programs')->nullOnDelete();
                }
                if ($needsYearLevelFk) {
                    $table->foreign('academic_year_level_id')->references('id')->on('academic_year_levels')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('schedule_templates')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        $fkNames = [];
        if ($driver === 'mysql') {
            $fkNames = DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'schedule_templates' AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
            );
            $fkNames = array_column($fkNames, 'CONSTRAINT_NAME');
        }
        Schema::table('schedule_templates', function (Blueprint $table) use ($fkNames) {
            if (in_array('schedule_templates_program_id_foreign', $fkNames, true)) {
                $table->dropForeign(['program_id']);
            }
            if (in_array('schedule_templates_academic_year_level_id_foreign', $fkNames, true)) {
                $table->dropForeign(['academic_year_level_id']);
            }
        });
        $cols = [];
        if (Schema::hasColumn('schedule_templates', 'program_id')) {
            $cols[] = 'program_id';
        }
        if (Schema::hasColumn('schedule_templates', 'academic_year_level_id')) {
            $cols[] = 'academic_year_level_id';
        }
        if ($cols !== []) {
            Schema::table('schedule_templates', function (Blueprint $table) use ($cols) {
                $table->dropColumn($cols);
            });
        }
    }
};
