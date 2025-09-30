<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name'         => fake()->sentence(3),
            'description'  => fake()->optional()->paragraph(),
            'status'       => 'active',
        ];
    }
}
