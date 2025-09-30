<?php

namespace App\Jobs\Agent;

use App\Models\AgentMessage;
use App\Models\AgentThread;
use App\Models\Project;
use App\Services\Agent\AgentService;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Generate Daily Summary Job - Creates daily project digests
 *
 * Runs automatically via scheduler to generate daily summaries
 * for all active projects, providing participants with
 * progress updates and key highlights.
 */
class GenerateDailySummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Project $project;

    public Carbon $date;

    /**
     * Job configuration
     */
    public int $timeout = 300; // 5 minutes max

    public int $tries = 2;

    /**
     * Create a new job instance
     */
    public function __construct(Project $project, ?Carbon $date = null)
    {
        $this->project = $project;
        $this->date = $date ?? now()->subDay();

        // Set queue configuration
        $this->onQueue('agent-summaries');
        $this->tags([
            'summary',
            'daily',
            "workspace:{$project->workspace_id}",
            "project:{$project->id}",
        ]);
    }

    /**
     * Execute the job
     */
    public function handle(AgentService $agentService): void
    {
        Log::info('Generating daily summary', [
            'project_id' => $this->project->id,
            'date' => $this->date->toDateString(),
        ]);

        try {
            // Find or create admin thread for summaries
            $summaryThread = $this->findOrCreateSummaryThread();

            // Build summary prompt
            $summaryPrompt = $this->buildDailySummaryPrompt();

            // Create a mock user for system-generated content
            $systemUser = $this->project->workspace->users()
                ->where('role', 'admin')
                ->first();

            if (! $systemUser) {
                Log::warning('No admin user found for workspace, skipping summary', [
                    'workspace_id' => $this->project->workspace_id,
                    'project_id' => $this->project->id,
                ]);

                return;
            }

            // Process summary generation
            $run = $agentService->processMessage(
                $summaryThread,
                $summaryPrompt,
                $systemUser
            );

            // Store the summary with metadata
            $this->storeDailySummary($run);

            Log::info('Daily summary generated successfully', [
                'project_id' => $this->project->id,
                'run_id' => $run->id,
                'date' => $this->date->toDateString(),
            ]);

        } catch (Exception $e) {
            Log::error('Failed to generate daily summary', [
                'project_id' => $this->project->id,
                'date' => $this->date->toDateString(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Find or create thread for daily summaries
     */
    private function findOrCreateSummaryThread(): AgentThread
    {
        return AgentThread::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'audience' => 'admin',
                'title' => 'Daily Project Summaries',
            ],
            [
                'created_by' => $this->project->workspace->users()
                    ->where('role', 'admin')
                    ->first()?->id,
                'status' => 'active',
            ]
        );
    }

    /**
     * Build the daily summary prompt
     */
    private function buildDailySummaryPrompt(): string
    {
        $dateStr = $this->date->format('Y-m-d');
        $dayName = $this->date->format('l');

        // Gather activity data for the day
        $activities = $this->gatherDailyActivities();

        return "Generate a daily project summary for {$this->project->name} for {$dayName}, {$dateStr}.

**Context:**
- Tasks completed: {$activities['tasks_completed']}
- Messages sent: {$activities['messages_sent']}
- Files uploaded: {$activities['files_uploaded']}
- Team members active: {$activities['active_users']}

**Recent Activity:**
{$activities['recent_tasks']}

**Please provide:**
1. **Key Accomplishments** - What was completed today
2. **Team Activity** - Who contributed and how
3. **Outstanding Items** - What needs attention
4. **Tomorrow's Focus** - Suggested priorities

Keep it concise but informative. This will be visible to all project participants.

Format as clean markdown with proper headings and bullet points.";
    }

    /**
     * Gather daily activity data
     */
    private function gatherDailyActivities(): array
    {
        $start = $this->date->startOfDay();
        $end = $this->date->endOfDay();

        // Count completed tasks
        $tasksCompleted = $this->project->tasks()
            ->where('completed_at', '>=', $start)
            ->where('completed_at', '<=', $end)
            ->count();

        // Count messages sent
        $messagesSent = AgentMessage::whereHas('thread', function ($query) {
            $query->where('project_id', $this->project->id);
        })
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->where('role', 'user')
            ->count();

        // Count files uploaded (if file system exists)
        $filesUploaded = 0; // TODO: Implement file counting

        // Get active users
        $activeUsers = $this->project->users()
            ->whereHas('actions', function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end]);
            })
            ->count();

        // Get recent task details
        $recentTasks = $this->project->tasks()
            ->where('updated_at', '>=', $start)
            ->where('updated_at', '<=', $end)
            ->latest('updated_at')
            ->limit(5)
            ->get(['title', 'status', 'assigned_to'])
            ->map(fn ($task) => "- {$task->title} ({$task->status})")
            ->implode("\n");

        return [
            'tasks_completed' => $tasksCompleted,
            'messages_sent' => $messagesSent,
            'files_uploaded' => $filesUploaded,
            'active_users' => $activeUsers,
            'recent_tasks' => $recentTasks ?: 'No task updates today',
        ];
    }

    /**
     * Store the generated summary
     */
    private function storeDailySummary($run): void
    {
        // Find the assistant message from the run
        $summaryMessage = AgentMessage::where('thread_id', $run->thread_id)
            ->where('role', 'assistant')
            ->whereJsonContains('meta->run_id', $run->id)
            ->first();

        if ($summaryMessage) {
            // Update message metadata to mark as daily summary
            $meta = $summaryMessage->meta ?? [];
            $meta['summary_type'] = 'daily';
            $meta['summary_date'] = $this->date->toDateString();
            $meta['project_id'] = $this->project->id;

            $summaryMessage->update(['meta' => $meta]);

            // TODO: Broadcast summary ready event
            // broadcast(new AgentSummaryReady($this->project, 'daily', $summaryMessage->content));
        }
    }

    /**
     * Handle job failure
     */
    public function failed(Exception $exception): void
    {
        Log::error('Daily summary job failed permanently', [
            'project_id' => $this->project->id,
            'date' => $this->date->toDateString(),
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get job tags
     */
    public function tags(): array
    {
        return [
            'summary',
            'daily',
            "workspace:{$this->project->workspace_id}",
            "project:{$this->project->id}",
            "date:{$this->date->toDateString()}",
        ];
    }
}
