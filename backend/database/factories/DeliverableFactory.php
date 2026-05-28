<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliverableFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id'    => Project::factory(),
            'name'          => $this->faker->words(3, true),
            'description'   => $this->faker->sentence(),
            'delivery_date' => now()->addMonths(3),
            'approved'      => false,
        ];
    }
}
