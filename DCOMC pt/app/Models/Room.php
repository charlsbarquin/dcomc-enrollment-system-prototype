<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'capacity',
        'building',
        'is_active',
        'department_id',
        'department_scope',
        'created_by_role',
        'created_by_user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function schedules()
    {
        return $this->hasMany(ClassSchedule::class);
    }

    /** Slots from Schedule by Program (scope_schedule_slots). */
    public function scopeScheduleSlots(): HasMany
    {
        return $this->hasMany(\App\Models\ScopeScheduleSlot::class);
    }
}

