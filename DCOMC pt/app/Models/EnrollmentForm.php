<?php

namespace App\Models;

use App\Services\AcademicCalendarService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnrollmentForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_year_id',
        'title',
        'description',
        'questions',
        'is_active',
        'assigned_year',
        'assigned_semester',
        'incoming_year_level',
        'incoming_semester',
        'lock_course_major',
    ];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    // This tells Laravel to automatically convert the database JSON into a PHP Array
    protected $casts = [
        'questions' => 'array',
        'is_active' => 'boolean',
        'lock_course_major' => 'boolean',
    ];

    public function formResponses()
    {
        return $this->hasMany(FormResponse::class, 'enrollment_form_id');
    }

    /** Scope to enrollment forms for the currently selected school year (session). */
    public function scopeForSelectedSchoolYear(Builder $query): Builder
    {
        $id = AcademicCalendarService::getSelectedSchoolYearId();
        if ($id === null) {
            $activeId = AcademicCalendarService::getActiveSchoolYearId();
            if ($activeId !== null) {
                return $query->where('school_year_id', $activeId);
            }
            return $query->whereNull('school_year_id');
        }
        return $query->where('school_year_id', $id);
    }

    /**
     * Validate submitted answers against form questions (required keys, non-empty where required).
     * Questions array may contain items with: id, name, key, required (bool), type (optional).
     *
     * @return array<string> Empty if valid; list of error messages otherwise.
     */
    public function validateAnswers(array $answers): array
    {
        $questions = $this->questions ?? [];
        if (! is_array($questions)) {
            return [];
        }

        $errors = [];
        foreach ($questions as $index => $q) {
            if (! is_array($q)) {
                continue;
            }
            $key = $q['id'] ?? $q['name'] ?? $q['key'] ?? ('q_' . $index);
            $required = (bool) ($q['required'] ?? false);
            if (! $required) {
                continue;
            }
            $value = $answers[$index] ?? $answers[$key] ?? null;
            if ($value === null || $value === '') {
                $label = $q['label'] ?? $q['title'] ?? $key;
                $errors[] = "Answer for \"{$label}\" is required.";
            }
        }

        return $errors;
    }
}