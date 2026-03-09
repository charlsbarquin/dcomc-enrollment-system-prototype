<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fees')) {
            return;
        }
        if (! Schema::hasTable('programs') || ! Schema::hasTable('academic_year_levels')) {
            return;
        }

        if (! Schema::hasColumn('fees', 'program_id')) {
            Schema::table('fees', function (Blueprint $table) {
                $table->unsignedBigInteger('program_id')->nullable()->after('program');
            });
        }
        if (! Schema::hasColumn('fees', 'academic_year_level_id')) {
            Schema::table('fees', function (Blueprint $table) {
                $table->unsignedBigInteger('academic_year_level_id')->nullable()->after('year_level');
            });
        }

        $programMap = DB::table('programs')->pluck('id', 'program_name');
        $yearLevelMap = DB::table('academic_year_levels')->pluck('id', 'name');

        DB::table('fees')->orderBy('id')->chunk(100, function ($fees) use ($programMap, $yearLevelMap) {
            foreach ($fees as $row) {
                $programId = $programMap->get(trim($row->program ?? ''));
                $yearLevelId = $yearLevelMap->get(trim($row->year_level ?? ''));
                if ($programId !== null || $yearLevelId !== null) {
                    DB::table('fees')->where('id', $row->id)->update([
                        'program_id' => $programId,
                        'academic_year_level_id' => $yearLevelId,
                    ]);
                }
            }
        });

        $driver = Schema::getConnection()->getDriverName();
        $fkNames = [];
        if ($driver === 'mysql') {
            $fkNames = DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fees' AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
            );
            $fkNames = array_column($fkNames, 'CONSTRAINT_NAME');
        }
        $needsProgramFk = ! in_array('fees_program_id_foreign', $fkNames, true);
        $needsYearLevelFk = ! in_array('fees_academic_year_level_id_foreign', $fkNames, true);

        if ($needsProgramFk || $needsYearLevelFk) {
            Schema::table('fees', function (Blueprint $table) use ($needsProgramFk, $needsYearLevelFk) {
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
        if (! Schema::hasTable('fees')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        $fkNames = [];
        if ($driver === 'mysql') {
            $fkNames = DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fees' AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
            );
            $fkNames = array_column($fkNames, 'CONSTRAINT_NAME');
        }
        Schema::table('fees', function (Blueprint $table) use ($fkNames) {
            if (in_array('fees_program_id_foreign', $fkNames, true)) {
                $table->dropForeign(['program_id']);
            }
            if (in_array('fees_academic_year_level_id_foreign', $fkNames, true)) {
                $table->dropForeign(['academic_year_level_id']);
            }
        });
        $cols = [];
        if (Schema::hasColumn('fees', 'program_id')) {
            $cols[] = 'program_id';
        }
        if (Schema::hasColumn('fees', 'academic_year_level_id')) {
            $cols[] = 'academic_year_level_id';
        }
        if ($cols !== []) {
            Schema::table('fees', function (Blueprint $table) use ($cols) {
                $table->dropColumn($cols);
            });
        }
    }
};
