<?php

namespace Tests\Feature\Employee;

use App\Models\Business;
use App\Models\BusinessInvitationCode;
use App\Models\BusinessMembership;
use App\Models\User;
use App\Support\InvitationCodeGenerator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class JoinBusinessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_employee_login_from_business_code_step(): void
    {
        $this->get(route('employee.business.join.create'))
            ->assertRedirectToRoute('employee.login');

        $this->post(route('employee.business.join.store'))
            ->assertRedirectToRoute('employee.login');
    }

    public function test_authenticated_employee_can_open_business_code_step(): void
    {
        $employee = User::factory()->create(['name' => 'Karyawan Baru']);

        $this->actingAs($employee)
            ->get(route('employee.business.join.create'))
            ->assertOk()
            ->assertSeeText('Masukkan Kode Usaha')
            ->assertSeeText('Karyawan Baru');
    }

    public function test_valid_business_code_creates_active_employee_membership(): void
    {
        $employee = User::factory()->create();
        $business = Business::factory()->create(['name' => 'Kedai Tenant Tepat']);
        $invitation = $this->createInvitation($business, 'ABCD-EFGH-2345');

        $response = $this->actingAs($employee)->post(route('employee.business.join.store'), [
            'business_code' => ' abcd efgh 2345 ',
        ]);

        $response
            ->assertRedirectToRoute('employee.dashboard')
            ->assertSessionHas('active_employee_business_id', $business->id)
            ->assertSessionHas('success', 'Anda berhasil bergabung ke usaha.');
        $this->assertDatabaseHas('business_memberships', [
            'business_id' => $business->id,
            'user_id' => $employee->id,
            'role' => BusinessMembership::ROLE_EMPLOYEE,
            'status' => BusinessMembership::STATUS_ACTIVE,
        ]);
        $this->assertSame(1, $invitation->fresh()->uses_count);
    }

    public function test_unknown_business_code_is_rejected_without_creating_membership(): void
    {
        $employee = User::factory()->create();

        $response = $this->actingAs($employee)->post(route('employee.business.join.store'), [
            'business_code' => 'XXXX-YYYY-9999',
        ]);

        $response->assertSessionHasErrors([
            'business_code' => 'Kode Usaha tidak valid atau sudah tidak berlaku.',
        ]);
        $this->assertSame(0, BusinessMembership::query()->count());
    }

    /**
     * @param  array<string, mixed>  $invitationState
     */
    #[DataProvider('unavailableInvitationStates')]
    public function test_unavailable_business_code_is_rejected(array $invitationState): void
    {
        $employee = User::factory()->create();
        $business = Business::factory()->create();
        $this->createInvitation($business, 'LOCK-EDCO-DE99', $invitationState);

        $response = $this->actingAs($employee)->post(route('employee.business.join.store'), [
            'business_code' => 'LOCK-EDCO-DE99',
        ]);

        $response->assertSessionHasErrors([
            'business_code' => 'Kode Usaha tidak valid atau sudah tidak berlaku.',
        ]);
        $this->assertDatabaseMissing('business_memberships', [
            'business_id' => $business->id,
            'user_id' => $employee->id,
        ]);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function unavailableInvitationStates(): array
    {
        return [
            'expired' => [['expires_at' => now()->subMinute()]],
            'revoked' => [['revoked_at' => now()]],
            'exhausted' => [['max_uses' => 2, 'uses_count' => 2]],
        ];
    }

    public function test_existing_owner_membership_is_never_replaced_by_employee_role(): void
    {
        $owner = User::factory()->create();
        $business = Business::factory()->create();
        $membership = BusinessMembership::factory()
            ->for($owner)
            ->for($business)
            ->create();
        $this->createInvitation($business, 'OWNE-RCOD-E123');

        $response = $this->actingAs($owner)->post(route('employee.business.join.store'), [
            'business_code' => 'OWNE-RCOD-E123',
        ]);

        $response->assertSessionHasErrors('business_code');
        $this->assertSame(BusinessMembership::ROLE_OWNER, $membership->fresh()->role);
    }

    public function test_valid_code_cannot_reactivate_an_inactive_employee(): void
    {
        $employee = User::factory()->create();
        $business = Business::factory()->create();
        $membership = BusinessMembership::factory()
            ->employee()
            ->inactive()
            ->for($employee)
            ->for($business)
            ->create();
        $invitation = $this->createInvitation($business, 'REAC-TIVA-TE12', ['uses_count' => 1]);

        $response = $this->actingAs($employee)->post(route('employee.business.join.store'), [
            'business_code' => 'REAC-TIVA-TE12',
        ]);

        $response->assertSessionHasErrors([
            'business_code' => 'Akses Anda ke usaha ini telah dinonaktifkan. Hubungi pemilik usaha untuk mengaktifkannya kembali.',
        ]);
        $this->assertSame(BusinessMembership::STATUS_INACTIVE, $membership->fresh()->status);
        $this->assertSame(1, $invitation->fresh()->uses_count);
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function createInvitation(Business $business, string $rawCode, array $state = []): BusinessInvitationCode
    {
        return BusinessInvitationCode::factory()
            ->for($business)
            ->create(array_merge([
                'code_hash' => app(InvitationCodeGenerator::class)->hash($rawCode),
            ], $state));
    }
}
