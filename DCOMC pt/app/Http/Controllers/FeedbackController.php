<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{
    public function create(Request $request): \Illuminate\View\View
    {
        $user = Auth::user();
        $role = $user->effectiveRole();

        $createRoute = match ($role) {
            'admin' => 'admin.feedback.create',
            'registrar' => 'registrar.feedback',
            'staff' => 'staff.feedback',
            'dean' => 'dean.feedback',
            'unifast' => 'unifast.feedback',
            'student' => 'student.feedback',
            default => 'admin.feedback.create',
        };

        $storeRoute = match ($role) {
            'admin' => 'admin.feedback.store',
            'registrar' => 'registrar.feedback.store',
            'staff' => 'staff.feedback.store',
            'dean' => 'dean.feedback.store',
            'unifast' => 'unifast.feedback.store',
            'student' => 'student.feedback.store',
            default => 'admin.feedback.store',
        };

        $backRoute = match ($role) {
            'admin' => 'admin.dashboard',
            'registrar' => 'registrar.dashboard',
            'staff' => 'staff.dashboard',
            'dean' => 'dean.dashboard',
            'unifast' => 'unifast.dashboard',
            'student' => 'student.dashboard',
            default => 'admin.dashboard',
        };

        return view('dashboards.feedback-create', [
            'storeRoute' => $storeRoute,
            'backRoute' => $backRoute,
            'roleLabel' => ucfirst($role),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'min:3', 'max:160'],
            'message' => ['required', 'string', 'min:5', 'max:5000'],
            'priority' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5])],
        ]);

        $user = Auth::user();
        $role = $user->effectiveRole();

        Feedback::create([
            'user_id' => $user->id,
            'role' => $role,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'priority' => (int) $validated['priority'],
        ]);

        $backRoute = match ($role) {
            'admin' => 'admin.feedback.index',
            'registrar' => 'registrar.dashboard',
            'staff' => 'staff.dashboard',
            'dean' => 'dean.dashboard',
            'unifast' => 'unifast.dashboard',
            'student' => 'student.dashboard',
            default => 'admin.dashboard',
        };

        return redirect()->route($backRoute)->with('success', 'Thank you! Your feedback has been submitted.');
    }
}
