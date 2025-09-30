<?php

namespace App\Http\Middleware;

use App\Services\Agent\CostTracker;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Agent Rate Limiting Middleware
 *
 * Enforces rate limits and budget constraints for AI agent requests
 * before they reach the controller.
 */
class AgentRateLimit
{
    private CostTracker $costTracker;

    public function __construct(CostTracker $costTracker)
    {
        $this->costTracker = $costTracker;
    }

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'error' => 'Authentication required',
                'code' => 'UNAUTHENTICATED',
            ], 401);
        }

        $workspace = $user->workspace;

        if (! $workspace) {
            return response()->json([
                'error' => 'No workspace context',
                'code' => 'NO_WORKSPACE',
            ], 400);
        }

        // Check rate limits
        if (! $this->costTracker->canMakeRequest($user, $workspace)) {
            return response()->json([
                'error' => 'Rate limit exceeded. Please try again later.',
                'code' => 'RATE_LIMIT_EXCEEDED',
                'retry_after' => 60, // seconds
            ], 429);
        }

        // Check budget constraints
        $budgetStatus = $this->costTracker->checkBudget($user, $workspace);

        if (! $budgetStatus['can_proceed'] && ! $budgetStatus['should_degrade']) {
            return response()->json([
                'error' => 'Budget limit exceeded. Please contact your administrator.',
                'code' => 'BUDGET_EXCEEDED',
                'budget_info' => [
                    'user_budget' => $budgetStatus['user'],
                    'workspace_budget' => $budgetStatus['workspace'],
                ],
            ], 402); // Payment Required
        }

        // Check circuit breaker
        $provider = config('ai.provider');
        if (! $this->costTracker->canUseProvider($provider)) {
            return response()->json([
                'error' => 'AI service is temporarily unavailable. Please try again later.',
                'code' => 'SERVICE_UNAVAILABLE',
                'provider' => $provider,
            ], 503);
        }

        // Record the request for rate limiting
        $this->costTracker->recordRequest($user, $workspace);

        // Add budget info to response headers for client awareness
        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $response->header('X-Agent-User-Budget', $budgetStatus['user']['percentage'] ?? 0);
            $response->header('X-Agent-Workspace-Budget', $budgetStatus['workspace']['percentage'] ?? 0);

            // Warn if approaching limits
            if (($budgetStatus['user']['percentage'] ?? 0) > 80) {
                $response->header('X-Agent-Budget-Warning', 'User budget approaching limit');
            }

            if (($budgetStatus['workspace']['percentage'] ?? 0) > 80) {
                $response->header('X-Agent-Budget-Warning', 'Workspace budget approaching limit');
            }
        }

        return $response;
    }
}
