<?php

namespace App\Http\Middleware;

use App\Services\AcademicCalendarService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSelectedSchoolYear
{
    /**
     * Ensure session has a selected_school_year_id for staff roles (registrar, dean, unifast, admin).
     * Defaults to active school year. Does not run for student or guest.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $role = $user->role ?? '';
        $staffRoles = ['admin', 'registrar', 'dean', 'staff', 'unifast'];
        if (! in_array($role, $staffRoles, true)) {
            return $next($request);
        }

        if (! $request->session()->has('selected_school_year_id')) {
            $activeId = AcademicCalendarService::getActiveSchoolYearId();
            AcademicCalendarService::setSelectedSchoolYearId($activeId);
        }

        return $next($request);
    }
}
