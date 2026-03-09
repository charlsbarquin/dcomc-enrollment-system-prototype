<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnifastFeatureAccess extends Model
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
     * When admin is mirroring unifast, grant full access so they can use all unifast features.
     */
    public static function isEnabledForUser(?User $user, string $feature): bool
    {
        if (! $user) {
            return static::isEnabled($feature);
        }
        $switch = session('role_switch');
        if ($user->isAdmin() && is_array($switch) && ($switch['active'] ?? false) && ($switch['as_role'] ?? '') === User::ROLE_UNIFAST) {
            return true;
        }
        if ($user->role !== User::ROLE_UNIFAST) {
            return static::isEnabled($feature);
        }

        $override = UnifastFeatureUserAccess::query()
            ->where('user_id', $user->id)
            ->where('feature', $feature)
            ->first();

        if ($override) {
            return (bool) $override->enabled;
        }

        return static::isEnabled($feature);
    }
}
