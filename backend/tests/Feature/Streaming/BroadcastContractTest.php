<?php

namespace Tests\Feature\Streaming;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use App\Events\Agent\ThreadTokenStreamed;
use App\Services\Agent\StreamingService;
use App\Models\{AgentRun, AgentThread, User, Project, Workspace};

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

    /** @test */
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

        // Test streaming with broadcast faking
        Broadcast::fake();
        cache()->forget('ai:seq:s1');

        app(StreamingService::class)->streamToken($thread, $run, 's1', 'Hi');
        app(StreamingService::class)->endStream($thread, $run, 's1', 'Hi');

        // Validate broadcast contract
        Broadcast::assertBroadcasted(ThreadTokenStreamed::class, function ($event) use ($thread) {
            return $event->broadcastAs() === 'agent.thread.token'
                && $event->broadcastOn()[0]->name === "agent.thread.{$thread->id}";
        });

        // Validate sequence ordering
        $events = Broadcast::events(ThreadTokenStreamed::class);
        $sequences = array_map(fn($event) => $event->broadcastWith()['seq'] ?? -1, $events);

        // Sequences should be monotonically increasing
        $this->assertSame($sequences, collect($sequences)->sort()->values()->all(), 'Sequence ordering must be monotonic');

        // Final event should be completion
        $finalEvent = last($events);
        $this->assertTrue($finalEvent->broadcastWith()['done'], 'Final event must have done=true');
        $this->assertEquals('Hi', $finalEvent->broadcastWith()['full_response'], 'Final event must contain full response');
    }

    /** @test */
    public function private_channel_auth_gate_respects_workspace_boundaries()
    {
        // Create two separate workspaces with projects
        $workspace1 = Workspace::factory()->create();
        $workspace2 = Workspace::factory()->create();

        $project1 = Project::factory()->create(['workspace_id' => $workspace1->id]);
        $project2 = Project::factory()->create(['workspace_id' => $workspace2->id]);

        $owner1 = User::factory()->create();
        $owner2 = User::factory()->create();

        // Thread belongs to workspace1
        $thread = AgentThread::factory()->create([
            'project_id' => $project1->id,
            'user_id' => $owner1->id,
        ]);

        // Owner1 should have access to their workspace's thread
        $this->actingAs($owner1);
        $response1 = $this->post('/broadcasting/auth', [
            'channel_name' => "private-agent.thread.{$thread->id}",
        ]);
        $response1->assertStatus(200);

        // Owner2 should NOT have access to workspace1's thread
        $this->actingAs($owner2);
        $response2 = $this->post('/broadcasting/auth', [
            'channel_name' => "private-agent.thread.{$thread->id}",
        ]);
        $response2->assertStatus(403);
    }

    /** @test */
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
        $this->assertEquals("agent.thread.{$thread->id}", $event->broadcastOn()[0]->name, 'Event must broadcast on correct channel');

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

    /** @test */
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

        Broadcast::fake();

        $streamId = 'seq-test-' . uniqid();
        cache()->forget("ai:seq:{$streamId}");

        $service = app(StreamingService::class);

        // Stream multiple tokens
        $tokens = ['Hello', ' ', 'streaming', ' ', 'world!'];
        foreach ($tokens as $token) {
            $service->streamToken($thread, $run, $streamId, $token);
        }

        $service->endStream($thread, $run, $streamId, implode('', $tokens));

        // Verify all events were broadcasted
        Broadcast::assertBroadcasted(ThreadTokenStreamed::class, count($tokens) + 1); // tokens + done event

        // Verify sequence integrity
        $events = Broadcast::events(ThreadTokenStreamed::class);
        $tokenEvents = array_slice($events, 0, -1); // exclude done event
        $sequences = array_map(fn($event) => $event->broadcastWith()['seq'], $tokenEvents);

        $this->assertEquals(range(1, count($tokens)), $sequences, 'Token sequences must be consecutive starting from 1');

        // Verify cleanup
        $this->assertFalse(cache()->has("ai:seq:{$streamId}"), 'Sequence cache should be cleaned up');
        $this->assertTrue(cache()->has("ai:done:{$streamId}"), 'Done flag should persist for idempotency');
    }
}
