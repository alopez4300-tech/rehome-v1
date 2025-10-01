<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceContext
{
    /**
     * Handle an incoming request.
     *
     * Ensures that the authenticated user has proper workspace context
     * and shares workspace data with views.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            // Ensure user has workspace
            if (! $user->current_workspace_id) {
                // If no workspace, could redirect to workspace selection
                // For now, we'll use a default workspace (created in seeder)
                $user->current_workspace_id = 1;
                $user->save();
            }

            // Load workspace relationship if not loaded
            if (! $user->relationLoaded('workspace')) {
                $user->load('workspace');
            }

            // Share workspace context with all views
            if ($user->workspace) {
                View::share('currentWorkspace', $user->workspace);

                // Set application context for scoped queries
                app()->instance('current.workspace', $user->workspace);
            }
        }

        return $next($request);
    }
}
