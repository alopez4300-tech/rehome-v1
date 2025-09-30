<?php

namespace App\Jobs\Agent;

use App\Models\AgentMessage;
use App\Models\AgentRun;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Generate weekly activity summaries for all workspaces
 *
 * Runs automatically via scheduler to generate weekly summaries
 * every Sunday at 2 AM
 */
class GenerateWeeklySummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes

    public int $tries = 3;

    public string $queue = 'agent-processing';

    public function handle(): void
    {
        $startDate = Carbon::now()->subWeek()->startOfWeek();
        $endDate = Carbon::now()->subWeek()->endOfWeek();

        Log::info('Starting weekly summary generation', [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ]);

        $workspaces = Workspace::with(['projects.agent_runs.agent_messages'])
            ->whereHas('projects.agent_runs', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->get();

        foreach ($workspaces as $workspace) {
            $this->generateWorkspaceWeeklySummary($workspace, $startDate, $endDate);
        }

        Log::info('Weekly summary generation completed', [
            'workspaces_processed' => $workspaces->count(),
        ]);
    }

    private function generateWorkspaceWeeklySummary(Workspace $workspace, Carbon $startDate, Carbon $endDate): void
    {
        Log::info('Generating weekly summary for workspace', [
            'workspace_id' => $workspace->id,
            'workspace_name' => $workspace->name,
        ]);

        $weeklyRuns = AgentRun::whereHas('agent_thread.project', function ($query) use ($workspace) {
            $query->where('workspace_id', $workspace->id);
        })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['agent_thread.project', 'agent_messages'])
            ->get();

        if ($weeklyRuns->isEmpty()) {
            Log::info('No agent runs found for workspace in date range', [
                'workspace_id' => $workspace->id,
            ]);

            return;
        }

        $summary = $this->calculateWeeklySummary($weeklyRuns, $workspace);

        // Store the weekly summary
        AgentMessage::create([
            'agent_thread_id' => null, // System-generated summary
            'role' => 'system',
            'content' => $this->formatWeeklySummaryMessage($summary, $startDate, $endDate),
            'metadata' => [
                'type' => 'weekly_summary',
                'workspace_id' => $workspace->id,
                'date_range' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                ],
                'summary_data' => $summary,
            ],
            'cost_cents' => 0,
            'token_count' => 0,
        ]);

        Log::info('Weekly summary generated for workspace', [
            'workspace_id' => $workspace->id,
            'total_runs' => $summary['total_runs'],
            'total_messages' => $summary['total_messages'],
            'total_cost' => $summary['total_cost_cents'],
        ]);
    }

    private function calculateWeeklySummary($weeklyRuns, Workspace $workspace): array
    {
        $totalRuns = $weeklyRuns->count();
        $totalMessages = $weeklyRuns->sum(fn ($run) => $run->agent_messages->count());
        $totalCostCents = $weeklyRuns->sum(fn ($run) => $run->agent_messages->sum('cost_cents'));
        $totalTokens = $weeklyRuns->sum(fn ($run) => $run->agent_messages->sum('token_count'));

        // Group by project
        $projectStats = $weeklyRuns->groupBy('agent_thread.project.id')
            ->map(function ($runs, $projectId) {
                $project = $runs->first()->agent_thread->project;

                return [
                    'project_name' => $project->name,
                    'runs' => $runs->count(),
                    'messages' => $runs->sum(fn ($run) => $run->agent_messages->count()),
                    'cost_cents' => $runs->sum(fn ($run) => $run->agent_messages->sum('cost_cents')),
                    'tokens' => $runs->sum(fn ($run) => $run->agent_messages->sum('token_count')),
                ];
            })
            ->sortByDesc('runs')
            ->values()
            ->toArray();

        // Top AI providers used
        $providerStats = $weeklyRuns->flatMap(fn ($run) => $run->agent_messages)
            ->groupBy(fn ($message) => $message->metadata['provider'] ?? 'unknown')
            ->map(function ($messages, $provider) {
                return [
                    'provider' => $provider,
                    'messages' => $messages->count(),
                    'cost_cents' => $messages->sum('cost_cents'),
                    'tokens' => $messages->sum('token_count'),
                ];
            })
            ->sortByDesc('messages')
            ->values()
            ->toArray();

        return [
            'workspace_name' => $workspace->name,
            'total_runs' => $totalRuns,
            'total_messages' => $totalMessages,
            'total_cost_cents' => $totalCostCents,
            'total_tokens' => $totalTokens,
            'project_stats' => $projectStats,
            'provider_stats' => $providerStats,
            'avg_messages_per_run' => $totalRuns > 0 ? round($totalMessages / $totalRuns, 1) : 0,
            'avg_cost_per_run' => $totalRuns > 0 ? round($totalCostCents / $totalRuns, 1) : 0,
        ];
    }

    private function formatWeeklySummaryMessage(array $summary, Carbon $startDate, Carbon $endDate): string
    {
        $costDollars = number_format($summary['total_cost_cents'] / 100, 2);

        $message = "📅 **Weekly AI Agent Summary** ({$startDate->format('M j')} - {$endDate->format('M j, Y')})\n\n";
        $message .= "**Workspace:** {$summary['workspace_name']}\n\n";

        $message .= "**📊 Overall Activity:**\n";
        $message .= "• {$summary['total_runs']} agent runs completed\n";
        $message .= "• {$summary['total_messages']} messages generated\n";
        $message .= "• {$summary['total_tokens']} tokens processed\n";
        $message .= "• \${$costDollars} total cost\n";
        $message .= "• {$summary['avg_messages_per_run']} avg messages per run\n\n";

        if (! empty($summary['project_stats'])) {
            $message .= "**🎯 Top Projects:**\n";
            foreach (array_slice($summary['project_stats'], 0, 5) as $project) {
                $projectCost = number_format($project['cost_cents'] / 100, 2);
                $message .= "• **{$project['project_name']}**: {$project['runs']} runs, {$project['messages']} messages, \${$projectCost}\n";
            }
            $message .= "\n";
        }

        if (! empty($summary['provider_stats'])) {
            $message .= "**🤖 AI Provider Usage:**\n";
            foreach ($summary['provider_stats'] as $provider) {
                $providerCost = number_format($provider['cost_cents'] / 100, 2);
                $message .= "• **{$provider['provider']}**: {$provider['messages']} messages, \${$providerCost}\n";
            }
        }

        return $message;
    }

    public function tags(): array
    {
        return ['weekly-summary', 'system', 'reporting'];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Weekly summary generation failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
