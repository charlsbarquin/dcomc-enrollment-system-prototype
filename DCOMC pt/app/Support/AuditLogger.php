<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    public static function log(string $action, ?object $target = null, array $meta = []): void
    {
        try {
            $user = Auth::user();
            $targetType = $target ? get_class($target) : null;
            $targetId = null;

            if ($target && isset($target->id)) {
                $targetId = (int) $target->id;
            }

            AuditLog::create([
                'actor_user_id' => $user?->id,
                'actor_role' => $user?->effectiveRole(),
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'meta' => $meta ?: null,
                'ip_address' => Request::ip(),
                'user_agent' => (string) Request::userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Audit logging must never break application flow.
        }
    }
}
