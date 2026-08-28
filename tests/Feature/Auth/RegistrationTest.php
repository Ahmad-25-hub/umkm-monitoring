<?php

namespace Tests\Feature\Auth;

use App\Models\Business;
use App\Models\BusinessInvitationCode;
use App\Models\BusinessMembership;
use App\Models\User;
use App\Support\InvitationCodeGenerator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use RuntimeException;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_registration_creates_the_account_business_membership_and_invitation_code(): void
    {
        $response = $this->post(route('register.store'), [
            'owner_name' => '  Siti Rahma  ',
            'business_name' => '  Toko Sejahtera  ',
            'email' => ' SITI@EXAMPLE.COM ',
            'password' => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
        ]);

        $response
            ->assertRedirectToRoute('overview')
            ->assertSessionHas('invitation_code');

        $user = User::query()->where('email', 'siti@example.com')->firstOrFail();
        $business = Business::query()->where('name', 'Toko Sejahtera')->firstOrFail();
        $rawInvitationCode = session('invitation_code');
        $storedInvitation = BusinessInvitationCode::query()->sole();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('Siti Rahma', $user->name);
        $this->assertDatabaseHas('business_memberships', [
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => BusinessMembership::ROLE_OWNER,
            'status' => BusinessMembership::STATUS_ACTIVE,
        ]);
        $this->assertSame(
            app(InvitationCodeGenerator::class)->hash($rawInvitationCode),
            $storedInvitation->code_hash,
        );
        $this->assertNotSame($rawInvitationCode, $storedInvitation->code_hash);
        $this->assertDatabaseMissing('business_invitation_codes', [
            'code_hash' => $rawInvitationCode,
        ]);

        $this->get(route('overview'))
            ->assertOk()
            ->assertSeeText($rawInvitationCode)
            ->assertSeeText('Salin sekarang');
    }

    public function test_owner_registration_is_atomic_when_invitation_generation_fails(): void
    {
        Exceptions::fake();
        $this->mock(InvitationCodeGenerator::class)
            ->shouldReceive('generateUnique')
            ->once()
            ->andThrow(new RuntimeException('Generator gagal.'));

        $response = $this->post(route('register.store'), $this->validPayload());

        $response->assertInternalServerError();
        Exceptions::assertReported(RuntimeException::class);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('businesses', 0);
        $this->assertDatabaseCount('business_memberships', 0);
        $this->assertDatabaseCount('business_invitation_codes', 0);
        $this->assertGuest();
    }

    public function test_duplicate_email_is_rejected_after_normalization(): void
    {
        User::factory()->create(['email' => 'pemilik@example.com']);

        $response = $this->post(route('register.store'), [
            ...$this->validPayload(),
            'email' => ' PEMILIK@EXAMPLE.COM ',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'Email sudah terdaftar.',
        ]);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('businesses', 0);
    }

    public function test_password_confirmation_is_required_to_match(): void
    {
        $response = $this->post(route('register.store'), [
            ...$this->validPayload(),
            'password_confirmation' => 'TidakSama123',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'Konfirmasi password tidak sesuai.',
        ]);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('businesses', 0);
    }

    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'owner_name' => 'Ahmad Pemilik',
            'business_name' => 'Usaha Mandiri',
            'email' => 'ahmad@example.com',
            'password' => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
        ];
    }
}
