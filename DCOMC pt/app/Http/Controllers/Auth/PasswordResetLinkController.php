<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     * When ?portal=student, show student-friendly form (School ID instead of Email).
     */
    public function create(Request $request): View
    {
        $isStudent = $request->query('portal') === 'student';
        return view('auth.forgot-password', ['isStudentPortal' => $isStudent]);
    }

    /**
     * Handle an incoming password reset link request.
     * For student portal: accept Student ID / School ID and look up user by email or school_id.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $isStudent = $request->input('portal_type') === 'student';

        if ($isStudent) {
            $request->validate([
                'email' => ['required', 'string', 'max:255'],
                'portal_type' => ['nullable', 'string', 'in:student'],
            ]);
            $input = trim((string) $request->input('email'));
            $user = User::query()
                ->where('role', User::ROLE_STUDENT)
                ->where(function ($q) use ($input) {
                    $q->where('email', $input)
                        ->orWhere('school_id', $input);
                })
                ->first();

            if (! $user) {
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => 'No student account found with this Student ID / School ID.']);
            }

            $status = Password::sendResetLink(['email' => $user->email]);
        } else {
            $request->validate([
                'email' => ['required', 'email'],
            ]);
            $status = Password::sendResetLink($request->only('email'));
        }

        return $status == Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }
}
