<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'       => $this->faker->words(3, true),
            'code'       => strtoupper($this->faker->unique()->bothify('??-###')),
            'description' => $this->faker->sentence(),
            'status'     => ProjectStatus::Active->value,
            'start_date' => now(),
            'end_date'   => now()->addMonths(6),
            'budget'     => $this->faker->randomFloat(2, 1000, 100000),
            'progress'   => 0,
            'owner_id'   => User::factory(),
        ];
    }
}
