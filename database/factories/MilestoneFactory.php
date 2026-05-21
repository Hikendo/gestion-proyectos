<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class MilestoneFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id'  => Project::factory(),
            'title'       => $this->faker->words(3, true),
            'target_date' => now()->addMonths(2),
            'completed'   => false,
        ];
    }
}
