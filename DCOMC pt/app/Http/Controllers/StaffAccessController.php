<?php

namespace App\Http\Controllers;

use App\Models\StaffFeatureAccess;
use App\Models\StaffFeatureUserAccess;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class StaffAccessController extends Controller
{
    /**
     * Feature groups (modules) and their staff-togglable features.
     *
     * Keys are used in DB; labels/descriptions are for UI only.
     */
    protected function featureGroups(): array
    {
        return [
            'admission' => [
                'label' => 'Admission',
                'features' => [
                    'staff_admission_manual_register' => [
                        'label' => 'Manual Register',
                        'description' => 'Access to manual registration.',
                    ],
                    'staff_admission_form_builder' => [
                        'label' => 'Form Builder',
                        'description' => 'Access to enrollment form builder.',
                    ],
                    'staff_admission_responses' => [
                        'label' => 'Responses',
                        'description' => 'Access to registration responses and response folders.',
                    ],
                ],
            ],
            'student_records' => [
                'label' => 'Student Records',
                'features' => [
                    'staff_student_records_students_explorer' => [
                        'label' => 'Students Explorer',
                        'description' => 'Access to Students Explorer (tree + table + edit student record).',
                    ],
                    'staff_student_records_irregularities' => [
                        'label' => 'Irregularities',
                        'description' => 'Access to irregular students and create-schedule workflow.',
                    ],
                    'staff_student_records_blocks' => [
                        'label' => 'Blocks',
                        'description' => 'Access to Block Management table (code, program, year/sem, shift, capacity, current).',
                    ],
                    'staff_student_records_block_requests' => [
                        'label' => 'Block Requests',
                        'description' => 'Access to block change requests.',
                    ],
                    'staff_student_records_cor_archive_irregular' => [
                        'label' => 'COR Archive Irregular',
                        'description' => 'Access to irregular COR archive.',
                    ],
                    'staff_student_records_cor_archive' => [
                        'label' => 'COR Archive',
                        'description' => 'Access to COR archive.',
                    ],
                ],
            ],
            'schedule' => [
                'label' => 'Schedule',
                'features' => [
                    'staff_schedule_program_schedule' => [
                        'label' => 'Program Schedule',
                        'description' => 'Access to program schedule folders and table.',
                    ],
                ],
            ],
            'reports' => [
                'label' => 'Reports',
                'features' => [
                    'staff_reports_analytics' => [
                        'label' => 'Analytics',
                        'description' => 'Access to analytics dashboard.',
                    ],
                    'staff_reports_reports' => [
                        'label' => 'Reports',
                        'description' => 'Access to system reports.',
                    ],
                ],
            ],
            'settings' => [
                'label' => 'Settings',
                'features' => [
                    'staff_settings_fee_settings' => [
                        'label' => 'Fee Settings',
                        'description' => 'Access to program/year fee configuration screens.',
                    ],
                ],
            ],
        ];
    }

    /**
     * Flatten feature groups to a single-level [featureKey => meta] array.
     */
    protected function featureDefinitions(): array
    {
        $groups = $this->featureGroups();
        $flat = [];

        foreach ($groups as $groupKey => $group) {
            $groupLabel = $group['label'] ?? ucfirst(str_replace('_', ' ', $groupKey));
            foreach ($group['features'] as $featureKey => $meta) {
                $flat[$featureKey] = array_merge($meta, [
                    'group' => $groupKey,
                    'group_label' => $groupLabel,
                ]);
            }
        }

        return $flat;
    }

    /**
     * Show the Staff Access settings page for Admin / Registrar.
     */
    public function index(Request $request): View
    {
        $features = $this->featureDefinitions();
        $featureKeys = array_keys($features);
        $states = StaffFeatureAccess::statesFor($featureKeys);

        $search = trim($request->string('search')->toString());

        $staffQuery = User::query()
            ->where('role', User::ROLE_STAFF)
            ->orderBy('name');

        if ($search !== '') {
            $staffQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        $staffUsers = $staffQuery->limit(100)->get(['id', 'name', 'email', 'role']);

        $role = $request->user()?->effectiveRole() ?? '';
        $isAdmin = $role === 'admin';

        return view('dashboards.settings-staff-access', [
            'featureGroups' => $this->featureGroups(),
            'features' => $features,
            'states' => $states,
            'isAdmin' => $isAdmin,
            'staffUsers' => $staffUsers,
            'search' => $search,
        ]);
    }

    /**
     * Toggle a specific feature on/off for staff.
     */
    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'feature' => ['required', 'string', 'max:255'],
            'enabled' => ['required', 'boolean'],
        ]);

        StaffFeatureAccess::query()->updateOrCreate(
            ['feature' => $data['feature']],
            ['enabled' => $data['enabled']]
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Staff access settings updated.');
    }

    /**
     * Get per-user feature states (for modal).
     */
    public function showUser(Request $request, User $user): JsonResponse
    {
        abort_unless($user->role === User::ROLE_STAFF, 404);

        $featureKeys = array_keys($this->featureDefinitions());

        $overrides = StaffFeatureUserAccess::statesForUser($user, $featureKeys);
        $global = StaffFeatureAccess::statesFor($featureKeys);

        $resolved = [];
        foreach ($featureKeys as $feature) {
            $resolved[$feature] = [
                'override' => array_key_exists($feature, $overrides) ? (bool) $overrides[$feature] : null,
                'effective' => array_key_exists($feature, $overrides)
                    ? (bool) $overrides[$feature]
                    : (bool) ($global[$feature] ?? true),
            ];
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'features' => $resolved,
        ]);
    }

    /**
     * Update per-user feature access.
     */
    public function updateUser(Request $request, User $user): JsonResponse
    {
        abort_unless($user->role === User::ROLE_STAFF, 404);

        $data = $request->validate([
            'feature' => ['required', 'string', 'max:255'],
            'enabled' => ['required', 'boolean'],
        ]);

        StaffFeatureUserAccess::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'feature' => $data['feature'],
            ],
            [
                'enabled' => $data['enabled'],
            ]
        );

        return response()->json(['success' => true]);
    }
}

