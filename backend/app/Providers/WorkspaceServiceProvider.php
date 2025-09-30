<?php

namespace App\Providers;

use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class WorkspaceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register a singleton for current workspace context
        $this->app->singleton('current.workspace', function ($app) {
            if (Auth::check() && Auth::user()->workspace) {
                return Auth::user()->workspace;
            }

            return null;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Helper functions moved outside to avoid redeclaration in tests
    }
}
