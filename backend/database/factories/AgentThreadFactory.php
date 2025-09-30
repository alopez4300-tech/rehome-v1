<?php

namespace Database\Factories;

use App\Models\AgentThread;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgentThread>
 */
class AgentThreadFactory extends Factory
{
    protected $model = AgentThread::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'audience' => $this->faker->randomElement(['participant', 'admin']),
            'title' => $this->faker->sentence(),
            'metadata' => [
                'created_by' => 'test',
                'tags' => ['streaming', 'test'],
            ],
        ];
    }

    /**
     * Create a participant thread
     */
    public function participant(): static
    {
        return $this->state([
            'audience' => 'participant',
        ]);
    }

    /**
     * Create an admin thread
     */
    public function admin(): static
    {
        return $this->state([
            'audience' => 'admin',
        ]);
    }
}
