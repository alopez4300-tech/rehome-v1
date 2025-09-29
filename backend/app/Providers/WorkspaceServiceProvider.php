<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Models\Workspace;

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
        // Add a helper function to get current workspace
        if (!function_exists('current_workspace')) {
            function current_workspace(): ?Workspace {
                return app('current.workspace');
            }
        }

        // Add a helper function to get current workspace ID
        if (!function_exists('current_workspace_id')) {
            function current_workspace_id(): ?int {
                $workspace = current_workspace();
                return $workspace ? $workspace->id : null;
            }
        }
    }
}
