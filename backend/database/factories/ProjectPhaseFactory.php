<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectPhaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name'       => $this->faker->words(2, true),
            'start_date' => now()->subDays(7),
            'end_date'   => now()->addDays(30),
            'progress'   => 0,
            'status'     => 'in_progress',
        ];
    }
}