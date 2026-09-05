<?php

namespace Tests\Feature\Account;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_profile_and_account_settings(): void
    {
        $this->get(route('profile.show'))->assertRedirectToRoute('login');
        $this->get(route('account.settings'))->assertRedirectToRoute('login');
    }

    public function test_authenticated_owner_can_open_profile_with_account_dropdown(): void
    {
        $owner = $this->createMember(BusinessMembership::ROLE_OWNER);

        $response = $this->actingAs($owner)->get(route('profile.show'));

        $response
            ->assertSeeText('Profil Saya')
            ->assertSeeText('Pengaturan Akun')
            ->assertSeeText('Ringkasan')
            ->assertDontSeeText('Pengaturan Sistem')
            ->assertSee('action="'.route('logout').'"', false);
    }

    public function test_employee_does_not_see_personal_or_system_settings_in_sidebar(): void
    {
        $employee = $this->createMember(BusinessMembership::ROLE_EMPLOYEE);

        $response = $this->actingAs($employee)
            ->withSession(['active_employee_business_id' => $employee->memberships()->value('business_id')])
            ->get(route('profile.show'));

        $response
            ->assertSeeText('Pengaturan Akun')
            ->assertDontSeeText('Pengaturan Sistem')
            ->assertDontSee('nav-item">Profil', false);
    }

    public function test_logout_route_rejects_get_requests(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('logout'))
            ->assertMethodNotAllowed();
    }

    public function test_authenticated_user_can_update_only_their_own_profile(): void
    {
        $user = User::factory()->create(['name' => 'Nama Lama', 'email' => 'sendiri@example.com']);
        $otherUser = User::factory()->create(['name' => 'Pengguna Lain', 'email' => 'lain@example.com']);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => '  Nama Baru  ',
            'user_id' => $otherUser->id,
            'email' => 'diubah@example.com',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('status', 'profile-updated');
        $this->assertSame('Nama Baru', $user->fresh()->name);
        $this->assertSame('sendiri@example.com', $user->fresh()->email);
        $this->assertSame('Pengguna Lain', $otherUser->fresh()->name);
    }

    public function test_invalid_profile_data_is_rejected(): void
    {
        $user = User::factory()->create(['name' => 'Nama Tetap']);

        $response = $this->actingAs($user)->patch(route('profile.update'), ['name' => '']);

        $response->assertSessionHasErrorsIn('profileUpdate', [
            'name' => 'Nama lengkap wajib diisi.',
        ]);
        $this->assertSame('Nama Tetap', $user->fresh()->name);
    }

    private function createMember(string $role): User
    {
        $user = User::factory()->create();
        $business = Business::factory()->create();
        BusinessMembership::factory()
            ->for($user)
            ->for($business)
            ->create(['role' => $role]);

        return $user;
    }
}
