<?php

namespace Tests\Feature\Owner;

use App\Actions\GenerateTaskOccurrencesAction;
use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\SalesOrder;
use App\Models\Task;
use App\Models\TaskOccurrence;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EmployeeMembershipControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_deactivates_employee_without_deleting_history_or_other_business_access(): void
    {
        [$owner, $business, $membership] = $this->team();
        $employee = $membership->user;
        $otherMembership = BusinessMembership::factory()->employee()->for($employee)->create();
        $task = Task::factory()->for($business)->create();
        $task->assignees()->attach($employee);
        $occurrence = TaskOccurrence::factory()->completed()->for($task)->create(['user_id' => $employee->id]);
        $sale = SalesOrder::factory()->for($business)->create(['imported_by_user_id' => $employee->id]);
        $occurrenceBefore = $occurrence->fresh()->getAttributes();
        $saleBefore = $sale->fresh()->getAttributes();

        $this->actingAs($owner)->withSession(['active_business_id' => $business->id])
            ->patch(route('employees.update', $membership), ['status' => 'inactive'])
            ->assertRedirect(route('overview').'#team')
            ->assertSessionHas('success', 'Akses karyawan berhasil dinonaktifkan. Riwayat tugas dan penjualan tetap tersimpan.');

        $this->assertSame('inactive', $membership->fresh()->status);
        $this->assertSame('active', $otherMembership->fresh()->status);
        $this->assertSame($occurrenceBefore, $occurrence->fresh()->getAttributes());
        $this->assertSame($saleBefore, $sale->fresh()->getAttributes());
        $this->assertTrue($task->assignees()->whereKey($employee)->exists());
        $this->assertModelExists($employee);
    }

    public function test_owner_reactivates_employee_and_existing_session_can_access_dashboard_again(): void
    {
        [$owner, $business, $membership] = $this->team();
        $membership->update(['status' => 'inactive']);

        $this->actingAs($owner)->withSession(['active_business_id' => $business->id])
            ->patch(route('employees.update', $membership), ['status' => 'active'])
            ->assertRedirect(route('overview').'#team')
            ->assertSessionHas('success', 'Akses karyawan berhasil diaktifkan kembali.');

        $this->assertSame('active', $membership->fresh()->status);
        $this->actingAs($membership->user)
            ->withSession(['active_employee_business_id' => $business->id])
            ->get(route('employee.dashboard'))->assertOk();
    }

    public function test_guest_cannot_change_employee_access(): void
    {
        [, , $membership] = $this->team();

        $this->patch(route('employees.update', $membership), ['status' => 'inactive'])
            ->assertRedirectToRoute('login');

        $this->assertSame('active', $membership->fresh()->status);
    }

    public function test_employee_cannot_change_their_own_access(): void
    {
        [, $business, $membership] = $this->team();

        $this->actingAs($membership->user)->withSession(['active_business_id' => $business->id])
            ->patch(route('employees.update', $membership), ['status' => 'inactive'])
            ->assertRedirectToRoute('login');

        $this->assertSame('active', $membership->fresh()->status);
    }

    public function test_owner_cannot_modify_another_business_employee(): void
    {
        [$owner, $business] = $this->team();
        [, , $otherMembership] = $this->team();

        $this->actingAs($owner)->withSession(['active_business_id' => $business->id])
            ->patch(route('employees.update', $otherMembership), ['status' => 'inactive'])
            ->assertNotFound();

        $this->assertSame('active', $otherMembership->fresh()->status);
    }

    public function test_owner_must_select_the_employees_business_even_when_they_own_both(): void
    {
        [$owner, $business] = $this->team();
        [, $otherBusiness, $otherMembership] = $this->team();
        BusinessMembership::factory()->for($owner)->for($otherBusiness)->create();

        $this->actingAs($owner)->withSession(['active_business_id' => $business->id])
            ->patch(route('employees.update', $otherMembership), ['status' => 'inactive'])
            ->assertNotFound();

        $this->assertSame('active', $otherMembership->fresh()->status);
    }

    public function test_employee_endpoint_cannot_disable_an_owner_membership(): void
    {
        [$owner, $business] = $this->team();
        $ownerMembership = $owner->memberships()->whereBelongsTo($business)->sole();

        $this->actingAs($owner)->withSession(['active_business_id' => $business->id])
            ->patch(route('employees.update', $ownerMembership), ['status' => 'inactive'])
            ->assertNotFound();

        $this->assertSame('active', $ownerMembership->fresh()->status);
    }

    #[DataProvider('invalidStatuses')]
    public function test_invalid_status_does_not_change_access(mixed $status, string $message): void
    {
        [$owner, $business, $membership] = $this->team();

        $this->actingAs($owner)->withSession(['active_business_id' => $business->id])
            ->patch(route('employees.update', $membership), ['status' => $status])
            ->assertSessionHasErrors(['status' => $message]);

        $this->assertSame('active', $membership->fresh()->status);
    }

    /** @return array<string, array{mixed, string}> */
    public static function invalidStatuses(): array
    {
        return [
            'missing' => [null, 'Pilih status akses karyawan.'],
            'unknown' => ['owner', 'Status akses karyawan tidak valid.'],
            'array' => [['active'], 'Status akses karyawan tidak valid.'],
        ];
    }

    public function test_unexpected_fields_cannot_change_membership_identity_or_role(): void
    {
        [$owner, $business, $membership] = $this->team();
        $employeeId = $membership->user_id;
        $otherBusiness = Business::factory()->create();

        $this->actingAs($owner)->withSession(['active_business_id' => $business->id])
            ->patch(route('employees.update', $membership), [
                'status' => 'inactive',
                'role' => 'owner',
                'user_id' => $owner->id,
                'business_id' => $otherBusiness->id,
            ])->assertRedirect();

        $this->assertSame('inactive', $membership->fresh()->status);
        $this->assertSame('employee', $membership->role);
        $this->assertSame($employeeId, $membership->user_id);
        $this->assertSame($business->id, $membership->business_id);
    }

    public function test_revoked_existing_session_cannot_upload_or_change_tasks_and_is_sent_to_join_page(): void
    {
        [$owner, $business, $membership] = $this->team();
        $task = Task::factory()->for($business)->create();
        $occurrence = TaskOccurrence::factory()->for($task)->create(['user_id' => $membership->user_id]);

        $this->actingAs($owner)->withSession(['active_business_id' => $business->id])
            ->patch(route('employees.update', $membership), ['status' => 'inactive'])
            ->assertRedirect();

        $this->actingAs($membership->user)->withSession(['active_employee_business_id' => $business->id])
            ->post(route('employee.sales-imports.store'), [
                'sales_file' => UploadedFile::fake()->createWithContent('sales.csv', file_get_contents(base_path('tests/Fixtures/tiktok-sales.csv'))),
            ])->assertForbidden();
        $this->patch(route('employee.tasks.update', $occurrence), ['status' => 'completed'])
            ->assertForbidden();
        $this->get(route('employee.dashboard'))
            ->assertRedirectToRoute('employee.business.join.create')
            ->assertSessionMissing('active_employee_business_id')
            ->assertSessionHas('access_notice');

        $this->assertSame('pending', $occurrence->fresh()->status->value);
        $this->assertDatabaseCount('sales_orders', 0);
        $this->get(route('employee.business.join.create'))->assertSeeText('hubungi pemilik usaha');
    }

    public function test_stale_upload_is_not_silently_imported_into_another_active_business(): void
    {
        [, $business, $membership] = $this->team();
        $membership->update(['status' => 'inactive']);
        $otherMembership = BusinessMembership::factory()->employee()->for($membership->user)->create();

        $this->actingAs($membership->user)->withSession(['active_employee_business_id' => $business->id])
            ->post(route('employee.sales-imports.store'), [
                'sales_file' => UploadedFile::fake()->createWithContent('sales.csv', file_get_contents(base_path('tests/Fixtures/tiktok-sales.csv'))),
            ])->assertForbidden();

        $this->assertDatabaseCount('sales_orders', 0);
        $this->get(route('employee.dashboard'))->assertOk()
            ->assertSessionHas('active_employee_business_id', $otherMembership->business_id);
    }

    public function test_team_controls_render_for_active_and_inactive_employees_and_escape_names(): void
    {
        [$owner, $business, $membership] = $this->team();
        $membership->user->update(['name' => '<script>alert(1)</script>']);
        $inactive = BusinessMembership::factory()->employee()->inactive()->for($business)->create();

        $this->actingAs($owner)->withSession(['active_business_id' => $business->id])
            ->get(route('overview'))
            ->assertOk()
            ->assertSeeText('Nonaktifkan')
            ->assertSeeText('Aktifkan kembali')
            ->assertSee(route('employees.update', $membership))
            ->assertSee(route('employees.update', $inactive))
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_daily_tasks_pause_for_inactive_employee_and_resume_without_resetting_history(): void
    {
        [$owner, $business, $membership] = $this->team();
        $this->travelTo('2026-09-10 01:00:00');
        $task = Task::factory()->daily()->for($business)->create(['starts_on' => '2026-09-09', 'ends_on' => null]);
        $task->assignees()->attach($membership->user);
        $generator = app(GenerateTaskOccurrencesAction::class);
        $this->assertSame(1, $generator->execute('2026-09-09'));
        $history = $task->occurrences()->sole()->getAttributes();

        $this->actingAs($owner)->withSession(['active_business_id' => $business->id])
            ->patch(route('employees.update', $membership), ['status' => 'inactive'])
            ->assertRedirect();
        $this->assertSame(0, $generator->execute('2026-09-10'));
        $this->assertSame($history, $task->occurrences()->sole()->getAttributes());

        $this->patch(route('employees.update', $membership), ['status' => 'active'])->assertRedirect();
        $this->assertSame(1, $generator->execute('2026-09-10'));
        $this->assertSame(0, $generator->execute('2026-09-10'));
        $this->assertSame($history, $task->occurrences()->where('occurrence_date', '2026-09-09')->sole()->getAttributes());
    }

    /** @return array{User, Business, BusinessMembership} */
    private function team(): array
    {
        $owner = User::factory()->create();
        $business = Business::factory()->create();
        BusinessMembership::factory()->for($owner)->for($business)->create();
        $membership = BusinessMembership::factory()->employee()->for($business)->create();

        return [$owner, $business, $membership];
    }
}
