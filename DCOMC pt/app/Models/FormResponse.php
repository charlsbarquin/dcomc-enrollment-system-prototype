<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FormResponse extends Model
{
    protected $fillable = [
        'enrollment_form_id',
        'user_id',
        'preferred_block_id',
        'assigned_block_id',
        'answers',
        'approval_status',
        'process_status',
        'process_notes',
        'reviewed_by',
        'reviewed_at',
        'reviewed_by_role',
    ];
    protected $casts = [
        'answers' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function enrollmentForm()
    {
        return $this->belongsTo(EnrollmentForm::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function preferredBlock()
    {
        return $this->belongsTo(Block::class, 'preferred_block_id');
    }

    public function assignedBlock()
    {
        return $this->belongsTo(Block::class, 'assigned_block_id');
    }

    /** Scope to responses whose enrollment form belongs to the currently selected school year. */
    public function scopeForSelectedSchoolYear(Builder $query): Builder
    {
        return $query->whereHas('enrollmentForm', function (Builder $q) {
            $q->forSelectedSchoolYear();
        });
    }
}
