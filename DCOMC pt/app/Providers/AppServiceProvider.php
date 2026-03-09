<?php

namespace App\Providers;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Login::class, function (Login $event) {
            try {
                $user = $event->user;
                ActivityLog::create([
                    'user_id' => $user?->id,
                    'role' => method_exists($user, 'effectiveRole') ? $user->effectiveRole() : ($user->role ?? null),
                    'action' => 'login',
                    'description' => 'User login',
                    'method' => null,
                    'path' => null,
                    'route_name' => null,
                    'status_code' => null,
                    'meta' => [
                        'guard' => $event->guard,
                        'remember' => (bool) $event->remember,
                    ],
                    'ip_address' => Request::ip(),
                    'user_agent' => (string) Request::userAgent(),
                ]);
            } catch (\Throwable $e) {
            }
        });

        Event::listen(Logout::class, function (Logout $event) {
            try {
                $user = $event->user;
                ActivityLog::create([
                    'user_id' => $user?->id,
                    'role' => $user && method_exists($user, 'effectiveRole') ? $user->effectiveRole() : ($user->role ?? null),
                    'action' => 'logout',
                    'description' => 'User logout',
                    'method' => null,
                    'path' => null,
                    'route_name' => null,
                    'status_code' => null,
                    'meta' => [
                        'guard' => $event->guard,
                    ],
                    'ip_address' => Request::ip(),
                    'user_agent' => (string) Request::userAgent(),
                ]);
            } catch (\Throwable $e) {
            }
        });
    }
}
