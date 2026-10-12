<?php

namespace App\Http\Resources;

use App\Models\Article;
use Illuminate\Http\Request;

/**
 * Artikel lengkap untuk halaman baca publik dan preview editor (keduanya memakai
 * Public/Articles/Show.vue). Data yang bergantung pada pembaca ditambahkan di controller.
 *
 * @mixin Article
 */
class ArticleDetailResource extends ArticleCardResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'content' => $this->content,
            'writing_source' => $this->writing_source,
            'thumbnail_source' => $this->thumbnail_source,
            'comment_count' => $this->comment_count,
        ];
    }
}
