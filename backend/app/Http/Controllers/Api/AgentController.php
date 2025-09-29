<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentThread;
use App\Models\AgentMessage;
use App\Models\AgentRun;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AgentController extends Controller
{
    /**
     * Get a specific thread
     */
    public function getThread(AgentThread $thread): JsonResponse
    {
        // Ensure user has access to this thread
        $this->authorizeThreadAccess($thread);

        return response()->json([
            'data' => $thread->load(['messages', 'user', 'project'])
        ]);
    }

    /**
     * Get messages for a thread
     */
    public function getMessages(AgentThread $thread): JsonResponse
    {
        $this->authorizeThreadAccess($thread);

        $messages = $thread->messages()
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => $messages
        ]);
    }

    /**
     * Send a message to a thread
     */
    public function sendMessage(Request $request, AgentThread $thread): JsonResponse
    {
        $this->authorizeThreadAccess($thread);

        $validated = $request->validate([
            'content' => 'required|string|max:10000',
            'metadata' => 'sometimes|array'
        ]);

        // Create user message
        $message = AgentMessage::create([
            'thread_id' => $thread->id,
            'role' => 'user',
            'content' => $validated['content'],
            'metadata' => $validated['metadata'] ?? null,
        ]);

        // TODO: Dispatch job to process the message with AI
        // dispatch(new ProcessAgentMessage($thread, $message));

        return response()->json([
            'data' => $message,
            'message' => 'Message sent successfully'
        ], 201);
    }

    /**
     * Stream responses for a thread (Server-Sent Events)
     */
    public function stream(AgentThread $thread): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorizeThreadAccess($thread);

        return response()->stream(function () use ($thread) {
            // Set headers for SSE
            echo "data: " . json_encode(['type' => 'connected', 'thread_id' => $thread->id]) . "\n\n";
            ob_flush();
            flush();

            // TODO: Implement actual streaming logic
            // This would typically involve:
            // 1. Listening for new messages on this thread
            // 2. Streaming AI responses as they're generated
            // 3. Handling cancellation requests

            // For now, just keep the connection alive
            while (true) {
                sleep(1);
                echo "data: " . json_encode(['type' => 'heartbeat', 'timestamp' => now()]) . "\n\n";
                ob_flush();
                flush();

                // Break if connection is closed
                if (connection_aborted()) {
                    break;
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
        ]);
    }

    /**
     * Cancel an active agent run
     */
    public function cancel(Request $request, AgentThread $thread): JsonResponse
    {
        $this->authorizeThreadAccess($thread);

        // Find active runs for this thread
        $activeRuns = AgentRun::where('thread_id', $thread->id)
            ->where('status', 'running')
            ->get();

        foreach ($activeRuns as $run) {
            $run->update([
                'status' => 'cancelled',
                'finished_at' => now(),
                'error' => 'Cancelled by user'
            ]);
        }

        return response()->json([
            'message' => 'Agent runs cancelled successfully',
            'cancelled_runs' => $activeRuns->count()
        ]);
    }

    /**
     * Authorize thread access based on user role and context
     */
    private function authorizeThreadAccess(AgentThread $thread): void
    {
        $user = Auth::user();

        if ($thread->audience === 'admin') {
            // Admin threads: user must be in same workspace
            if ($thread->project->workspace_id !== $user->workspace_id) {
                abort(403, 'Access denied to this thread');
            }
        } else {
            // Participant threads: user must be assigned to the project
            if (!$thread->project->users()->where('users.id', $user->id)->exists()) {
                abort(403, 'Access denied to this thread');
            }
        }
    }
}
