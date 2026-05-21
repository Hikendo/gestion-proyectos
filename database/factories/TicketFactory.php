<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id'  => Project::factory(),
            'created_by'  => User::factory(),
            'assigned_to' => null,
            'subject'     => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'status'      => TicketStatus::Open->value,
            'priority'    => TicketPriority::Medium->value,
        ];
    }
}
