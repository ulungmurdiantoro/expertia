<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Support\ImageOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Butuh ekstensi GD. Tanpa GD test dilewati; jalankan dengan:
 * php -d extension=gd artisan test --filter=ImageOptimizerTest
 */
class ImageOptimizerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! ImageOptimizer::isAvailable()) {
            $this->markTestSkipped('Ekstensi GD tidak terpasang.');
        }

        Storage::fake('public');
    }

    private function largePng(int $width = 2400, int $height = 1350): string
    {
        $image = imagecreatetruecolor($width, $height);
        for ($x = 0; $x < $width; $x += 6) {
            imageline($image, $x, 0, $width - $x, $height, imagecolorallocate($image, $x % 255, ($x * 3) % 255, ($x * 7) % 255));
        }
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    public function test_upload_is_resized_and_converted_to_webp(): void
    {
        $png = $this->largePng();
        $file = UploadedFile::fake()->createWithContent('sampul.png', $png);

        $path = app(ImageOptimizer::class)->store($file, 'articles/thumbnails', ImageOptimizer::ARTICLE_THUMBNAIL_WIDTH);

        $this->assertStringEndsWith('.webp', $path);
        $stored = Storage::disk('public')->get($path);
        $this->assertLessThan(strlen($png), strlen($stored));
        $this->assertSame(ImageOptimizer::ARTICLE_THUMBNAIL_WIDTH, getimagesizefromstring($stored)[0]);
    }

    public function test_existing_large_thumbnails_are_optimized_by_command(): void
    {
        Storage::disk('public')->put('articles/thumbnails/lama.png', $this->largePng());
        $article = Article::factory()->published()->create(['thumbnail' => 'articles/thumbnails/lama.png']);
        $updatedAt = $article->fresh()->updated_at;

        $this->artisan('images:optimize', ['--min-kb' => 10])->assertSuccessful();

        $fresh = $article->fresh();
        $this->assertStringEndsWith('.webp', $fresh->thumbnail);
        Storage::disk('public')->assertExists($fresh->thumbnail);
        Storage::disk('public')->assertExists('articles/thumbnails/lama.png');
        $this->assertTrue($fresh->updated_at->equalTo($updatedAt), 'mengganti format gambar tidak mengubah tanggal artikel');
    }

    /**
     * JPEG 2000x1000 dengan kotak merah di kiri atas, plus segmen EXIF berisi tag Orientation.
     */
    private function jpegWithOrientation(int $orientation, string $byteOrder = 'MM'): string
    {
        $image = imagecreatetruecolor(2000, 1000);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imagefilledrectangle($image, 0, 0, 199, 199, imagecolorallocate($image, 255, 0, 0));
        ob_start();
        imagejpeg($image, null, 95);
        $jpeg = (string) ob_get_clean();

        $short = fn (int $value) => pack($byteOrder === 'II' ? 'v' : 'n', $value);
        $long = fn (int $value) => pack($byteOrder === 'II' ? 'V' : 'N', $value);
        // Header TIFF, IFD0 berisi satu entri: tag 0x0112 (Orientation), tipe SHORT, 1 nilai.
        $tiff = $byteOrder.$short(42).$long(8).$short(1)
            .$short(0x0112).$short(3).$long(1).$short($orientation).$short(0)
            .$long(0);
        $segment = "Exif\x00\x00".$tiff;

        return substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($segment) + 2).$segment.substr($jpeg, 2);
    }

    public function test_reads_exif_orientation_in_both_byte_orders(): void
    {
        $this->assertSame(6, ImageOptimizer::jpegOrientation($this->jpegWithOrientation(6, 'MM')));
        $this->assertSame(8, ImageOptimizer::jpegOrientation($this->jpegWithOrientation(8, 'II')));
        $this->assertSame(1, ImageOptimizer::jpegOrientation($this->largePng()));
    }

    public function test_phone_photo_rotation_is_applied_before_exif_is_dropped(): void
    {
        // Orientation 6: kamera menyimpan landscape, tampilan yang benar diputar 90° searah jarum jam.
        $optimized = app(ImageOptimizer::class)->optimize($this->jpegWithOrientation(6), ImageOptimizer::ARTICLE_THUMBNAIL_WIDTH);

        $this->assertNotNull($optimized);
        $result = imagecreatefromstring($optimized['binary']);
        $this->assertSame([1000, 2000], [imagesx($result), imagesy($result)]);

        // Kotak merah di kiri atas berpindah ke kanan atas setelah diputar searah jarum jam.
        $red = fn (int $x, int $y) => (imagecolorat($result, $x, $y) >> 16) & 0xFF;
        $green = fn (int $x, int $y) => (imagecolorat($result, $x, $y) >> 8) & 0xFF;
        $this->assertGreaterThan(200, $red(950, 50));
        $this->assertLessThan(60, $green(950, 50));
        $this->assertGreaterThan(200, $green(50, 50), 'kiri atas sekarang putih');
    }
}
