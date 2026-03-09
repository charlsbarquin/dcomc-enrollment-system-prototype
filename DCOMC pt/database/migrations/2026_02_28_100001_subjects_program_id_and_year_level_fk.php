<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('programs')) {
            return;
        }

        $programNames = config('fee_programs.programs', []);
        foreach ($programNames as $name) {
            DB::table('programs')->insertOrIgnore([
                'program_name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('subjects', function (Blueprint $table) {
            $table->unsignedBigInteger('program_id')->nullable()->after('id');
            $table->unsignedBigInteger('academic_year_level_id')->nullable()->after('program_id');
        });

        $programMap = DB::table('programs')->pluck('id', 'program_name');
        $yearLevelMap = DB::table('academic_year_levels')->pluck('id', 'name');

        $defaultProgramId = $programMap->first();
        $defaultYearLevelId = $yearLevelMap->first();

        DB::table('subjects')->orderBy('id')->chunk(100, function ($subjects) use ($programMap, $yearLevelMap, $defaultProgramId, $defaultYearLevelId) {
            foreach ($subjects as $row) {
                $programId = $programMap->get(trim($row->program ?? '')) ?? $defaultProgramId;
                $yearLevelId = $yearLevelMap->get(trim($row->year_level ?? '')) ?? $defaultYearLevelId;
                DB::table('subjects')->where('id', $row->id)->update([
                    'program_id' => $programId,
                    'academic_year_level_id' => $yearLevelId,
                ]);
            }
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
            $table->foreign('academic_year_level_id')->references('id')->on('academic_year_levels')->restrictOnDelete();
            $table->unique(['program_id', 'academic_year_level_id', 'code'], 'subjects_program_year_code_unique');
        });

        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'code')) {
                $table->dropUnique(['code']);
            }
            if (Schema::hasColumn('subjects', 'program')) {
                $table->dropColumn('program');
            }
            if (Schema::hasColumn('subjects', 'year_level')) {
                $table->dropColumn('year_level');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('subjects')) {
            return;
        }

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->dropForeign(['academic_year_level_id']);
            $table->dropUnique('subjects_program_year_code_unique');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->string('program')->nullable()->after('major');
            $table->string('year_level')->nullable()->after('program');
        });

        $programs = DB::table('programs')->pluck('program_name', 'id');
        $yearLevels = DB::table('academic_year_levels')->pluck('name', 'id');

        DB::table('subjects')->orderBy('id')->chunk(100, function ($subjects) use ($programs, $yearLevels) {
            foreach ($subjects as $row) {
                DB::table('subjects')->where('id', $row->id)->update([
                    'program' => $programs->get($row->program_id),
                    'year_level' => $yearLevels->get($row->academic_year_level_id),
                ]);
            }
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn(['program_id', 'academic_year_level_id']);
            $table->unique('code');
        });
    }
};
