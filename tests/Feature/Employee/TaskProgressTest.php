<?php

namespace Tests\Feature\Employee;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\Task;
use App\Models\TaskOccurrence;
use App\Models\User;
use App\TaskOccurrenceStatus;
use App\TaskPriority;
use App\TaskType;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TaskProgressTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_employee_dashboard_shows_only_occurrences_assigned_to_them(): void
    {
        [$business, $owner] = $this->createBusinessWithOwner();
        $employee = $this->createEmployee($business, 'Karyawan Utama');
        $otherEmployee = $this->createEmployee($business, 'Karyawan Lain');
        $task = Task::factory()->for($business)->for($owner, 'creator')->create();
        TaskOccurrence::factory()->for($task)->for($employee, 'assignee')->create([
            'title' => 'Tugas milik saya',
        ]);
        TaskOccurrence::factory()->for($task)->for($otherEmployee, 'assignee')->create([
            'title' => 'Tugas rahasia orang lain',
            'occurrence_date' => today()->addDay(),
        ]);

        $this->actingAs($employee)
            ->withSession(['active_employee_business_id' => $business->id])
            ->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSeeText('Tugas milik saya')
            ->assertSeeText('Mulai Kerjakan')
            ->assertDontSeeText('Tugas rahasia orang lain');
    }

    public function test_employee_starts_and_completes_their_occurrence_without_overwriting_start_time(): void
    {
        $this->travelTo('2026-09-01 09:00:00');
        [$business, $owner] = $this->createBusinessWithOwner();
        $employee = $this->createEmployee($business);
        $task = Task::factory()->for($business)->for($owner, 'creator')->create();
        $occurrence = TaskOccurrence::factory()->for($task)->for($employee, 'assignee')->create();

        $this->actingAs($employee)
            ->withSession(['active_employee_business_id' => $business->id])
            ->patch(route('employee.tasks.update', $occurrence), [
                'status' => TaskOccurrenceStatus::InProgress->value,
            ])
            ->assertRedirectToRoute('employee.dashboard')
            ->assertSessionHas('success', 'Progres tugas berhasil diperbarui.');

        $this->assertSame(TaskOccurrenceStatus::InProgress, $occurrence->fresh()->status);
        $this->assertSame('2026-09-01 09:00:00', $occurrence->fresh()->started_at->format('Y-m-d H:i:s'));
        $this->assertNull($occurrence->fresh()->completed_at);

        $this->travelTo('2026-09-01 10:00:00');

        $this->actingAs($employee)
            ->withSession(['active_employee_business_id' => $business->id])
            ->patch(route('employee.tasks.update', $occurrence), [
                'status' => TaskOccurrenceStatus::Completed->value,
                'employee_note' => 'Selesai tanpa selisih.',
            ])
            ->assertRedirectToRoute('employee.dashboard');

        $freshOccurrence = $occurrence->fresh();
        $this->assertSame(TaskOccurrenceStatus::Completed, $freshOccurrence->status);
        $this->assertSame('2026-09-01 09:00:00', $freshOccurrence->started_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-01 10:00:00', $freshOccurrence->completed_at->format('Y-m-d H:i:s'));
        $this->assertSame('Selesai tanpa selisih.', $freshOccurrence->employee_note);
    }

    public function test_employee_cannot_update_another_employees_occurrence(): void
    {
        [$business, $owner] = $this->createBusinessWithOwner();
        $employee = $this->createEmployee($business);
        $otherEmployee = $this->createEmployee($business);
        $task = Task::factory()->for($business)->for($owner, 'creator')->create();
        $occurrence = TaskOccurrence::factory()->for($task)->for($otherEmployee, 'assignee')->create();

        $this->actingAs($employee)
            ->withSession(['active_employee_business_id' => $business->id])
            ->patch(route('employee.tasks.update', $occurrence), [
                'status' => TaskOccurrenceStatus::Completed->value,
            ])
            ->assertNotFound();

        $this->assertSame(TaskOccurrenceStatus::Pending, $occurrence->fresh()->status);
    }

    public function test_employee_cannot_update_task_definition_even_when_they_own_another_business(): void
    {
        [$business, $owner] = $this->createBusinessWithOwner();
        $employee = $this->createEmployee($business);
        $employeeOwnedBusiness = Business::factory()->create();
        BusinessMembership::factory()->for($employee)->for($employeeOwnedBusiness)->create();
        $task = Task::factory()->for($business)->for($owner, 'creator')->create(['title' => 'Definisi terlindungi']);

        $this->actingAs($employee)
            ->withSession(['active_business_id' => $employeeOwnedBusiness->id])
            ->put(route('tasks.update', $task), [
                'title' => 'Diubah karyawan',
                'type' => TaskType::OneTime->value,
                'priority' => TaskPriority::Low->value,
                'starts_on' => today()->format('Y-m-d'),
                'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'is_active' => '1',
                'assignee_ids' => [$employee->id],
            ])
            ->assertNotFound();

        $this->assertSame('Definisi terlindungi', $task->fresh()->title);
    }

    public function test_overdue_occurrence_is_grouped_as_late_on_employee_dashboard(): void
    {
        $this->travelTo('2026-09-01 12:00:00');
        [$business, $owner] = $this->createBusinessWithOwner();
        $employee = $this->createEmployee($business);
        $task = Task::factory()->for($business)->for($owner, 'creator')->create();
        TaskOccurrence::factory()->for($task)->for($employee, 'assignee')->create([
            'title' => 'Tugas melewati tenggat',
            'occurrence_date' => '2026-09-01',
            'due_at' => '2026-09-01 10:00:00',
        ]);

        $this->actingAs($employee)
            ->withSession(['active_employee_business_id' => $business->id])
            ->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSeeText('Terlambat')
            ->assertSeeText('Tugas melewati tenggat');
    }

    public function test_invalid_completion_restores_note_only_on_the_submitted_task(): void
    {
        $this->travelTo('2026-09-01 09:00:00');
        [$business, $owner] = $this->createBusinessWithOwner();
        $employee = $this->createEmployee($business);
        $task = Task::factory()->for($business)->for($owner, 'creator')->create();
        $submittedOccurrence = TaskOccurrence::factory()->for($task)->for($employee, 'assignee')->create();
        $otherTask = Task::factory()->for($business)->for($owner, 'creator')->create();
        TaskOccurrence::factory()->for($otherTask)->for($employee, 'assignee')->create();
        $note = str_repeat('Catatan pekerjaan. ', 120);

        $this->actingAs($employee)
            ->from(route('employee.dashboard'))
            ->patch(route('employee.tasks.update', $submittedOccurrence), [
                'status' => TaskOccurrenceStatus::Completed->value,
                'employee_note' => $note,
                'task_occurrence_id' => $submittedOccurrence->id,
            ])
            ->assertSessionHasErrors('employee_note');

        $response = $this->get(route('employee.dashboard'));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), e(trim($note))));
        $this->assertSame(TaskOccurrenceStatus::Pending, $submittedOccurrence->fresh()->status);
        $this->assertNull($submittedOccurrence->fresh()->employee_note);
    }

    public function test_employee_gets_a_fresh_daily_task_after_midnight_and_can_complete_it_again(): void
    {
        $this->travelTo('2026-09-05 16:59:00');
        [$business, $owner] = $this->createBusinessWithOwner();
        $employee = $this->createEmployee($business);
        $otherEmployee = $this->createEmployee($business);
        $task = Task::factory()->daily()->for($business)->for($owner, 'creator')->create([
            'title' => 'Print resi harian', 'starts_on' => '2026-09-05', 'ends_on' => null,
        ]);
        $task->assignees()->attach([$employee->id, $otherEmployee->id]);
        $otherTask = Task::factory()->daily()->create(['starts_on' => '2026-09-05']);
        $otherTask->assignees()->attach($employee);
        $yesterday = TaskOccurrence::factory()->completed()->for($task)->for($employee, 'assignee')->create([
            'title' => 'Print resi harian', 'occurrence_date' => '2026-09-05',
            'due_at' => '2026-09-05 17:00:00', 'employee_note' => 'Catatan kemarin.',
        ]);
        $history = $yesterday->getAttributes();

        $this->travelTo('2026-09-05 17:00:00');
        $this->actingAs($employee)->withSession(['active_employee_business_id' => $business->id])
            ->get(route('employee.dashboard'))
            ->assertSeeText('Belum dikerjakan')
            ->assertSeeText('06 Sep 2026')
            ->assertViewHas('taskGroups', fn ($groups) => $groups['new']->count() === 1 && $groups['completed']->contains($yesterday));
        $today = $task->occurrences()->whereDate('occurrence_date', '2026-09-06')->sole();
        $this->assertDatabaseCount('task_occurrences', 2);
        $this->assertSame(TaskOccurrenceStatus::Pending, $today->status);
        $this->assertNull($today->employee_note);
        $this->assertDatabaseMissing('task_occurrences', ['task_id' => $otherTask->id]);
        $this->assertDatabaseMissing('task_occurrences', ['user_id' => $otherEmployee->id]);

        $this->get(route('employee.dashboard'))
            ->assertViewHas('taskGroups', fn ($groups) => $groups['today']->contains($today));
        $this->patch(route('employee.tasks.update', $today), [
            'status' => 'completed', 'employee_note' => 'Resi hari ini selesai.',
        ])->assertRedirectToRoute('employee.dashboard');
        $this->get(route('employee.dashboard'))->assertSeeText('Resi hari ini selesai.');

        $this->assertDatabaseCount('task_occurrences', 2);
        $this->assertDatabaseHas('task_occurrences', ['id' => $today->id, 'status' => 'completed', 'employee_note' => 'Resi hari ini selesai.']);
        $this->assertSame($history['completed_at'], $yesterday->fresh()->getAttributes()['completed_at']);
        $this->assertSame('Catatan kemarin.', $yesterday->fresh()->employee_note);
    }

    public function test_unfinished_yesterday_remains_overdue_alongside_todays_new_task(): void
    {
        $this->travelTo('2026-09-05 17:00:00');
        [$business, $owner] = $this->createBusinessWithOwner();
        $employee = $this->createEmployee($business);
        $task = Task::factory()->daily()->for($business)->for($owner, 'creator')->create(['starts_on' => '2026-09-05']);
        $task->assignees()->attach($employee);
        $yesterday = TaskOccurrence::factory()->inProgress()->for($task)->for($employee, 'assignee')->create([
            'occurrence_date' => '2026-09-05', 'due_at' => '2026-09-05 23:59:00',
        ]);

        $this->actingAs($employee)->withSession(['active_employee_business_id' => $business->id])
            ->get(route('employee.dashboard'))
            ->assertViewHas('taskGroups', fn ($groups) => $groups['overdue']->contains($yesterday) && $groups['new']->count() === 1);

        $this->assertSame(TaskOccurrenceStatus::InProgress, $yesterday->fresh()->status);
    }

    public function test_todays_daily_task_is_visible_even_with_more_than_one_hundred_old_occurrences(): void
    {
        $this->travelTo('2026-09-05 17:00:00');
        [$business, $owner] = $this->createBusinessWithOwner();
        $employee = $this->createEmployee($business);
        $task = Task::factory()->daily()->for($business)->for($owner, 'creator')->create(['starts_on' => '2026-01-01']);
        $task->assignees()->attach($employee);
        TaskOccurrence::factory()->count(101)->for($task)->for($employee, 'assignee')
            ->sequence(fn ($sequence) => [
                'occurrence_date' => today()->subDays($sequence->index + 1),
                'due_at' => today()->subDays($sequence->index + 1)->setTime(17, 0),
            ])->create();

        $this->actingAs($employee)->withSession(['active_employee_business_id' => $business->id])
            ->get(route('employee.dashboard'))
            ->assertViewHas('taskGroups', fn ($groups) => $groups['new']->count() === 1
                && $groups['new']->first()->occurrence_date->toDateString() === '2026-09-06');
    }

    /** @return array{Business, User} */
    private function createBusinessWithOwner(): array
    {
        $business = Business::factory()->create();
        $owner = User::factory()->create();
        BusinessMembership::factory()->for($owner)->for($business)->create();

        return [$business, $owner];
    }

    private function createEmployee(Business $business, string $name = 'Karyawan'): User
    {
        $employee = User::factory()->create(['name' => $name]);
        BusinessMembership::factory()->employee()->for($employee)->for($business)->create();

        return $employee;
    }
}
