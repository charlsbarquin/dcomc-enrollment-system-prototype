<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicCalendarSetting extends Model
{
    protected $fillable = [
        'active_school_year_id',
        'first_semester_start_month',
        'first_semester_end_month',
        'second_semester_start_month',
        'second_semester_end_month',
        'midyear_start_month',
        'midyear_end_month',
    ];

    protected $casts = [
        'first_semester_start_month' => 'integer',
        'first_semester_end_month' => 'integer',
        'second_semester_start_month' => 'integer',
        'second_semester_end_month' => 'integer',
        'midyear_start_month' => 'integer',
        'midyear_end_month' => 'integer',
    ];

    public function activeSchoolYear()
    {
        return $this->belongsTo(SchoolYear::class, 'active_school_year_id');
    }

    /** Get the label of the active school year (e.g. 2026-2027) or null. */
    public function getActiveSchoolYearLabel(): ?string
    {
        $sy = $this->activeSchoolYear;
        return $sy ? $sy->label : null;
    }
}
