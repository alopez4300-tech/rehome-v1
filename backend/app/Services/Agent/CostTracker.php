<?php

namespace App\Services\Agent;

use App\Models\User;
use App\Models\Workspace;
use App\Models\AgentRun;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Cost Tracker - Manages AI usage costs and budget enforcement
 *
 * Implements rate limiting, budget tracking, and graceful degradation
 * as specified in the governance requirements.
 */
class CostTracker
{
    private array $config;

    public function __construct()
    {
        $this->config = config('ai');
    }

    /**
     * Check if user can make an agent request within rate limits
     */
    public function canMakeRequest(User $user, Workspace $workspace): bool
    {
        $rateLimits = $this->config['rate_limits'];

        // Check per-user per-minute limit
        if (!$this->checkRateLimit($user->id, 'minute', $rateLimits['per_user_minute'])) {
            Log::warning('User rate limit exceeded (per minute)', [
                'user_id' => $user->id,
                'limit' => $rateLimits['per_user_minute'],
            ]);
            return false;
        }

        // Check per-user per-day limit
        if (!$this->checkRateLimit($user->id, 'day', $rateLimits['per_user_day'])) {
            Log::warning('User rate limit exceeded (per day)', [
                'user_id' => $user->id,
                'limit' => $rateLimits['per_user_day'],
            ]);
            return false;
        }

        // Check per-workspace per-day limit
        if (!$this->checkRateLimit("workspace:{$workspace->id}", 'day', $rateLimits['per_workspace_day'])) {
            Log::warning('Workspace rate limit exceeded (per day)', [
                'workspace_id' => $workspace->id,
                'limit' => $rateLimits['per_workspace_day'],
            ]);
            return false;
        }

        return true;
    }

    /**
     * Record an agent request for rate limiting
     */
    public function recordRequest(User $user, Workspace $workspace): void
    {
        $this->incrementRateLimit($user->id, 'minute');
        $this->incrementRateLimit($user->id, 'day');
        $this->incrementRateLimit("workspace:{$workspace->id}", 'day');
    }

    /**
     * Check if user/workspace is within budget limits
     */
    public function checkBudget(User $user, Workspace $workspace): array
    {
        $userBudget = $this->getUserDailyBudget($user);
        $workspaceBudget = $this->getWorkspaceMonthlyBudget($workspace);

        $userUsage = $this->getUserDailyUsage($user);
        $workspaceUsage = $this->getWorkspaceMonthlyUsage($workspace);

        $userPercentage = $userUsage / max($userBudget, 1) * 100;
        $workspacePercentage = $workspaceUsage / max($workspaceBudget, 1) * 100;

        $warningThreshold = $this->config['budgets']['warning_threshold'] * 100;

        return [
            'user' => [
                'budget_cents' => $userBudget,
                'usage_cents' => $userUsage,
                'percentage' => $userPercentage,
                'over_budget' => $userUsage >= $userBudget,
                'warning' => $userPercentage >= $warningThreshold,
            ],
            'workspace' => [
                'budget_cents' => $workspaceBudget,
                'usage_cents' => $workspaceUsage,
                'percentage' => $workspacePercentage,
                'over_budget' => $workspaceUsage >= $workspaceBudget,
                'warning' => $workspacePercentage >= $warningThreshold,
            ],
            'can_proceed' => $userUsage < $userBudget && $workspaceUsage < $workspaceBudget,
            'should_degrade' => ($userUsage >= $userBudget || $workspaceUsage >= $workspaceBudget)
                && $this->config['budgets']['graceful_degradation'],
        ];
    }

    /**
     * Calculate cost for an agent run
     */
    public function calculateCost(string $model, int $inputTokens, int $outputTokens): int
    {
        $costs = $this->config['costs'][$model] ?? null;

        if (!$costs) {
            Log::warning('Unknown model for cost calculation', ['model' => $model]);
            return 0;
        }

        // Cost is in USD per 1M tokens, convert to cents
        $inputCost = ($inputTokens / 1000000) * $costs['input'] * 100;
        $outputCost = ($outputTokens / 1000000) * $costs['output'] * 100;

        return (int) round($inputCost + $outputCost);
    }

    /**
     * Record usage and cost for an agent run
     */
    public function recordUsage(AgentRun $run): void
    {
        $cost = $this->calculateCost(
            $run->model,
            $run->tokens_in,
            $run->tokens_out
        );

        $run->update(['cost_cents' => $cost]);

        Log::info('Agent run cost recorded', [
            'run_id' => $run->id,
            'model' => $run->model,
            'tokens_in' => $run->tokens_in,
            'tokens_out' => $run->tokens_out,
            'cost_cents' => $cost,
        ]);

        // Update cache for budget tracking
        $this->updateBudgetCache($run);
    }

    /**
     * Get current circuit breaker status for a provider
     */
    public function getCircuitBreakerStatus(string $provider): array
    {
        $key = "circuit_breaker:{$provider}";
        $status = Cache::get($key, ['state' => 'closed', 'failures' => 0]);

        return $status;
    }

    /**
     * Record a failure for circuit breaker
     */
    public function recordFailure(string $provider): void
    {
        $key = "circuit_breaker:{$provider}";
        $status = $this->getCircuitBreakerStatus($provider);

        $status['failures']++;
        $status['last_failure'] = now();

        $threshold = $this->config['circuit_breaker']['failure_threshold'];

        if ($status['failures'] >= $threshold && $status['state'] === 'closed') {
            $status['state'] = 'open';
            $status['opened_at'] = now();

            Log::error('Circuit breaker opened for provider', [
                'provider' => $provider,
                'failures' => $status['failures'],
                'threshold' => $threshold,
            ]);
        }

        Cache::put($key, $status, now()->addMinutes(60));
    }

    /**
     * Record a success for circuit breaker
     */
    public function recordSuccess(string $provider): void
    {
        $key = "circuit_breaker:{$provider}";
        $status = $this->getCircuitBreakerStatus($provider);

        if ($status['state'] === 'half-open') {
            $status['successes'] = ($status['successes'] ?? 0) + 1;

            $threshold = $this->config['circuit_breaker']['success_threshold'];

            if ($status['successes'] >= $threshold) {
                $status = ['state' => 'closed', 'failures' => 0];

                Log::info('Circuit breaker closed for provider', [
                    'provider' => $provider,
                    'successes' => $status['successes'],
                ]);
            }
        }

        Cache::put($key, $status, now()->addMinutes(60));
    }

    /**
     * Check if circuit breaker allows requests
     */
    public function canUseProvider(string $provider): bool
    {
        $status = $this->getCircuitBreakerStatus($provider);

        if ($status['state'] === 'closed') {
            return true;
        }

        if ($status['state'] === 'open') {
            $timeout = $this->config['circuit_breaker']['recovery_timeout'];
            $openedAt = Carbon::parse($status['opened_at']);

            if ($openedAt->addSeconds($timeout)->isPast()) {
                // Move to half-open state
                $status['state'] = 'half-open';
                $status['successes'] = 0;
                Cache::put("circuit_breaker:{$provider}", $status, now()->addMinutes(60));

                return true;
            }

            return false;
        }

        // half-open state - allow limited requests
        return true;
    }

    /**
     * Check rate limit for a key
     */
    private function checkRateLimit(string $key, string $period, int $limit): bool
    {
        $cacheKey = "rate_limit:{$key}:{$period}";
        $current = Cache::get($cacheKey, 0);

        return $current < $limit;
    }

    /**
     * Increment rate limit counter
     */
    private function incrementRateLimit(string $key, string $period): void
    {
        $cacheKey = "rate_limit:{$key}:{$period}";
        $ttl = $period === 'minute' ? 60 : 86400; // 1 minute or 1 day

        Cache::increment($cacheKey, 1);
        Cache::expire($cacheKey, $ttl);
    }

    /**
     * Get user's daily budget in cents
     */
    private function getUserDailyBudget(User $user): int
    {
        // TODO: Check user-specific budget override when implemented
        return $this->config['budgets']['default_user_daily_cents'];
    }

    /**
     * Get workspace's monthly budget in cents
     */
    private function getWorkspaceMonthlyBudget(Workspace $workspace): int
    {
        // TODO: Check workspace-specific budget override when implemented
        return $this->config['budgets']['default_workspace_monthly_cents'];
    }

    /**
     * Get user's daily usage in cents
     */
    private function getUserDailyUsage(User $user): int
    {
        $cacheKey = "daily_usage:user:{$user->id}:" . now()->format('Y-m-d');

        return Cache::remember($cacheKey, 3600, function () use ($user) {
            return AgentRun::whereHas('thread', function ($query) use ($user) {
                $query->where('created_by', $user->id);
            })
            ->whereDate('created_at', today())
            ->sum('cost_cents') ?? 0;
        });
    }

    /**
     * Get workspace's monthly usage in cents
     */
    private function getWorkspaceMonthlyUsage(Workspace $workspace): int
    {
        $cacheKey = "monthly_usage:workspace:{$workspace->id}:" . now()->format('Y-m');

        return Cache::remember($cacheKey, 3600, function () use ($workspace) {
            return AgentRun::whereHas('thread.project', function ($query) use ($workspace) {
                $query->where('workspace_id', $workspace->id);
            })
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('cost_cents') ?? 0;
        });
    }

    /**
     * Update budget tracking cache after recording usage
     */
    private function updateBudgetCache(AgentRun $run): void
    {
        // Invalidate cache entries to force recalculation
        $userKey = "daily_usage:user:{$run->thread->created_by}:" . now()->format('Y-m-d');
        $workspaceKey = "monthly_usage:workspace:{$run->thread->project->workspace_id}:" . now()->format('Y-m');

        Cache::forget($userKey);
        Cache::forget($workspaceKey);
    }
}
