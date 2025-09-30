<?php

namespace App\Events\Agent;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Agent Message Created Event - Broadcasts real-time agent responses
 *
 * Used for token streaming, typing indicators, and message updates
 * via Laravel Echo + WebSockets.
 */
class AgentMessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $threadId = $this->message->thread_id ?? $this->message->agent_thread_id;

        return [
            new PrivateChannel("agent.thread.{$threadId}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'agent.message.created';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'thread_id' => $this->message->thread_id ?? $this->message->agent_thread_id,
                'role' => $this->message->role,
                'content' => $this->message->content,
                'metadata' => $this->message->metadata ?? [],
                'cost_cents' => $this->message->cost_cents ?? 0,
                'token_count' => $this->message->token_count ?? 0,
                'created_at' => $this->message->created_at?->toISOString(),
            ],
            'timestamp' => Carbon::now()->toISOString(),
        ];
    }

    /**
     * Determine if this event should broadcast.
     */
    public function shouldBroadcast(): bool
    {
        // Always broadcast agent events for real-time updates
        return true;
    }
}
