<?php

namespace Tests\Feature;

use App\Events\ThreadTokenStreamed;
use App\Models\AgentThread;
use App\Models\AgentRun;
use App\Models\User;
use App\Services\Agent\StreamingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Production-Ready Streaming & Broadcasting Tests
 *
 * Validates:
 * - Event broadcasting contracts
 * - Sequence ordering & monotonicity
 * - Idempotent operations
 * - Channel authorization
 * - Real-time streaming flow
 */
class StreamingBroadcastTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected AgentThread $thread;
    protected StreamingService $streamingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->thread = AgentThread::factory()->create();
        $this->streamingService = app(StreamingService::class);

        // Clear any cached data
        Cache::flush();
    }

    /** @test */
    public function it_broadcasts_thread_token_streamed_events_with_correct_structure()
    {
        Broadcast::fake();

        $streamId = 'test-stream-123';

        // Test token streaming
        $this->streamingService->streamToken($this->thread->id, 'Hello', $streamId);

        Broadcast::assertDispatched(ThreadTokenStreamed::class, function ($event) use ($streamId) {
            return $event->threadId === $this->thread->id
                && $event->token === 'Hello'
                && $event->streamId === $streamId
                && $event->sequence === 1
                && $event->done === false;
        });
    }

    /** @test */
    public function it_broadcasts_completion_event_with_done_flag()
    {
        Broadcast::fake();

        $streamId = 'test-stream-456';
        $fullResponse = 'Complete AI response text';

        // Stream some tokens first
        $this->streamingService->streamToken($this->thread->id, 'Hello', $streamId);
        $this->streamingService->streamToken($this->thread->id, ' World', $streamId);

        // End the stream
        $this->streamingService->endStream($this->thread->id, $streamId, $fullResponse);

        Broadcast::assertDispatched(ThreadTokenStreamed::class, function ($event) use ($fullResponse) {
            return $event->done === true
                && $event->fullResponse === $fullResponse;
        });
    }

    /** @test */
    public function it_maintains_sequence_monotonicity()
    {
        Event::fake([ThreadTokenStreamed::class]);

        $streamId = 'sequence-test';
        $tokens = ['Hello', ' ', 'World', '!'];

        foreach ($tokens as $token) {
            $this->streamingService->streamToken($this->thread->id, $token, $streamId);
        }

        $this->streamingService->endStream($this->thread->id, $streamId);

        // Verify sequence ordering
        Event::assertDispatched(ThreadTokenStreamed::class, 5); // 4 tokens + 1 done

        $events = [];
        Event::assertDispatched(ThreadTokenStreamed::class, function ($event) use (&$events) {
            $events[] = $event;
            return true;
        });

        // Check sequence monotonicity
        $sequences = array_map(fn($event) => $event->sequence ?? 0, array_slice($events, 0, 4));
        $this->assertEquals([1, 2, 3, 4], $sequences, 'Sequences should be monotonic');

        // Check done event has no sequence
        $doneEvent = end($events);
        $this->assertTrue($doneEvent->done);
        $this->assertNull($doneEvent->sequence);
    }

    /** @test */
    public function it_prevents_duplicate_completion_signals_idempotently()
    {
        Event::fake([ThreadTokenStreamed::class]);

        $streamId = 'idempotent-test';

        // Stream a token
        $this->streamingService->streamToken($this->thread->id, 'Test', $streamId);

        // End stream multiple times (should be idempotent)
        $this->streamingService->endStream($this->thread->id, $streamId, 'Final response');
        $this->streamingService->endStream($this->thread->id, $streamId, 'Final response');
        $this->streamingService->endStream($this->thread->id, $streamId, 'Different response');

        // Should only have 2 events: 1 token + 1 done (not 4)
        Event::assertDispatched(ThreadTokenStreamed::class, 2);

        // Verify completion flag exists in cache
        $this->assertTrue(
            Cache::has("ai:done:{$streamId}"),
            'Completion flag should be cached'
        );
    }

    /** @test */
    public function it_uses_correct_broadcast_channel_and_event_names()
    {
        $event = new ThreadTokenStreamed(
            threadId: $this->thread->id,
            token: 'test',
            streamId: 'test-123'
        );

        // Test channel name
        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertEquals("agent.thread.{$this->thread->id}", $channels[0]->name);

        // Test event name
        $this->assertEquals('agent.thread.token', $event->broadcastAs());

        // Test payload structure
        $payload = $event->broadcastWith();
        $expectedKeys = ['token', 'sequence', 'stream_id', 'done', 'full_response'];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $payload, "Payload should contain {$key}");
        }
    }

    /** @test */
    public function it_handles_concurrent_streaming_without_sequence_collisions()
    {
        Event::fake([ThreadTokenStreamed::class]);

        $streamId = 'concurrent-test';

        // Simulate concurrent token streaming
        $processes = [];
        for ($i = 0; $i < 5; $i++) {
            $processes[] = function() use ($streamId, $i) {
                $this->streamingService->streamToken(
                    $this->thread->id,
                    "Token-{$i}",
                    $streamId
                );
            };
        }

        // Execute concurrently (simulate with sequential for test)
        foreach ($processes as $process) {
            $process();
        }

        // Verify all tokens were processed
        Event::assertDispatched(ThreadTokenStreamed::class, 5);

        // Verify sequence integrity
        $cacheKey = "ai:sequence:{$streamId}";
        $finalSequence = Cache::get($cacheKey, 0);
        $this->assertEquals(5, $finalSequence, 'Final sequence should be 5');
    }

    /** @test */
    public function it_handles_streaming_service_integration_end_to_end()
    {
        // Don't fake - test real broadcasting
        $streamId = 'integration-test';

        // Start streaming
        $this->streamingService->streamToken($this->thread->id, 'Integration', $streamId);
        $this->streamingService->streamToken($this->thread->id, ' test', $streamId);

        // Verify cache state
        $sequenceKey = "ai:sequence:{$streamId}";
        $this->assertEquals(2, Cache::get($sequenceKey));

        // End stream
        $fullResponse = 'Integration test complete';
        $result = $this->streamingService->endStream($this->thread->id, $streamId, $fullResponse);

        $this->assertTrue($result, 'endStream should return true on first call');

        // Verify idempotent behavior
        $result2 = $this->streamingService->endStream($this->thread->id, $streamId, $fullResponse);
        $this->assertFalse($result2, 'endStream should return false on subsequent calls');

        // Verify cleanup
        $doneKey = "ai:done:{$streamId}";
        $this->assertTrue(Cache::has($doneKey), 'Done flag should be set');
    }

    /** @test */
    public function it_validates_broadcast_event_contracts()
    {
        $event = new ThreadTokenStreamed(
            threadId: $this->thread->id,
            token: 'Contract test',
            streamId: 'contract-123',
            sequence: 1
        );

        // Test ShouldBroadcastNow interface
        $this->assertInstanceOf(
            \Illuminate\Contracts\Broadcasting\ShouldBroadcastNow::class,
            $event,
            'ThreadTokenStreamed should implement ShouldBroadcastNow for ultra-low latency'
        );

        // Test required properties exist
        $this->assertObjectHasProperty('threadId', $event);
        $this->assertObjectHasProperty('token', $event);
        $this->assertObjectHasProperty('streamId', $event);
        $this->assertObjectHasProperty('sequence', $event);
        $this->assertObjectHasProperty('done', $event);
    }

    /** @test */
    public function it_cleans_up_cache_data_after_stream_completion()
    {
        $streamId = 'cleanup-test';

        // Stream tokens
        $this->streamingService->streamToken($this->thread->id, 'Clean', $streamId);
        $this->streamingService->streamToken($this->thread->id, 'up', $streamId);

        // Verify cache data exists
        $sequenceKey = "ai:sequence:{$streamId}";
        $this->assertTrue(Cache::has($sequenceKey));

        // End stream
        $this->streamingService->endStream($this->thread->id, $streamId);

        // Sequence should be cleaned up, but done flag should remain for idempotency
        $this->assertFalse(Cache::has($sequenceKey), 'Sequence should be cleaned up');
        $this->assertTrue(Cache::has("ai:done:{$streamId}"), 'Done flag should remain');
    }
}
