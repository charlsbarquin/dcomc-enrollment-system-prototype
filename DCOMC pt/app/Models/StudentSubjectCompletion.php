<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Subject completion history: one row per (student, subject, school_year, semester).
 * Status: passed, failed, dropped, credited, withdrawn.
 * Used to prevent irregular students from retaking subjects they already completed (passed/credited).
 * Populated when grades are finalized or when registrar records transfer credit.
 */
class StudentSubjectCompletion extends Model
{
    protected $fillable = [
        'student_id',
        'subject_id',
        'school_year',
        'semester',
        'status',
        'grade',
        'credited_from',
        'remarks',
    ];

    protected $casts = [
        'grade' => 'decimal:2',
    ];

    public const STATUS_PASSED = 'passed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_DROPPED = 'dropped';
    public const STATUS_CREDITED = 'credited';
    public const STATUS_WITHDRAWN = 'withdrawn';

    /** Statuses that count as "completed" — student must not re-enroll in this subject. */
    public static function completedStatuses(): array
    {
        return [self::STATUS_PASSED, self::STATUS_CREDITED];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** Whether this record represents a completed subject (passed or credited). */
    public function isCompleted(): bool
    {
        return in_array($this->status, self::completedStatuses(), true);
    }
}
