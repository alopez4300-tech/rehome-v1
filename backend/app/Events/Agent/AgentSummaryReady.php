<?php

namespace App\Events\Agent;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Agent Summary Ready Event - Broadcasts when summaries are completed
 *
 * Used for daily digests, weekly rollups, and milestone summaries.
 */
class AgentSummaryReady implements ShouldBroadcast
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
        $projectId = $this->data['project_id'];

        // Broadcast to both project participants and workspace admins
        return [
            new PrivateChannel("project.{$projectId}"),
            new PrivateChannel("workspace.{$this->getWorkspaceId()}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'agent.summary.ready';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'project_id' => $this->data['project_id'],
            'type' => $this->data['type'], // 'daily', 'weekly', 'milestone', 'meeting'
            'content' => $this->data['content'],
            'metadata' => $this->data['metadata'] ?? [],
            'created_at' => $this->data['created_at'],
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Get workspace ID for broadcasting (placeholder)
     */
    private function getWorkspaceId(): int
    {
        // TODO: Get workspace ID from project when models are available
        return 1; // Placeholder
    }

    /**
     * Determine if this event should broadcast.
     */
    public function shouldBroadcast(): bool
    {
        return true;
    }
}
