<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Memperkecil gambar unggahan (lebar maksimum, WebP) agar halaman cepat dimuat.
 * Butuh ekstensi GD (Debian: sudo apt install php8.3-gd); tanpa GD, file asli
 * disimpan apa adanya sehingga unggahan tidak pernah gagal karena optimasi.
 */
class ImageOptimizer
{
    public const ARTICLE_THUMBNAIL_WIDTH = 1600;

    public const AVATAR_WIDTH = 400;

    private const WEBP_QUALITY = 82;

    private const JPEG_QUALITY = 85;

    public static function isAvailable(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatefromstring');
    }

    /**
     * Simpan unggahan ke disk; dioptimasi bila memungkinkan. Mengembalikan path relatif disk.
     */
    public function store(UploadedFile $file, string $directory, int $maxWidth, string $disk = 'public'): string
    {
        $optimized = $this->optimize((string) file_get_contents($file->getRealPath()), $maxWidth);

        if ($optimized === null) {
            return $file->store($directory, $disk);
        }

        return $this->put($optimized, $directory, $disk);
    }

    /**
     * Optimasi file yang sudah ada di disk. Mengembalikan path baru, atau null bila
     * tidak bisa/tidak perlu dioptimasi (path lama tetap dipakai).
     */
    public function optimizeStored(string $path, string $directory, int $maxWidth, string $disk = 'public'): ?string
    {
        if (! Storage::disk($disk)->exists($path)) {
            return null;
        }

        $optimized = $this->optimize((string) Storage::disk($disk)->get($path), $maxWidth);

        return $optimized === null ? null : $this->put($optimized, $directory, $disk);
    }

    /**
     * @return array{binary:string,extension:string}|null
     */
    public function optimize(string $binary, int $maxWidth): ?array
    {
        if (! self::isAvailable() || $binary === '') {
            return null;
        }

        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            return null;
        }

        imagepalettetotruecolor($image);

        $width = imagesx($image);
        if ($width > $maxWidth) {
            $height = (int) round(imagesy($image) * $maxWidth / $width);
            $resized = imagescale($image, $maxWidth, $height, IMG_BICUBIC);
            if ($resized === false) {
                return null;
            }
            $image = $resized;
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        if (function_exists('imagewebp')) {
            imagewebp($image, null, self::WEBP_QUALITY);
            $extension = 'webp';
        } else {
            imagejpeg($image, null, self::JPEG_QUALITY);
            $extension = 'jpg';
        }
        $result = (string) ob_get_clean();

        // Bila hasilnya tidak lebih kecil (gambar sudah kecil), simpan aslinya saja.
        if ($result === '' || strlen($result) >= strlen($binary)) {
            return null;
        }

        return ['binary' => $result, 'extension' => $extension];
    }

    /**
     * @param  array{binary:string,extension:string}  $optimized
     */
    private function put(array $optimized, string $directory, string $disk): string
    {
        $path = trim($directory, '/').'/'.Str::random(40).'.'.$optimized['extension'];
        Storage::disk($disk)->put($path, $optimized['binary']);

        return $path;
    }
}
