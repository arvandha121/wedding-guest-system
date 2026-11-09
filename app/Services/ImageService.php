<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Semua gambar yang disimpan sistem dikonversi ke WebP. Re-encode penuh juga
 * membuang metadata (EXIF/GPS) dan payload tersembunyi dari file unggahan.
 */
final class ImageService
{
    public const DISK = 'public';

    public function storeWebp(UploadedFile $file, string $directory, int $maxSide = 1920, int $quality = 82): string
    {
        if (! function_exists('imagewebp')) {
            throw new ApiException('Server belum mendukung konversi WebP (aktifkan ekstensi GD dengan WebP).', 'IMAGE_UNSUPPORTED', 500);
        }

        $binary = $this->toWebp((string) $file->getRealPath(), $maxSide, $quality);
        $path = trim($directory, '/') . '/' . Str::random(40) . '.webp';

        Storage::disk(self::DISK)->put($path, $binary);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path !== null && $path !== '') {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    private function toWebp(string $path, int $maxSide, int $quality): string
    {
        $data = @file_get_contents($path);
        $image = $data === false ? false : @imagecreatefromstring($data);

        if (! $image instanceof GdImage) {
            throw new ApiException('File gambar tidak valid.', 'INVALID_IMAGE', 422);
        }

        $image = $this->applyOrientation($image, $path);

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = $maxSide / max($width, $height);

        if ($scale < 1) {
            $resized = imagescale($image, (int) round($width * $scale), -1, IMG_BICUBIC);
            $image = $resized instanceof GdImage ? $resized : $image;
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        $ok = imagewebp($image, null, $quality);
        $binary = (string) ob_get_clean();

        if (! $ok || $binary === '') {
            throw new ApiException('Gagal mengonversi gambar ke WebP.', 'IMAGE_CONVERSION_FAILED', 500);
        }

        return $binary;
    }

    private function applyOrientation(GdImage $image, string $path): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated instanceof GdImage ? $rotated : $image;
    }
}
