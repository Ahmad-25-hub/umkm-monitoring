<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Task;
use App\Models\User;
use App\TaskPriority;
use App\TaskType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'created_by_user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'type' => TaskType::OneTime,
            'priority' => TaskPriority::Normal,
            'starts_on' => today(),
            'ends_on' => null,
            'due_at' => now()->addDay(),
            'daily_due_time' => null,
            'is_active' => true,
        ];
    }

    public function daily(): static
    {
        return $this->state(fn (): array => [
            'type' => TaskType::Daily,
            'starts_on' => today(),
            'ends_on' => today()->addMonth(),
            'due_at' => null,
            'daily_due_time' => '17:00:00',
        ]);
    }
}
