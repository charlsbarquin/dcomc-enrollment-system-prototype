<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class StaffFeatureUserAccess extends Model
{
    protected $fillable = [
        'user_id',
        'feature',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function statesForUser(User $user, array $features): array
    {
        $existing = static::query()
            ->where('user_id', $user->id)
            ->whereIn('feature', $features)
            ->get()
            ->keyBy('feature');

        $result = [];
        foreach ($features as $feature) {
            $record = $existing->get($feature);
            if ($record !== null) {
                $result[$feature] = (bool) $record->enabled;
            }
        }

        return $result;
    }
}

