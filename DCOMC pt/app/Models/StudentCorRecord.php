<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable snapshot of a student's COR (Course Outline) per subject after deployment.
 * Data must not change when schedule/professor/room are edited later.
 */
class StudentCorRecord extends Model
{
    protected $fillable = [
        'student_id',
        'subject_id',
        'professor_id',
        'is_overload',
        'professor_name_snapshot',
        'room_name_snapshot',
        'days_snapshot',
        'start_time_snapshot',
        'end_time_snapshot',
        'program_id',
        'year_level',
        'block_id',
        'shift',
        'semester',
        'school_year',
        'cor_source',
        'deployed_by',
        'deployed_at',
    ];

    public const COR_SOURCE_SCHEDULE_BY_PROGRAM = 'schedule_by_program';
    public const COR_SOURCE_CREATE_SCHEDULE = 'create_schedule';

    protected $casts = [
        'is_overload' => 'boolean',
        'start_time_snapshot' => 'datetime:H:i',
        'end_time_snapshot' => 'datetime:H:i',
        'deployed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professor_id');
    }

    public function deployedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deployed_by');
    }
}
