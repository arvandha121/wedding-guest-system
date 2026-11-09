<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\EventSession;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Semua timestamptz dibaca/ditulis dalam UTC, tanpa tergantung zona waktu server PostgreSQL.
        config(['database.connections.pgsql.timezone' => config('database.connections.pgsql.timezone') ?? 'UTC']);
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->environment('production'));

        // Route binding yang dibatasi hak akses: acara di luar penugasan dianggap tidak ada (404).
        Route::bind('event', function (string $value): Event {
            $user = request()->user();
            abort_if($user === null, 404);

            return Event::query()->accessibleBy($user)->where('ulid', $value)->firstOrFail();
        });

        Route::bind('trashedEvent', function (string $value): Event {
            abort_unless(request()->user()?->isSuperAdmin(), 404);

            return Event::onlyTrashed()->where('ulid', $value)->firstOrFail();
        });

        Route::bind('eventSession', function (string $value): EventSession {
            $user = request()->user();
            abort_if($user === null, 404);

            return EventSession::query()
                ->with('event')
                ->whereIn('event_id', Event::query()->accessibleBy($user)->select('events.id'))
                ->where('ulid', $value)
                ->firstOrFail();
        });

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
