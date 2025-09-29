<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Provider Configuration
    |--------------------------------------------------------------------------
    */
    'provider' => env('AI_PROVIDER', 'openai'),
    'model' => env('AI_MODEL', 'gpt-4o-mini'),
    'max_tokens' => (int) env('AI_MAX_TOKENS', 4096),
    'temperature' => (float) env('AI_TEMPERATURE', 0.7),

    /*
    |--------------------------------------------------------------------------
    | Context Building Policy
    |--------------------------------------------------------------------------
    | Fit to token budget with configurable split percentages
    */
    'context_budget' => [
        'messages' => 0.5,  // 50% of tokens for messages
        'tasks' => 0.3,     // 30% of tokens for tasks
        'files' => 0.2,     // 20% of tokens for files/meta
    ],
    'token_safety_buffer' => (float) env('AI_TOKEN_SAFETY', 0.10),
    'truncate_strategy' => 'drop_whole', // Avoid mid-message truncation

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting & Governance
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'per_user_minute' => 5,
        'per_user_day' => 50,
        'per_workspace_day' => 500,
    ],
    'timeout_seconds' => (int) env('AI_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Cost Configuration (USD per 1M tokens)
    |--------------------------------------------------------------------------
    */
    'costs' => [
        'gpt-4o-mini' => [
            'input' => 0.15,
            'output' => 0.60,
        ],
        'gpt-4o' => [
            'input' => 5.00,
            'output' => 15.00,
        ],
        'claude-3-haiku' => [
            'input' => 0.25,
            'output' => 1.25,
        ],
        'claude-3-sonnet' => [
            'input' => 3.00,
            'output' => 15.00,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Budget Enforcement
    |--------------------------------------------------------------------------
    */
    'budgets' => [
        'default_user_daily_cents' => 500,    // $5.00 per user per day
        'default_workspace_monthly_cents' => 10000, // $100.00 per workspace per month
        'warning_threshold' => 0.80, // Warn at 80% budget usage
        'graceful_degradation' => true, // Serve cached summaries when over budget
    ],

    /*
    |--------------------------------------------------------------------------
    | Security & PII Protection
    |--------------------------------------------------------------------------
    */
    'pii_redaction' => [
        'enabled' => env('AI_PII_REDACTION', true),
        'patterns' => [
            'email' => '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/',
            'phone' => '/\b\d{3}[-.]?\d{3}[-.]?\d{4}\b/',
            'ssn' => '/\b\d{3}-\d{2}-\d{4}\b/',
            'credit_card' => '/\b\d{4}[-\s]?\d{4}[-\s]?\d{4}[-\s]?\d{4}\b/',
        ],
        'replacement' => '[REDACTED]',
    ],

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker Configuration
    |--------------------------------------------------------------------------
    */
    'circuit_breaker' => [
        'failure_threshold' => 5, // Number of failures before opening circuit
        'recovery_timeout' => 60, // Seconds before attempting recovery
        'success_threshold' => 3, // Successful calls needed to close circuit
    ],

    /*
    |--------------------------------------------------------------------------
    | API Keys (Provider-specific)
    |--------------------------------------------------------------------------
    */
    'api_keys' => [
        'openai' => env('OPENAI_API_KEY'),
        'anthropic' => env('ANTHROPIC_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Model-specific Configurations
    |--------------------------------------------------------------------------
    */
    'models' => [
        'gpt-4o-mini' => [
            'context_window' => 128000,
            'max_output_tokens' => 16384,
            'supports_streaming' => true,
            'supports_function_calling' => true,
        ],
        'gpt-4o' => [
            'context_window' => 128000,
            'max_output_tokens' => 4096,
            'supports_streaming' => true,
            'supports_function_calling' => true,
        ],
        'claude-3-haiku' => [
            'context_window' => 200000,
            'max_output_tokens' => 4096,
            'supports_streaming' => true,
            'supports_function_calling' => false,
        ],
        'claude-3-sonnet' => [
            'context_window' => 200000,
            'max_output_tokens' => 4096,
            'supports_streaming' => true,
            'supports_function_calling' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Agent Behavior Configuration
    |--------------------------------------------------------------------------
    */
    'behavior' => [
        'max_concurrent_runs' => (int) env('AGENT_MAX_CONCURRENT', 3),
        'retry_attempts' => 3,
        'retry_delay_seconds' => [2, 4, 8], // Exponential backoff
        'stream_chunk_size' => 1024,
        'context_refresh_threshold' => 0.90, // Rebuild context at 90% token usage
    ],

    /*
    |--------------------------------------------------------------------------
    | Summary Configuration
    |--------------------------------------------------------------------------
    */
    'summaries' => [
        'daily_digest_time' => '09:00', // Time to send daily digests
        'weekly_rollup_day' => 'monday', // Day of week for weekly rollups
        'max_summary_tokens' => 2048,
        'summary_templates' => [
            'daily' => 'Summarize today\'s key activities, decisions, and blockers for this project.',
            'weekly' => 'Provide a weekly workspace summary including progress, risks, and priorities.',
            'milestone' => 'Summarize milestone completion status and next steps.',
            'meeting' => 'Summarize meeting outcomes, action items, and decisions.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging & Monitoring
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'log_requests' => env('AI_LOG_REQUESTS', true),
        'log_responses' => env('AI_LOG_RESPONSES', false), // PII concerns
        'log_costs' => true,
        'log_rate_limits' => true,
        'audit_high_value_queries' => true,
    ],
];
