<?php

namespace Tests\Feature;

use App\Actions\GenerateTaskOccurrencesAction;
use App\Models\Business;
use App\Models\Task;
use App\Models\TaskOccurrence;
use App\Models\User;
use App\TaskOccurrenceStatus;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class GenerateDailyTaskOccurrencesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_midnight_in_jakarta_creates_a_new_pending_day_without_resetting_history(): void
    {
        $this->travelTo('2026-09-05 16:59:59');
        $task = Task::factory()->daily()->create(['starts_on' => '2026-09-05', 'ends_on' => null]);
        $employee = User::factory()->create();
        $task->assignees()->attach($employee);
        $generator = app(GenerateTaskOccurrencesAction::class);
        $generator->execute();
        $yesterday = $task->occurrences()->sole();
        $yesterday->update([
            'status' => TaskOccurrenceStatus::Completed,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'viewed_at' => now()->subHours(2),
            'employee_note' => 'Semua resi kemarin sudah dicetak.',
        ]);
        $history = $yesterday->fresh()->getAttributes();
        $this->assertSame(0, $generator->execute());

        $this->travelTo('2026-09-05 17:00:00');
        $this->artisan('tasks:generate-daily-occurrences')->assertSuccessful();
        $this->artisan('tasks:generate-daily-occurrences')->assertSuccessful();

        $this->assertSame($history, $yesterday->fresh()->getAttributes());
        $this->assertDatabaseCount('task_occurrences', 2);
        $this->assertDatabaseHas('task_occurrences', [
            'task_id' => $task->id,
            'user_id' => $employee->id,
            'occurrence_date' => '2026-09-06',
            'due_at' => '2026-09-06 17:00:00',
            'status' => 'pending',
            'started_at' => null,
            'completed_at' => null,
            'employee_note' => null,
            'viewed_at' => null,
        ]);
    }

    public function test_daily_schedule_is_due_at_midnight_jakarta_only(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command, 'tasks:generate-daily-occurrences'));

        $this->travelTo('2026-09-05 16:59:00');
        $this->assertFalse($event->isDue($this->app));
        $this->travelTo('2026-09-05 17:00:00');
        $this->assertTrue($event->isDue($this->app));
        $this->travelTo('2026-09-05 17:01:00');
        $this->assertFalse($event->isDue($this->app));
    }

    public function test_daily_date_range_includes_first_and_last_local_day_and_stops_afterwards(): void
    {
        $task = Task::factory()->daily()->create(['starts_on' => '2026-09-06', 'ends_on' => '2026-09-06']);
        $task->assignees()->attach(User::factory()->create());
        $generator = app(GenerateTaskOccurrencesAction::class);

        $this->travelTo('2026-09-05 16:59:59');
        $this->assertSame(0, $generator->execute());
        $this->travelTo('2026-09-05 17:00:00');
        $this->assertSame(1, $generator->execute(now()));
        $this->travelTo('2026-09-06 17:00:00');
        $this->assertSame(0, $generator->execute());
        $this->assertDatabaseCount('task_occurrences', 1);
    }

    public function test_overdue_query_and_badge_use_the_entered_jakarta_deadline(): void
    {
        $occurrence = TaskOccurrence::factory()->create(['due_at' => '2026-09-06 00:30:00']);
        $this->travelTo('2026-09-05 17:29:59');
        $this->assertFalse($occurrence->isOverdue());
        $this->assertFalse(TaskOccurrence::query()->overdue()->exists());

        $this->travelTo('2026-09-05 17:30:01');
        $this->assertTrue($occurrence->isOverdue());
        $this->assertTrue(TaskOccurrence::query()->overdue()->whereKey($occurrence->id)->exists());
    }

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
