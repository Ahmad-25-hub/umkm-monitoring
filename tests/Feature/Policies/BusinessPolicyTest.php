<?php

namespace Tests\Feature\Policies;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
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
