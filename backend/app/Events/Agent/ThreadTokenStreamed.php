<?php

namespace App\Events\Agent;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ThreadTokenStreamed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $threadId,
        public array $chunk
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("agent.thread.{$this->threadId}")];
    }

    public function broadcastAs(): string
    {
        return 'Agent.Token';
    }

    public function broadcastWith(): array
    {
        return $this->chunk; // e.g. ['token' => '...','done'=>false]
    }
}
