<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array<int, class-string>
     */
    protected $middleware = [
        \App\Http\Middleware\CorsHeaders::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \Illuminate\Foundation\Http\Middleware\TransformsRequest::class,
        \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \Illuminate\Http\Middleware\TrustProxies::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string>>
     */
    protected $middlewareGroups = [
        'web' => [
            // Laravel default web stack is not required for this API-focused project.
        ],

        'api' => [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * @var array<string, class-string>
     */
    protected $routeMiddleware = [
        // Role middleware
        'role' => \App\Http\Middleware\CheckRole::class,

        // Organisation access middleware (the alias expected by your error)
        'ensure.organization.access' => \App\Http\Middleware\EnsureOrganizationAccess::class,

        // Also register by class-style to avoid confusion
        'EnsureOrganizationAccess' => \App\Http\Middleware\EnsureOrganizationAccess::class,
    ];
}

