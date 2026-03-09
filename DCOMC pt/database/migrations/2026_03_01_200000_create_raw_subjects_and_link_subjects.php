<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Recover from a previous partial run (e.g. table created then insert failed)
        if (Schema::hasTable('raw_subjects')) {
            if (Schema::hasColumn('professor_subject_assignments', 'raw_subject_id')) {
                try {
                    Schema::table('professor_subject_assignments', fn (Blueprint $t) => $t->dropForeign(['raw_subject_id']));
                } catch (\Throwable $e) {
                    // FK may not exist if migration failed earlier
                }
                Schema::table('professor_subject_assignments', fn (Blueprint $t) => $t->dropColumn('raw_subject_id'));
            }
            if (Schema::hasColumn('subjects', 'raw_subject_id')) {
                try {
                    Schema::table('subjects', fn (Blueprint $t) => $t->dropForeign(['raw_subject_id']));
                } catch (\Throwable $e) {
                    // FK may not exist if migration failed earlier
                }
                Schema::table('subjects', fn (Blueprint $t) => $t->dropColumn('raw_subject_id'));
            }
            Schema::dropIfExists('raw_subjects');
        }

        Schema::create('raw_subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('title');
            $table->unsignedInteger('units')->default(3);
            $table->string('prerequisites', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique('code');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->unsignedBigInteger('raw_subject_id')->nullable()->after('id');
        });

        // Populate raw_subjects: one row per distinct trimmed code (one row per code via grouped min(id))
        $codeToRawId = [];
        $minIds = DB::table('subjects')->selectRaw('TRIM(code) as code_trim, MIN(id) as min_id')->groupByRaw('TRIM(code)')->pluck('min_id', 'code_trim');
        $distinctSubjects = $minIds->isEmpty() ? collect() : DB::table('subjects')->whereIn('id', $minIds->values())->orderBy('id')->get();
        foreach ($distinctSubjects as $row) {
            $code = trim((string) $row->code);
            if ($code === '') {
                continue;
            }
            $id = DB::table('raw_subjects')->insertGetId([
                'code' => $code,
                'title' => trim((string) $row->title),
                'units' => (int) $row->units,
                'prerequisites' => $row->prerequisites ? trim((string) $row->prerequisites) : null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $codeToRawId[$code] = $id;
        }
        foreach (DB::table('subjects')->get() as $s) {
            $rawId = $codeToRawId[trim((string) $s->code)] ?? null;
            if ($rawId) {
                DB::table('subjects')->where('id', $s->id)->update(['raw_subject_id' => $rawId]);
            }
        }

        Schema::table('subjects', function (Blueprint $table) {
            $table->foreign('raw_subject_id')->references('id')->on('raw_subjects')->nullOnDelete();
        });

        Schema::table('professor_subject_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('raw_subject_id')->nullable()->after('professor_id');
            $table->foreign('raw_subject_id')->references('id')->on('raw_subjects')->nullOnDelete();
        });

        // Backfill professor_subject_assignments.raw_subject_id from subject.raw_subject_id
        $assignments = DB::table('professor_subject_assignments')->whereNotNull('subject_id')->get();
        foreach ($assignments as $a) {
            $sub = DB::table('subjects')->where('id', $a->subject_id)->value('raw_subject_id');
            if ($sub) {
                DB::table('professor_subject_assignments')->where('id', $a->id)->update(['raw_subject_id' => $sub]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('professor_subject_assignments', function (Blueprint $table) {
            $table->dropForeign(['raw_subject_id']);
            $table->dropColumn('raw_subject_id');
        });
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['raw_subject_id']);
            $table->dropColumn('raw_subject_id');
        });
        Schema::dropIfExists('raw_subjects');
    }
};
