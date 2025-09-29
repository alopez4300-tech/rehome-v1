<?php

namespace App\Services\Agent\Providers;

use App\Models\AgentRun;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use Generator;

/**
 * Anthropic Provider - Handles Anthropic Claude API integration with streaming
 *
 * Supports Claude 3.5 Haiku, Claude 3.5 Sonnet, and other Anthropic models
 * with token streaming and cost calculation.
 */
class AnthropicProvider
{
    private string $apiKey;
    private string $baseUrl = 'https://api.anthropic.com/v1';
    private int $timeout;
    private string $version = '2023-06-01';

    public function __construct()
    {
        $this->apiKey = config('ai.api_keys.anthropic');
        $this->timeout = config('ai.timeout_seconds', 60);

        if (!$this->apiKey) {
            throw new Exception('Anthropic API key not configured');
        }
    }

    /**
     * Execute chat completion with streaming
     */
    public function chatCompletion(AgentRun $run, array $messages, bool $stream = true): Generator
    {
        $model = $run->model;
        $maxTokens = config('ai.max_tokens', 4096);
        $temperature = config('ai.temperature', 0.7);

        // Convert OpenAI format messages to Anthropic format
        $anthropicMessages = $this->convertMessagesToAnthropic($messages);

        $payload = [
            'model' => $model,
            'messages' => $anthropicMessages['messages'],
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'stream' => $stream,
        ];

        // Add system message if present
        if ($anthropicMessages['system']) {
            $payload['system'] = $anthropicMessages['system'];
        }

        Log::info('Anthropic API Request', [
            'run_id' => $run->id,
            'model' => $model,
            'message_count' => count($anthropicMessages['messages']),
            'stream' => $stream
        ]);

        try {
            if ($stream) {
                yield from $this->streamCompletion($payload);
            } else {
                $response = $this->makeRequest('/messages', $payload);
                yield $this->parseNonStreamResponse($response);
            }
        } catch (Exception $e) {
            Log::error('Anthropic API Error', [
                'run_id' => $run->id,
                'error' => $e->getMessage(),
                'model' => $model
            ]);
            throw $e;
        }
    }

    /**
     * Stream completion with Server-Sent Events
     */
    private function streamCompletion(array $payload): Generator
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => $this->version,
            'Content-Type' => 'application/json',
        ])
        ->timeout($this->timeout)
        ->post("{$this->baseUrl}/messages", $payload);

        if (!$response->successful()) {
            throw new Exception("Anthropic API error: {$response->status()} - {$response->body()}");
        }

        // Parse SSE stream
        $buffer = '';
        $content = '';
        $inputTokens = 0;
        $outputTokens = 0;

        foreach (str_split($response->body()) as $char) {
            $buffer .= $char;

            // Look for complete SSE lines
            if (str_ends_with($buffer, "\n\n")) {
                $lines = explode("\n", trim($buffer));
                $buffer = '';

                foreach ($lines as $line) {
                    if (str_starts_with($line, 'data: ')) {
                        $data = substr($line, 6);

                        try {
                            $json = json_decode($data, true);

                            if ($json['type'] === 'content_block_delta') {
                                $token = $json['delta']['text'] ?? '';
                                if ($token) {
                                    $content .= $token;

                                    yield [
                                        'type' => 'token',
                                        'content' => $token,
                                        'done' => false
                                    ];
                                }
                            } elseif ($json['type'] === 'message_delta') {
                                // Usage information
                                if (isset($json['usage'])) {
                                    $inputTokens = $json['usage']['input_tokens'] ?? 0;
                                    $outputTokens = $json['usage']['output_tokens'] ?? 0;
                                }
                            } elseif ($json['type'] === 'message_stop') {
                                // Stream completed
                                yield [
                                    'type' => 'complete',
                                    'content' => $content,
                                    'done' => true,
                                    'usage' => [
                                        'prompt_tokens' => $inputTokens,
                                        'completion_tokens' => $outputTokens,
                                        'total_tokens' => $inputTokens + $outputTokens
                                    ]
                                ];
                                return;
                            }
                        } catch (Exception $e) {
                            Log::warning('Failed to parse Anthropic SSE data', ['data' => $data]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Make standard HTTP request
     */
    private function makeRequest(string $endpoint, array $payload): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => $this->version,
            'Content-Type' => 'application/json',
        ])
        ->timeout($this->timeout)
        ->post("{$this->baseUrl}{$endpoint}", $payload);

        if (!$response->successful()) {
            throw new Exception("Anthropic API error: {$response->status()} - {$response->body()}");
        }

        return $response->json();
    }

    /**
     * Parse non-streaming response
     */
    private function parseNonStreamResponse(array $response): array
    {
        return [
            'type' => 'complete',
            'content' => $response['content'][0]['text'] ?? '',
            'done' => true,
            'usage' => $response['usage'] ?? []
        ];
    }

    /**
     * Convert OpenAI format messages to Anthropic format
     */
    private function convertMessagesToAnthropic(array $messages): array
    {
        $anthropicMessages = [];
        $systemMessage = null;

        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                // Anthropic handles system messages separately
                $systemMessage = $message['content'];
            } elseif ($message['role'] === 'assistant') {
                $anthropicMessages[] = [
                    'role' => 'assistant',
                    'content' => $message['content']
                ];
            } elseif ($message['role'] === 'user') {
                $anthropicMessages[] = [
                    'role' => 'user',
                    'content' => $message['content']
                ];
            }
        }

        return [
            'messages' => $anthropicMessages,
            'system' => $systemMessage
        ];
    }

    /**
     * Calculate cost for tokens
     */
    public function calculateCost(string $model, int $inputTokens, int $outputTokens): int
    {
        $costs = config("ai.costs.{$model}", [
            'input' => 1.00,
            'output' => 5.00
        ]);

        // Convert from USD per 1M tokens to cents
        $inputCost = ($inputTokens / 1_000_000) * $costs['input'] * 100;
        $outputCost = ($outputTokens / 1_000_000) * $costs['output'] * 100;

        return (int) ceil($inputCost + $outputCost);
    }

    /**
     * Count tokens (approximate for Anthropic models)
     */
    public function countTokens(array $messages, string $model): int
    {
        // Rough approximation: 3.5 characters per token for Anthropic
        $text = json_encode($messages);
        return (int) ceil(strlen($text) / 3.5);
    }

    /**
     * Get supported models
     */
    public static function getSupportedModels(): array
    {
        return [
            'claude-3-5-haiku-20241022',
            'claude-3-5-sonnet-20241022',
            'claude-3-haiku-20240307',
            'claude-3-sonnet-20240229'
        ];
    }
}
