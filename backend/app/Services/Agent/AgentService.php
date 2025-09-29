<?php

namespace App\Services\Agent;

use App\Models\AgentThread;
use App\Models\AgentMessage;
use App\Models\AgentRun;
use App\Models\User;
use App\Services\Agent\ContextBuilder;
use App\Services\Agent\CostTracker;
use App\Services\Agent\StreamingService;
use App\Services\Agent\Providers\OpenAIProvider;
use App\Services\Agent\Providers\AnthropicProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

/**
 * Agent Service - Main orchestration for AI agent interactions
 *
 * Handles the complete agent execution flow: context building,
 * LLM interaction, streaming, and cost tracking.
 */
class AgentService
{
    private ContextBuilder $contextBuilder;
    private CostTracker $costTracker;
    private StreamingService $streamingService;
    private array $config;

    public function __construct(
        ContextBuilder $contextBuilder,
        CostTracker $costTracker,
        StreamingService $streamingService
    ) {
        $this->contextBuilder = $contextBuilder;
        $this->costTracker = $costTracker;
        $this->streamingService = $streamingService;
        $this->config = config('ai');
    }

    /**
     * Process an agent message with full orchestration
     */
    public function processMessage(AgentThread $thread, string $userMessage, User $user): AgentRun
    {
        Log::info('Starting agent message processing', [
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'message_length' => strlen($userMessage),
        ]);

        // Pre-flight checks
        $this->validateRequest($thread, $user);

        // Create the agent run
        $run = $this->createAgentRun($thread, $user);

        try {
            // Create user message
            $userMessageRecord = $this->createUserMessage($thread, $userMessage, $user);

            // Build context within token budget
            $context = $this->contextBuilder->buildContext($thread, $this->getMaxTokens());

            // Store context for debugging/audit
            $run->update(['context_used' => $context]);

            // Execute the LLM request with streaming
            $response = $this->executeLLMRequest($run, $context, $userMessage);

            // Create assistant message
            $assistantMessage = $this->createAssistantMessage($thread, $response['content'], $run);

            // Record usage and costs
            $this->recordUsage($run, $response);

            // Mark run as completed
            $run->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);

            Log::info('Agent message processing completed', [
                'run_id' => $run->id,
                'tokens_used' => $response['tokens_used'],
                'cost_cents' => $run->cost_cents,
            ]);

            return $run;

        } catch (Exception $e) {
            $this->handleError($run, $e);
            throw $e;
        }
    }

    /**
     * Create a new agent thread
     */
    public function createThread(
        int $projectId,
        string $title,
        string $audience,
        User $user
    ): AgentThread {
        Log::info('Creating new agent thread', [
            'project_id' => $projectId,
            'title' => $title,
            'audience' => $audience,
            'user_id' => $user->id,
        ]);

        $thread = AgentThread::create([
            'project_id' => $projectId,
            'title' => $title,
            'audience' => $audience,
            'created_by' => $user->id,
            'status' => 'active',
        ]);

        // Create system message
        $systemPrompt = $this->contextBuilder->buildSystemPrompt($thread);
        $this->createSystemMessage($thread, $systemPrompt);

        return $thread;
    }

    /**
     * Validate that the request can proceed
     */
    private function validateRequest(AgentThread $thread, User $user): void
    {
        $workspace = $thread->project->workspace;

        // Check rate limits
        if (!$this->costTracker->canMakeRequest($user, $workspace)) {
            throw new Exception('Rate limit exceeded. Please try again later.');
        }

        // Check budget constraints
        $budgetStatus = $this->costTracker->checkBudget($user, $workspace);

        if (!$budgetStatus['can_proceed'] && !$budgetStatus['should_degrade']) {
            throw new Exception('Budget limit exceeded. Please contact your administrator.');
        }

        // Check circuit breaker
        $provider = $this->config['provider'];
        if (!$this->costTracker->canUseProvider($provider)) {
            throw new Exception('AI service is temporarily unavailable. Please try again later.');
        }

        // Record the request for rate limiting
        $this->costTracker->recordRequest($user, $workspace);
    }

    /**
     * Create a new agent run record
     */
    private function createAgentRun(AgentThread $thread, User $user): AgentRun
    {
        return AgentRun::create([
            'thread_id' => $thread->id,
            'status' => 'running',
            'provider' => $this->config['provider'],
            'model' => $this->config['model'],
            'started_at' => now(),
        ]);
    }

    /**
     * Create user message record
     */
    private function createUserMessage(AgentThread $thread, string $content, User $user): AgentMessage
    {
        return AgentMessage::create([
            'thread_id' => $thread->id,
            'role' => 'user',
            'content' => $content,
            'created_by' => $user->id,
        ]);
    }

    /**
     * Create system message record
     */
    private function createSystemMessage(AgentThread $thread, string $content): AgentMessage
    {
        return AgentMessage::create([
            'thread_id' => $thread->id,
            'role' => 'system',
            'content' => $content,
        ]);
    }

    /**
     * Create assistant message record
     */
    private function createAssistantMessage(AgentThread $thread, string $content, AgentRun $run): AgentMessage
    {
        return AgentMessage::create([
            'thread_id' => $thread->id,
            'role' => 'assistant',
            'content' => $content,
            'meta' => [
                'run_id' => $run->id,
                'model' => $run->model,
                'provider' => $run->provider,
            ],
        ]);
    }

    /**
     * Execute the LLM request with streaming support
     */
    private function executeLLMRequest(AgentRun $run, array $context, string $userMessage): array
    {
        $provider = $this->config['provider'];

        try {
            // Prepare messages for the LLM
            $messages = $this->prepareMessages($context, $userMessage);

            // Execute request based on provider
            $response = match ($provider) {
                'openai' => $this->executeOpenAIRequest($run, $messages),
                'anthropic' => $this->executeAnthropicRequest($run, $messages),
                default => throw new Exception("Unsupported provider: {$provider}")
            };

            // Record success for circuit breaker
            $this->costTracker->recordSuccess($provider);

            return $response;

        } catch (Exception $e) {
            // Record failure for circuit breaker
            $this->costTracker->recordFailure($provider);

            Log::error('LLM request failed', [
                'run_id' => $run->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Prepare messages array for LLM request
     */
    private function prepareMessages(array $context, string $userMessage): array
    {
        $messages = [];

        // Add system prompt
        if (!empty($context['system_prompt'])) {
            $messages[] = [
                'role' => 'system',
                'content' => $context['system_prompt'],
            ];
        }

        // Add context messages
        foreach ($context['messages'] as $msg) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }

        // Add context information as system message
        $contextInfo = $this->buildContextInfo($context);
        if (!empty($contextInfo)) {
            $messages[] = [
                'role' => 'system',
                'content' => $contextInfo,
            ];
        }

        // Add current user message
        $messages[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        return $messages;
    }

    /**
     * Build context information string for LLM
     */
    private function buildContextInfo(array $context): string
    {
        $parts = [];

        // Add task information
        if (!empty($context['tasks']['recent_tasks'])) {
            $parts[] = "Recent Tasks:\n" . json_encode($context['tasks']['recent_tasks'], JSON_PRETTY_PRINT);
        }

        // Add file information
        if (!empty($context['files']['recent_files'])) {
            $parts[] = "Recent Files:\n" . json_encode($context['files']['recent_files'], JSON_PRETTY_PRINT);
        }

        // Add project metadata
        if (!empty($context['files']['project_meta'])) {
            $parts[] = "Project Info:\n" . json_encode($context['files']['project_meta'], JSON_PRETTY_PRINT);
        }

        return implode("\n\n", $parts);
    }

    /**
     * Execute OpenAI API request with streaming
     */
    private function executeOpenAIRequest(AgentRun $run, array $messages): array
    {
        $provider = new OpenAIProvider();

        Log::info('Executing OpenAI request', [
            'run_id' => $run->id,
            'model' => $run->model,
            'message_count' => count($messages),
        ]);

        $fullContent = '';
        $usage = [];

        // Process streaming response
        foreach ($provider->chatCompletion($run, $messages, true) as $chunk) {
            if ($chunk['type'] === 'token') {
                // Stream token via WebSocket
                $this->streamingService->streamToken($run->thread, $run, 'stream_' . $run->id, $chunk['content']);
                $fullContent .= $chunk['content'];
            } elseif ($chunk['type'] === 'complete') {
                $usage = $chunk['usage'];

                // End stream
                $this->streamingService->endStream($run->thread, $run, 'stream_' . $run->id, $fullContent);
                break;
            }
        }

        return [
            'content' => $fullContent,
            'tokens_used' => [
                'input' => $usage['prompt_tokens'] ?? 0,
                'output' => $usage['completion_tokens'] ?? 0,
            ],
            'finish_reason' => 'stop',
        ];
    }

    /**
     * Execute Anthropic API request with streaming
     */
    private function executeAnthropicRequest(AgentRun $run, array $messages): array
    {
        $provider = new AnthropicProvider();

        Log::info('Executing Anthropic request', [
            'run_id' => $run->id,
            'model' => $run->model,
            'message_count' => count($messages),
        ]);

        $fullContent = '';
        $usage = [];

        // Process streaming response
        foreach ($provider->chatCompletion($run, $messages, true) as $chunk) {
            if ($chunk['type'] === 'token') {
                // Stream token via WebSocket
                $this->streamingService->streamToken($run->thread, $run, 'stream_' . $run->id, $chunk['content']);
                $fullContent .= $chunk['content'];
            } elseif ($chunk['type'] === 'complete') {
                $usage = $chunk['usage'];

                // End stream
                $this->streamingService->endStream($run->thread, $run, 'stream_' . $run->id, $fullContent);
                break;
            }
        }

        return [
            'content' => $fullContent,
            'tokens_used' => [
                'input' => $usage['prompt_tokens'] ?? 0,
                'output' => $usage['completion_tokens'] ?? 0,
            ],
            'finish_reason' => 'stop',
        ];
    }

    /**
     * Record usage statistics and costs
     */
    private function recordUsage(AgentRun $run, array $response): void
    {
        $run->update([
            'tokens_in' => $response['tokens_used']['input'],
            'tokens_out' => $response['tokens_used']['output'],
        ]);

        $this->costTracker->recordUsage($run);
    }

    /**
     * Handle errors during agent processing
     */
    private function handleError(AgentRun $run, Exception $e): void
    {
        $run->update([
            'status' => 'failed',
            'error' => $e->getMessage(),
            'finished_at' => now(),
        ]);

        Log::error('Agent run failed', [
            'run_id' => $run->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /**
     * Get maximum tokens for current model
     */
    private function getMaxTokens(): int
    {
        $model = $this->config['model'];
        $modelConfig = $this->config['models'][$model] ?? null;

        if (!$modelConfig) {
            return $this->config['max_tokens'];
        }

        return min(
            $this->config['max_tokens'],
            $modelConfig['context_window'] - $modelConfig['max_output_tokens']
        );
    }
}
