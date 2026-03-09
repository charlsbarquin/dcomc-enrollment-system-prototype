<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessorSubjectAssignment extends Model
{
    protected $fillable = ['professor_id', 'subject_id', 'raw_subject_id', 'semester', 'school_year'];

    public function professor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professor_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function rawSubject(): BelongsTo
    {
        return $this->belongsTo(RawSubject::class, 'raw_subject_id');
    }

    /** Prefer raw subject for display; fallback to arranged subject. */
    public function getDisplaySubjectAttribute(): ?object
    {
        if ($this->raw_subject_id && $this->relationLoaded('rawSubject') && $this->rawSubject) {
            return $this->rawSubject;
        }
        if ($this->subject_id && $this->relationLoaded('subject') && $this->subject) {
            return $this->subject->rawSubject ?? $this->subject;
        }
        $this->loadMissing(['rawSubject', 'subject']);
        if ($this->rawSubject) {
            return $this->rawSubject;
        }
        return $this->subject;
    }

    /**
     * Professor IDs eligible to teach a subject for the given semester/school year.
     * Matches by subject_id or by raw_subject_id when the given subject is linked to a raw subject.
     * Rows with null semester/school_year are treated as eligible for any.
     */
    public static function getEligibleProfessorIds(int $subjectId, ?string $semester, ?string $schoolYear): array
    {
        $rawId = Subject::where('id', $subjectId)->value('raw_subject_id');
        $q = static::query()->where(function ($q) use ($subjectId, $rawId) {
            $q->where('subject_id', $subjectId);
            if ($rawId !== null) {
                $q->orWhere('raw_subject_id', $rawId);
            }
        });
        if ($semester !== null && $semester !== '') {
            $q->where(function ($q2) use ($semester) {
                $q2->where('semester', $semester)->orWhereNull('semester');
            });
        }
        if ($schoolYear !== null && $schoolYear !== '') {
            $q->where(function ($q2) use ($schoolYear) {
                $q2->where('school_year', $schoolYear)->orWhereNull('school_year');
            });
        }
        return $q->pluck('professor_id')->unique()->values()->all();
    }

    /** Check if a professor is assigned to teach this subject for the given term. */
    public static function isProfessorEligibleForSubject(int $professorId, int $subjectId, ?string $semester, ?string $schoolYear): bool
    {
        $ids = static::getEligibleProfessorIds($subjectId, $semester, $schoolYear);
        return in_array($professorId, $ids, true);
    }
}
