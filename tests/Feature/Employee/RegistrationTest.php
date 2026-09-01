<?php

namespace Tests\Feature\Employee;

use App\Models\Business;
use App\Models\BusinessInvitationCode;
use App\Models\BusinessMembership;
use App\Models\User;
use App\Support\InvitationCodeGenerator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_can_open_employee_registration_from_employee_login(): void
    {
        $this->get(route('employee.login'))
            ->assertOk()
            ->assertSeeText('Daftar sebagai karyawan');

        $this->get(route('employee.register'))
            ->assertOk()
            ->assertSeeText('Daftar akun karyawan')
            ->assertSeeText('Kode Usaha');
    }

    public function test_valid_registration_creates_employee_account_and_membership_for_code_business(): void
    {
        $business = Business::factory()->create(['name' => 'Usaha Undangan Valid']);
        $otherBusiness = Business::factory()->create();
        $invitation = $this->createInvitation($business, 'REGI-STER-1234');

        $response = $this->post(route('employee.register.store'), [
            'name' => '  Rina Karyawan  ',
            'email' => ' RINA@EXAMPLE.COM ',
            'password' => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
            'business_code' => ' regi ster 1234 ',
            'business_id' => $otherBusiness->id,
            'role' => BusinessMembership::ROLE_OWNER,
        ]);

        $employee = User::query()->where('email', 'rina@example.com')->firstOrFail();

        $response
            ->assertRedirectToRoute('employee.dashboard')
            ->assertSessionHas('active_employee_business_id', $business->id)
            ->assertSessionHas('success', 'Akun karyawan berhasil dibuat dan terhubung ke usaha.');
        $this->assertAuthenticatedAs($employee);
        $this->assertSame('Rina Karyawan', $employee->name);
        $this->assertTrue(Hash::check('Rahasia123', $employee->password));
        $this->assertDatabaseHas('business_memberships', [
            'business_id' => $business->id,
            'user_id' => $employee->id,
            'role' => BusinessMembership::ROLE_EMPLOYEE,
            'status' => BusinessMembership::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseMissing('business_memberships', [
            'business_id' => $otherBusiness->id,
            'user_id' => $employee->id,
        ]);
        $this->assertSame(1, $invitation->fresh()->uses_count);

        $this->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSeeText('Usaha Undangan Valid');
    }

    public function test_invalid_business_code_does_not_leave_an_unattached_account(): void
    {
        $response = $this->post(route('employee.register.store'), [
            ...$this->validPayload(),
            'business_code' => 'XXXX-YYYY-9999',
        ]);

        $response->assertSessionHasErrors([
            'business_code' => 'Kode Usaha tidak valid atau sudah tidak berlaku.',
        ]);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('business_memberships', 0);
        $this->assertGuest();
    }

    public function test_required_registration_fields_show_clear_messages(): void
    {
        $response = $this->post(route('employee.register.store'));

        $response->assertSessionHasErrors([
            'name' => 'Nama karyawan wajib diisi.',
            'email' => 'Email wajib diisi.',
            'password' => 'Password wajib diisi.',
            'business_code' => 'Kode Usaha wajib diisi.',
        ]);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('business_memberships', 0);
    }

    public function test_registration_form_escapes_repopulated_employee_name(): void
    {
        $dangerousName = "Karyawan <script>alert('xss')</script>";

        $response = $this
            ->from(route('employee.register'))
            ->followingRedirects()
            ->post(route('employee.register.store'), [
                ...$this->validPayload(),
                'name' => $dangerousName,
                'business_code' => 'XXXX-YYYY-9999',
            ]);

        $response
            ->assertOk()
            ->assertSee($dangerousName)
            ->assertDontSee($dangerousName, false);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_unavailable_business_code_does_not_create_an_account(): void
    {
        $business = Business::factory()->create();
        $invitation = $this->createInvitation($business, 'EXPI-REDC-ODE1', [
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->post(route('employee.register.store'), [
            ...$this->validPayload(),
            'business_code' => 'EXPI-REDC-ODE1',
        ]);

        $response->assertSessionHasErrors('business_code');
        $this->assertDatabaseMissing('users', ['email' => 'employee@example.com']);
        $this->assertDatabaseCount('business_memberships', 0);
        $this->assertSame(0, $invitation->fresh()->uses_count);
        $this->assertGuest();
    }

    public function test_duplicate_email_is_rejected_without_consuming_the_code(): void
    {
        User::factory()->create(['email' => 'employee@example.com']);
        $business = Business::factory()->create();
        $invitation = $this->createInvitation($business, 'DUPL-ICAT-E123');

        $response = $this->post(route('employee.register.store'), [
            ...$this->validPayload(),
            'email' => ' EMPLOYEE@EXAMPLE.COM ',
            'business_code' => 'DUPL-ICAT-E123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'Email sudah terdaftar. Silakan masuk menggunakan akun tersebut.',
        ]);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('business_memberships', 0);
        $this->assertSame(0, $invitation->fresh()->uses_count);
    }

    public function test_password_confirmation_must_match_before_code_is_consumed(): void
    {
        $business = Business::factory()->create();
        $invitation = $this->createInvitation($business, 'PASS-WORD-CODE');

        $response = $this->post(route('employee.register.store'), [
            ...$this->validPayload(),
            'password_confirmation' => 'TidakSama123',
            'business_code' => 'PASS-WORD-CODE',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'Konfirmasi password tidak sesuai.',
        ]);
        $this->assertDatabaseMissing('users', ['email' => 'employee@example.com']);
        $this->assertDatabaseCount('business_memberships', 0);
        $this->assertSame(0, $invitation->fresh()->uses_count);
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

    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Karyawan Baru',
            'email' => 'employee@example.com',
            'password' => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
            'business_code' => 'WILL-BEOV-ERRI',
        ];
    }
}
