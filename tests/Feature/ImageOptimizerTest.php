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
}
