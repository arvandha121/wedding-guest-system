<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

/**
 * Kesalahan bisnis yang diketahui; membawa kode error API dan status HTTP.
 */
class ApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly int $status = 400,
        public readonly array $errors = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function render(): JsonResponse
    {
        return ApiResponse::error($this->getMessage(), $this->errorCode, $this->status, $this->errors);
    }
}
