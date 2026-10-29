<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleCardResource;
use App\Models\Article;
use App\Models\Category;
use App\Services\ArticleShowcaseService;
use App\Support\PageMeta;
use App\Support\StructuredData;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $articles = collect();
        $rubricCounts = collect();

        if (Schema::hasTable('articles') && Schema::hasTable('categories')) {
            $articles = Article::query()
                ->with([ArticleShowcaseService::AUTHOR_COLUMNS, 'category:id,name,slug', 'tags:id,name,slug'])
                ->publiclyVisible()
                ->whereNotNull('published_at')
                ->latest('published_at')
                ->limit(8)
                ->get();

            $rubricCounts = Category::query()
                ->where('is_active', true)
                ->withCount([
                    'articles as published_articles_count' => fn ($query) => $query
                        ->publiclyVisible()
                        ->whereNotNull('published_at'),
                ])
                ->pluck('published_articles_count', 'slug');
        }

        $cards = $articles
            ->map(fn (Article $article) => ArticleCardResource::make($article)->resolve())
            ->values();

        return Inertia::render('Welcome', [
            'featured' => $cards->get(0),
            'secondary' => $cards->get(1),
            // Seksi "Terbaru" baru tampil setelah ada lebih dari 4 artikel terbit.
            'latest' => $cards->count() > 4 ? $cards->slice(2)->values() : [],
            'rubricCounts' => $rubricCounts,
            'meta' => PageMeta::make(
                'Expertia.id — Media Kepakaran Indonesia',
                config('expertia.description'),
                url('/'),
                jsonLd: [StructuredData::organization(), StructuredData::website()],
            ),
        ]);
    }
}
