<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
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
        Password::defaults(
            fn (): Password => Password::min(8)->mixedCase()->numbers(),
        );

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
