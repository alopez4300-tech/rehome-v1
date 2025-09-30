<?php

namespace App\Jobs\Agent;

use App\Models\AgentThread;
use App\Models\User;
use App\Services\Agent\AgentService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Process Agent Run Job - Core job that handles AI agent message processing
 *
 * This job is dispatched when a user sends a message to an agent thread.
 * It orchestrates context building, LLM interaction, streaming, and cost tracking.
 */
class ProcessAgentRunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public AgentThread $thread;

    public string $userMessage;

    public User $user;

    /**
     * Job configuration
     */
    public int $timeout = 120; // 2 minutes max execution

    public int $tries = 3;

    public int $maxExceptions = 3;

    /**
     * Create a new job instance
     */
    public function __construct(AgentThread $thread, string $userMessage, User $user)
    {
        $this->thread = $thread;
        $this->userMessage = $userMessage;
        $this->user = $user;

        // Set queue tags for Horizon monitoring
        $this->onQueue('agent');
        $this->tags([
            'agent',
            "workspace:{$thread->project->workspace_id}",
            "project:{$thread->project_id}",
            "thread:{$thread->id}",
        ]);
    }

    /**
     * Execute the job
     */
    public function handle(AgentService $agentService): void
    {
        Log::info('Processing agent message job', [
            'job_id' => $this->job->getJobId(),
            'thread_id' => $this->thread->id,
            'user_id' => $this->user->id,
            'message_preview' => substr($this->userMessage, 0, 100),
        ]);

        try {
            // Process the message through AgentService
            $run = $agentService->processMessage(
                $this->thread,
                $this->userMessage,
                $this->user
            );

            Log::info('Agent message job completed successfully', [
                'job_id' => $this->job->getJobId(),
                'run_id' => $run->id,
                'tokens_used' => $run->tokens_in + $run->tokens_out,
                'cost_cents' => $run->cost_cents,
            ]);

        } catch (Exception $e) {
            Log::error('Agent message job failed', [
                'job_id' => $this->job->getJobId(),
                'thread_id' => $this->thread->id,
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
                'attempts' => $this->attempts(),
            ]);

            // Re-throw to trigger retry logic
            throw $e;
        }
    }

    /**
     * Handle job failure after all retries
     */
    public function failed(Exception $exception): void
    {
        Log::error('Agent message job failed permanently', [
            'thread_id' => $this->thread->id,
            'user_id' => $this->user->id,
            'message' => $this->userMessage,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // TODO: Notify user of failure
        // Could dispatch another job to send notification
        // or broadcast an error event via WebSocket
    }

    /**
     * Calculate the number of seconds to wait before retrying the job
     */
    public function backoff(): array
    {
        // Exponential backoff: 10s, 30s, 60s
        return [10, 30, 60];
    }

    /**
     * Determine if the job should be retried based on the exception
     */
    public function shouldRetry(Exception $exception): bool
    {
        // Don't retry authentication/authorization errors
        if (str_contains($exception->getMessage(), 'Rate limit exceeded')) {
            return false;
        }

        if (str_contains($exception->getMessage(), 'Budget limit exceeded')) {
            return false;
        }

        if (str_contains($exception->getMessage(), 'Unauthorized')) {
            return false;
        }

        // Retry on network/API errors
        return true;
    }

    /**
     * Get the tags that should be assigned to the job
     */
    public function tags(): array
    {
        return [
            'agent',
            "workspace:{$this->thread->project->workspace_id}",
            "project:{$this->thread->project_id}",
            "thread:{$this->thread->id}",
            "user:{$this->user->id}",
        ];
    }
}
