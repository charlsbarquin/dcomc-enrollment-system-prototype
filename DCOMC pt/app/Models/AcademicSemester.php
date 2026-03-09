<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicSemester extends Model
{
    /** Canonical values - use only these for clean data. */
    public const CANONICAL = ['First Semester', 'Second Semester'];

    protected $fillable = [
        'name',
        'is_active',
    ];
}
