<?php

namespace App\Services;

use App\Models\Block;
use App\Models\BlockTransferLog;
use App\Models\User;
use App\Services\AcademicNormalizer;
use Illuminate\Support\Facades\DB;

class BlockPromotionService
{
    /** Canonical year level order for promotion (1st -> 2nd -> 3rd -> 4th). */
    public const YEAR_LEVEL_ORDER = ['1st Year', '2nd Year', '3rd Year', '4th Year'];

    /**
     * Promote all active students: advance year_level, reset semester to First Semester,
     * move to block with same section_name and new year_level (create if missing).
     * Returns summary: promoted count, blocks created.
     */
    public function runPromotion(): array
    {
        $promoted = 0;
        $blocksCreated = 0;

        $students = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->whereNotNull('block_id')
            ->whereIn('student_status', ['Regular', 'Irregular', 'Enrolled', null])
            ->with('block')
            ->get();

        foreach ($students as $student) {
            $block = $student->block;
            if (! $block) {
                continue;
            }

            $currentYear = $block->year_level;
            $nextYear = $this->nextYearLevel($currentYear);
            if ($nextYear === null) {
                continue;
            }

            $sectionName = $block->section_name ?? $this->deriveSectionFromCode($block->code);
            $targetBlock = Block::query()
                ->where('program_id', $block->program_id)
                ->where('program', $block->program)
                ->where('year_level', $nextYear)
                ->where('semester', 'First Semester')
                ->where(function ($q) use ($sectionName) {
                    $q->where('section_name', $sectionName)->orWhere('code', 'like', '%' . $sectionName . '%');
                })
                ->where('is_active', true)
                ->first();

            if (! $targetBlock) {
                $newCode = $this->promotedBlockCode($block->code, $nextYear, $sectionName);
                $targetBlock = Block::firstOrCreate(
                    ['code' => $newCode],
                    [
                        'program_id' => $block->program_id,
                        'program' => $block->program,
                        'year_level' => $nextYear,
                        'section_name' => $sectionName,
                        'semester' => 'First Semester',
                        'shift' => $block->shift ?? 'day',
                        'capacity' => $block->capacity ?? 50,
                        'max_capacity' => $block->max_capacity ?? 50,
                        'current_size' => 0,
                        'is_active' => true,
                    ]
                );
                if ($targetBlock->wasRecentlyCreated) {
                    $blocksCreated++;
                }
            }

            DB::transaction(function () use ($student, $block, $targetBlock) {
                $student->update([
                    'block_id' => $targetBlock->id,
                    'year_level' => $targetBlock->year_level,
                    'semester' => $targetBlock->semester,
                    'course' => $targetBlock->program ?? $student->course,
                ]);

                BlockTransferLog::create([
                    'student_id' => $student->id,
                    'from_block_id' => $block->id,
                    'to_block_id' => $targetBlock->id,
                    'transfer_type' => BlockTransferLog::TYPE_PROMOTION,
                    'initiated_by' => null,
                    'reason' => 'Annual promotion.',
                    'metadata' => [
                        'from_year' => $block->year_level,
                        'to_year' => $targetBlock->year_level,
                        'from_semester' => $block->semester,
                        'to_semester' => $targetBlock->semester,
                    ],
                ]);

                $block->decrement('current_size');
                $targetBlock->increment('current_size');
            });

            $promoted++;
        }

        return ['promoted' => $promoted, 'blocks_created' => $blocksCreated];
    }

    protected function nextYearLevel(string $current): ?string
    {
        $idx = array_search($current, self::YEAR_LEVEL_ORDER, true);
        if ($idx === false || $idx >= count(self::YEAR_LEVEL_ORDER) - 1) {
            return null;
        }

        return self::YEAR_LEVEL_ORDER[$idx + 1];
    }

    /** Extract section (e.g. 1, A) from code like "BEED 1 - 1" or "CAED 1 - A". */
    protected function deriveSectionFromCode(string $code): string
    {
        if (preg_match('/\s\d+\s*-\s*([A-Z0-9]+)\s*$/i', trim($code), $m)) {
            return $m[1];
        }
        if (preg_match('/\d+[A-Z]?$/i', $code, $m)) {
            return $m[0];
        }
        return $code;
    }

    /** New code for promoted block: PREFIX yearNum - section (e.g. BEED 2 - 1, CAED 2 - A). */
    protected function promotedBlockCode(string $oldCode, string $newYearLevel, string $sectionName): string
    {
        $yearNum = $this->yearLevelToNumber($newYearLevel);
        $prefix = $this->prefixFromCode($oldCode);
        $section = $sectionName ?: $this->deriveSectionFromCode($oldCode);

        return $prefix . ' ' . $yearNum . ' - ' . $section;
    }

    private function yearLevelToNumber(string $yearLevel): int
    {
        $yl = AcademicNormalizer::normalizeYearLevel($yearLevel) ?? trim($yearLevel);
        $map = ['1st Year' => 1, '2nd Year' => 2, '3rd Year' => 3, '4th Year' => 4];
        return $map[$yl] ?? 1;
    }

    private function prefixFromCode(string $code): string
    {
        if (preg_match('/^([A-Z]+)\s+\d+/i', trim($code), $m)) {
            return strtoupper($m[1]);
        }
        return 'PRG';
    }
}
