<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Normalize block codes to consistent format: PREFIX yearNum - section
 * (e.g. BEED 1 - 1, BEED 2 - 1, BEED 3 - 1) so 1st–4th year all follow the same pattern.
 */
return new class extends Migration
{
    private function yearLevelToNumber(?string $yearLevel): int
    {
        $y = trim((string) $yearLevel);
        $map = ['1st Year' => 1, '2nd Year' => 2, '3rd Year' => 3, '4th Year' => 4];
        return $map[$y] ?? 1;
    }

    /** Extract program prefix from code (e.g. "BEED 1" or "BEED 3 - 1" -> "BEED"). */
    private function prefixFromCode(?string $code): string
    {
        if ($code === null || trim($code) === '') {
            return 'BLK';
        }
        if (preg_match('/^([A-Z]+)\s+\d+/i', trim($code), $m)) {
            return strtoupper($m[1]);
        }
        return 'BLK';
    }

    /** Return true if code already follows "PREFIX yearNum - section" with yearNum matching the block's year_level. */
    private function isConsistentCode(?string $code, int $yearNum): bool
    {
        if ($code === null || trim($code) === '') {
            return false;
        }
        return (bool) preg_match('/^\s*[A-Z0-9]+\s+' . $yearNum . '\s*-\s*[A-Z0-9]+\s*$/i', trim($code));
    }

    public function up(): void
    {
        $blocks = DB::table('blocks')->orderBy('id')->get();

        $groupKey = function ($row) {
            $p = $row->program_id ?? $row->program ?? '';
            return $p . '|' . ($row->year_level ?? '') . '|' . ($row->semester ?? '');
        };
        $groups = [];
        foreach ($blocks as $block) {
            $key = $groupKey($block);
            if (! isset($groups[$key])) {
                $groups[$key] = [];
            }
            $groups[$key][] = $block;
        }

        foreach ($groups as $groupBlocks) {
            $first = $groupBlocks[0];
            $yearNum = $this->yearLevelToNumber($first->year_level ?? null);
            $prefix = $this->prefixFromCode($first->code ?? null);

            $usedSections = [];
            foreach ($groupBlocks as $b) {
                if ($this->isConsistentCode($b->code ?? null, $yearNum) && preg_match('/-\s*([A-Z0-9]+)\s*$/i', trim($b->code ?? ''), $m)) {
                    $usedSections[$m[1]] = true;
                }
            }

            $nextSection = 1;
            foreach ($groupBlocks as $block) {
                $currentCode = trim($block->code ?? '');
                if ($this->isConsistentCode($currentCode, $yearNum)) {
                    continue;
                }
                // Pick a section number that is not used in this group AND not already in DB (global uniqueness)
                while (true) {
                    $candidateCode = $prefix . ' ' . $yearNum . ' - ' . $nextSection;
                    $takenInGroup = isset($usedSections[(string) $nextSection]);
                    $takenInDb = DB::table('blocks')->where('code', $candidateCode)->where('id', '!=', $block->id)->exists();
                    if (! $takenInGroup && ! $takenInDb) {
                        break;
                    }
                    $nextSection++;
                }
                $section = $nextSection;
                $newCode = $prefix . ' ' . $yearNum . ' - ' . $section;
                $usedSections[(string) $section] = true;
                $nextSection++;

                $update = ['code' => $newCode];
                if (Schema::hasColumn('blocks', 'name')) {
                    $update['name'] = $newCode;
                }
                if (Schema::hasColumn('blocks', 'section_name')) {
                    $update['section_name'] = (string) $section;
                }
                DB::table('blocks')->where('id', $block->id)->update($update);
            }
        }
    }

    public function down(): void
    {
        // Cannot reverse normalization; leave blocks as-is.
    }
};
