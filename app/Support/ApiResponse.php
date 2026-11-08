<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use stdClass;

/**
 * Format respons API standar (lihat dokumen desain bagian 6.1).
 */
final class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Data berhasil diproses.',
        int $status = 200,
        array $meta = [],
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data ?? new stdClass(),
            'meta' => $meta === [] ? new stdClass() : $meta,
        ], $status);
    }

    public static function paginated(
        LengthAwarePaginator $paginator,
        string $resourceClass,
        string $message = 'Data berhasil diambil.',
    ): JsonResponse {
        return self::success(
            $resourceClass::collection($paginator->getCollection())->resolve(),
            $message,
            200,
            [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        );
    }

    public static function error(
        string $message,
        string $code,
        int $status,
        array $errors = [],
        array $headers = [],
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors === [] ? new stdClass() : $errors,
            'code' => $code,
        ], $status, $headers);
    }
}
