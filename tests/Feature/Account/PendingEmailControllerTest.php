<?php

namespace Tests\Feature\Account;

use App\Models\User;
use App\Notifications\EmailAddressChanged;
use App\Notifications\VerifyPendingEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PendingEmailControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_email_change_is_rejected_when_current_password_is_wrong(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'lama@example.com']);

        $response = $this->actingAs($user)->patch(route('account.email.update'), [
            'new_email' => 'baru@example.com',
            'current_password' => 'password-salah',
        ]);

        $response->assertSessionHasErrorsIn('emailUpdate', [
            'current_password' => 'Password saat ini tidak sesuai.',
        ]);
        $this->assertSame('lama@example.com', $user->fresh()->email);
        $this->assertNull($user->fresh()->pending_email);
        Notification::assertNothingSent();
    }

    public function test_invalid_and_current_email_are_rejected_with_clear_messages(): void
    {
        $user = User::factory()->create(['email' => 'sendiri@example.com']);

        $this->actingAs($user)->patch(route('account.email.update'), [
            'new_email' => 'bukan-email',
            'current_password' => 'password',
        ])->assertSessionHasErrorsIn('emailUpdate', [
            'new_email' => 'Masukkan alamat email baru yang valid.',
        ]);

        $this->actingAs($user)->patch(route('account.email.update'), [
            'new_email' => 'sendiri@example.com',
            'current_password' => 'password',
        ])->assertSessionHasErrorsIn('emailUpdate', [
            'new_email' => 'Email baru harus berbeda dari email saat ini.',
        ]);
    }

    public function test_email_owned_or_reserved_by_another_user_is_rejected(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['email' => 'dipakai@example.com']);
        User::factory()->create(['pending_email' => 'dipesan@example.com']);

        $this->actingAs($user)->patch(route('account.email.update'), [
            'new_email' => 'dipakai@example.com',
            'current_password' => 'password',
        ])->assertSessionHasErrorsIn('emailUpdate', 'new_email');

        $this->actingAs($user)->patch(route('account.email.update'), [
            'new_email' => 'dipesan@example.com',
            'current_password' => 'password',
        ])->assertSessionHasErrorsIn('emailUpdate', 'new_email');
    }

    public function test_email_change_remains_pending_until_verification(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'lama@example.com']);

        $response = $this->actingAs($user)->patch(route('account.email.update'), [
            'new_email' => '  BARU@EXAMPLE.COM ',
            'current_password' => 'password',
        ]);

        $response->assertSessionHas('status', 'email-verification-sent');
        $user->refresh();
        $this->assertSame('lama@example.com', $user->email);
        $this->assertSame('baru@example.com', $user->pending_email);
        $this->assertNotNull($user->pending_email_requested_at);
        Notification::assertSentOnDemand(
            VerifyPendingEmail::class,
            fn (VerifyPendingEmail $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === 'baru@example.com',
        );
    }

    public function test_valid_signed_link_promotes_pending_email_and_notifies_old_address(): void
    {
        $this->travelTo('2026-09-04 10:00:00');
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'lama@example.com',
            'pending_email' => 'baru@example.com',
            'pending_email_requested_at' => now(),
            'email_verified_at' => null,
        ]);
        $url = (new VerifyPendingEmail(
            $user->id,
            'baru@example.com',
            now()->getTimestamp(),
        ))->verificationUrl();

        $response = $this->actingAs($user)->get($url);

        $response
            ->assertRedirectToRoute('account.settings')
            ->assertSessionHas('status', 'email-updated');
        $user->refresh();
        $this->assertSame('baru@example.com', $user->email);
        $this->assertNull($user->pending_email);
        $this->assertNull($user->pending_email_requested_at);
        $this->assertTrue($user->email_verified_at->equalTo(now()));
        Notification::assertSentOnDemand(
            EmailAddressChanged::class,
            fn (EmailAddressChanged $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === 'lama@example.com'
                && $notification->newEmail === 'baru@example.com',
        );
    }

    public function test_unsigned_expired_and_other_users_links_are_rejected(): void
    {
        $this->travelTo('2026-09-04 10:00:00');
        $user = User::factory()->create([
            'pending_email' => 'baru@example.com',
            'pending_email_requested_at' => now(),
        ]);
        $otherUser = User::factory()->create();
        $parameters = [
            'user' => $user->id,
            'hash' => hash('sha256', 'baru@example.com'),
            'requested' => now()->getTimestamp(),
        ];

        $this->actingAs($user)
            ->get(route('account.email.verify', $parameters))
            ->assertForbidden();

        $validUrl = URL::temporarySignedRoute('account.email.verify', now()->addMinute(), $parameters);
        $this->travel(2)->minutes();
        $this->actingAs($user)->get($validUrl)->assertForbidden();

        $this->travelBack();
        $otherUserUrl = URL::temporarySignedRoute('account.email.verify', now()->addHour(), $parameters);
        $this->actingAs($otherUser)->get($otherUserUrl)->assertForbidden();
        $this->assertNotSame('baru@example.com', $user->fresh()->email);
    }

    public function test_resending_email_verification_is_rate_limited(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'pending_email' => 'baru@example.com',
            'pending_email_requested_at' => now(),
        ]);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->actingAs($user)
                ->post(route('account.email.verification.send'))
                ->assertSessionHas('status', 'email-verification-sent');
        }

        $this->actingAs($user)
            ->post(route('account.email.verification.send'))
            ->assertTooManyRequests();
    }
}
