<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrarFeatureAccess extends Model
{
    protected $fillable = [
        'feature',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public static function isEnabled(string $feature): bool
    {
        $record = static::query()->where('feature', $feature)->first();

        if ($record) {
            return (bool) $record->enabled;
        }

        // Default: enabled so new features are available until admin turns them off.
        return true;
    }

    public static function statesFor(array $features): array
    {
        $existing = static::query()
            ->whereIn('feature', $features)
            ->get()
            ->keyBy('feature');

        $result = [];
        foreach ($features as $feature) {
            $result[$feature] = optional($existing->get($feature))->enabled ?? true;
        }

        return $result;
    }

    /**
     * Resolve whether a feature is enabled for a registrar user.
     * When admin is mirroring registrar, grant full access so they can use all registrar features.
     */
    public static function isEnabledForUser(?User $user, string $feature): bool
    {
        if (! $user) {
            return static::isEnabled($feature);
        }
        $switch = session('role_switch');
        if ($user->isAdmin() && is_array($switch) && ($switch['active'] ?? false) && ($switch['as_role'] ?? '') === User::ROLE_REGISTRAR) {
            return true;
        }
        if ($user->role !== User::ROLE_REGISTRAR) {
            return static::isEnabled($feature);
        }
        return static::isEnabled($feature);
    }
}

