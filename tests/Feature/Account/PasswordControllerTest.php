<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_password_change_is_rejected_when_current_password_is_wrong(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('account.password.update'), [
            'current_password' => 'password-salah',
            'password' => 'PasswordBaru9',
            'password_confirmation' => 'PasswordBaru9',
        ]);

        $response->assertSessionHasErrorsIn('passwordUpdate', [
            'current_password' => 'Password saat ini tidak sesuai.',
        ]);
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_mismatched_password_confirmation_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('account.password.update'), [
            'current_password' => 'password',
            'password' => 'PasswordBaru9',
            'password_confirmation' => 'Berbeda9',
        ]);

        $response->assertSessionHasErrorsIn('passwordUpdate', [
            'password' => 'Konfirmasi password baru tidak cocok.',
        ]);
    }

    public function test_weak_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('account.password.update'), [
            'current_password' => 'password',
            'password' => 'lemah',
            'password_confirmation' => 'lemah',
        ]);

        $response->assertSessionHasErrorsIn('passwordUpdate', 'password');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_new_password_must_differ_from_current_password(): void
    {
        $user = User::factory()->create(['password' => 'PasswordLama9']);

        $response = $this->actingAs($user)->patch(route('account.password.update'), [
            'current_password' => 'PasswordLama9',
            'password' => 'PasswordLama9',
            'password_confirmation' => 'PasswordLama9',
        ]);

        $response->assertSessionHasErrorsIn('passwordUpdate', [
            'password' => 'Password baru harus berbeda dari password saat ini.',
        ]);
    }

    public function test_valid_password_is_hashed_and_current_session_remains_authenticated(): void
    {
        $user = User::factory()->create();
        $oldRememberToken = $user->remember_token;

        $response = $this->actingAs($user)->patch(route('account.password.update'), [
            'current_password' => 'password',
            'password' => 'PasswordBaru9',
            'password_confirmation' => 'PasswordBaru9',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('status', 'password-updated');
        $user->refresh();
        $this->assertFalse(Hash::check('password', $user->password));
        $this->assertTrue(Hash::check('PasswordBaru9', $user->password));
        $this->assertNotSame($oldRememberToken, $user->remember_token);
        $this->assertAuthenticatedAs($user);
    }
}
