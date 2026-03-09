<?php

namespace App\Http\Middleware;

use App\Models\StaffFeatureAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict staff routes by feature flag. Use as: middleware('staff.feature:staff_admission_responses')
 */
class CheckStaffFeatureAccess
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'staff') {
            return $next($request);
        }

        if (! StaffFeatureAccess::isEnabledForUser($user, $feature)) {
            abort(403, 'You do not have access to this feature. Contact your administrator.');
        }

        return $next($request);
    }
}
