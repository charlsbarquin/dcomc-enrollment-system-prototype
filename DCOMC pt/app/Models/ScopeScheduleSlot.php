<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScopeScheduleSlot extends Model
{
    protected $fillable = [
        'program_id',
        'academic_year_level_id',
        'block_id',
        'shift',
        'semester',
        'subject_id',
        'day_of_week',
        'start_time',
        'end_time',
        'room_id',
        'professor_id',
        'is_overload',
        'school_year',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_overload' => 'boolean',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function academicYearLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicYearLevel::class, 'academic_year_level_id');
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professor_id');
    }

    public static function dayName(int $dayOfWeek): string
    {
        $days = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
        return $days[$dayOfWeek] ?? 'Day ' . $dayOfWeek;
    }
}
