<?php

namespace App\Http\Controllers;

use App\Models\UnifastFeatureAccess;
use App\Models\UnifastFeatureUserAccess;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class UnifastAccessController extends Controller
{
    protected function featureGroups(): array
    {
        return [
            'settings' => [
                'label' => 'Settings',
                'features' => [
                    'unifast_fees' => [
                        'label' => 'Fees',
                        'description' => 'Access to fee settings (program/year fee configuration).',
                    ],
                ],
            ],
            'reports' => [
                'label' => 'Reports',
                'features' => [
                    'unifast_reports' => [
                        'label' => 'Reports',
                        'description' => 'Access to system reports.',
                    ],
                ],
            ],
        ];
    }

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

    public function index(Request $request): View
    {
        $features = $this->featureDefinitions();
        $featureKeys = array_keys($features);
        $states = UnifastFeatureAccess::statesFor($featureKeys);

        $search = trim($request->string('search')->toString());

        $unifastQuery = User::query()
            ->where('role', User::ROLE_UNIFAST)
            ->orderBy('name');

        if ($search !== '') {
            $unifastQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        $unifastUsers = $unifastQuery->limit(100)->get(['id', 'name', 'email', 'role']);

        return view('dashboards.settings-unifast-access', [
            'featureGroups' => $this->featureGroups(),
            'features' => $features,
            'states' => $states,
            'unifastUsers' => $unifastUsers,
            'search' => $search,
        ]);
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'feature' => ['required', 'string', 'max:255'],
            'enabled' => ['required', 'boolean'],
        ]);

        UnifastFeatureAccess::query()->updateOrCreate(
            ['feature' => $data['feature']],
            ['enabled' => $data['enabled']]
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Unifast access settings updated.');
    }

    public function showUser(Request $request, User $user): JsonResponse
    {
        abort_unless($user->role === User::ROLE_UNIFAST, 404);

        $featureKeys = array_keys($this->featureDefinitions());

        $overrides = UnifastFeatureUserAccess::statesForUser($user, $featureKeys);
        $global = UnifastFeatureAccess::statesFor($featureKeys);

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

    public function updateUser(Request $request, User $user): JsonResponse
    {
        abort_unless($user->role === User::ROLE_UNIFAST, 404);

        $data = $request->validate([
            'feature' => ['required', 'string', 'max:255'],
            'enabled' => ['required', 'boolean'],
        ]);

        UnifastFeatureUserAccess::query()->updateOrCreate(
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
