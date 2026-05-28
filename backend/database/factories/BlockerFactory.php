<?php

namespace Database\Factories;

use App\Enums\BlockerSeverity;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlockerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id'  => Project::factory(),
            'task_id'     => null,
            'title'       => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'severity'    => BlockerSeverity::Medium->value,
            'resolved'    => false,
        ];
    }
}
