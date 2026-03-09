<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'raw_subject_id',
        'code',
        'title',
        'units',
        'prerequisites',
        'program_id',
        'academic_year_level_id',
        'major',
        'semester',
        'is_active',
    ];

    public function rawSubject(): BelongsTo
    {
        return $this->belongsTo(RawSubject::class, 'raw_subject_id');
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function academicYearLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicYearLevel::class, 'academic_year_level_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }

    /** Scope: only subjects for the given program and year level (by ID). */
    public function scopeForProgramAndYear(Builder $q, int $programId, int $academicYearLevelId): Builder
    {
        return $q->where('program_id', $programId)
            ->where('academic_year_level_id', $academicYearLevelId);
    }

    /** Scope: only subjects for the given program name and year level name (resolved to IDs). */
    public function scopeForProgramNameAndYearName(Builder $q, ?string $programName, ?string $yearLevelName): Builder
    {
        if ($programName === null && $yearLevelName === null) {
            return $q;
        }
        if ($programName !== null) {
            $q->whereHas('program', fn ($p) => $p->where('program_name', $programName));
        }
        if ($yearLevelName !== null) {
            $q->whereHas('academicYearLevel', fn ($y) => $y->where('name', $yearLevelName));
        }
        return $q;
    }

    /**
     * Check if this subject belongs to the given program and year level (by name, e.g. from schedule template).
     */
    public function belongsToProgramAndYear(?string $programName, ?string $yearLevelName): bool
    {
        if ($programName === null || $yearLevelName === null) {
            return false;
        }
        return $this->program && $this->program->program_name === trim($programName)
            && $this->academicYearLevel && $this->academicYearLevel->name === trim($yearLevelName);
    }
}

