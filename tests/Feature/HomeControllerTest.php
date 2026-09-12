<?php

namespace Tests\Feature;

use App\Models\BusinessMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_can_explore_nadi_without_logging_in(): void
    {
        $response = $this->get('/');

        $response
            ->assertViewIs('home')
            ->assertSeeText('Usaha terpantau.')
            ->assertSeeText('Daftarkan usaha')
            ->assertSeeText('Seluruh angka merupakan data contoh')
            ->assertSee(route('register'))
            ->assertSee(route('login'))
            ->assertSee(route('employee.register'))
            ->assertSee(route('employee.login'));

        $this->assertGuest();
    }

    public function test_owner_opening_home_goes_directly_to_the_dashboard(): void
    {
        $owner = User::factory()->create();
        BusinessMembership::factory()->for($owner)->create();

        $response = $this->actingAs($owner)->get(route('home'));

        $response->assertRedirectToRoute('overview');
        $this->assertAuthenticatedAs($owner);
    }

    public function test_employee_opening_home_goes_to_the_employee_workspace(): void
    {
        $employee = User::factory()->create();
        BusinessMembership::factory()->employee()->for($employee)->create();

        $response = $this->actingAs($employee)->get(route('home'));

        $response->assertRedirectToRoute('employee.dashboard');
        $this->assertAuthenticatedAs($employee);
    }

    public function test_account_without_a_business_can_continue_to_the_join_step(): void
    {
        $employee = User::factory()->create();

        $response = $this->actingAs($employee)->followingRedirects()->get(route('home'));

        $response->assertViewIs('employee.join-business');
        $this->assertAuthenticatedAs($employee);
    }

    public function test_inactive_owner_membership_does_not_route_an_employee_to_the_owner_dashboard(): void
    {
        $employee = User::factory()->create();
        BusinessMembership::factory()->inactive()->for($employee)->create();
        BusinessMembership::factory()->employee()->for($employee)->create();

        $response = $this->actingAs($employee)->get(route('home'));

        $response->assertRedirectToRoute('employee.dashboard');
    }

    public function test_account_with_both_roles_opens_the_owner_dashboard(): void
    {
        $owner = User::factory()->create();
        BusinessMembership::factory()->for($owner)->create();
        BusinessMembership::factory()->employee()->for($owner)->create();

        $response = $this->actingAs($owner)->get(route('home'));

        $response->assertRedirectToRoute('overview');
    }

    public function test_protected_destination_is_preserved_after_visiting_home_and_logging_in(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        BusinessMembership::factory()->for($owner)->create();

        $this->get(route('sales.index'))->assertRedirectToRoute('login');
        $this->get(route('home'))->assertViewIs('home');

        $response = $this->post(route('login.store'), [
            'email' => 'owner@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirectToRoute('sales.index');
    }
}
