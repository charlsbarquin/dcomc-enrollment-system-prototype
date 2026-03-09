<?php

namespace App\Http\Middleware;

use App\Models\UnifastFeatureAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict unifast routes by feature flag. Use as: middleware('unifast.feature:unifast_fees')
 */
class CheckUnifastFeatureAccess
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'unifast') {
            return $next($request);
        }

        if (! UnifastFeatureAccess::isEnabledForUser($user, $feature)) {
            abort(403, 'You do not have access to this feature. Contact your administrator.');
        }

        return $next($request);
    }
}
