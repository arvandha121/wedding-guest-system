<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->environment('production'));

        Password::defaults(
            fn (): Password => Password::min(10)->letters()->mixedCase()->numbers()
        );

        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(120)->by($request->user()?->getAuthIdentifier() ?: $request->ip());
        });

        RateLimiter::for('auth-login', function (Request $request): array {
            $email = mb_strtolower((string) $request->input('email'));

            return [
                Limit::perMinute((int) config('wgs.login_attempts_per_minute'))->by($email . '|' . $request->ip()),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });
    }
}
