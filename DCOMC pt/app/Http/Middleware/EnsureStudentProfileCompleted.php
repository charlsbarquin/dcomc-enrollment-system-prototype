<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentProfileCompleted
{
    /** Routes that are allowed when profile is not completed (so student can complete the form). */
    private const ALLOWED_WHEN_INCOMPLETE = [
        'student.profile',
        'student.profile.update',
    ];

    /**
     * Force students to complete their profile before accessing the dashboard or any other student page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'student') {
            return $next($request);
        }

        if ($user->profile_completed) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if ($routeName && in_array($routeName, self::ALLOWED_WHEN_INCOMPLETE, true)) {
            return $next($request);
        }

        return redirect()->route('student.profile')
            ->with('info', 'Please complete your personal information below to access your dashboard.');
    }
}
