<?php

namespace App\Services;

use App\Models\ClassSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Professor workload & teaching policy engine.
 *
 * Employment type rules (enforced in validation):
 * (a) Permanent: weekdays only (Mon–Fri), time within 08:00–17:00 is standard; max 24 units.
 *     Outside 8am–5pm (start before 08:00 or end after 17:00) is allowed but marked as overload.
 * (b) Part-time: weekends only (Sat/Sun). Scheduling on weekdays is rejected.
 * (c) COS: no day or time restriction.
 *
 * Overload: For permanent faculty, any schedule outside 08:00–17:00 (e.g. past 5pm) is "overload".
 */
class ProfessorWorkloadService
{
    /** Day of week: 1 = Monday .. 7 = Sunday. Weekdays = 1–5, Weekend = 6–7 */
    public const WEEKDAY_MON = 1;
    public const WEEKDAY_TUE = 2;
    public const WEEKDAY_WED = 3;
    public const WEEKDAY_THU = 4;
    public const WEEKDAY_FRI = 5;
    public const WEEKEND_SAT = 6;
    public const WEEKEND_SUN = 7;

    public const EMPLOYMENT_PERMANENT = 'permanent';
    public const EMPLOYMENT_PART_TIME = 'part_time';
    public const EMPLOYMENT_COS = 'cos';

    /** Standard hours for permanent: 08:00–17:00. Outside this = overload. */
    public const STANDARD_START = '08:00';
    public const STANDARD_END = '17:00';

    /**
     * Total units assigned to professor for a given term (optional semester/school_year).
     */
    public function getTotalAssignedUnits(
        int $professorId,
        ?string $semester = null,
        ?string $schoolYear = null
    ): int {
        $query = ClassSchedule::query()
            ->where('professor_id', $professorId)
            ->with('subject');
        if ($semester !== null && $semester !== '') {
            $query->where('semester', $semester);
        }
        if ($schoolYear !== null && $schoolYear !== '') {
            $query->where('school_year', $schoolYear);
        }
        return (int) $query->get()->sum(fn ($s) => (int) ($s->subject->units ?? 0));
    }

    /**
     * Whether professor exceeds max_units (unit overload).
     */
    public function isUnitOverload(User $professor, ?string $semester = null, ?string $schoolYear = null): bool
    {
        $maxUnits = (int) ($professor->max_units ?? ($professor->faculty_type === self::EMPLOYMENT_PERMANENT ? 24 : 0));
        if ($maxUnits <= 0) {
            return false;
        }
        $total = $this->getTotalAssignedUnits($professor->id, $semester, $schoolYear);
        return $total > $maxUnits;
    }

    /**
     * Employment type from user (faculty_type).
     */
    public function getEmploymentType(User $professor): string
    {
        $t = strtolower((string) ($professor->faculty_type ?? 'cos'));
        if (in_array($t, [self::EMPLOYMENT_PERMANENT, 'permanent'], true)) {
            return self::EMPLOYMENT_PERMANENT;
        }
        if (in_array($t, [self::EMPLOYMENT_PART_TIME, 'part-time', 'part_time'], true)) {
            return self::EMPLOYMENT_PART_TIME;
        }
        return self::EMPLOYMENT_COS;
    }

    /**
     * Validate schedule against employment rules.
     * Returns null if valid; otherwise error message.
     * (a) Permanent: weekdays only; time can be any (outside 08:00–17:00 = overload, set by caller).
     * (b) Part-time: weekends only.
     * (c) COS: no restriction.
     */
    public function validateEmploymentRules(
        User $professor,
        int $dayOfWeek,
        string $startTime,
        string $endTime
    ): ?string {
        $employment = $this->getEmploymentType($professor);

        if ($employment === self::EMPLOYMENT_PART_TIME) {
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                return 'Part-time professors can teach weekends only (Sat/Sun).';
            }
        }

        if ($employment === self::EMPLOYMENT_PERMANENT) {
            if ($dayOfWeek >= 6) {
                return 'Permanent professors can only be scheduled on weekdays (Monday–Friday).';
            }
            // Time: within 08:00–17:00 is standard; outside = overload (allowed, caller sets is_overload)
        }

        return null;
    }

    /**
     * Whether this schedule slot should be marked as time overload.
     * Permanent only: true if start before 08:00 or end after 17:00.
     */
    public function shouldMarkTimeOverload(User $professor, string $startTime, string $endTime): bool
    {
        $employment = $this->getEmploymentType($professor);
        if ($employment !== self::EMPLOYMENT_PERMANENT) {
            return false;
        }
        $start = strtotime($startTime ?: self::STANDARD_START);
        $end = strtotime($endTime ?: self::STANDARD_END);
        $standardStart = strtotime(self::STANDARD_START);
        $standardEnd = strtotime(self::STANDARD_END);
        return $start < $standardStart || $end > $standardEnd;
    }

    /**
     * Whether the provided times are within standard working hours (08:00–17:00 inclusive).
     */
    public function isWithinStandardHours(string $startTime, string $endTime): bool
    {
        $s = strtotime($startTime ?: self::STANDARD_START);
        $e = strtotime($endTime ?: self::STANDARD_END);
        return $s >= strtotime(self::STANDARD_START) && $e <= strtotime(self::STANDARD_END);
    }

    /**
     * Determine if assigning additional units would exceed professor's max units.
     */
    public function willExceedMaxUnits(User $professor, int $additionalUnits, ?string $semester = null, ?string $schoolYear = null): bool
    {
        $maxUnits = (int) ($professor->max_units ?? ($professor->faculty_type === self::EMPLOYMENT_PERMANENT ? 24 : 0));
        if ($maxUnits <= 0) {
            return false;
        }
        $total = $this->getTotalAssignedUnits($professor->id, $semester, $schoolYear);
        return ($total + $additionalUnits) > $maxUnits;
    }

    /**
     * Check for time conflict on a base query (e.g. same professor + same day).
     */
    public function hasTimeConflict(Builder $baseQuery, string $startTime, string $endTime, ?int $excludeScheduleId = null): bool
    {
        $q = (clone $baseQuery)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($inner) use ($startTime, $endTime) {
                        $inner->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            });
        if ($excludeScheduleId !== null) {
            $q->where('id', '!=', $excludeScheduleId);
        }
        return $q->exists();
    }

    /**
     * Get default max_units by employment type (for display/defaults).
     */
    public function defaultMaxUnits(string $employmentType): int
    {
        if ($employmentType === self::EMPLOYMENT_PERMANENT) {
            return 24;
        }
        if ($employmentType === self::EMPLOYMENT_PART_TIME) {
            return 12;
        }
        return 18;
    }
}
