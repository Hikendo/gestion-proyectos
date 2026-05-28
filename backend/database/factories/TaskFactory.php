<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id'      => Project::factory(),
            'created_by'      => User::factory(),
            'assigned_to'     => null,
            'title'           => $this->faker->sentence(4),
            'description'     => $this->faker->paragraph(),
            'priority'        => TaskPriority::Medium->value,
            'status'          => TaskStatus::Pending->value,
            'due_date'        => now()->addDays(7),
            'estimated_hours' => $this->faker->randomFloat(1, 1, 40),
            'worked_hours'    => 0,
            'progress'        => 0,
        ];
    }
}
