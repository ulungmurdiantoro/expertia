<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ArticleSearchService
{
    /**
     * @param array{q?:string,category?:string,tag?:string,sort?:string,per_page?:int} $filters
     */
    public function queryPublished(array $filters = []): LengthAwarePaginator
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $category = trim((string) ($filters['category'] ?? ''));
        $tag = trim((string) ($filters['tag'] ?? ''));
        $sort = trim((string) ($filters['sort'] ?? 'latest'));
        $perPage = (int) ($filters['per_page'] ?? 12);
        $perPage = max(1, min(30, $perPage));

        $query = Article::query()
            ->with(['author:id,name,profile_slug', 'category:id,name,slug', 'tags:id,name,slug'])
            ->where('status', 'published')
            ->whereNotNull('published_at');

        if ($q !== '') {
            $query->where(function ($inner) use ($q): void {
                $inner->where('title', 'like', "%{$q}%")
                    ->orWhere('excerpt', 'like', "%{$q}%")
                    ->orWhere('content', 'like', "%{$q}%");
            });
        }

        if ($category !== '') {
            $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('slug', $category));
        }

        if ($tag !== '') {
            $query->whereHas('tags', fn ($tagQuery) => $tagQuery->where('slug', $tag));
        }

        match ($sort) {
            'trending' => $query->orderByDesc('score_trending')->latest('published_at'),
            'hot' => $query->orderByDesc('score_hotness')->latest('published_at'),
            default => $query->latest('published_at'),
        };

        return $query->paginate($perPage)->withQueryString();
    }
}
