<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminHasWorkspace
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // If user is not authenticated, let other middleware handle it
        if (!$user) {
            return $next($request);
        }
        
        // If user has admin role but no workspace assigned
        if ($user->hasRole('admin') && !$user->workspace_id) {
            // For API requests, return JSON error
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Admin user must be assigned to a workspace.',
                    'code' => 'ADMIN_NO_WORKSPACE'
                ], 403);
            }
            
            // For web requests, redirect or show error page
            abort(403, 'Admin user must be assigned to a workspace.');
        }
        
        return $next($request);
    }
}
