<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();
        return [
            'name'      => $name,
            'slug'      => Str::slug($name) . '-' . Str::random(6),
            'is_active' => true,
        ];
    }
}
