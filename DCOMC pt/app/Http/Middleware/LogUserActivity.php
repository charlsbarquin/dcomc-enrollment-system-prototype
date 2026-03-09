<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;
use App\Support\ActivityLogger;

class LogUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Keep pruning lightweight and cron-free.
        ActivityLogger::pruneOldOccasionally();

        try {
            $user = Auth::user();
            if (! $user) {
                return $response;
            }

            $method = strtoupper($request->method());
            $isWrite = ! in_array($method, ['GET', 'HEAD', 'OPTIONS'], true);
            if (! $isWrite) {
                return $response;
            }

            $routeName = $request->route()?->getName();

            ActivityLog::create([
                'user_id' => $user->id,
                'role' => $user->effectiveRole(),
                'action' => 'request',
                'description' => $routeName ?: ($method . ' ' . $request->path()),
                'method' => $method,
                'path' => substr((string) $request->path(), 0, 255),
                'route_name' => $routeName,
                'status_code' => $response->getStatusCode(),
                'meta' => null,
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Never break requests.
        }

        return $response;
    }
}
