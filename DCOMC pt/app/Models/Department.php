<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = ['name', 'all_professors_all_subjects'];

    protected $casts = [
        'all_professors_all_subjects' => 'boolean',
    ];

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class, 'department_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'department_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'department_id');
    }

    /** Name for Entrepreneurship department (for strict isolation). */
    public const NAME_ENTREPRENEURSHIP = 'Entrepreneurship';

    /** Name for Education department (all non-Entrepreneurship programs). */
    public const NAME_EDUCATION = 'Education';
}
