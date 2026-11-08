<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun yang dinonaktifkan kehilangan akses seketika, termasuk token/sesi yang sudah ada.
 */
class EnsureUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->isActive()) {
            return $next($request);
        }

        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        } elseif ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->is('api/*')) {
            throw new ApiException('Akun Anda tidak aktif. Hubungi administrator.', 'ACCOUNT_INACTIVE', 403);
        }

        return redirect()->route('login')->withErrors(['email' => 'Akun Anda tidak aktif. Hubungi administrator.']);
    }
}
