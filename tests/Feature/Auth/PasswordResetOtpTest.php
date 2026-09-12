<?php

namespace Tests\Feature\Auth;

use App\Models\BusinessMembership;
use App\Models\User;
use App\Notifications\ResetPasswordOtp;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class PasswordResetOtpTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['login', 'owner'])]
    #[TestWith(['employee.login', 'employee'])]
    public function test_login_pages_link_to_password_recovery(string $loginRoute, string $role): void
    {
        $this->get(route($loginRoute))
            ->assertSee(route('password.request', ['role' => $role]));

        $this->get(route('password.request', ['role' => $role]))
            ->assertSeeText('Lupa password?')
            ->assertSee(route('password.email'));
    }

    public function test_sends_six_digit_otp_to_primary_email_and_stores_only_its_hash(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'user@example.com', 'pending_email' => 'pending@example.com']);

        $this->post(route('password.email'), ['email' => ' USER@EXAMPLE.COM '])
            ->assertRedirectToRoute('password.otp')
            ->assertSessionHas('password_reset_email', 'user@example.com');

        Notification::assertSentTo($user, ResetPasswordOtp::class, function (ResetPasswordOtp $notification, array $channels): bool {
            $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $notification->otp);
            $this->assertSame(['mail'], $channels);

            $challenge = Cache::get('password-reset-otp:'.hash('sha256', 'user@example.com'));
            $this->assertTrue(Hash::check($notification->otp, $challenge['otp_hash']));
            $this->assertArrayNotHasKey('otp', $challenge);

            return true;
        });
        Notification::assertCount(1);
        $this->get(route('password.otp'))->assertSeeText('user@example.com');
    }

    public function test_unknown_email_receives_the_same_public_response_without_email_delivery(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $known = $this->post(route('password.email'), ['email' => $user->email]);
        $knownStatus = session('status');

        $this->post(route('password.email'), ['email' => 'unknown@example.com'])
            ->assertRedirect($known->headers->get('Location'))
            ->assertSessionHas('status', $knownStatus);
        Notification::assertCount(1);

        $this->post(route('password.otp.verify'), ['otp' => '123456'])->assertSessionHasErrors('otp');
        $this->assertGuest();
    }

    #[TestWith(['owner', 'login'])]
    #[TestWith(['employee', 'employee.login'])]
    public function test_verified_user_can_reset_password_and_login(string $role, string $loginRoute): void
    {
        Notification::fake();
        $user = User::factory()->create();
        BusinessMembership::factory()->for($user)->create(['role' => $role]);
        $rememberToken = $user->getRememberToken();
        $otp = $this->requestOtp($user, $role);
        Event::fake([PasswordReset::class]);

        $this->post(route('password.otp.verify'), ['otp' => $otp])
            ->assertRedirectToRoute('password.reset');
        $this->get(route('password.reset'))->assertSeeText('Buat password baru');

        $this->post(route('password.update'), $this->newPassword())
            ->assertRedirectToRoute($loginRoute)
            ->assertSessionMissing('password_reset_grant')
            ->assertSessionHas('status', 'Password berhasil diperbarui. Silakan masuk dengan password baru Anda.');

        $this->assertTrue(Hash::check('NewPassword123', $user->fresh()->password));
        $this->assertNotSame($rememberToken, $user->fresh()->getRememberToken());
        Event::assertDispatched(PasswordReset::class, fn (PasswordReset $event): bool => $event->user->is($user));
        $this->assertGuest();
        $this->get(route($loginRoute))->assertSeeText('Password berhasil diperbarui.');

        $this->post(route($loginRoute.'.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->post(route($loginRoute.'.store'), ['email' => $user->email, 'password' => 'NewPassword123'])
            ->assertSessionDoesntHaveErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_reset_requires_verified_session_even_when_email_and_otp_are_submitted_directly(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $otp = $this->requestOtp($user);

        $this->get(route('password.reset'))->assertRedirectToRoute('password.request', ['role' => 'owner']);
        $this->post(route('password.update'), [
            ...$this->newPassword(), 'email' => $user->email, 'otp' => $otp, 'token' => $otp,
        ])->assertRedirectToRoute('password.request', ['role' => 'owner']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_five_wrong_attempts_invalidate_otp_across_sessions(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $otp = $this->requestOtp($user);
        $wrongOtp = $otp === '000000' ? '111111' : '000000';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withSession(['password_reset_email' => $user->email])
                ->post(route('password.otp.verify'), ['otp' => $wrongOtp])
                ->assertSessionHasErrors('otp')
                ->assertSessionMissing('_old_input.otp');
        }

        $this->post(route('password.otp.verify'), ['otp' => $otp])
            ->assertSessionHasErrors('otp')
            ->assertSessionMissing('password_reset_grant');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_otp_expires_after_five_minutes(): void
    {
        Notification::fake();
        $this->freezeTime();
        $user = User::factory()->create();
        $otp = $this->requestOtp($user);
        $this->travel(5)->minutes();

        $this->post(route('password.otp.verify'), ['otp' => $otp])
            ->assertSessionHasErrors('otp')
            ->assertSessionMissing('password_reset_grant');
    }

    public function test_resend_waits_sixty_seconds_and_invalidates_previous_code(): void
    {
        Notification::fake();
        $this->freezeTime();
        $user = User::factory()->create();
        $oldOtp = $this->requestOtp($user);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHasErrors(['email' => 'Tunggu 60 detik sebelum meminta OTP lagi.']);
        Notification::assertSentToTimes($user, ResetPasswordOtp::class, 1);

        $this->travel(60)->seconds();
        $this->post(route('password.email'), ['email' => $user->email])->assertRedirectToRoute('password.otp');
        Notification::assertSentToTimes($user, ResetPasswordOtp::class, 2);
        $newOtp = Notification::sent($user, ResetPasswordOtp::class)->last()->otp;

        $this->post(route('password.otp.verify'), ['otp' => $oldOtp])->assertSessionHasErrors('otp');
        $this->post(route('password.otp.verify'), ['otp' => $newOtp])->assertRedirectToRoute('password.reset');
    }

    public function test_otp_and_verified_grant_cannot_be_reused(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $otp = $this->requestOtp($user);
        $this->post(route('password.otp.verify'), ['otp' => $otp])->assertRedirectToRoute('password.reset');
        $verifiedSession = session()->only(['password_reset_email', 'password_reset_grant', 'password_reset_verified_until']);

        $this->post(route('password.otp.verify'), ['otp' => $otp])->assertSessionHasErrors('otp');
        $this->post(route('password.update'), $this->newPassword())->assertRedirectToRoute('login');
        $this->withSession($verifiedSession)->post(route('password.update'), [
            'password' => 'AnotherPassword123', 'password_confirmation' => 'AnotherPassword123',
        ])->assertRedirectToRoute('password.request', ['role' => 'owner']);

        $this->assertTrue(Hash::check('NewPassword123', $user->fresh()->password));
    }

    public function test_grant_is_bound_to_verified_account_and_ignores_submitted_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $other = User::factory()->create();
        $otp = $this->requestOtp($user);
        $this->post(route('password.otp.verify'), ['otp' => $otp])->assertRedirectToRoute('password.reset');

        $this->post(route('password.update'), [...$this->newPassword(), 'email' => $other->email])
            ->assertRedirectToRoute('login');

        $this->assertTrue(Hash::check('NewPassword123', $user->fresh()->password));
        $this->assertTrue(Hash::check('password', $other->fresh()->password));
    }

    public function test_verified_grant_expires_after_five_minutes(): void
    {
        Notification::fake();
        $this->freezeTime();
        $user = User::factory()->create();
        $otp = $this->requestOtp($user);
        $this->post(route('password.otp.verify'), ['otp' => $otp])->assertRedirectToRoute('password.reset');
        $this->travel(5)->minutes();

        $this->post(route('password.update'), $this->newPassword())
            ->assertRedirectToRoute('password.request', ['role' => 'owner']);
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_resend_invalidates_already_verified_grant_in_another_session(): void
    {
        Notification::fake();
        $this->freezeTime();
        $user = User::factory()->create();
        $otp = $this->requestOtp($user);
        $this->post(route('password.otp.verify'), ['otp' => $otp])->assertRedirectToRoute('password.reset');
        $verifiedSession = session()->only(['password_reset_email', 'password_reset_grant', 'password_reset_verified_until']);
        $this->travel(60)->seconds();
        $this->post(route('password.email'), ['email' => $user->email])->assertRedirectToRoute('password.otp');

        $this->withSession($verifiedSession)->post(route('password.update'), $this->newPassword())
            ->assertRedirectToRoute('password.request', ['role' => 'owner']);
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        Notification::assertSentToTimes($user, ResetPasswordOtp::class, 2);
    }

    public function test_password_change_since_issuance_invalidates_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $otp = $this->requestOtp($user);
        $this->post(route('password.otp.verify'), ['otp' => $otp])->assertRedirectToRoute('password.reset');
        $user->update(['password' => 'ChangedElsewhere123']);

        $this->post(route('password.update'), $this->newPassword())
            ->assertRedirectToRoute('password.request', ['role' => 'owner']);
        $this->assertTrue(Hash::check('ChangedElsewhere123', $user->fresh()->password));
    }

    public function test_otp_cannot_verify_another_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $other = User::factory()->create();
        $otp = $this->requestOtp($user);

        $this->withSession(['password_reset_email' => $other->email])
            ->post(route('password.otp.verify'), ['otp' => $otp])->assertSessionHasErrors('otp');
    }

    #[TestWith(['short', 'short', 'Password baru minimal 8 karakter.'])]
    #[TestWith(['lowercase123', 'lowercase123', 'Password baru harus mengandung huruf besar dan huruf kecil.'])]
    #[TestWith(['NoNumbersHere', 'NoNumbersHere', 'Password baru harus mengandung setidaknya satu angka.'])]
    #[TestWith(['NewPassword123', 'Mismatch123', 'Konfirmasi password baru tidak cocok.'])]
    public function test_invalid_password_is_rejected_without_consuming_verification(string $password, string $confirmation, string $message): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $otp = $this->requestOtp($user);
        $this->post(route('password.otp.verify'), ['otp' => $otp])->assertRedirectToRoute('password.reset');

        $this->post(route('password.update'), ['password' => $password, 'password_confirmation' => $confirmation])
            ->assertSessionHasErrors(['password' => $message]);
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $this->post(route('password.update'), $this->newPassword())->assertRedirectToRoute('login');
    }

    public function test_same_password_is_rejected(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => 'NewPassword123']);
        $otp = $this->requestOtp($user);
        $this->post(route('password.otp.verify'), ['otp' => $otp])->assertRedirectToRoute('password.reset');

        $this->post(route('password.update'), $this->newPassword())
            ->assertSessionHasErrors(['password' => 'Password baru harus berbeda dari password saat ini.']);
    }

    public function test_forged_grant_cannot_reset_an_account_with_an_active_otp(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->requestOtp($user);

        $this->withSession([
            'password_reset_email' => $user->email,
            'password_reset_grant' => str_repeat('a', 64),
            'password_reset_verified_until' => now()->addMinutes(5)->timestamp,
        ])->post(route('password.update'), $this->newPassword())
            ->assertRedirectToRoute('password.request', ['role' => 'owner']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_email_change_since_issuance_invalidates_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $otp = $this->requestOtp($user);
        $this->post(route('password.otp.verify'), ['otp' => $otp])->assertRedirectToRoute('password.reset');
        $user->update(['email' => 'changed@example.com']);

        $this->post(route('password.update'), $this->newPassword())
            ->assertRedirectToRoute('password.request', ['role' => 'owner']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_authenticated_users_cannot_start_password_recovery(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('password.email'), ['email' => $user->email])
            ->assertRedirectToRoute('overview');

        Notification::assertNothingSent();
    }

    public function test_email_and_otp_input_are_validated(): void
    {
        Notification::fake();

        $this->post(route('password.email'), [])->assertSessionHasErrors(['email' => 'Email wajib diisi.']);
        $this->post(route('password.email'), ['email' => 'not-an-email'])->assertSessionHasErrors(['email' => 'Format email tidak valid.']);
        $this->post(route('password.otp.verify'), [])->assertSessionHasErrors(['otp' => 'Kode OTP wajib diisi.']);
        $this->post(route('password.otp.verify'), ['otp' => '12345a'])
            ->assertSessionHasErrors(['otp' => 'Kode OTP harus terdiri dari 6 angka.'])
            ->assertSessionMissing('_old_input.otp');
        Notification::assertNothingSent();
    }

    public function test_send_is_limited_per_ip_even_for_different_emails(): void
    {
        Notification::fake();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('password.email'), ['email' => "unknown{$attempt}@example.com"])->assertRedirectToRoute('password.otp');
        }

        $this->post(route('password.email'), ['email' => 'another@example.com'])->assertTooManyRequests();
        Notification::assertNothingSent();
    }

    public function test_send_is_limited_per_email_across_an_hour(): void
    {
        Notification::fake();
        $this->freezeTime();
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('password.email'), ['email' => $user->email])->assertRedirectToRoute('password.otp');
            $this->travel(61)->seconds();
        }

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHasErrors(['email' => 'Batas permintaan OTP tercapai. Silakan coba lagi dalam satu jam.']);
        Notification::assertSentToTimes($user, ResetPasswordOtp::class, 5);
    }

    public function test_mail_failure_is_reported_and_does_not_leave_a_usable_otp(): void
    {
        Exceptions::fake();
        $user = User::factory()->create();
        $failure = new TransportException('SMTP unavailable');
        Notification::shouldReceive('send')->once()->andThrow($failure);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHasErrors(['email' => 'Email OTP belum dapat dikirim. Silakan coba lagi nanti.'])
            ->assertSessionMissing('password_reset_email');

        Exceptions::assertReported(fn (TransportException $exception): bool => $exception === $failure);
        $this->assertNull(Cache::get('password-reset-otp:'.hash('sha256', $user->email)));
    }

    public function test_reset_revokes_database_sessions_for_only_the_reset_account(): void
    {
        config(['session.driver' => 'database']);
        Notification::fake();
        $user = User::factory()->create();
        $other = User::factory()->create();
        $otp = $this->requestOtp($user);
        $this->post(route('password.otp.verify'), ['otp' => $otp])->assertRedirectToRoute('password.reset');
        DB::table('sessions')->insert([
            ['id' => 'old-user-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => now()->timestamp],
            ['id' => 'other-user-session', 'user_id' => $other->id, 'payload' => '', 'last_activity' => now()->timestamp],
        ]);
        $this->post(route('password.update'), $this->newPassword())->assertRedirectToRoute('login');

        $this->assertDatabaseMissing('sessions', ['id' => 'old-user-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'other-user-session']);
    }

    public function test_email_content_contains_code_expiry_and_security_instructions(): void
    {
        $notification = new ResetPasswordOtp('012345');
        $message = $notification->toMail(User::factory()->make());
        $html = (string) $message->render();

        $this->assertSame('Kode OTP Reset Password NADI', $message->subject);
        $this->assertStringContainsString('012345', $html);
        $this->assertStringContainsString('5 menit', $html);
        $this->assertStringContainsString('Jangan bagikan kode', $html);
    }

    private function requestOtp(User $user, string $role = 'owner'): string
    {
        $this->post(route('password.email'), ['email' => $user->email, 'role' => $role])
            ->assertRedirectToRoute('password.otp');
        Notification::assertSentTo($user, ResetPasswordOtp::class);

        return Notification::sent($user, ResetPasswordOtp::class)->last()->otp;
    }

    /** @return array{password: string, password_confirmation: string} */
    private function newPassword(): array
    {
        return ['password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'];
    }
}
