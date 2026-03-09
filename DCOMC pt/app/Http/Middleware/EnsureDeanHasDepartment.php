<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeanHasDepartment
{
    /**
     * Deans must have department_id set for department-based schedule isolation.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->role === 'dean' && ($user->department_id === null || $user->department_id === '')) {
            return redirect()->route('dean.dashboard')
                ->with('error', 'Your account is not assigned to a department. Contact the administrator to set your department (Education or Entrepreneurship).');
        }

        return $next($request);
    }
}
