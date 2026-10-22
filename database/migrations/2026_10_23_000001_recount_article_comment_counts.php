<?php

use App\Models\Comment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyamakan articles.comment_count dengan komentar yang benar-benar tampil
 * (Comment::visibleInThread). Sebelumnya hitungan ditambah/dikurangi manual dan
 * tetap menghitung balasan dari komentar induk yang dihapus atau disembunyikan.
 * Lewat query builder agar updated_at tidak ikut berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('articles') || ! Schema::hasTable('comments')) {
            return;
        }

        DB::table('articles')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $visible = Comment::query()->where('article_id', $row->id)->visibleInThread()->count();

                    if ((int) $row->comment_count !== $visible) {
                        DB::table('articles')->where('id', $row->id)->update(['comment_count' => $visible]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Hitungan lama tidak disimpan dan memang keliru.
    }
};
