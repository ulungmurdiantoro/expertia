<?php

namespace App\Http\Resources;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Artikel untuk kartu dan daftar di halaman publik.
 *
 * Tanggal dikirim ISO 8601 dengan zona waktu agar browser pembaca (WIB/WITA/WIT)
 * menampilkan tanggal yang benar.
 *
 * @mixin Article
 */
class ArticleCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'thumbnail_url' => $this->thumbnail ? Storage::url($this->thumbnail) : null,
            'thumbnail_alt' => $this->thumbnail_alt,
            'published_at' => $this->published_at?->toIso8601String(),
            'author' => $this->whenLoaded('author', fn () => $this->author
                ? PublicAuthorResource::make($this->author)->resolve($request)
                : null),
            'category' => $this->whenLoaded('category', fn () => $this->category?->only(['id', 'name', 'slug'])),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags
                ->map(fn ($tag) => $tag->only(['id', 'name', 'slug']))
                ->values()
                ->all()),
        ];
    }
}
