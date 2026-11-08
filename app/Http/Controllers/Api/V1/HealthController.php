<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

final class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('select 1');
        } catch (Throwable) {
            return ApiResponse::error(
                'Database tidak dapat dijangkau.',
                'SERVICE_UNAVAILABLE',
                503,
                ['database' => 'error'],
            );
        }

        return ApiResponse::success(['app' => 'ok', 'database' => 'ok'], 'Layanan berjalan normal.');
    }
}
