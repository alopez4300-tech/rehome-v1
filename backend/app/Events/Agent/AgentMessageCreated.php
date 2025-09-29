<?php

namespace App\Events\Agent;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Agent Message Created Event - Broadcasts real-time agent responses
 *
 * Used for token streaming, typing indicators, and message updates
 * via Laravel Echo + WebSockets.
 */
class AgentMessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $data;

    /**
     * Create a new event instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $threadId = $this->data['thread_id'];

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
            'thread_id' => $this->data['thread_id'],
            'run_id' => $this->data['run_id'] ?? null,
            'stream_id' => $this->data['stream_id'] ?? null,
            'type' => $this->data['type'], // 'stream_start', 'token', 'stream_end', 'error', 'typing', etc.
            'content' => $this->data['content'],
            'done' => $this->data['done'],
            'metadata' => $this->data['metadata'] ?? [],
            'error' => $this->data['error'] ?? null,
            'typing' => $this->data['typing'] ?? null,
            'progress' => $this->data['progress'] ?? null,
            'token_count' => $this->data['token_count'] ?? null,
            'timestamp' => now()->toISOString(),
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
