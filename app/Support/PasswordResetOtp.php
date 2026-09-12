<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\ResetPasswordOtp;
use Closure;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetOtp
{
    public const EXPIRES_IN_SECONDS = 300;

    public const RESEND_AFTER_SECONDS = 60;

    public const INVALID_OTP = 'Kode OTP tidak valid atau sudah kedaluwarsa. Silakan minta kode baru.';

    public function send(string $email): void
    {
        $this->withLock($email, function () use ($email): void {
            $key = $this->key($email);

            if (RateLimiter::tooManyAttempts($key.':resend', 1)) {
                $seconds = RateLimiter::availableIn($key.':resend');

                throw ValidationException::withMessages(['email' => "Tunggu {$seconds} detik sebelum meminta OTP lagi."]);
            }

            if (RateLimiter::tooManyAttempts($key.':hour', 5)) {
                throw ValidationException::withMessages(['email' => 'Batas permintaan OTP tercapai. Silakan coba lagi dalam satu jam.']);
            }

            RateLimiter::hit($key.':resend', self::RESEND_AFTER_SECONDS);
            RateLimiter::hit($key.':hour', 3600);

            $user = User::query()->where('email', $email)->first();
            $previousHash = Cache::get($key)['otp_hash'] ?? null;

            do {
                $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            } while ($previousHash !== null && Hash::check($otp, $previousHash));

            Cache::put($key, [
                'user_id' => $user?->getKey(),
                'password_fingerprint' => $user ? hash('sha256', $user->password) : null,
                'otp_hash' => Hash::make($otp),
                'attempts' => 0,
                'expires_at' => now()->getTimestamp() + self::EXPIRES_IN_SECONDS,
                'grant_hash' => null,
            ], self::EXPIRES_IN_SECONDS);

            try {
                $user?->notify(new ResetPasswordOtp($otp));
            } catch (TransportExceptionInterface $exception) {
                Cache::forget($key);
                report($exception);

                throw ValidationException::withMessages([
                    'email' => 'Email OTP belum dapat dikirim. Silakan coba lagi nanti.',
                ]);
            }
        });
    }

    public function verify(string $email, string $otp): string
    {
        return $this->withLock($email, function () use ($email, $otp): string {
            $key = $this->key($email);
            $challenge = Cache::get($key);

            if (! $challenge || $challenge['expires_at'] <= now()->getTimestamp() || $challenge['otp_hash'] === null) {
                throw ValidationException::withMessages(['otp' => self::INVALID_OTP]);
            }

            $matches = Hash::check($otp, $challenge['otp_hash']);

            if (! $matches || $challenge['user_id'] === null) {
                $challenge['attempts']++;

                if ($challenge['attempts'] >= 5) {
                    Cache::forget($key);
                } else {
                    Cache::put($key, $challenge, $challenge['expires_at'] - now()->getTimestamp());
                }

                throw ValidationException::withMessages(['otp' => self::INVALID_OTP]);
            }

            $grant = Str::random(64);
            $challenge['otp_hash'] = null;
            $challenge['grant_hash'] = hash('sha256', $grant);
            $challenge['expires_at'] = now()->getTimestamp() + self::EXPIRES_IN_SECONDS;
            Cache::put($key, $challenge, self::EXPIRES_IN_SECONDS);

            return $grant;
        });
    }

    public function reset(string $email, string $grant, string $password): bool
    {
        return $this->withLock($email, function () use ($email, $grant, $password): bool {
            $key = $this->key($email);
            $challenge = Cache::get($key);

            if (! $challenge || $challenge['expires_at'] <= now()->getTimestamp()
                || $challenge['grant_hash'] === null || ! hash_equals($challenge['grant_hash'], hash('sha256', $grant))) {
                return false;
            }

            $user = DB::transaction(function () use ($email, $password, $challenge): ?User {
                $user = User::query()->whereKey($challenge['user_id'])->where('email', $email)->lockForUpdate()->first();

                if (! $user || ! hash_equals($challenge['password_fingerprint'], hash('sha256', $user->password))) {
                    return null;
                }

                if (Hash::check($password, $user->password)) {
                    throw ValidationException::withMessages(['password' => 'Password baru harus berbeda dari password saat ini.']);
                }

                $user->password = $password;
                $user->setRememberToken(Str::random(60));
                $user->save();

                return $user;
            });

            Cache::forget($key);

            if ($user === null) {
                return false;
            }

            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))
                    ->table(config('session.table', 'sessions'))
                    ->where('user_id', $user->getKey())->delete();
            }

            event(new PasswordReset($user));

            return true;
        });
    }

    private function key(string $email): string
    {
        return 'password-reset-otp:'.hash('sha256', $email);
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    private function withLock(string $email, Closure $callback): mixed
    {
        try {
            return Cache::lock($this->key($email).':lock', 120)->block(3, $callback);
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['email' => 'Permintaan sedang diproses. Silakan coba lagi sebentar.']);
        }
    }
}
