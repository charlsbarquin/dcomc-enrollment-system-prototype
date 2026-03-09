<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // This line registers our custom Role Middleware and names it 'role'
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'dean.department' => \App\Http\Middleware\EnsureDeanHasDepartment::class,
            'ensure.student.profile' => \App\Http\Middleware\EnsureStudentProfileCompleted::class,
            'staff.feature' => \App\Http\Middleware\CheckStaffFeatureAccess::class,
            'unifast.feature' => \App\Http\Middleware\CheckUnifastFeatureAccess::class,
        ]);
        // Ensure staff roles (admin, registrar, dean, unifast, staff) have selected_school_year_id in session
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureSelectedSchoolYear::class);
        // Record user actions for Activity Log (all roles)
        $middleware->appendToGroup('web', \App\Http\Middleware\LogUserActivity::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();