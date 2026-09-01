<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskOccurrence;
use App\Models\User;
use App\TaskOccurrenceStatus;
use App\TaskPriority;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskOccurrence>
 */
class TaskOccurrenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'occurrence_date' => today(),
            'due_at' => now()->addDay(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'priority' => TaskPriority::Normal,
            'status' => TaskOccurrenceStatus::Pending,
            'started_at' => null,
            'completed_at' => null,
            'employee_note' => null,
            'viewed_at' => null,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (): array => [
            'status' => TaskOccurrenceStatus::InProgress,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => TaskOccurrenceStatus::Completed,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);
    }
}
