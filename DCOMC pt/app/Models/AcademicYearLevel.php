<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYearLevel extends Model
{
    /** Canonical values - use only these for clean data. */
    public const CANONICAL = ['1st Year', '2nd Year', '3rd Year', '4th Year'];

    protected $fillable = [
        'name',
        'is_active',
    ];
}
