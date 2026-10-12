<?php

use App\Support\ArticleHtmlSanitizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyaring isi artikel yang tersimpan sebelum model Article menyaring HTML saat
 * menyimpan. Lewat query builder agar updated_at tidak ikut berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('articles')) {
            return;
        }

        $sanitizer = app(ArticleHtmlSanitizer::class);

        DB::table('articles')
            ->whereNotNull('content')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($sanitizer): void {
                foreach ($rows as $row) {
                    $clean = $sanitizer->sanitize($row->content);

                    if ($clean !== $row->content) {
                        DB::table('articles')->where('id', $row->id)->update(['content' => $clean]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Penyaringan tidak bisa dibatalkan; isi lama tidak disimpan.
    }
};
