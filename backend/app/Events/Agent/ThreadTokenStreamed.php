<?php

namespace App\Events\Agent;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class ThreadTokenStreamed implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public int $threadId,
        public array $payload // ['token','done','stream_id','run_id','seq'...]
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("agent.thread.{$this->threadId}")];
    }

    // Namespaced so Echo can listen('.agent.thread.token')
    public function broadcastAs(): string
    {
        return 'agent.thread.token';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
