<?php

namespace App\Console\Commands;

use App\Services\Agent\ContextBuilder;
use App\Services\Agent\CostTracker;
use App\Services\Agent\PIIRedactor;
use App\Services\Agent\AgentService;
use App\Models\AgentThread;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Agent System Test Command
 *
 * Tests the core agent services and validates configuration
 */
class TestAgentSystem extends Command
{
    protected $signature = 'agent:test {--component= : Test specific component (context|pii|cost|all)}';
    protected $description = 'Test the AI agent system components';

    public function handle()
    {
        $component = $this->option('component') ?? 'all';

        $this->info('🧪 Testing ReHome Agent System');
        $this->info('=============================');

        switch ($component) {
            case 'context':
                $this->testContextBuilder();
                break;
            case 'pii':
                $this->testPIIRedactor();
                break;
            case 'cost':
                $this->testCostTracker();
                break;
            case 'all':
            default:
                $this->testAll();
                break;
        }
    }

    private function testAll()
    {
        $this->testConfiguration();
        $this->testContextBuilder();
        $this->testPIIRedactor();
        $this->testCostTracker();
        $this->testIntegration();
    }

    private function testConfiguration()
    {
        $this->info("\n📋 Testing Configuration");
        $this->line('----------------------------');

        $config = config('ai');

        if (!$config) {
            $this->error('❌ AI configuration not found');
            return;
        }

        $this->info('✅ AI configuration loaded');

        // Test required config sections
        $sections = ['provider', 'model', 'context_budget', 'rate_limits', 'costs'];
        foreach ($sections as $section) {
            if (isset($config[$section])) {
                $this->info("✅ {$section} configuration present");
            } else {
                $this->warn("⚠️  {$section} configuration missing");
            }
        }

        // Test model configuration
        $model = $config['model'];
        if (isset($config['models'][$model])) {
            $this->info("✅ Model '{$model}' configuration found");
        } else {
            $this->warn("⚠️  Model '{$model}' configuration missing");
        }

        // Test API keys
        $provider = $config['provider'];
        $apiKey = $config['api_keys'][$provider] ?? null;
        if ($apiKey) {
            $this->info("✅ API key for '{$provider}' configured");
        } else {
            $this->warn("⚠️  API key for '{$provider}' not configured");
        }
    }

    private function testContextBuilder()
    {
        $this->info("\n🏗️  Testing Context Builder");
        $this->line('-------------------------------');

        try {
            $piiRedactor = app(PIIRedactor::class);
            $contextBuilder = new ContextBuilder($piiRedactor);

            $this->info('✅ ContextBuilder instantiated');

            // Test token allocation calculation
            $reflection = new \ReflectionClass($contextBuilder);
            $method = $reflection->getMethod('calculateTokenAllocations');
            $method->setAccessible(true);

            $allocations = $method->invoke($contextBuilder, 1000);

            $expected = ['messages' => 500, 'tasks' => 300, 'files' => 200];
            if ($allocations === $expected) {
                $this->info('✅ Token allocation calculation works (50/30/20 split)');
            } else {
                $this->error('❌ Token allocation calculation failed');
                $this->line('Expected: ' . json_encode($expected));
                $this->line('Got: ' . json_encode($allocations));
            }

            // Test token estimation
            $method = $reflection->getMethod('estimateTokens');
            $method->setAccessible(true);

            $tokens = $method->invoke($contextBuilder, 'Hello world');
            if ($tokens === 3) { // ~12 chars / 4 = 3 tokens
                $this->info('✅ Token estimation works');
            } else {
                $this->warn("⚠️  Token estimation: got {$tokens}, expected 3");
            }

        } catch (\Exception $e) {
            $this->error("❌ ContextBuilder test failed: " . $e->getMessage());
        }
    }

    private function testPIIRedactor()
    {
        $this->info("\n🔒 Testing PII Redactor");
        $this->line('-------------------------');

        try {
            $redactor = new PIIRedactor();
            $this->info('✅ PIIRedactor instantiated');

            // Test configuration validation
            $issues = $redactor->validateConfiguration();
            if (empty($issues)) {
                $this->info('✅ PII redaction configuration is valid');
            } else {
                $this->warn('⚠️  PII redaction configuration issues:');
                foreach ($issues as $issue) {
                    $this->line("  - {$issue}");
                }
            }

            // Test PII redaction
            $testText = 'Contact John at john.doe@example.com or call 555-123-4567';
            $redacted = $redactor->redactText($testText);

            if (strpos($redacted, '[REDACTED]') !== false) {
                $this->info('✅ PII redaction works');
                $this->line("  Original: {$testText}");
                $this->line("  Redacted: {$redacted}");
            } else {
                $this->warn('⚠️  PII redaction may not be working correctly');
            }

        } catch (\Exception $e) {
            $this->error("❌ PIIRedactor test failed: " . $e->getMessage());
        }
    }

    private function testCostTracker()
    {
        $this->info("\n💰 Testing Cost Tracker");
        $this->line('-------------------------');

        try {
            $costTracker = new CostTracker();
            $this->info('✅ CostTracker instantiated');

            // Test cost calculation
            $cost = $costTracker->calculateCost('gpt-4o-mini', 1000, 500);
            $expected = 45; // (1000/1M * 0.15 + 500/1M * 0.60) * 100 cents

            if ($cost === $expected) {
                $this->info("✅ Cost calculation works: {$cost} cents");
            } else {
                $this->warn("⚠️  Cost calculation: got {$cost} cents, expected {$expected}");
            }

            // Test circuit breaker status
            $status = $costTracker->getCircuitBreakerStatus('openai');
            if (isset($status['state']) && $status['state'] === 'closed') {
                $this->info('✅ Circuit breaker status works');
            } else {
                $this->warn('⚠️  Circuit breaker status unexpected');
            }

        } catch (\Exception $e) {
            $this->error("❌ CostTracker test failed: " . $e->getMessage());
        }
    }

    private function testIntegration()
    {
        $this->info("\n🔗 Testing Integration");
        $this->line('------------------------');

        try {
            // Test service instantiation via Laravel container
            $contextBuilder = app(ContextBuilder::class);
            $costTracker = app(CostTracker::class);
            $streamingService = app(\App\Services\Agent\StreamingService::class);

            $this->info('✅ Services can be resolved from container');

            // Test AgentService instantiation
            $agentService = new \App\Services\Agent\AgentService(
                $contextBuilder,
                $costTracker,
                $streamingService
            );

            $this->info('✅ AgentService can be instantiated with dependencies');

        } catch (\Exception $e) {
            $this->error("❌ Integration test failed: " . $e->getMessage());
        }
    }
}
