<?php

namespace App\Services\Agent;

use App\Models\AgentThread;
use App\Models\AgentRun;
use App\Events\Agent\AgentMessageCreated;
use App\Events\Agent\ThreadTokenStreamed;
use Illuminate\Support\Facades\Log;

/**
 * Streaming Service - Handles real-time token streaming via WebSockets
 *
 * Implements token-by-token streaming with Laravel Echo + WebSockets
 * as specified in the broadcasting requirements.
 */
class StreamingService
{
    /**
     * Start streaming response tokens to the client
     */
    public function startStream(AgentThread $thread, AgentRun $run): string
    {
        $streamId = $this->generateStreamId($run);

        Log::info('Starting agent response stream', [
            'thread_id' => $thread->id,
            'run_id' => $run->id,
            'stream_id' => $streamId,
        ]);

        // Broadcast stream start event
        broadcast(new AgentMessageCreated([
            'thread_id' => $thread->id,
            'run_id' => $run->id,
            'stream_id' => $streamId,
            'type' => 'stream_start',
            'content' => '',
            'done' => false,
        ]));

        return $streamId;
    }

    /**
     * Stream a token chunk to the client
     */
    public function streamToken(
        AgentThread $thread,
        AgentRun $run,
        string $streamId,
        string $token
    ): void {
        // Broadcast token chunk via ThreadTokenStreamed (Phase 3: Real-time AI streaming)
        $seq = cache()->increment("ai:seq:{$streamId}");

        // TTL hygiene: Set 15-minute expiry on sequence key as belt-and-suspenders cleanup
        cache()->put("ai:seq:{$streamId}", $seq, now()->addMinutes(15));

        broadcast(new ThreadTokenStreamed($thread->id, [
            'token' => $token,
            'done' => false,
            'stream_id' => $streamId,
            'run_id' => $run->id,
            'seq' => $seq,
        ]));
    }

    /**
     * End the stream and broadcast final message
     */
    public function endStream(
        AgentThread $thread,
        AgentRun $run,
        string $streamId,
        string $fullResponse,
        array $metadata = []
    ): void {
        // First writer wins; others bail (idempotent endStream)
        $flag = cache()->add("ai:done:{$streamId}", 1, now()->addMinutes(5));
        if (! $flag) return;

        Log::info('Ending agent response stream', [
            'thread_id' => $thread->id,
            'run_id' => $run->id,
            'stream_id' => $streamId,
            'response_length' => strlen($fullResponse),
        ]);

        // Broadcast stream end event via ThreadTokenStreamed (Phase 3: Real-time AI streaming)
        $seq = cache()->increment("ai:seq:{$streamId}");
        broadcast(new ThreadTokenStreamed($thread->id, [
            'token' => null,
            'done' => true,
            'stream_id' => $streamId,
            'run_id' => $run->id,
            'seq' => $seq,
            'full_response' => $fullResponse,
        ]));

        // Clean up sequence cache (belt-and-suspenders since TTL will handle it)
        cache()->forget("ai:seq:{$streamId}");
        
        // Keep done flag for idempotency - it will expire naturally per TTL
    }

    /**
     * Cancel an active stream
     */
    public function cancelStream(
        AgentThread $thread,
        AgentRun $run,
        string $streamId,
        string $reason = 'cancelled'
    ): void {
        Log::info('Cancelling agent response stream', [
            'thread_id' => $thread->id,
            'run_id' => $run->id,
            'stream_id' => $streamId,
            'reason' => $reason,
        ]);

        // Broadcast cancellation event
        broadcast(new AgentMessageCreated([
            'thread_id' => $thread->id,
            'run_id' => $run->id,
            'stream_id' => $streamId,
            'type' => 'stream_cancelled',
            'content' => '',
            'done' => true,
            'error' => $reason,
        ]));
    }

    /**
     * Stream an error to the client
     */
    public function streamError(
        AgentThread $thread,
        AgentRun $run,
        string $streamId,
        string $error
    ): void {
        Log::error('Streaming error to client', [
            'thread_id' => $thread->id,
            'run_id' => $run->id,
            'stream_id' => $streamId,
            'error' => $error,
        ]);

        // Broadcast error event
        broadcast(new AgentMessageCreated([
            'thread_id' => $thread->id,
            'run_id' => $run->id,
            'stream_id' => $streamId,
            'type' => 'error',
            'content' => '',
            'done' => true,
            'error' => $error,
        ]));
    }

    /**
     * Stream typing indicator
     */
    public function streamTyping(AgentThread $thread, bool $isTyping = true): void
    {
        broadcast(new AgentMessageCreated([
            'thread_id' => $thread->id,
            'type' => 'typing',
            'content' => '',
            'done' => false,
            'typing' => $isTyping,
        ]));
    }

    /**
     * Generate unique stream ID
     */
    private function generateStreamId(AgentRun $run): string
    {
        return 'stream_' . $run->id . '_' . uniqid();
    }

    /**
     * Get WebSocket channel name for thread
     */
    public function getChannelName(AgentThread $thread): string
    {
        // Private channel based on project - ensures proper scoping
        return "private-agent.thread.{$thread->id}";
    }

    /**
     * Check if user can access thread's WebSocket channel
     */
    public function canAccessChannel(AgentThread $thread, $user): bool
    {
        // Check if user has access to the project
        $project = $thread->project;

        // Admin users can access all threads in their workspace
        if ($thread->audience === 'admin') {
            return $user->workspace_id === $project->workspace_id;
        }

        // Participant users can only access threads in projects they're assigned to
        return $project->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Stream progress update (for long-running operations)
     */
    public function streamProgress(
        AgentThread $thread,
        AgentRun $run,
        string $streamId,
        int $percentage,
        string $message = ''
    ): void {
        broadcast(new AgentMessageCreated([
            'thread_id' => $thread->id,
            'run_id' => $run->id,
            'stream_id' => $streamId,
            'type' => 'progress',
            'content' => $message,
            'done' => false,
            'progress' => $percentage,
        ]));
    }

    /**
     * Stream context building progress
     */
    public function streamContextProgress(
        AgentThread $thread,
        AgentRun $run,
        string $streamId,
        string $stage,
        int $percentage
    ): void {
        $messages = [
            'building_context' => 'Building context from project data...',
            'loading_messages' => 'Loading recent messages...',
            'loading_tasks' => 'Loading relevant tasks...',
            'loading_files' => 'Loading file metadata...',
            'applying_pii' => 'Applying privacy filters...',
            'sending_request' => 'Sending request to AI provider...',
        ];

        $this->streamProgress(
            $thread,
            $run,
            $streamId,
            $percentage,
            $messages[$stage] ?? "Processing: {$stage}"
        );
    }

    /**
     * Batch stream multiple tokens (for efficiency)
     */
    public function streamTokenBatch(
        AgentThread $thread,
        AgentRun $run,
        string $streamId,
        array $tokens
    ): void {
        $batchContent = implode('', $tokens);

        broadcast(new AgentMessageCreated([
            'thread_id' => $thread->id,
            'run_id' => $run->id,
            'stream_id' => $streamId,
            'type' => 'token_batch',
            'content' => $batchContent,
            'done' => false,
            'token_count' => count($tokens),
        ]));
    }

    /**
     * Stream summary ready notification
     */
    public function streamSummaryReady(
        int $projectId,
        string $summaryType,
        string $content,
        array $metadata = []
    ): void {
        broadcast(new \App\Events\Agent\AgentSummaryReady([
            'project_id' => $projectId,
            'type' => $summaryType,
            'content' => $content,
            'metadata' => $metadata,
            'created_at' => now()->toISOString(),
        ]));
    }
}
