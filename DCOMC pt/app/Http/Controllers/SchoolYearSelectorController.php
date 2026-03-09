<?php

namespace App\Http\Controllers;

use App\Models\SchoolYear;
use App\Services\AcademicCalendarService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class SchoolYearSelectorController extends Controller
{
    /**
     * Set the selected school year for the session and redirect back.
     * All data views will be scoped to this year.
     */
    public function set(Request $request): RedirectResponse
    {
        $user = $request->user();
        $role = $user && method_exists($user, 'effectiveRole') ? $user->effectiveRole() : ($user->role ?? null);
        $allowed = in_array($role, ['admin', 'registrar', 'dean', 'staff', 'unifast'], true);
        if (! $allowed) {
            abort(403, 'Only staff roles can change the school year filter.');
        }

        $validated = $request->validate([
            'school_year_id' => ['required', 'integer', 'exists:school_years,id'],
        ]);

        AcademicCalendarService::setSelectedSchoolYearId((int) $validated['school_year_id']);

        return redirect()->back()->with('success', 'School year filter updated. Data shown is now for the selected year.');
    }
}
