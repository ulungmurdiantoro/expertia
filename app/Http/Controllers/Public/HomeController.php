<?php

namespace App\Http\Controllers\Public;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleCardResource;
use App\Models\Article;
use App\Models\Category;
use App\Support\PageMeta;
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
                ->with(['author:id,name,profile_slug', 'category:id,name,slug', 'tags:id,name,slug'])
                ->where('status', ArticleStatus::Published->value)
                ->whereNotNull('published_at')
                ->latest('published_at')
                ->limit(8)
                ->get();

            $rubricCounts = Category::query()
                ->where('is_active', true)
                ->withCount([
                    'articles as published_articles_count' => fn ($query) => $query
                        ->where('status', ArticleStatus::Published->value)
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
                'Riset, paten, inovasi, dan perspektif para pakar Indonesia, disajikan dengan jelas untuk masyarakat, industri, dan pengambil keputusan.',
                url('/'),
            ),
        ]);
    }
}
