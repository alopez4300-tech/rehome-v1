<?php

namespace App\Console\Commands;

use App\Events\Agent\AgentMessageCreated;
use Illuminate\Console\Command;

class TestBroadcasting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:broadcasting {--thread-id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the agent broadcasting system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $threadId = $this->option('thread-id');

        $this->info('Testing agent broadcasting system...');

        // Create a test message
        $testMessage = (object) [
            'id' => 999,
            'thread_id' => $threadId,
            'agent_thread_id' => $threadId,
            'role' => 'assistant',
            'content' => 'This is a test message from the broadcasting system at '.now(),
            'metadata' => [
                'provider' => 'test',
                'model' => 'test-model',
                'test' => true,
            ],
            'cost_cents' => 10,
            'token_count' => 25,
            'created_at' => now(),
        ];

        $this->info("Broadcasting test message to thread {$threadId}...");

        try {
            // Try broadcasting the event
            $event = new AgentMessageCreated($testMessage);
            broadcast($event);

            $this->info('✅ Event broadcast successfully!');
            $this->info("Channel: agent.thread.{$threadId}");
            $this->info('Event: agent.message.created');
            $this->info('Content: '.substr($testMessage->content, 0, 50).'...');

        } catch (\Exception $e) {
            $this->error('❌ Broadcasting failed: '.$e->getMessage());
            $this->error('Stack trace: '.$e->getTraceAsString());
        }

        $this->info("\n💡 To test client-side, open browser to:");
        $this->info("   http://localhost/admin/agent-threads/{$threadId}");
        $this->info('   And check the browser console for WebSocket messages.');

        return 0;
    }
}
