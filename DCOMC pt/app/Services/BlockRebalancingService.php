<?php

namespace App\Services;

use App\Models\Block;
use App\Models\BlockTransferLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BlockRebalancingService
{
    public function __construct(
        protected BlockManagementService $blockManagement
    ) {}

    /**
     * Rebalance blocks with vacancy: pull students from same program/year/semester,
     * from blocks with higher section_name (alphabetically), until target reaches max_capacity.
     * Returns total number of students moved.
     */
    public function rebalanceBlock(int $blockId): int
    {
        $block = Block::findOrFail($blockId);
        return $this->rebalanceBlockInstance($block);
    }

    /**
     * Rebalance all blocks with vacancy for a given program / year_level / semester.
     */
    public function rebalanceScope(?int $programId, string $yearLevel, string $semester): int
    {
        $query = Block::query()
            ->where('year_level', $yearLevel)
            ->where('semester', $semester)
            ->where('is_active', true);

        if ($programId !== null) {
            $query->where(function ($q) use ($programId) {
                $q->where('program_id', $programId)->orWhereNull('program_id');
            });
        }

        $blocksWithVacancy = $query->get()->filter(function (Block $b) {
            return (int) $b->current_size < $b->effectiveMaxCapacity();
        })->sortBy('section_name')->sortBy('code');

        $totalMoved = 0;
        foreach ($blocksWithVacancy as $block) {
            $totalMoved += $this->rebalanceBlockInstance($block);
        }

        return $totalMoved;
    }

    protected function rebalanceBlockInstance(Block $block): int
    {
        $maxCap = $block->effectiveMaxCapacity();
        $current = (int) $block->current_size;
        $vacancy = $maxCap - $current;
        if ($vacancy <= 0) {
            return 0;
        }

        $programId = $block->program_id;
        $programName = $block->program;
        $yearLevel = $block->year_level;
        $semester = $block->semester;
        $sectionName = $block->section_name ?? $block->code;

        $sourceBlocksQuery = Block::query()
            ->where('year_level', $yearLevel)
            ->where('semester', $semester)
            ->where('is_active', true)
            ->where('id', '!=', $block->id)
            ->where(function ($q) use ($programId, $programName) {
                if ($programId) {
                    $q->where('program_id', $programId);
                } else {
                    $q->where('program', $programName);
                }
            });

        $sourceBlocks = $sourceBlocksQuery->orderBy('section_name')->orderBy('code')->get();

        $movedTotal = 0;

        foreach ($sourceBlocks as $sourceBlock) {
            if ($vacancy <= 0) {
                break;
            }

            $toMove = User::query()
                ->where('block_id', $sourceBlock->id)
                ->where('role', User::ROLE_STUDENT)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->orderBy('id')
                ->limit($vacancy)
                ->get();

            foreach ($toMove as $student) {
                DB::transaction(function () use ($student, $sourceBlock, $block) {
                    $student->update([
                        'block_id' => $block->id,
                        'year_level' => $block->year_level ?? $student->year_level,
                        'semester' => $block->semester ?? $student->semester,
                        'course' => $block->program ?? $student->course,
                    ]);

                    BlockTransferLog::create([
                        'student_id' => $student->id,
                        'from_block_id' => $sourceBlock->id,
                        'to_block_id' => $block->id,
                        'transfer_type' => BlockTransferLog::TYPE_AUTO_REBALANCE,
                        'initiated_by' => null,
                        'reason' => 'Auto-rebalance: vacancy filled.',
                    ]);

                    $sourceBlock->decrement('current_size');
                    $block->increment('current_size');
                });

                $vacancy--;
                $movedTotal++;
            }
        }

        return $movedTotal;
    }
}
