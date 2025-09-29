<?php

namespace App\Services\Agent\Providers;

use App\Models\AgentRun;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use Generator;

/**
 * OpenAI Provider - Handles OpenAI API integration with streaming
 *
 * Supports GPT-4o-mini, GPT-4o, and other OpenAI models with
 * token streaming and cost calculation.
 */
class OpenAIProvider
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1';
    private int $timeout;

    public function __construct()
    {
        $this->apiKey = config('ai.api_keys.openai');
        $this->timeout = config('ai.timeout_seconds', 60);

        if (!$this->apiKey) {
            throw new Exception('OpenAI API key not configured');
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

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'stream' => $stream,
            'user' => "user_{$run->thread->created_by}",
        ];

        Log::info('OpenAI API Request', [
            'run_id' => $run->id,
            'model' => $model,
            'message_count' => count($messages),
            'stream' => $stream
        ]);

        try {
            if ($stream) {
                yield from $this->streamCompletion($payload);
            } else {
                $response = $this->makeRequest('/chat/completions', $payload);
                yield $this->parseNonStreamResponse($response);
            }
        } catch (Exception $e) {
            Log::error('OpenAI API Error', [
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
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])
        ->timeout($this->timeout)
        ->post("{$this->baseUrl}/chat/completions", $payload);

        if (!$response->successful()) {
            throw new Exception("OpenAI API error: {$response->status()} - {$response->body()}");
        }

        // Parse SSE stream
        $buffer = '';
        $content = '';
        $totalTokens = 0;
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

                        if ($data === '[DONE]') {
                            // Final yield with complete response
                            yield [
                                'type' => 'complete',
                                'content' => $content,
                                'done' => true,
                                'usage' => [
                                    'prompt_tokens' => $inputTokens,
                                    'completion_tokens' => $outputTokens,
                                    'total_tokens' => $totalTokens
                                ]
                            ];
                            return;
                        }

                        try {
                            $json = json_decode($data, true);
                            if (isset($json['choices'][0]['delta']['content'])) {
                                $token = $json['choices'][0]['delta']['content'];
                                $content .= $token;

                                yield [
                                    'type' => 'token',
                                    'content' => $token,
                                    'done' => false
                                ];
                            }

                            // Extract usage info if available
                            if (isset($json['usage'])) {
                                $inputTokens = $json['usage']['prompt_tokens'];
                                $outputTokens = $json['usage']['completion_tokens'];
                                $totalTokens = $json['usage']['total_tokens'];
                            }
                        } catch (Exception $e) {
                            Log::warning('Failed to parse OpenAI SSE data', ['data' => $data]);
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
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])
        ->timeout($this->timeout)
        ->post("{$this->baseUrl}{$endpoint}", $payload);

        if (!$response->successful()) {
            throw new Exception("OpenAI API error: {$response->status()} - {$response->body()}");
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
            'content' => $response['choices'][0]['message']['content'] ?? '',
            'done' => true,
            'usage' => $response['usage'] ?? []
        ];
    }

    /**
     * Calculate cost for tokens
     */
    public function calculateCost(string $model, int $inputTokens, int $outputTokens): int
    {
        $costs = config("ai.costs.{$model}", [
            'input' => 0.15,
            'output' => 0.60
        ]);

        // Convert from USD per 1M tokens to cents
        $inputCost = ($inputTokens / 1_000_000) * $costs['input'] * 100;
        $outputCost = ($outputTokens / 1_000_000) * $costs['output'] * 100;

        return (int) ceil($inputCost + $outputCost);
    }

    /**
     * Count tokens (approximate for OpenAI models)
     */
    public function countTokens(array $messages, string $model): int
    {
        // Rough approximation: 4 characters per token for most models
        $text = json_encode($messages);
        return (int) ceil(strlen($text) / 4);
    }

    /**
     * Get supported models
     */
    public static function getSupportedModels(): array
    {
        return [
            'gpt-4o-mini',
            'gpt-4o',
            'gpt-4-turbo',
            'gpt-3.5-turbo'
        ];
    }
}
