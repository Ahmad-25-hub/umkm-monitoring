<?php

namespace Tests\Feature\Owner;

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

class TaskManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_can_open_task_list_create_and_edit_pages_for_active_business(): void
    {
        [$owner, $business] = $this->createOwner();
        $employee = $this->createEmployee($business, 'Karyawan Form');
        $task = Task::factory()->for($business)->for($owner, 'creator')->create([
            'title' => 'Tugas untuk diedit',
        ]);
        $task->assignees()->attach($employee);

        $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSeeText('Manajemen tugas')
            ->assertSeeText('Tugas untuk diedit');

        $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->get(route('tasks.create'))
            ->assertOk()
            ->assertSeeText('Buat tugas baru')
            ->assertSeeText('Karyawan Form');

        $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->get(route('tasks.edit', $task))
            ->assertOk()
            ->assertSeeText('Edit tugas')
            ->assertSee('Tugas untuk diedit');
    }

    public function test_owner_creates_one_time_task_and_occurrence_for_each_selected_employee(): void
    {
        $this->travelTo('2026-09-01 09:00:00');
        [$owner, $business] = $this->createOwner();
        $firstEmployee = $this->createEmployee($business, 'Karyawan Pertama');
        $secondEmployee = $this->createEmployee($business, 'Karyawan Kedua');

        $response = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->post(route('tasks.store'), [
                'title' => 'Hitung stok penutup',
                'description' => 'Catat selisih stok di etalase.',
                'type' => TaskType::OneTime->value,
                'priority' => TaskPriority::High->value,
                'starts_on' => '2026-09-01',
                'due_at' => '2026-09-01 18:00:00',
                'is_active' => '1',
                'assignee_ids' => [$firstEmployee->id, $secondEmployee->id],
            ]);

        $response
            ->assertRedirectToRoute('tasks.index')
            ->assertSessionHas('success', 'Tugas berhasil dibuat.');
        $this->assertDatabaseHas('tasks', [
            'business_id' => $business->id,
            'created_by_user_id' => $owner->id,
            'title' => 'Hitung stok penutup',
            'type' => TaskType::OneTime->value,
        ]);
        $this->assertDatabaseCount('task_assignees', 2);
        $this->assertDatabaseCount('task_occurrences', 2);
        $this->assertDatabaseHas('task_occurrences', [
            'user_id' => $firstEmployee->id,
            'title' => 'Hitung stok penutup',
            'status' => TaskOccurrenceStatus::Pending->value,
        ]);
        $this->assertDatabaseHas('task_occurrences', ['user_id' => $secondEmployee->id]);
    }

    public function test_owner_creates_daily_task_and_today_occurrence_immediately(): void
    {
        $this->travelTo('2026-09-01 09:00:00');
        [$owner, $business] = $this->createOwner();
        $employee = $this->createEmployee($business);

        $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->post(route('tasks.store'), [
                'title' => 'Bersihkan area kasir',
                'type' => TaskType::Daily->value,
                'priority' => TaskPriority::Normal->value,
                'starts_on' => '2026-09-01',
                'daily_due_time' => '17:30',
                'is_active' => '1',
                'assignee_ids' => [$employee->id],
            ])
            ->assertRedirectToRoute('tasks.index');

        $this->assertDatabaseHas('tasks', [
            'business_id' => $business->id,
            'type' => TaskType::Daily->value,
            'daily_due_time' => '17:30',
            'ends_on' => null,
        ]);
        $this->assertDatabaseHas('task_occurrences', [
            'user_id' => $employee->id,
            'occurrence_date' => '2026-09-01',
            'due_at' => '2026-09-01 17:30:00',
        ]);
    }

    public function test_owner_cannot_assign_task_to_employee_from_another_business(): void
    {
        [$owner, $business] = $this->createOwner();
        $otherBusiness = Business::factory()->create();
        $otherEmployee = $this->createEmployee($otherBusiness);

        $response = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->post(route('tasks.store'), [
                'title' => 'Tugas lintas tenant',
                'type' => TaskType::OneTime->value,
                'priority' => TaskPriority::Normal->value,
                'starts_on' => today()->format('Y-m-d'),
                'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'is_active' => '1',
                'assignee_ids' => [$otherEmployee->id],
            ]);

        $response->assertSessionHasErrors('assignee_ids.0');
        $this->assertDatabaseCount('tasks', 0);
        $this->assertDatabaseCount('task_occurrences', 0);
    }

    public function test_owner_cannot_view_or_update_task_from_another_business(): void
    {
        [$owner, $ownedBusiness] = $this->createOwner();
        [$otherOwner, $otherBusiness] = $this->createOwner();
        $task = Task::factory()->for($otherBusiness)->for($otherOwner, 'creator')->create();

        $this->actingAs($owner)
            ->withSession(['active_business_id' => $ownedBusiness->id])
            ->get(route('tasks.edit', $task))
            ->assertNotFound();

        $this->actingAs($owner)
            ->withSession(['active_business_id' => $ownedBusiness->id])
            ->put(route('tasks.update', $task), [
                'title' => 'Tidak boleh berubah',
                'type' => TaskType::OneTime->value,
                'priority' => TaskPriority::Normal->value,
                'starts_on' => today()->format('Y-m-d'),
                'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'is_active' => '1',
                'assignee_ids' => [],
            ])
            ->assertNotFound();

        $this->assertNotSame('Tidak boleh berubah', $task->fresh()->title);
    }

    public function test_disabling_daily_task_preserves_completed_snapshot_and_cancels_pending_work(): void
    {
        $this->travelTo('2026-09-01 09:00:00');
        [$owner, $business] = $this->createOwner();
        $employee = $this->createEmployee($business);
        $task = Task::factory()
            ->daily()
            ->for($business)
            ->for($owner, 'creator')
            ->create(['title' => 'Judul lama']);
        $task->assignees()->attach($employee);
        $completed = TaskOccurrence::factory()->completed()->for($task)->for($employee, 'assignee')->create([
            'occurrence_date' => '2026-08-31',
            'title' => 'Snapshot selesai',
            'due_at' => '2026-08-31 17:00:00',
        ]);
        $pending = TaskOccurrence::factory()->for($task)->for($employee, 'assignee')->create([
            'occurrence_date' => '2026-09-01',
            'title' => 'Snapshot pending',
            'due_at' => '2026-09-01 17:00:00',
        ]);

        $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->put(route('tasks.update', $task), [
                'title' => 'Judul baru',
                'type' => TaskType::Daily->value,
                'priority' => TaskPriority::High->value,
                'starts_on' => '2026-09-01',
                'daily_due_time' => '18:00',
                'is_active' => '0',
                'assignee_ids' => [$employee->id],
            ])
            ->assertRedirectToRoute('tasks.index');

        $this->assertSame('Snapshot selesai', $completed->fresh()->title);
        $this->assertSame(TaskOccurrenceStatus::Completed, $completed->fresh()->status);
        $this->assertSame('Snapshot pending', $pending->fresh()->title);
        $this->assertSame(TaskOccurrenceStatus::Cancelled, $pending->fresh()->status);
        $this->assertFalse($task->fresh()->is_active);
    }

    public function test_monitoring_page_shows_overdue_occurrence_from_active_business_only(): void
    {
        $this->travelTo('2026-09-01 12:00:00');
        [$owner, $business] = $this->createOwner();
        $employee = $this->createEmployee($business, 'Karyawan Terlambat');
        $task = Task::factory()->for($business)->for($owner, 'creator')->create();
        TaskOccurrence::factory()->for($task)->for($employee, 'assignee')->create([
            'title' => 'Laporan terlambat',
            'due_at' => '2026-09-01 10:00:00',
        ]);
        $otherTask = Task::factory()->create();
        TaskOccurrence::factory()->for($otherTask)->create(['title' => 'Rahasia tenant lain']);

        $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->get(route('task-occurrences.index', ['status' => 'overdue']))
            ->assertOk()
            ->assertSeeText('Laporan terlambat')
            ->assertSeeText('Karyawan Terlambat')
            ->assertSeeText('Terlambat')
            ->assertDontSeeText('Rahasia tenant lain');
    }

    /** @return array{User, Business} */
    private function createOwner(): array
    {
        $owner = User::factory()->create();
        $business = Business::factory()->create();
        BusinessMembership::factory()->for($owner)->for($business)->create();

        return [$owner, $business];
    }

    private function createEmployee(Business $business, string $name = 'Karyawan'): User
    {
        $employee = User::factory()->create(['name' => $name]);
        BusinessMembership::factory()->employee()->for($employee)->for($business)->create();

        return $employee;
    }
}
