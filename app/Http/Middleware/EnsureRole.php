<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Pemakaian: ->middleware('role:super_admin,event_admin')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            throw new AuthenticationException();
        }

        $allowed = array_map(static fn (string $role): UserRole => UserRole::from($role), $roles);

        if (! $user->hasRole(...$allowed)) {
            throw new AccessDeniedHttpException('Anda tidak memiliki izin untuk tindakan ini.');
        }

        return $next($request);
    }
}
