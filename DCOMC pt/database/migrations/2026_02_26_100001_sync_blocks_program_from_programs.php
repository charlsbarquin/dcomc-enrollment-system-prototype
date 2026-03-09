<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sync blocks.program_id and blocks.program so they stay consistent with programs table.
     */
    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('blocks') || ! \Illuminate\Support\Facades\Schema::hasTable('programs')) {
            return;
        }

        // Set program from program_id where program_id is set
        DB::table('blocks')
            ->whereNotNull('program_id')
            ->whereIn('program_id', DB::table('programs')->pluck('id'))
            ->orderBy('id')
            ->chunkById(100, function ($blocks) {
                foreach ($blocks as $block) {
                    $name = DB::table('programs')->where('id', $block->program_id)->value('program_name');
                    if ($name !== null) {
                        DB::table('blocks')->where('id', $block->id)->update(['program' => $name]);
                    }
                }
            });

        // Set program_id from program name where program_id is null
        $blocks = DB::table('blocks')->whereNull('program_id')->whereNotNull('program')->get(['id', 'program']);
        foreach ($blocks as $block) {
            $programId = DB::table('programs')->where('program_name', $block->program)->value('id');
            if ($programId !== null) {
                DB::table('blocks')->where('id', $block->id)->update(['program_id' => $programId]);
            }
        }
    }

    public function down(): void
    {
        // No-op: cannot safely reverse sync
    }
};
