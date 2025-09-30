<?php

namespace Database\Factories;

use App\Models\AgentRun;
use App\Models\AgentThread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgentRun>
 */
class AgentRunFactory extends Factory
{
    protected $model = AgentRun::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'thread_id' => AgentThread::factory(),
            'status' => $this->faker->randomElement(['running', 'completed', 'failed']),
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'tokens_in' => $this->faker->numberBetween(10, 100),
            'tokens_out' => $this->faker->numberBetween(50, 500),
            'cost_cents' => $this->faker->numberBetween(1, 100),
            'context_used' => [
                'system_prompt' => $this->faker->paragraph(),
                'user_context' => $this->faker->sentence(),
            ],
            'started_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'finished_at' => $this->faker->optional(0.7)->dateTimeBetween('now', '+10 minutes'),
            'error' => null,
        ];
    }

    /**
     * Create a running agent run
     */
    public function running(): static
    {
        return $this->state([
            'status' => 'running',
            'finished_at' => null,
        ]);
    }

    /**
     * Create a completed agent run
     */
    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'finished_at' => now(),
        ]);
    }

    /**
     * Create a failed agent run
     */
    public function failed(): static
    {
        return $this->state([
            'status' => 'failed',
            'finished_at' => now(),
            'error' => 'Test error message',
        ]);
    }
}
