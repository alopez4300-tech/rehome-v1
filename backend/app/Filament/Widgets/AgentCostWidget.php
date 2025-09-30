<?php

namespace App\Filament\Widgets;

use App\Models\AgentRun;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AgentCostWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $user = Auth::user();
        $workspaceId = $user->workspace_id;

        // Get date ranges
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();
        $last30Days = $now->copy()->subDays(30);

        // Base query for workspace
        $baseQuery = AgentRun::whereHas('thread.project', function ($query) use ($workspaceId) {
            $query->where('workspace_id', $workspaceId);
        })->where('status', 'completed');

        // This month's costs
        $thisMonthCostCents = (clone $baseQuery)
            ->where('finished_at', '>=', $startOfMonth)
            ->sum('cost_cents');

        $thisMonthCost = $thisMonthCostCents / 100; // Convert to dollars

        // Last month's costs for comparison
        $lastMonthCostCents = (clone $baseQuery)
            ->whereBetween('finished_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('cost_cents');

        $lastMonthCost = $lastMonthCostCents / 100;

        // Calculate month-over-month change
        $monthlyChange = $lastMonthCost > 0
            ? (($thisMonthCost - $lastMonthCost) / $lastMonthCost) * 100
            : 0;

        // Last 30 days costs
        $last30DaysCostCents = (clone $baseQuery)
            ->where('finished_at', '>=', $last30Days)
            ->sum('cost_cents');

        $last30DaysCost = $last30DaysCostCents / 100;

        // Token usage this month
        $thisMonthTokens = (clone $baseQuery)
            ->where('finished_at', '>=', $startOfMonth)
            ->selectRaw('SUM(tokens_in + tokens_out) as total_tokens')
            ->value('total_tokens') ?? 0;

        // Number of runs this month
        $thisMonthRuns = (clone $baseQuery)
            ->where('finished_at', '>=', $startOfMonth)
            ->count();

        // Average cost per run
        $avgCostPerRun = $thisMonthRuns > 0 ? $thisMonthCost / $thisMonthRuns : 0;

        return [
            Stat::make('This Month AI Costs', '$'.number_format($thisMonthCost, 2))
                ->description($monthlyChange >= 0 ? '+'.number_format($monthlyChange, 1).'% from last month' : number_format($monthlyChange, 1).'% from last month')
                ->descriptionIcon($monthlyChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($monthlyChange >= 0 ? 'warning' : 'success')
                ->chart($this->getMonthlyCostChart()),

            Stat::make('Last 30 Days', '$'.number_format($last30DaysCost, 2))
                ->description('Total AI spend')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make('Tokens Used', number_format($thisMonthTokens))
                ->description('This month')
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color('gray'),

            Stat::make('AI Runs', number_format($thisMonthRuns))
                ->description('Avg: $'.number_format($avgCostPerRun, 3).' per run')
                ->descriptionIcon('heroicon-m-play')
                ->color('primary'),
        ];
    }

    /**
     * Get monthly cost chart data for the trend
     */
    private function getMonthlyCostChart(): array
    {
        $user = Auth::user();
        $workspaceId = $user->workspace_id;

        $chartData = [];

        // Get last 12 months of data
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();

            $monthlyCost = AgentRun::whereHas('thread.project', function ($query) use ($workspaceId) {
                $query->where('workspace_id', $workspaceId);
            })
                ->where('status', 'completed')
                ->whereBetween('finished_at', [$startOfMonth, $endOfMonth])
                ->sum('cost_cents');

            $chartData[] = $monthlyCost / 100; // Convert to dollars
        }

        return $chartData;
    }

    /**
     * Widget should be visible to admin users only
     */
    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->workspace_id;
    }
}
