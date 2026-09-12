<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DevCommands::artisan('schedule:work --no-interaction', 'scheduler');

        Password::defaults(
            fn (): Password => Password::min(8)->mixedCase()->numbers(),
        );

        RateLimiter::for('password-reset-send', fn (Request $request): Limit => Limit::perMinute(5)
            ->by('password-reset-send:'.$request->ip()));
        RateLimiter::for('password-reset-verify', fn (Request $request): Limit => Limit::perMinute(15)
            ->by('password-reset-verify:'.$request->ip()));

        RateLimiter::for('ai-insight', fn (Request $request): array => [
            Limit::perMinute(6)->by('ai-insight:user:'.$request->user()?->getAuthIdentifier())
                ->response(fn (): JsonResponse => response()->json(['message' => 'Terlalu banyak pertanyaan. Tunggu sebentar lalu coba lagi.'], 429)),
            Limit::perMinute(15)->by('ai-insight:shared')
                ->response(fn (): JsonResponse => response()->json(['message' => 'Asisten sedang melayani banyak pertanyaan. Coba lagi sebentar.'], 429)),
        ]);

        RateLimiter::for(
            'account-sensitive',
            fn (Request $request): Limit => Limit::perMinute(6)
                ->by('account-sensitive:'.$request->user()?->getAuthIdentifier()),
        );

        RateLimiter::for(
            'email-verification',
            fn (Request $request): Limit => Limit::perMinute(3)
                ->by('email-verification:'.$request->user()?->getAuthIdentifier()),
        );
    }
}
