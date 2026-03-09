<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class StaffFeatureAccess extends Model
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

        // Default: enabled, so new features appear unless explicitly turned off.
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
     * Resolve whether a feature is enabled for a specific staff user.
     * When admin is mirroring staff, grant full access. Otherwise per-user overrides win, then global.
     */
    public static function isEnabledForUser(?User $user, string $feature): bool
    {
        if (! $user) {
            return static::isEnabled($feature);
        }
        $switch = session('role_switch');
        if ($user->isAdmin() && is_array($switch) && ($switch['active'] ?? false) && ($switch['as_role'] ?? '') === User::ROLE_STAFF) {
            return true;
        }
        if ($user->role !== User::ROLE_STAFF) {
            return static::isEnabled($feature);
        }

        $override = StaffFeatureUserAccess::query()
            ->where('user_id', $user->id)
            ->where('feature', $feature)
            ->first();

        if ($override) {
            return (bool) $override->enabled;
        }

        return static::isEnabled($feature);
    }
}

