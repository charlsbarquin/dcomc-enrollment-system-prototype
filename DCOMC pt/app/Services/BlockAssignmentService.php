<?php

namespace App\Services;

use App\Models\Block;
use App\Models\Program;
use App\Models\User;
use App\Services\AcademicNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BlockAssignmentService
{
    public function assignStudentToBlock(
        User $student,
        string $yearLevel,
        string $semester,
        ?int $preferredBlockId = null
    ): ?Block
    {
        if ($student->role !== User::ROLE_STUDENT) {
            return null;
        }

        $shift = $student->shift ?: 'day';
        $program = $student->course ?: 'UNDECLARED';
        $major = null;
        $gender = strtolower((string) ($student->gender ?? ''));
        $yearLevel = AcademicNormalizer::normalizeYearLevel($yearLevel) ?? $yearLevel;
        $semester = AcademicNormalizer::normalizeSemester($semester) ?? $semester;

        return DB::transaction(function () use ($student, $yearLevel, $semester, $shift, $program, $major, $preferredBlockId, $gender) {
            $targetBlock = null;

            // If student selected a block in enrollment form, use it only if it still has a free slot.
            if ($preferredBlockId) {
                $preferred = Block::query()
                    ->where('id', $preferredBlockId)
                    ->where('is_active', true)
                    ->where('year_level', $yearLevel)
                    ->where('semester', $semester)
                    ->where('shift', $shift)
                    ->where(function ($query) use ($gender) {
                        $query->whereNull('gender_group')
                            ->orWhere('gender_group', 'mixed')
                            ->orWhereRaw('LOWER(gender_group) = ?', [$gender]);
                    })
                    ->first();

                if ($preferred && $this->hasCapacity($preferred)) {
                    $targetBlock = $preferred;
                }
            }

            // Fill previous blocks first if they still have missing slots.
            if (! $targetBlock) {
                $targetBlock = $this->oldestNonFullBlock($yearLevel, $semester, $shift, $program, $gender);
            }

            if (! $targetBlock) {
                // Lock existing blocks for this scope to avoid duplicate block creation under concurrency.
                $this->lockBlocksForScope($yearLevel, $semester, $shift, $program);
                $targetBlock = $this->oldestNonFullBlock($yearLevel, $semester, $shift, $program, $gender);
            }

            if (! $targetBlock) {
                $newCode = $this->generateBlockCode($program, $yearLevel, $semester, $shift);
                $sectionName = $this->deriveSectionFromCode($newCode);
                $capacity = config('blocks.strict_50_per_block', true)
                    ? 50
                    : (int) config('blocks.default_capacity', 50);
                $programModel = Program::where('program_name', $program)->first();
                $targetBlock = Block::create([
                    'name' => $newCode,
                    'code' => $newCode,
                    'section_name' => $sectionName,
                    'program_id' => $programModel?->id,
                    'program' => $program,
                    'major' => $major,
                    'year_level' => $yearLevel,
                    'semester' => $semester,
                    'shift' => $shift,
                    'gender_group' => 'mixed',
                    'capacity' => $capacity,
                    'max_capacity' => $capacity,
                    'max_students' => $capacity,
                    'current_size' => 0,
                    'is_active' => true,
                ]);
            }

            $previousBlockId = $student->block_id;
            if ($previousBlockId && $previousBlockId !== $targetBlock->id) {
                Block::where('id', $previousBlockId)
                    ->update([
                        'current_size' => DB::raw('GREATEST(COALESCE(current_size, 0) - 1, 0)'),
                    ]);
            }

            if ($previousBlockId !== $targetBlock->id) {
                $student->update([
                    'block_id' => $targetBlock->id,
                    'shift' => $shift,
                    'course' => $targetBlock->program ?? $student->course,
                    'year_level' => $targetBlock->year_level ?? $student->year_level,
                    'semester' => $targetBlock->semester ?? $student->semester,
                ]);

                Block::where('id', $targetBlock->id)->update([
                    'current_size' => DB::raw('COALESCE(current_size, 0) + 1'),
                ]);
            }

            return $targetBlock->fresh();
        });
    }

    /**
     * Suggest the next block code for the given program/year/semester (consistent format: PREFIX yearNum - section).
     * Used when manually adding blocks or when the system auto-creates blocks.
     */
    public function suggestNextBlockCode(string $program, string $yearLevel, string $semester, string $shift = 'day'): string
    {
        $yearLevel = AcademicNormalizer::normalizeYearLevel($yearLevel) ?? $yearLevel;
        $semester = AcademicNormalizer::normalizeSemester($semester) ?? $semester;

        return $this->generateBlockCode($program, $yearLevel, $semester, $shift);
    }

    /**
     * Return true if the code follows the standard format "PREFIX yearNum - section" and yearNum matches the given year level.
     */
    public function codeMatchesYearLevel(string $code, string $yearLevel): bool
    {
        $expectedYearNum = $this->yearLevelToNumber($yearLevel);
        return (bool) preg_match('/^\s*[A-Z0-9]+\s+' . preg_quote((string) $expectedYearNum, '/') . '\s*-\s*.+/i', trim($code));
    }

    /** Format: BEED 1 - 1, BEED 2 - 1 (educ) or CAED 1 - A, CAED 2 - A (letter). */
    private function generateBlockCode(string $program, string $yearLevel, string $semester, string $shift): string
    {
        $courseMeta = $this->resolveCourseMeta($program);
        $isElementary = $courseMeta['is_numeric'];
        $prefix = $courseMeta['prefix'];
        $yearNum = $this->yearLevelToNumber($yearLevel);
        if ($yearNum < 1 || $yearNum > 4) {
            $yearNum = 1;
        }

        if ($isElementary) {
            $section = $this->nextNumericSuffixForYear($prefix, $yearNum, $yearLevel, $semester, $program);
            return "{$prefix} {$yearNum} - {$section}";
        }

        $section = $this->nextAlphabeticSuffixForYear($prefix, $yearNum, $yearLevel, $semester, $program);
        return "{$prefix} {$yearNum} - {$section}";
    }

    private function yearLevelToNumber(string $yearLevel): int
    {
        $yl = AcademicNormalizer::normalizeYearLevel($yearLevel) ?? trim($yearLevel);
        $map = ['1st Year' => 1, '2nd Year' => 2, '3rd Year' => 3, '4th Year' => 4];
        return $map[$yl] ?? 1;
    }

    private function resolveCourseMeta(string $program): array
    {
        $programLower = strtolower(trim($program));

        // Config rules first so we get CAED for Culture and Arts (not BCAED), BEED for Elementary, etc.
        foreach (config('block_naming.rules', []) as $rule) {
            $keywords = array_map('strtolower', $rule['keywords'] ?? []);
            foreach ($keywords as $keyword) {
                if ($keyword !== '' && str_contains($programLower, $keyword)) {
                    return [
                        'prefix' => strtoupper((string) ($rule['prefix'] ?? $this->abbreviate($program) ?: 'PRG')),
                        'is_numeric' => (($rule['suffix'] ?? 'letter') === 'number'),
                    ];
                }
            }
        }

        // Fallback: Program model code (e.g. BEED, BSED).
        $programModel = Program::where('program_name', $program)->first();
        if ($programModel && $programModel->code) {
            $isNumeric = str_contains($programLower, 'elementary');
            $code = trim($programModel->code);
            $prefix = explode('-', $code)[0];
            return [
                'prefix' => strtoupper($prefix),
                'is_numeric' => $isNumeric,
            ];
        }

        // Fallback: read course metadata if courses table exists.
        $course = null;
        if (Schema::hasTable('courses')) {
            $course = DB::table('courses')
                ->where('name', $program)
                ->orWhere('code', $program)
                ->first();
        }

        $rawPrefix = $course->code ?? $this->abbreviate($program);
        $prefix = strtoupper(trim((string) $rawPrefix));
        $blockFormat = strtolower((string) ($course->block_format ?? 'letter'));
        $isNumeric = $blockFormat === 'number' || str_contains($programLower, 'elementary');

        return [
            'prefix' => $prefix !== '' ? $prefix : 'PRG',
            'is_numeric' => $isNumeric,
        ];
    }

    private function abbreviate(string $text): string
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $abbr = '';
        foreach ($words as $word) {
            $clean = preg_replace('/[^A-Za-z]/', '', $word);
            if ($clean !== '') {
                $abbr .= strtoupper(substr($clean, 0, 1));
            }
        }

        return $abbr;
    }

    /**
     * Lock blocks for the given scope (program/year/semester/shift) to serialize block creation.
     * Call inside transaction before creating a new block to avoid duplicate codes under concurrency.
     */
    private function lockBlocksForScope(string $yearLevel, string $semester, string $shift, string $program): void
    {
        $programId = Program::where('program_name', $program)->orWhere('code', $program)->value('id');
        Block::query()
            ->where('year_level', $yearLevel)
            ->where('semester', $semester)
            ->where('shift', $shift)
            ->where(function ($q) use ($program, $programId) {
                $q->where('program', $program);
                if ($programId !== null) {
                    $q->orWhere('program_id', $programId);
                }
            })
            ->lockForUpdate()
            ->get();
    }

    private function blockQueryForProgramYearSemester(string $program, string $yearLevel, string $semester)
    {
        $programId = Program::where('program_name', $program)->orWhere('code', $program)->value('id');
        return Block::query()
            ->where('year_level', $yearLevel)
            ->where('semester', $semester)
            ->where(function ($q) use ($program, $programId) {
                $q->where('program', $program);
                if ($programId !== null) {
                    $q->orWhere('program_id', $programId);
                }
            });
    }

    /** Next section number for PREFIX yearNum - N (e.g. BEED 1 - 1, BEED 1 - 2) in same program/year/semester. */
    private function nextNumericSuffixForYear(string $prefix, int $yearNum, string $yearLevel, string $semester, string $program): int
    {
        $like = $prefix . ' ' . $yearNum . ' - %';
        $existing = $this->blockQueryForProgramYearSemester($program, $yearLevel, $semester)
            ->where(function ($q) use ($like) {
                $q->where('code', 'like', $like)->orWhere('name', 'like', $like);
            })
            ->pluck('code')
            ->merge(
                $this->blockQueryForProgramYearSemester($program, $yearLevel, $semester)
                    ->where(function ($q) use ($like) {
                        $q->where('code', 'like', $like)->orWhere('name', 'like', $like);
                    })
                    ->pluck('name')
            )
            ->filter()
            ->unique()
            ->values();

        $max = 0;
        foreach ($existing as $value) {
            if (preg_match('/\s' . preg_quote((string) $yearNum, '/') . '\s*-\s*(\d+)/', (string) $value, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return $max + 1;
    }

    /** Next section letter for PREFIX yearNum - X (e.g. CAED 1 - A, CAED 2 - A) in same program/year/semester. */
    private function nextAlphabeticSuffixForYear(string $prefix, int $yearNum, string $yearLevel, string $semester, string $program): string
    {
        $like = $prefix . ' ' . $yearNum . ' - %';
        $existing = $this->blockQueryForProgramYearSemester($program, $yearLevel, $semester)
            ->where(function ($q) use ($like) {
                $q->where('code', 'like', $like)->orWhere('name', 'like', $like);
            })
            ->pluck('code')
            ->merge(
                $this->blockQueryForProgramYearSemester($program, $yearLevel, $semester)
                    ->where(function ($q) use ($like) {
                        $q->where('code', 'like', $like)->orWhere('name', 'like', $like);
                    })
                    ->pluck('name')
            )
            ->filter()
            ->unique()
            ->values();

        $letters = [];
        foreach ($existing as $value) {
            if (preg_match('/\s' . preg_quote((string) $yearNum, '/') . '\s*-\s*([A-Z]+)/', (string) $value, $matches)) {
                $letters[] = $matches[1];
            }
        }

        if (empty($letters)) {
            return 'A';
        }

        sort($letters);
        return $this->incrementLetters(end($letters));
    }

    /** Extract section (e.g. 1, A) from code like "BEED 1 - 1" or "CAED 2 - A". */
    private function deriveSectionFromCode(string $code): string
    {
        if (preg_match('/\s\d+\s*-\s*([A-Z0-9]+)\s*$/i', trim($code), $m)) {
            return $m[1];
        }
        if (preg_match('/\d+[A-Z]?$/i', trim($code), $m)) {
            return $m[0];
        }
        return trim($code) ?: '1';
    }

    private function incrementLetters(string $letters): string
    {
        $chars = str_split($letters);
        $i = count($chars) - 1;

        while ($i >= 0) {
            if ($chars[$i] !== 'Z') {
                $chars[$i] = chr(ord($chars[$i]) + 1);
                return implode('', $chars);
            }
            $chars[$i] = 'A';
            $i--;
        }

        array_unshift($chars, 'A');
        return implode('', $chars);
    }

    private function oldestNonFullBlock(string $yearLevel, string $semester, string $shift, string $program, string $gender): ?Block
    {
        return Block::query()
            ->where('is_active', true)
            ->where('year_level', $yearLevel)
            ->where('semester', $semester)
            ->where('shift', $shift)
            ->where(function ($query) use ($program) {
                $query->where('program', $program)->orWhereNull('program');
            })
            ->where(function ($query) use ($gender) {
                $query->whereNull('gender_group')
                    ->orWhere('gender_group', 'mixed')
                    ->orWhereRaw('LOWER(gender_group) = ?', [$gender]);
            })
            ->whereRaw(
                config('blocks.strict_50_per_block', true)
                    ? 'COALESCE(current_size, 0) < 50'
                    : 'COALESCE(current_size, 0) < COALESCE(capacity, max_students, 50)'
            )
            ->orderBy('id') // earlier blocks are prioritized to fill missing slots first
            ->first();
    }

    private function hasCapacity(Block $block): bool
    {
        $current = (int) ($block->current_size ?? 0);
        $cap = config('blocks.strict_50_per_block', true)
            ? 50
            : (int) ($block->capacity ?? $block->max_students ?? 50);

        return $current < $cap;
    }
}

