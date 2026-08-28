<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessInvitationCode;
use App\Models\BusinessMembership;
use App\Models\User;
use App\Support\InvitationCodeGenerator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BusinessInvitationCodeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_can_rotate_the_invitation_code_and_the_old_code_becomes_invalid(): void
    {
        [$owner, $business] = $this->createOwner();
        $generator = app(InvitationCodeGenerator::class);
        $oldHash = $generator->hash('ABCD-EFGH-JKLM');
        BusinessInvitationCode::factory()
            ->for($business)
            ->for($owner, 'creator')
            ->create(['code_hash' => $oldHash]);

        $response = $this->actingAs($owner)
            ->post(route('businesses.invitation-code.store', $business));

        $response
            ->assertRedirectToRoute('overview')
            ->assertSessionHas('invitation_code');

        $newPlainCode = session('invitation_code');

        $this->assertDatabaseCount('business_invitation_codes', 1);
        $this->assertDatabaseMissing('business_invitation_codes', ['code_hash' => $oldHash]);
        $this->assertDatabaseHas('business_invitation_codes', [
            'business_id' => $business->id,
            'created_by_user_id' => $owner->id,
            'code_hash' => $generator->hash($newPlainCode),
            'uses_count' => 0,
            'revoked_at' => null,
        ]);
        $this->assertDatabaseMissing('business_invitation_codes', ['code_hash' => $newPlainCode]);
    }

    public function test_user_cannot_rotate_another_business_invitation_code(): void
    {
        [$owner, $ownedBusiness] = $this->createOwner();
        [$otherOwner, $otherBusiness] = $this->createOwner();
        $generator = app(InvitationCodeGenerator::class);
        $oldHash = $generator->hash('WXYZ-6789-ABCD');
        BusinessInvitationCode::factory()
            ->for($otherBusiness)
            ->for($otherOwner, 'creator')
            ->create(['code_hash' => $oldHash]);

        $response = $this->actingAs($owner)
            ->post(route('businesses.invitation-code.store', $otherBusiness));

        $response->assertNotFound();
        $this->assertDatabaseHas('business_invitation_codes', [
            'business_id' => $otherBusiness->id,
            'code_hash' => $oldHash,
        ]);
        $this->assertDatabaseMissing('business_invitation_codes', [
            'business_id' => $ownedBusiness->id,
        ]);
    }

    /**
     * @return array{User, Business}
     */
    private function createOwner(): array
    {
        $owner = User::factory()->create();
        $business = Business::factory()->create();
        BusinessMembership::factory()
            ->for($owner)
            ->for($business)
            ->create();

        return [$owner, $business];
    }
}
