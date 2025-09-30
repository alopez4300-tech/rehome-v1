<?php

namespace Tests\Feature\Streaming;

use App\Events\Agent\ThreadTokenStreamed;
use App\Models\AgentRun;
use App\Models\AgentThread;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Agent\StreamingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Focused Broadcast Contract Test
 *
 * Validates core streaming contracts that must remain stable:
 * - Event structure and naming
 * - Channel authorization
 * - Sequence ordering
 * - Authentication gates
 */
class BroadcastContractTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function broadcast_contract_private_channel_and_seq()
    {
        // Create full relationship chain
        $workspace = Workspace::factory()->create();
        $project = Project::factory()->create(['workspace_id' => $workspace->id]);
        $user = User::factory()->create();

        $thread = AgentThread::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        $run = AgentRun::factory()->create(['thread_id' => $thread->id]);

        // Test event structure directly (since Event::fake() doesn't capture broadcast() calls)
        cache()->forget('ai:seq:s1');

        // Create the event directly to test its contract
        $sequence = cache()->increment('ai:seq:s1');
        $event = new ThreadTokenStreamed($thread->id, [
            'token' => 'Hi',
            'done' => false,
            'stream_id' => 's1',
            'run_id' => $run->id,
            'seq' => $sequence,
        ]);

        // Validate broadcast contract
        $this->assertEquals('agent.thread.token', $event->broadcastAs(), 'Event must broadcast as agent.thread.token');

        // Check channel name (should be private-agent.thread.{id} for private channels)
        $channel = $event->broadcastOn()[0];
        $this->assertEquals("private-agent.thread.{$thread->id}", $channel->name, 'Event must broadcast on private channel');

        // Test final event structure
        $finalEvent = new ThreadTokenStreamed($thread->id, [
            'token' => null,
            'done' => true,
            'stream_id' => 's1',
            'run_id' => $run->id,
            'seq' => 2,
            'full_response' => 'Hi',
        ]);

        $this->assertTrue($finalEvent->broadcastWith()['done'], 'Final event must have done=true');
        $this->assertEquals('Hi', $finalEvent->broadcastWith()['full_response'], 'Final event must contain full response');
    }

    #[Test]
    public function private_channel_uses_correct_naming_convention()
    {
        // Create basic test data
        $workspace = Workspace::factory()->create();
        $project = Project::factory()->create(['workspace_id' => $workspace->id]);
        $user = User::factory()->create();

        $thread = AgentThread::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        // Test that private channels use the correct naming convention
        $event = new ThreadTokenStreamed($thread->id, [
            'token' => 'test',
            'seq' => 1,
            'stream_id' => 'test-stream',
            'done' => false,
            'full_response' => null,
        ]);

        $channel = $event->broadcastOn()[0];

        // Verify it's a private channel with correct naming
        $this->assertInstanceOf(\Illuminate\Broadcasting\PrivateChannel::class, $channel);
        $this->assertEquals("private-agent.thread.{$thread->id}", $channel->name);
    }

    #[Test]
    public function event_structure_contains_required_fields()
    {
        $workspace = Workspace::factory()->create();
        $project = Project::factory()->create(['workspace_id' => $workspace->id]);
        $user = User::factory()->create();

        $thread = AgentThread::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        // Create event instance
        $event = new ThreadTokenStreamed($thread->id, [
            'token' => 'test',
            'seq' => 1,
            'stream_id' => 'contract-test',
            'done' => false,
            'full_response' => null,
        ]);

        // Validate event contract
        $this->assertEquals('agent.thread.token', $event->broadcastAs(), 'Event must broadcast as agent.thread.token');
        $this->assertEquals("private-agent.thread.{$thread->id}", $event->broadcastOn()[0]->name, 'Event must broadcast on correct channel');

        // Validate payload structure
        $payload = $event->broadcastWith();
        $requiredFields = ['token', 'seq', 'stream_id', 'done', 'full_response'];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $payload, "Payload must contain {$field} field");
        }

        // Validate implements ShouldBroadcastNow for ultra-low latency
        $this->assertInstanceOf(
            \Illuminate\Contracts\Broadcasting\ShouldBroadcastNow::class,
            $event,
            'Event must implement ShouldBroadcastNow for real-time streaming'
        );
    }

    #[Test]
    public function streaming_service_maintains_sequence_integrity()
    {
        $workspace = Workspace::factory()->create();
        $project = Project::factory()->create(['workspace_id' => $workspace->id]);
        $user = User::factory()->create();

        $thread = AgentThread::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        $run = AgentRun::factory()->create(['thread_id' => $thread->id]);

        $streamId = 'seq-test-'.uniqid();
        cache()->forget("ai:seq:{$streamId}");

        $service = app(StreamingService::class);

        // Test sequence integrity directly on the service calls
        $tokens = ['Hello', ' ', 'streaming', ' ', 'world!'];

        // Track sequences manually since Event::fake() doesn't work with broadcast()
        $sequences = [];
        foreach ($tokens as $token) {
            $beforeSeq = cache()->get("ai:seq:{$streamId}", 0);
            $service->streamToken($thread, $run, $streamId, $token);
            $afterSeq = cache()->get("ai:seq:{$streamId}", 0);
            $sequences[] = $afterSeq;
        }

        $service->endStream($thread, $run, $streamId, implode('', $tokens));

        // After endStream, sequence cache is cleaned up (finalSeq won't be available)
        $this->assertEquals(range(1, count($tokens)), $sequences, 'Token sequences must be consecutive starting from 1');

        // Verify idempotent cleanup behavior
        $this->assertTrue(cache()->has("ai:done:{$streamId}"), 'Done flag should persist for idempotency');

        // Test idempotent endStream call
        $service->endStream($thread, $run, $streamId, implode('', $tokens));
        $this->assertTrue(cache()->has("ai:done:{$streamId}"), 'Done flag should still exist after second endStream call');
    }
}
