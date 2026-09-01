<?php

namespace Tests\Feature;

use App\Actions\GenerateTaskOccurrencesAction;
use App\Models\Business;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class GenerateDailyTaskOccurrencesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_generator_creates_occurrence_for_each_assignee_on_matching_date(): void
    {
        $business = Business::factory()->create();
        $creator = User::factory()->create();
        $employees = User::factory()->count(2)->create();
        $task = Task::factory()
            ->daily()
            ->for($business)
            ->for($creator, 'creator')
            ->create([
                'title' => 'Tugas rutin',
                'starts_on' => '2026-09-01',
                'ends_on' => '2026-09-30',
                'daily_due_time' => '16:30:00',
            ]);
        $task->assignees()->attach($employees);

        $createdCount = app(GenerateTaskOccurrencesAction::class)->execute('2026-09-15');

        $this->assertSame(2, $createdCount);
        $this->assertDatabaseCount('task_occurrences', 2);
        foreach ($employees as $employee) {
            $this->assertDatabaseHas('task_occurrences', [
                'task_id' => $task->id,
                'user_id' => $employee->id,
                'occurrence_date' => '2026-09-15',
                'due_at' => '2026-09-15 16:30:00',
                'title' => 'Tugas rutin',
            ]);
        }
    }

    public function test_generator_is_idempotent_for_the_same_task_employee_and_date(): void
    {
        $task = Task::factory()->daily()->create([
            'starts_on' => '2026-09-01',
            'ends_on' => null,
        ]);
        $employee = User::factory()->create();
        $task->assignees()->attach($employee);
        $generator = app(GenerateTaskOccurrencesAction::class);

        $firstCreatedCount = $generator->execute('2026-09-15');
        $secondCreatedCount = $generator->execute('2026-09-15');

        $this->assertSame(1, $firstCreatedCount);
        $this->assertSame(0, $secondCreatedCount);
        $this->assertDatabaseCount('task_occurrences', 1);
    }

    public function test_generator_skips_inactive_and_out_of_range_daily_tasks(): void
    {
        $inactiveTask = Task::factory()->daily()->create([
            'starts_on' => '2026-09-01',
            'is_active' => false,
        ]);
        $futureTask = Task::factory()->daily()->create([
            'starts_on' => '2026-10-01',
        ]);
        $endedTask = Task::factory()->daily()->create([
            'starts_on' => '2026-08-01',
            'ends_on' => '2026-08-31',
        ]);
        $employee = User::factory()->create();
        $inactiveTask->assignees()->attach($employee);
        $futureTask->assignees()->attach($employee);
        $endedTask->assignees()->attach($employee);

        $createdCount = app(GenerateTaskOccurrencesAction::class)->execute('2026-09-15');

        $this->assertSame(0, $createdCount);
        $this->assertDatabaseCount('task_occurrences', 0);
    }
}
