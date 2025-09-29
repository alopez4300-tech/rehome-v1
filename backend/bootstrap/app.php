<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\EnsureWorkspaceContext::class,
        ]);

        $middleware->alias([
            'agent.rate_limit' => \App\Http\Middleware\AgentRateLimit::class,
            'ops.admin' => \App\Http\Middleware\EnsureWorkspaceAdmin::class,
            'admin.workspace' => \App\Http\Middleware\EnsureAdminHasWorkspace::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
