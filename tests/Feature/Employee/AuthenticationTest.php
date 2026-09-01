<?php

namespace Tests\Feature\Employee;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_can_open_employee_login_page(): void
    {
        $this->get(route('employee.login'))
            ->assertOk()
            ->assertSeeText('Masuk ke akun karyawan');
    }

    public function test_employee_without_membership_logs_in_and_is_sent_to_business_code_step(): void
    {
        $employee = User::factory()->create(['email' => 'employee@example.com']);

        $response = $this->post(route('employee.login.store'), [
            'email' => ' EMPLOYEE@EXAMPLE.COM ',
            'password' => 'password',
        ]);

        $response->assertRedirectToRoute('employee.business.join.create');
        $this->assertAuthenticatedAs($employee);
    }

    public function test_existing_employee_logs_in_and_is_sent_to_dashboard(): void
    {
        $employee = User::factory()->create(['email' => 'member@example.com']);
        $business = Business::factory()->create();
        BusinessMembership::factory()
            ->employee()
            ->for($employee)
            ->for($business)
            ->create();

        $response = $this->post(route('employee.login.store'), [
            'email' => 'member@example.com',
            'password' => 'password',
        ]);

        $response
            ->assertRedirectToRoute('employee.dashboard')
            ->assertSessionHas('active_employee_business_id', $business->id);
        $this->assertAuthenticatedAs($employee);
    }

    public function test_invalid_employee_credentials_are_rejected_with_a_safe_message(): void
    {
        User::factory()->create(['email' => 'employee@example.com']);

        $response = $this->post(route('employee.login.store'), [
            'email' => 'employee@example.com',
            'password' => 'password-yang-salah',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'Email atau password tidak sesuai.',
        ]);
        $this->assertGuest();
    }

    public function test_employee_login_attempts_are_rate_limited(): void
    {
        User::factory()->create(['email' => 'limited-employee@example.com']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('employee.login.store'), [
                'email' => 'limited-employee@example.com',
                'password' => 'salah',
            ])->assertSessionHasErrors('email');
        }

        $response = $this->post(route('employee.login.store'), [
            'email' => 'limited-employee@example.com',
            'password' => 'salah',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Terlalu banyak percobaan login.',
            session('errors')->first('email'),
        );
    }

    public function test_employee_can_logout_and_session_is_invalidated(): void
    {
        $employee = User::factory()->create();

        $response = $this->actingAs($employee)
            ->withSession(['_token' => 'token-lama', 'active_employee_business_id' => 99])
            ->post(route('employee.logout'));

        $response
            ->assertRedirectToRoute('employee.login')
            ->assertSessionMissing('active_employee_business_id')
            ->assertSessionHas('_token', fn (string $token): bool => $token !== 'token-lama');
        $this->assertGuest();
    }
}
