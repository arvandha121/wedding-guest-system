<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use GdImage;

/** QR selalu WebP lossless (lossy bisa merusak keterbacaan). Dibuat saat diminta, tidak disimpan. */
final class QRCodeService
{
    public function webp(string $content, int $size = 512): string
    {
        if (! function_exists('imagewebp')) {
            throw new ApiException('Server belum mendukung WebP (aktifkan ekstensi GD dengan WebP).', 'IMAGE_UNSUPPORTED', 500);
        }

        $png = (new Builder(
            writer: new PngWriter(),
            data: $content,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 64, // quiet zone sekitar 4 modul
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        ))->build()->getString();

        $image = imagecreatefromstring($png);

        if (! $image instanceof GdImage) {
            throw new ApiException('Gagal membuat QR Code.', 'QR_GENERATION_FAILED', 500);
        }

        imagepalettetotruecolor($image);

        ob_start();
        imagewebp($image, null, IMG_WEBP_LOSSLESS);

        return (string) ob_get_clean();
    }
}
