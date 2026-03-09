<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view (Breeze default, safely ignored by our custom routes).
     */
    public function create(): View
    {
        return view('auth.login-student');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $portal = $request->input('portal_type');
        $request->validate([
            'email' => $portal === 'student' ? ['required', 'string'] : ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'portal_type' => ['required', 'string', 'in:student,dcomc,admin'],
        ]);
        $portalLoginRoute = match ($portal) {
            'admin' => 'login.admin',
            'dcomc' => 'login.dcomc',
            default => 'login',
        };

        $request->session()->forget('role_switch');

        $loginInput = trim((string) $request->input('email'));
        $password = (string) $request->input('password');
        $remember = $request->boolean('remember');

        $authenticated = false;

        if ($portal === 'student') {
            // Student portal: allow login with email OR school_id (Student ID / School ID)
            $user = User::query()
                ->where('role', 'student')
                ->where(function ($q) use ($loginInput) {
                    $q->where('email', $loginInput)
                        ->orWhere('school_id', $loginInput);
                })
                ->first();
            if ($user && Hash::check($password, $user->password)) {
                Auth::login($user, $remember);
                $request->session()->regenerate();
                $authenticated = true;
            }
        } else {
            $email = strtolower($loginInput);
            if (Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
                $request->session()->regenerate();
                $authenticated = true;
            }
        }

        if (! $authenticated) {
            $message = $portal === 'student'
                ? 'Invalid credentials. Please check your Student ID / School ID and password.'
                : 'Invalid credentials. Please check your email and password.';
            return redirect()
                ->route($portalLoginRoute)
                ->withInput($request->only('email'))
                ->withErrors(['email' => $message]);
        }

        $user = $request->user();

        // STRICT LOGIN CHECK: Kick them out if they use the wrong portal!
        if ($portal === 'admin' && $user->role !== 'admin') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route($portalLoginRoute)->withErrors(['email' => 'Only Admins can use this portal.']);
        }
        if ($portal === 'dcomc' && !in_array($user->role, ['registrar', 'staff', 'dean', 'unifast'])) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route($portalLoginRoute)->withErrors(['email' => 'Only DCOMC Staff can use this portal.']);
        }
        if ($portal === 'student' && $user->role !== 'student') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route($portalLoginRoute)->withErrors(['email' => 'Only Students can use this portal.']);
        }

        // Send them to their specific dashboard based on their role.
        // Avoid using "intended" here because stale intended URLs (e.g. "/")
        // can incorrectly bounce users to another portal login page.
        $url = '/' . $user->role . '/dashboard';
        $request->session()->forget('url.intended');

        return redirect($url);
    }

    /**
     * Destroy an authenticated session (Logout).
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (
            $user &&
            method_exists($user, 'isAdmin') &&
            $user->isAdmin() &&
            $request->session()->has('role_switch')
        ) {
            return back()->withErrors([
                'logout' => 'You cannot log out while role switch is active. Switch back to admin first.',
            ]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // Send them back to the default Student portal after logging out
        return redirect('/');
    }
}