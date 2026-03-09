<?php

namespace App\Services;

use App\Models\AcademicCalendarSetting;
use App\Models\AcademicYearLevel;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Active school year, semester-by-month (Philippine calendar), and enrollment reset.
 */
class AcademicCalendarService
{
    /** @var AcademicCalendarSetting|null */
    protected static $settings;

    public static function settings(): ?AcademicCalendarSetting
    {
        if (self::$settings === null) {
            self::$settings = AcademicCalendarSetting::with('activeSchoolYear')->first();
        }
        return self::$settings;
    }

    /** Current active school year ID or null. */
    public static function getActiveSchoolYearId(): ?int
    {
        $s = self::settings();
        return $s && $s->active_school_year_id ? (int) $s->active_school_year_id : null;
    }

    /** Current active school year label (e.g. 2026-2027) or null. */
    public static function getActiveSchoolYearLabel(): ?string
    {
        $s = self::settings();
        return $s ? $s->getActiveSchoolYearLabel() : null;
    }

    /**
     * Selected school year for the current request (session-scoped).
     * Used by registrar/dean/unifast/admin to filter all data by year.
     * Defaults to active school year if not set.
     */
    public static function getSelectedSchoolYearId(): ?int
    {
        if (Session::has('selected_school_year_id')) {
            $id = Session::get('selected_school_year_id');
            return $id !== null && $id !== '' ? (int) $id : self::getActiveSchoolYearId();
        }
        return self::getActiveSchoolYearId();
    }

    /** Label for the currently selected school year (e.g. 2025-2026). */
    public static function getSelectedSchoolYearLabel(): ?string
    {
        $id = self::getSelectedSchoolYearId();
        if ($id === null) {
            return null;
        }
        $sy = SchoolYear::find($id);
        return $sy ? $sy->label : null;
    }

    /** Set the selected school year for the session (used when user changes dropdown). */
    public static function setSelectedSchoolYearId(?int $schoolYearId): void
    {
        Session::put('selected_school_year_id', $schoolYearId);
    }

    /** Whether the student is considered enrolled for the active SY (status + school_year match). */
    public static function isStudentEnrolledForActiveSy(User $user): bool
    {
        if ($user->role !== 'student') {
            return false;
        }
        $activeLabel = self::getActiveSchoolYearLabel();
        if ($activeLabel === null || $activeLabel === '') {
            return in_array(strtolower(trim((string) ($user->student_status ?? ''))), ['enrolled', 'regular', 'irregular'], true);
        }
        $userSy = trim((string) ($user->school_year ?? ''));
        $status = strtolower(trim((string) ($user->student_status ?? '')));
        return $userSy === $activeLabel && in_array($status, ['enrolled', 'regular', 'irregular'], true);
    }

    /**
     * Reset all students to Not Enrolled (e.g. when moving to a new school year).
     * Call this when the Registrar sets a new Active School Year.
     */
    public static function resetAllStudentsToNotEnrolled(): int
    {
        return User::where('role', 'student')->update(['student_status' => 'Not Enrolled']);
    }

    /**
     * Set active school year. If the ID/label changed, resets all students to Not Enrolled.
     * Returns count of students reset.
     */
    public static function setActiveSchoolYear(?int $schoolYearId): int
    {
        $settings = self::settings();
        if (!$settings) {
            $settings = new AcademicCalendarSetting();
            $settings->first_semester_start_month = 8;
            $settings->first_semester_end_month = 12;
            $settings->second_semester_start_month = 1;
            $settings->second_semester_end_month = 5;
        }
        $previousId = $settings->active_school_year_id;
        $settings->active_school_year_id = $schoolYearId;
        $settings->save();
        self::$settings = $settings;

        $count = 0;
        if ($previousId !== $schoolYearId) {
            $count = self::resetAllStudentsToNotEnrolled();
        }
        return $count;
    }

    /**
     * Get current semester name from current month using calendar settings.
     * Returns 'First Semester', 'Second Semester', 'Midyear', or null.
     */
    public static function getCurrentSemesterFromMonth(int $month): ?string
    {
        $s = self::settings();
        if (!$s) {
            if ($month >= 8 || $month <= 2) {
                return $month >= 8 ? 'First Semester' : 'Second Semester';
            }
            return null;
        }
        if ($month >= $s->first_semester_start_month && $month <= $s->first_semester_end_month) {
            return 'First Semester';
        }
        if ($month >= $s->second_semester_start_month && $month <= $s->second_semester_end_month) {
            return 'Second Semester';
        }
        if ($s->midyear_start_month !== null && $s->midyear_end_month !== null
            && $month >= $s->midyear_start_month && $month <= $s->midyear_end_month) {
            return 'Midyear';
        }
        return null;
    }

    /**
     * Whether the current date is past the end month of the given semester (suggest opening next term).
     */
    public static function isPastSemesterEnd(string $semesterName, int $month): bool
    {
        $s = self::settings();
        if (!$s) {
            return false;
        }
        if ($semesterName === 'First Semester' && $month > $s->first_semester_end_month) {
            return true;
        }
        if ($semesterName === 'Second Semester' && $month > $s->second_semester_end_month) {
            return true;
        }
        if ($semesterName === 'Midyear' && $s->midyear_end_month !== null && $month > $s->midyear_end_month) {
            return true;
        }
        return false;
    }

    /**
     * Next year level name (e.g. 1st Year -> 2nd Year). Returns null if no next level.
     */
    public static function getNextYearLevelName(string $currentYearLevelName): ?string
    {
        $levels = AcademicYearLevel::where('is_active', true)->orderBy('name')->pluck('name')->values()->all();
        $current = trim($currentYearLevelName);
        $found = false;
        foreach ($levels as $name) {
            if ($found) {
                return $name;
            }
            if (trim($name) === $current) {
                $found = true;
            }
        }
        return null;
    }

    /**
     * When a student enrolls for a new school year (not same as current), increment year level.
     */
    public static function maybeIncrementYearLevelForNewSy(User $user, string $enrollmentSchoolYear): void
    {
        $currentSy = trim((string) ($user->school_year ?? ''));
        if ($currentSy === '' || $currentSy === $enrollmentSchoolYear) {
            return;
        }
        $next = self::getNextYearLevelName((string) ($user->year_level ?? ''));
        if ($next !== null) {
            $user->year_level = $next;
            $user->save();
        }
    }
}
