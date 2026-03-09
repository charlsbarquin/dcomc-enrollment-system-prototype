<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminRoleSwitchController extends Controller
{
    public function start(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Only administrators can use role switch.');
        }

        $rules = [
            'role' => ['required', 'string', 'in:' . implode(',', User::nonAdminRoles())],
        ];
        if ($request->input('role') === 'dean') {
            $rules['department_id'] = ['required', 'integer', 'exists:departments,id'];
        }
        $validated = $request->validate($rules);

        $payload = [
            'active' => true,
            'as_role' => $validated['role'],
            'original_role' => $user->role,
            'started_at' => now()->toIso8601String(),
        ];
        if ($validated['role'] === 'dean' && ! empty($validated['department_id'])) {
            $dept = Department::find((int) $validated['department_id']);
            if ($dept && in_array(strtolower($dept->name), ['education', 'entrepreneurship'], true)) {
                $payload['department_id'] = (int) $validated['department_id'];
            }
        }
        $request->session()->put('role_switch', $payload);

        $message = 'Role switch active. You are now mirroring ' . strtoupper($validated['role']);
        if (! empty($payload['department_id'] ?? null)) {
            $deptForMessage = Department::find($payload['department_id']);
            $message .= ' (' . ($deptForMessage ? $deptForMessage->name : 'Dean') . ')';
        }
        $message .= '.';

        return redirect('/' . $validated['role'] . '/dashboard')
            ->with('success', $message);
    }

    public function stop(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Only administrators can stop role switch.');
        }

        $request->session()->forget('role_switch');

        return redirect()->route('admin.dashboard')->with('success', 'Role switch ended. You are back to ADMIN.');
    }
}
