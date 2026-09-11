<?php

namespace Tests\Feature\Policies;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BusinessPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_active_owner_can_rotate_the_business_invitation_code(): void
    {
        [$owner, $business] = $this->createMembership();

        $this->assertTrue(
            Gate::forUser($owner)->allows('rotateInvitationCode', $business),
        );
        $this->assertTrue(
            Gate::forUser($owner)->allows('activateForOwner', $business),
        );
    }

    public function test_employee_cannot_rotate_the_business_invitation_code(): void
    {
        [$employee, $business] = $this->createMembership(
            role: BusinessMembership::ROLE_EMPLOYEE,
        );

        $this->assertFalse(
            Gate::forUser($employee)->allows('rotateInvitationCode', $business),
        );
        $this->assertFalse(
            Gate::forUser($employee)->allows('activateForOwner', $business),
        );
    }

    public function test_inactive_owner_cannot_rotate_the_business_invitation_code(): void
    {
        [$owner, $business] = $this->createMembership(
            status: BusinessMembership::STATUS_INACTIVE,
        );

        $this->assertFalse(
            Gate::forUser($owner)->allows('rotateInvitationCode', $business),
        );
        $this->assertFalse(
            Gate::forUser($owner)->allows('activateForOwner', $business),
        );
    }

    public function test_unrelated_user_cannot_view_or_rotate_the_business_invitation_code(): void
    {
        [, $business] = $this->createMembership();
        $unrelatedUser = User::factory()->create();

        $this->assertFalse(Gate::forUser($unrelatedUser)->allows('view', $business));
        $this->assertFalse(Gate::forUser($unrelatedUser)->allows('rotateInvitationCode', $business));
        $this->assertFalse(Gate::forUser($unrelatedUser)->allows('activateForOwner', $business));
    }

    #[DataProvider('employeeManagementRoles')]
    public function test_employee_management_requires_an_active_owner(string $role, string $status, bool $allowed): void
    {
        [$user, $business] = $this->createMembership(role: $role, status: $status);

        $response = Gate::forUser($user)->inspect('manageEmployees', $business);

        $this->assertSame($allowed, $response->allowed());
        if (! $allowed) {
            $this->assertSame(404, $response->status());
        }
    }

    public function test_unrelated_user_cannot_manage_employees(): void
    {
        [, $business] = $this->createMembership();
        $user = User::factory()->create();

        $response = Gate::forUser($user)->inspect('manageEmployees', $business);

        $this->assertFalse($response->allowed());
        $this->assertSame(404, $response->status());
    }

    /** @return array<string, array{string, string, bool}> */
    public static function employeeManagementRoles(): array
    {
        return [
            'active owner' => [BusinessMembership::ROLE_OWNER, BusinessMembership::STATUS_ACTIVE, true],
            'inactive owner' => [BusinessMembership::ROLE_OWNER, BusinessMembership::STATUS_INACTIVE, false],
            'active employee' => [BusinessMembership::ROLE_EMPLOYEE, BusinessMembership::STATUS_ACTIVE, false],
            'inactive employee' => [BusinessMembership::ROLE_EMPLOYEE, BusinessMembership::STATUS_INACTIVE, false],
        ];
    }

    /**
     * @return array{User, Business}
     */
    private function createMembership(
        string $role = BusinessMembership::ROLE_OWNER,
        string $status = BusinessMembership::STATUS_ACTIVE,
    ): array {
        $user = User::factory()->create();
        $business = Business::factory()->create();
        BusinessMembership::factory()
            ->for($user)
            ->for($business)
            ->create([
                'role' => $role,
                'status' => $status,
            ]);

        return [$user, $business];
    }
}
