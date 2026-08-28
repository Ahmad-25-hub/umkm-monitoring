<?php

namespace Tests\Feature\Auth;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_can_open_login_and_registration_pages(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSeeText('Masuk ke akun pemilik');

        $this->get(route('register'))
            ->assertOk()
            ->assertSeeText('Daftarkan usaha Anda');
    }

    public function test_guest_is_redirected_from_owner_dashboard(): void
    {
        $this->get(route('overview'))
            ->assertRedirectToRoute('login');
    }

    public function test_owner_can_login_with_valid_credentials(): void
    {
        $owner = $this->createOwner(['email' => 'owner@example.com']);

        $response = $this->post(route('login.store'), [
            'email' => ' OWNER@EXAMPLE.COM ',
            'password' => 'password',
            'remember' => true,
        ]);

        $response->assertRedirectToRoute('overview');
        $this->assertAuthenticatedAs($owner);
    }

    public function test_invalid_credentials_are_rejected_with_a_safe_message(): void
    {
        $this->createOwner(['email' => 'owner@example.com']);

        $response = $this->post(route('login.store'), [
            'email' => 'owner@example.com',
            'password' => 'password-yang-salah',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'Email atau password tidak sesuai.',
        ]);
        $this->assertGuest();
    }

    public function test_inactive_owner_membership_is_rejected_with_the_same_safe_message(): void
    {
        $owner = User::factory()->create(['email' => 'inactive@example.com']);
        $business = Business::factory()->create();
        BusinessMembership::factory()
            ->for($owner)
            ->for($business)
            ->inactive()
            ->create();

        $response = $this->post(route('login.store'), [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'Email atau password tidak sesuai.',
        ]);
        $this->assertGuest();
    }

    public function test_employee_membership_cannot_enter_the_owner_dashboard(): void
    {
        $employee = User::factory()->create(['email' => 'employee@example.com']);
        $business = Business::factory()->create();
        BusinessMembership::factory()
            ->for($employee)
            ->for($business)
            ->employee()
            ->create();

        $response = $this->post(route('login.store'), [
            'email' => 'employee@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        $this->createOwner(['email' => 'limited@example.com']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), [
                'email' => 'limited@example.com',
                'password' => 'salah',
            ])->assertSessionHasErrors('email');
        }

        $response = $this->post(route('login.store'), [
            'email' => 'limited@example.com',
            'password' => 'salah',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Terlalu banyak percobaan login.',
            session('errors')->first('email'),
        );
    }

    public function test_authenticated_owner_is_redirected_away_from_guest_pages(): void
    {
        $owner = $this->createOwner();

        $this->actingAs($owner)->get(route('login'))
            ->assertRedirectToRoute('overview');

        $this->actingAs($owner)->get(route('register'))
            ->assertRedirectToRoute('overview');
    }

    public function test_owner_can_logout_and_the_session_is_invalidated(): void
    {
        $owner = $this->createOwner();

        $response = $this->actingAs($owner)
            ->withSession(['_token' => 'token-lama', 'penanda' => 'rahasia'])
            ->post(route('logout'));

        $response
            ->assertRedirectToRoute('login')
            ->assertSessionMissing('penanda')
            ->assertSessionHas('_token', fn (string $token): bool => $token !== 'token-lama');
        $this->assertGuest();
        $this->get('/logout')->assertMethodNotAllowed();
    }

    public function test_owner_dashboard_lists_only_businesses_owned_by_the_user(): void
    {
        $owner = $this->createOwner();
        $ownedBusiness = $owner->businesses()->firstOrFail();
        $ownedBusiness->update(['name' => 'Usaha Milik Saya Unik']);

        $employeeBusiness = Business::factory()->create(['name' => 'Usaha Tempat Bekerja Unik']);
        BusinessMembership::factory()
            ->for($owner)
            ->for($employeeBusiness)
            ->employee()
            ->create();

        $otherBusiness = Business::factory()->create(['name' => 'Usaha Orang Lain Unik']);
        BusinessMembership::factory()
            ->for(User::factory()->create())
            ->for($otherBusiness)
            ->create();

        $response = $this->actingAs($owner)->get(route('overview'));

        $response
            ->assertOk()
            ->assertSeeText('Usaha Milik Saya Unik')
            ->assertDontSeeText('Usaha Tempat Bekerja Unik')
            ->assertDontSeeText('Usaha Orang Lain Unik');
    }

    public function test_owner_can_switch_to_another_owned_active_business(): void
    {
        $owner = $this->createOwner();
        $secondBusiness = Business::factory()->create(['name' => 'Usaha Aktif Kedua']);
        BusinessMembership::factory()
            ->for($owner)
            ->for($secondBusiness)
            ->create();

        $response = $this->actingAs($owner)
            ->post(route('businesses.activate', $secondBusiness));

        $response
            ->assertRedirectToRoute('overview')
            ->assertSessionHas('active_business_id', $secondBusiness->id);

        $this->get(route('overview'))
            ->assertOk()
            ->assertSeeText('Usaha Aktif Kedua');
    }

    public function test_owner_cannot_activate_a_business_they_do_not_own(): void
    {
        $owner = $this->createOwner();
        $otherBusiness = Business::factory()->create();
        BusinessMembership::factory()
            ->for(User::factory()->create())
            ->for($otherBusiness)
            ->create();

        $response = $this->actingAs($owner)
            ->post(route('businesses.activate', $otherBusiness));

        $response->assertNotFound();
        $response->assertSessionMissing('active_business_id');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createOwner(array $attributes = []): User
    {
        $owner = User::factory()->create($attributes);
        $business = Business::factory()->create();

        BusinessMembership::factory()
            ->for($owner)
            ->for($business)
            ->create();

        return $owner;
    }
}
