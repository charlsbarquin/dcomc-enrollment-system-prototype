<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fee extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_category_id',
        'name',
        'amount',
        'category',
        'year_level',
        'program',
        'program_id',
        'academic_year_level_id',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class, 'fee_category_id');
    }

    public function programRelation(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function academicYearLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicYearLevel::class, 'academic_year_level_id');
    }

    /** Display name: category name when linked, else fee name */
    public function getDisplayNameAttribute(): string
    {
        return $this->feeCategory?->name ?? $this->name ?? '';
    }

    /** Scope: only fees for the given program and year level (by ID). Strict isolation. */
    public function scopeForProgramAndYear(Builder $q, ?int $programId, ?int $academicYearLevelId): Builder
    {
        if ($programId !== null) {
            $q->where('program_id', $programId);
        }
        if ($academicYearLevelId !== null) {
            $q->where('academic_year_level_id', $academicYearLevelId);
        }
        return $q;
    }

    /** Check if this fee belongs to the given program and year level (by ID). */
    public function belongsToProgramAndYear(?int $programId, ?int $academicYearLevelId): bool
    {
        if ($programId === null || $academicYearLevelId === null) {
            return false;
        }
        return (int) $this->program_id === (int) $programId
            && (int) $this->academic_year_level_id === (int) $academicYearLevelId;
    }

    /**
     * Resolve the best-matching fee for a category for the given program, optional major, and year level.
     * When fee has program_id/academic_year_level_id set, uses strict ID match first.
     */
    public static function resolveFor(string $categoryName, ?string $program, ?string $yearLevel, ?string $major = null): ?self
    {
        $category = FeeCategory::where('name', $categoryName)->first();
        if (! $category) {
            return null;
        }

        $programModel = $program ? Program::where('program_name', trim($program))->first() : null;
        $yearLevelModel = $yearLevel ? AcademicYearLevel::where('name', trim($yearLevel))->first() : null;
        $programId = $programModel?->id;
        $academicYearLevelId = $yearLevelModel?->id;

        if ($programId !== null && $academicYearLevelId !== null) {
            $strict = static::query()
                ->where('fee_category_id', $category->id)
                ->forProgramAndYear($programId, $academicYearLevelId)
                ->where('is_active', true)
                ->first();
            if ($strict !== null) {
                return $strict;
            }
        }

        $candidates = static::query()
            ->where('fee_category_id', $category->id)
            ->where('is_active', true)
            ->get();

        $program = $program ? trim($program) : null;
        $yearLevel = $yearLevel ? trim($yearLevel) : null;
        $programWithMajor = ($program && $major) ? trim($program) . ' Major in ' . trim($major) : null;

        $best = $candidates->filter(function ($f) use ($program, $programWithMajor, $yearLevel) {
            $pMatch = $f->program === null
                || $f->program === $program
                || ($programWithMajor !== null && $f->program === $programWithMajor);
            $yMatch = $f->year_level === null || $f->year_level === $yearLevel;
            return $pMatch && $yMatch;
        })->sortByDesc(function ($f) use ($program, $programWithMajor, $yearLevel) {
            $pScore = $f->program === null ? 0 : ($f->program === $programWithMajor ? 3 : ($f->program === $program ? 2 : 0));
            $yScore = $f->year_level === null ? 0 : ($f->year_level === $yearLevel ? 2 : 0);
            return $pScore + $yScore;
        })->first();

        return $best;
    }

    /** Get all fees for the given program and year level (strict: by ID). For schedule scope. Ordered by sort_order (nulls last) then id. */
    public static function feesForScope(?int $programId, ?int $academicYearLevelId): \Illuminate\Support\Collection
    {
        if ($programId === null || $academicYearLevelId === null) {
            return collect();
        }
        return static::query()
            ->with('feeCategory')
            ->forProgramAndYear($programId, $academicYearLevelId)
            ->where('is_active', true)
            ->orderByRaw('sort_order IS NULL, sort_order ASC')
            ->orderBy('id')
            ->get();
    }

    /** Get all resolved fees for a program (and optional major) and year level (one per category). Legacy: by name. */
    public static function resolvedFeesFor(?string $program, ?string $yearLevel, ?string $major = null): \Illuminate\Support\Collection
    {
        $categories = FeeCategory::orderBy('sort_order')->orderBy('name')->get();
        $result = collect();
        foreach ($categories as $cat) {
            $fee = static::resolveFor($cat->name, $program, $yearLevel, $major);
            if ($fee) {
                $result->push($fee);
            }
        }
        return $result;
    }
}
