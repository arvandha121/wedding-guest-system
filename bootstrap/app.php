<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureUserActive;
use App\Http\Middleware\ForceJson;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
            'active' => EnsureUserActive::class,
        ]);

        $middleware->api(prepend: [ForceJson::class]);
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReport(ApiException::class);

        $isApi = static fn (Request $request): bool => $request->is('api/*');

        $exceptions->render(function (ValidationException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error('Data yang dikirim tidak valid.', 'VALIDATION_ERROR', 422, $e->errors());
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error('Sesi login tidak valid atau sudah berakhir.', 'UNAUTHENTICATED', 401);
        });

        $exceptions->render(function (AuthorizationException|AccessDeniedHttpException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error('Anda tidak memiliki izin untuk tindakan ini.', 'FORBIDDEN', 403);
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error('Data tidak ditemukan.', 'NOT_FOUND', 404);
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error('Metode HTTP tidak diizinkan.', 'METHOD_NOT_ALLOWED', 405);
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error(
                'Terlalu banyak permintaan. Coba lagi sebentar.',
                'RATE_LIMITED',
                429,
                [],
                $e->getHeaders(),
            );
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            $status = $e->getStatusCode();

            if ($status >= 500) {
                return ApiResponse::error('Terjadi kesalahan pada server.', 'SERVER_ERROR', $status);
            }

            $code = match ($status) {
                400 => 'BAD_REQUEST',
                409 => 'CONFLICT',
                419 => 'CSRF_TOKEN_MISMATCH',
                default => 'HTTP_ERROR',
            };

            return ApiResponse::error($e->getMessage() ?: 'Permintaan tidak dapat diproses.', $code, $status);
        });

        $exceptions->render(function (Throwable $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            $errors = config('app.debug')
                ? ['exception' => $e::class, 'detail' => $e->getMessage()]
                : [];

            return ApiResponse::error('Terjadi kesalahan pada server.', 'SERVER_ERROR', 500, $errors);
        });
    })->create();
