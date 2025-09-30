<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Agent\StreamingService;
use App\Models\{AgentThread, AgentRun};
use Illuminate\Support\Facades\Cache;

class StreamSmoke extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'stream:smoke
                            {thread_id : The AgentThread ID to stream to}
                            {run_id : The AgentRun ID for the stream}
                            {--stream_id=smoke-test : Custom stream ID for diagnostics}
                            {--delay=10 : Delay between tokens in milliseconds}
                            {--message="Hello, streaming!" : Custom message to stream}';

    /**
     * The console command description.
     */
    protected $description = 'Emit a short token stream for diagnostics and verification';

    /**
     * Execute the console command.
     */
    public function handle(StreamingService $streamingService): int
    {
        $threadId = $this->argument('thread_id');
        $runId = $this->argument('run_id');
        $streamId = $this->option('stream_id');
        $delay = (int) $this->option('delay') * 1000; // Convert to microseconds
        $message = $this->option('message');

        try {
            // Validate models exist
            $thread = AgentThread::findOrFail($threadId);
            $run = AgentRun::findOrFail($runId);

            $this->info("🚀 Starting smoke stream:");
            $this->line("   Thread: {$thread->id} (Project: {$thread->project_id})");
            $this->line("   Run: {$run->id} (Status: {$run->status})");
            $this->line("   Stream ID: {$streamId}");
            $this->line("   Message: \"{$message}\"");
            $this->line("   Delay: {$this->option('delay')}ms between tokens");
            $this->newLine();

            // Clear any existing cache for this stream
            Cache::forget("ai:seq:{$streamId}");
            Cache::forget("ai:done:{$streamId}");

            // Start streaming character by character
            $this->info("📡 Streaming tokens...");
            $progressBar = $this->output->createProgressBar(strlen($message));
            $progressBar->start();

            foreach (str_split($message) as $token) {
                $streamingService->streamToken($thread, $run, $streamId, $token);
                $progressBar->advance();

                if ($delay > 0) {
                    usleep($delay);
                }
            }

            $progressBar->finish();
            $this->newLine();

            // End the stream
            $this->info("✅ Ending stream with full response...");
            $streamingService->endStream($thread, $run, $streamId, $message);

            // Verify cache state
            $sequenceExists = Cache::has("ai:seq:{$streamId}");
            $doneExists = Cache::has("ai:done:{$streamId}");

            $this->newLine();
            $this->info("🔍 Post-stream verification:");
            $this->line("   Sequence cache cleaned: " . ($sequenceExists ? '❌ NO' : '✅ YES'));
            $this->line("   Done flag exists: " . ($doneExists ? '✅ YES' : '❌ NO'));

            // Test idempotent behavior
            $this->info("🔒 Testing idempotent endStream...");
            $result = $streamingService->endStream($thread, $run, $streamId, $message);
            $this->line("   Second endStream call: " . ($result ? '❌ UNEXPECTED SUCCESS' : '✅ IDEMPOTENT'));

            $this->newLine();
            $this->info("🎉 Smoke stream completed successfully!");
            $this->comment("💡 Check your client-side Echo listener for received tokens:");
            $this->line("   Channel: agent.thread.{$thread->id}");
            $this->line("   Event: agent.thread.token");

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("🚨 Smoke stream failed: " . $e->getMessage());
            $this->line("   File: " . $e->getFile() . ":" . $e->getLine());

            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }
}
