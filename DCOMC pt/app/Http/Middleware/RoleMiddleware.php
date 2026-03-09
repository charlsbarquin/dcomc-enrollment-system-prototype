<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // 1. If they aren't logged in at all, send them to the main student login
        if (!Auth::check()) {
            return redirect('/');
        }

        $user = Auth::user();
        $effectiveRole = method_exists($user, 'effectiveRole') ? $user->effectiveRole() : $user->role;

        // 2. If their role DOES NOT match the required role for this route
        if ($effectiveRole !== $role) {
            return redirect('/' . $effectiveRole . '/dashboard');
        }

        // 3. If they have the correct role, let them pass
        return $next($request);
    }
}