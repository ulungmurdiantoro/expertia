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

        // Hasil encode ulang tidak membawa EXIF, jadi rotasi foto ponsel diterapkan ke pikselnya.
        $image = $this->applyExifOrientation($image, self::jpegOrientation($binary));
        if ($image === false) {
            return null;
        }

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
     * Nilai tag EXIF Orientation (1-8) dari JPEG, atau 1 bila tidak ada. Dibaca langsung
     * dari byte agar tidak bergantung pada ekstensi exif.
     */
    public static function jpegOrientation(string $binary): int
    {
        if (! str_starts_with($binary, "\xFF\xD8")) {
            return 1;
        }

        $length = strlen($binary);
        $offset = 2;

        // Telusuri segmen JPEG sampai APP1 "Exif" atau awal data gambar (SOS).
        while ($offset + 4 <= $length && $binary[$offset] === "\xFF") {
            $marker = ord($binary[$offset + 1]);
            $segmentLength = unpack('n', substr($binary, $offset + 2, 2))[1];

            if ($marker === 0xDA) {
                return 1;
            }

            if ($marker === 0xE1 && substr($binary, $offset + 4, 6) === "Exif\x00\x00") {
                return self::tiffOrientation(substr($binary, $offset + 10, $segmentLength - 8));
            }

            $offset += 2 + $segmentLength;
        }

        return 1;
    }

    private static function tiffOrientation(string $tiff): int
    {
        $byteOrder = substr($tiff, 0, 2);
        if ($byteOrder !== 'II' && $byteOrder !== 'MM') {
            return 1;
        }

        $short = fn (int $at): ?int => strlen($tiff) >= $at + 2 ? unpack($byteOrder === 'II' ? 'v' : 'n', substr($tiff, $at, 2))[1] : null;
        $long = fn (int $at): ?int => strlen($tiff) >= $at + 4 ? unpack($byteOrder === 'II' ? 'V' : 'N', substr($tiff, $at, 4))[1] : null;

        $ifd = $long(4);
        $entries = $ifd === null ? null : $short($ifd);
        if ($entries === null) {
            return 1;
        }

        for ($i = 0; $i < $entries; $i++) {
            $entry = $ifd + 2 + ($i * 12);
            if ($short($entry) === 0x0112) {
                $value = $short($entry + 8);

                return $value !== null && $value >= 1 && $value <= 8 ? $value : 1;
            }
        }

        return 1;
    }

    /**
     * @param  \GdImage  $image
     * @return \GdImage|false
     */
    private function applyExifOrientation($image, int $orientation)
    {
        // imagerotate() memutar berlawanan arah jarum jam.
        return match ($orientation) {
            2 => imageflip($image, IMG_FLIP_HORIZONTAL) ? $image : false,
            3 => imagerotate($image, 180, 0),
            4 => imageflip($image, IMG_FLIP_VERTICAL) ? $image : false,
            5 => imageflip($image, IMG_FLIP_HORIZONTAL) ? imagerotate($image, 90, 0) : false,
            6 => imagerotate($image, -90, 0),
            7 => imageflip($image, IMG_FLIP_HORIZONTAL) ? imagerotate($image, -90, 0) : false,
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
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
