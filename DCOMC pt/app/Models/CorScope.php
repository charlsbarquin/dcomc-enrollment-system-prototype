<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CorScope extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'academic_year_level_id',
        'semester',
        'school_year',
        'major',
        'created_by',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function academicYearLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicYearLevel::class, 'academic_year_level_id');
    }

    public function scopeSubjects(): HasMany
    {
        return $this->hasMany(CorScopeSubject::class, 'cor_scope_id');
    }

    public function scopeFees(): HasMany
    {
        return $this->hasMany(CorScopeFee::class, 'cor_scope_id');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'cor_scope_subjects', 'cor_scope_id', 'subject_id')
            ->withTimestamps();
    }

    public function fees(): BelongsToMany
    {
        return $this->belongsToMany(Fee::class, 'cor_scope_fees', 'cor_scope_id', 'fee_id')
            ->withTimestamps();
    }

    /**
     * Find a COR Scope matching the given program, year level, semester, and school year.
     * Optional major can be used for future scope (e.g. BSE majors).
     */
    public static function findForScope(
        int $programId,
        int $academicYearLevelId,
        string $semester,
        string $schoolYear,
        ?string $major = null
    ): ?self {
        $q = static::query()
            ->where('program_id', $programId)
            ->where('academic_year_level_id', $academicYearLevelId)
            ->where('semester', $semester)
            ->where('school_year', $schoolYear);
        if ($major !== null && $major !== '') {
            $q->where('major', $major);
        } else {
            $q->whereNull('major');
        }
        return $q->first();
    }

    /** Default subject IDs for this scope (for pre-loading schedule template). */
    public function getDefaultSubjectIds(): array
    {
        return $this->scopeSubjects()->pluck('subject_id')->all();
    }

    /** Default fee entries for this scope [['fee_id' => id, 'amount' => null], ...]. */
    public function getDefaultFeeEntries(): array
    {
        return $this->scopeFees()->get()->map(fn ($row) => [
            'fee_id' => $row->fee_id,
            'amount' => null,
        ])->all();
    }
}
