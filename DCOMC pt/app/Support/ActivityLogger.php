<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    public static function log(string $action, ?string $description = null, array $meta = []): void
    {
        try {
            $user = Auth::user();

            ActivityLog::create([
                'user_id' => $user?->id,
                'role' => $user?->effectiveRole(),
                'action' => $action,
                'description' => $description,
                'method' => Request::method(),
                'path' => substr((string) Request::path(), 0, 255),
                'route_name' => Request::route()?->getName(),
                'status_code' => null,
                'meta' => $meta ?: null,
                'ip_address' => Request::ip(),
                'user_agent' => (string) Request::userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Activity logging must never break application flow.
        }
    }

    public static function pruneOldOccasionally(): void
    {
        try {
            // Run at most once per hour to avoid overhead and avoid needing cron.
            $key = 'activity_logs:prune_lock';
            if (Cache::add($key, '1', now()->addHour())) {
                ActivityLog::where('created_at', '<', now()->subDays(4))->delete();
            }
        } catch (\Throwable $e) {
        }
    }
}
