<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Visibility and permission for department_scope (professors = users with faculty_type, and rooms).
 * Registrar sees all; Dean Education sees education + all; Dean Entrepreneurship sees entrepreneurship + all.
 */
class SchedulingScopeService
{
    public const SCOPE_ALL = 'all';
    public const SCOPE_EDUCATION = 'education';
    public const SCOPE_ENTREPRENEURSHIP = 'entrepreneurship';

    /**
     * Scopes the given dean can assign when creating a professor or room.
     */
    public function allowedScopesForCreator(?User $user): array
    {
        if (! $user) {
            return [];
        }
        $role = method_exists($user, 'effectiveRole') ? $user->effectiveRole() : $user->role;
        if ($role === 'registrar' || ($role === 'admin' && ! $this->isAdminMirroringDean($user))) {
            return config('scheduling_scope.registrar_allowed_scopes', [self::SCOPE_ALL, self::SCOPE_EDUCATION, self::SCOPE_ENTREPRENEURSHIP]);
        }
        if ($role === 'dean') {
            $dept = $this->effectiveDeanDepartment($user);
            if ($dept && strtolower($dept->name) === 'education') {
                return config('scheduling_scope.dean_education_allowed_scopes', [self::SCOPE_ALL, self::SCOPE_EDUCATION]);
            }
            if ($dept && strtolower($dept->name) === 'entrepreneurship') {
                return config('scheduling_scope.dean_entrepreneurship_allowed_scopes', [self::SCOPE_ALL, self::SCOPE_ENTREPRENEURSHIP]);
            }
        }
        return [];
    }

    /**
     * Scope values visible to this user for professors/rooms (for dropdowns and listing).
     */
    public function visibleScopesForViewer(?User $user): array
    {
        if (! $user) {
            return [];
        }
        if ($user->role === 'registrar' || ($user->role === 'admin' && ! $this->isAdminMirroringDean($user))) {
            return [self::SCOPE_ALL, self::SCOPE_EDUCATION, self::SCOPE_ENTREPRENEURSHIP];
        }
        if ($user->role === 'dean' || $this->isAdminMirroringDean($user)) {
            $dept = $this->effectiveDeanDepartment($user);
            if ($dept && strtolower($dept->name) === 'education') {
                return [self::SCOPE_EDUCATION, self::SCOPE_ALL];
            }
            if ($dept && strtolower($dept->name) === 'entrepreneurship') {
                return [self::SCOPE_ENTREPRENEURSHIP, self::SCOPE_ALL];
            }
        }
        return [];
    }

    /**
     * Apply scope filter to a query for professors (users with faculty_type).
     */
    public function scopeProfessorsForViewer(Builder $query, ?User $viewer): Builder
    {
        $scopes = $this->visibleScopesForViewer($viewer);
        if (empty($scopes)) {
            return $query->whereRaw('1 = 0');
        }
        return $query->where(function ($q) use ($scopes) {
            $q->whereIn('department_scope', $scopes)
                ->orWhereNull('department_scope');
        });
    }

    /**
     * Apply scope filter to a query for rooms.
     */
    public function scopeRoomsForViewer(Builder $query, ?User $viewer): Builder
    {
        $scopes = $this->visibleScopesForViewer($viewer);
        if (empty($scopes)) {
            return $query->whereRaw('1 = 0');
        }
        return $query->where(function ($q) use ($scopes) {
            $q->whereIn('department_scope', $scopes)
                ->orWhereNull('department_scope');
        });
    }

    /**
     * Whether the creator is allowed to set this scope.
     */
    public function isScopeAllowedForCreator(?User $user, string $scope): bool
    {
        return in_array(strtolower($scope), array_map('strtolower', $this->allowedScopesForCreator($user)), true);
    }

    /**
     * Dean's department as scope string (education | entrepreneurship) or null.
     */
    public function deanScope(?User $dean): ?string
    {
        if (! $dean) {
            return null;
        }
        $dept = $this->effectiveDeanDepartment($dean);
        if (! $dept) {
            return null;
        }
        $name = strtolower($dept->name);
        if (str_contains($name, 'education')) {
            return self::SCOPE_EDUCATION;
        }
        if (str_contains($name, 'entrepreneurship')) {
            return self::SCOPE_ENTREPRENEURSHIP;
        }
        return null;
    }

    private function isAdminMirroringDean(?User $user): bool
    {
        if (! $user || $user->role !== 'admin') {
            return false;
        }
        $switch = session('role_switch');
        return is_array($switch) && ($switch['active'] ?? false) && ($switch['as_role'] ?? '') === 'dean';
    }

    /**
     * Department to use for dean scope when user is a real dean or admin mirroring dean.
     */
    private function effectiveDeanDepartment(?User $user): ?Department
    {
        if (! $user) {
            return null;
        }
        $switch = session('role_switch');
        if ($user->role === 'admin' && is_array($switch) && ($switch['active'] ?? false) && ($switch['as_role'] ?? '') === 'dean') {
            $deptId = $switch['department_id'] ?? null;
            if ($deptId !== null && $deptId !== '') {
                return Department::find((int) $deptId);
            }
            return null;
        }
        if ($user->role === 'dean' && $user->department) {
            return $user->department;
        }
        return null;
    }

    /**
     * Check if professor (user) scope is compatible with dean's department.
     */
    public function professorScopeCompatibleWithDean(User $professor, User $dean): bool
    {
        $deanScope = $this->deanScope($dean);
        if ($deanScope === null) {
            return true;
        }
        $scope = strtolower((string) ($professor->department_scope ?? 'all'));
        if ($scope === 'all') {
            return true;
        }
        return $scope === $deanScope;
    }

    /**
     * Check if room scope is compatible with dean's department.
     */
    public function roomScopeCompatibleWithDean($room, User $dean): bool
    {
        $deanScope = $this->deanScope($dean);
        if ($deanScope === null) {
            return true;
        }
        $scope = strtolower((string) ($room->department_scope ?? 'all'));
        if ($scope === 'all') {
            return true;
        }
        return $scope === $deanScope;
    }
}
