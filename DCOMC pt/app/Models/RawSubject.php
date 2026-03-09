<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RawSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'units',
        'prerequisites',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'raw_subject_id');
    }

    public function professorAssignments(): HasMany
    {
        return $this->hasMany(ProfessorSubjectAssignment::class, 'raw_subject_id');
    }

    /** Display label: "CODE - Title (N units)" */
    public function getDisplayLabelAttribute(): string
    {
        return $this->code . ' - ' . $this->title . ' (' . (int) $this->units . ' units)';
    }
}
