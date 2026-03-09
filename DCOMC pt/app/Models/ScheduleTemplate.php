<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property array $template ['subject_ids' => int[], 'fees' => array, 'slots' => array{subject_id, day_of_week, start_time, end_time, room_id, professor_id}[]] */

class ScheduleTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'program',
        'program_id',
        'major',
        'year_level',
        'academic_year_level_id',
        'semester',
        'school_year',
        'block_id',
        'template',
        'is_active',
        'created_by',
    ];

    public function programRelation(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function academicYearLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicYearLevel::class, 'academic_year_level_id');
    }

    protected $casts = [
        'template' => 'array',
        'is_active' => 'boolean',
    ];

    public function block()
    {
        return $this->belongsTo(Block::class);
    }

    /** Template structure: ['subject_ids' => [1,2,3], 'fees' => [['fee_id' => 1, 'amount' => 100], ...]] */
    public function getSubjectIds(): array
    {
        $t = $this->template;
        return is_array($t['subject_ids'] ?? null) ? $t['subject_ids'] : [];
    }

    public function getFeeEntries(): array
    {
        $t = $this->template;
        return is_array($t['fees'] ?? null) ? $t['fees'] : [];
    }

    /** Slots for Create Schedule: one per subject (day, time, room, professor). */
    public function getSlots(): array
    {
        $t = $this->template;
        return is_array($t['slots'] ?? null) ? $t['slots'] : [];
    }
}


