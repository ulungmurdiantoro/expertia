<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleCardResource;
use App\Http\Resources\ArticleDetailResource;
use App\Models\Article;
use App\Models\ArticleView;
use App\Models\Category;
use App\Services\ArticleCommentThread;
use App\Services\ArticleScoringService;
use App\Services\ArticleSearchService;
use App\Services\EventTrackingService;
use App\Support\BotDetector;
use App\Support\PageMeta;
use App\Support\StructuredData;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function index(Request $request, ArticleSearchService $searchService): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'category' => trim((string) $request->query('category', '')),
            'tag' => trim((string) $request->query('tag', '')),
            'sort' => trim((string) $request->query('sort', 'latest')),
        ];

        $articles = $searchService->queryPublished($filters)
            ->through(fn (Article $article) => ArticleCardResource::make($article)->resolve($request));

        $category = $filters['category'] !== ''
            ? Category::query()->where('slug', $filters['category'])->first(['name', 'slug', 'description'])
            : null;
        $title = $category?->name ?? 'Artikel';
        $subtitle = $category?->description ?: 'Kumpulan artikel yang sudah dipublikasikan di Expertia.';

        return Inertia::render('Public/Articles/Index', [
            'articles' => $articles,
            'filters' => $filters,
            'categories' => $this->categoryOptions(),
            'pageMeta' => [
                'title' => $title,
                'subtitle' => $subtitle,
                'highlight' => $category ? 'Rubrik' : 'Jelajahi',
                'mode' => 'latest',
            ],
            'meta' => PageMeta::make(
                "{$title} — Expertia.id",
                $subtitle,
                // Halaman ke-2 dst. punya canonical sendiri agar artikel di dalamnya tetap ditemukan.
                route('articles.index', array_filter([
                    'category' => $category?->slug,
                    'page' => $articles->currentPage() > 1 ? $articles->currentPage() : null,
                ])),
                // Hasil pencarian dan variasi urutan hanyalah salinan daftar yang sama.
                robots: $filters['q'] !== '' || $filters['tag'] !== '' || ! in_array($filters['sort'], ['', 'latest'], true)
                    ? PageMeta::ROBOTS_NOINDEX
                    : PageMeta::ROBOTS_INDEX,
                jsonLd: [StructuredData::webPage('CollectionPage', $title, $subtitle)],
            ),
        ]);
    }

    public function trending(Request $request, ArticleSearchService $searchService): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'category' => trim((string) $request->query('category', '')),
            'tag' => trim((string) $request->query('tag', '')),
            'sort' => 'trending',
        ];

        $articles = $searchService->queryPublished($filters)
            ->through(fn (Article $article) => ArticleCardResource::make($article)->resolve($request));

        return Inertia::render('Public/Articles/Index', [
            'articles' => $articles,
            'filters' => $filters,
            'categories' => $this->categoryOptions(),
            'pageMeta' => [
                'title' => 'Trending',
                'subtitle' => 'Artikel dengan momentum tertinggi berdasarkan aktivitas terbaru.',
                'highlight' => 'Sedang dibaca',
                'mode' => 'trending',
            ],
            'meta' => PageMeta::make(
                'Trending — Expertia.id',
                'Artikel dengan momentum tertinggi berdasarkan aktivitas terbaru.',
                route('articles.trending'),
            ),
        ]);
    }

    public function feed(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user, 403);

        $q = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));
        $sort = trim((string) $request->query('sort', 'trending'));

        $recommendedIds = $user->recommendationSeeds()
            ->orderByDesc('score')
            ->limit(80)
            ->pluck('article_id');

        $followedAuthorIds = $user->follows()->pluck('users.id');

        $articles = Article::query()
            ->with(['author:id,name,profile_slug', 'category:id,name,slug', 'tags:id,name,slug'])
            ->publiclyVisible()
            ->where(function ($query) use ($recommendedIds, $followedAuthorIds): void {
                $query->whereIn('id', $recommendedIds)
                    ->orWhereIn('user_id', $followedAuthorIds);
            })
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($inner) use ($q): void {
                    $inner->where('title', 'like', "%{$q}%")
                        ->orWhere('excerpt', 'like', "%{$q}%")
                        ->orWhere('content', 'like', "%{$q}%");
                });
            })
            ->when($category !== '', fn ($query) => $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('slug', $category)))
            ->when($sort === 'hot', fn ($query) => $query->orderByDesc('score_hotness'))
            ->when($sort === 'latest', fn ($query) => $query->latest('published_at'))
            ->when($sort !== 'latest' && $sort !== 'hot', fn ($query) => $query->orderByDesc('score_trending')->latest('published_at'))
            ->paginate(12)
            ->through(fn (Article $article) => ArticleCardResource::make($article)->resolve($request));

        return Inertia::render('Public/Articles/Index', [
            'articles' => $articles,
            'filters' => [
                'q' => $q,
                'category' => $category,
                'tag' => '',
                'sort' => $sort,
            ],
            'categories' => $this->categoryOptions(),
            'pageMeta' => [
                'title' => 'Feed Personal',
                'subtitle' => 'Artikel dari penulis yang Anda ikuti dan rekomendasi untuk Anda.',
                'highlight' => 'Untuk Anda',
                'mode' => 'feed',
            ],
            'meta' => PageMeta::make(
                'Feed Personal — Expertia.id',
                'Artikel dari penulis yang Anda ikuti dan rekomendasi untuk Anda.',
                robots: 'noindex, nofollow',
            ),
        ]);
    }

    public function show(Request $request, Article $article, ArticleCommentThread $commentThread): Response
    {
        abort_unless($article->isPubliclyVisible(), 404);

        $article->load(['author:id,name,profile_slug,bio,avatar','category:id,name,slug', 'tags:id,name,slug']);
        $this->trackViewAfterResponse($request, $article);

        $viewer = $request->user();
        $reactionCounts = $article->reactions()
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');
        $viewerReaction = $viewer
            ? $article->reactions()->where('user_id', $viewer->id)->value('type')
            : null;

        return Inertia::render('Public/Articles/Show', [
            'article' => [
                ...ArticleDetailResource::make($article)->resolve($request),
                'reactions' => [
                    'like' => (int) ($reactionCounts['like'] ?? 0),
                    'love' => (int) ($reactionCounts['love'] ?? 0),
                    'insightful' => (int) ($reactionCounts['insightful'] ?? 0),
                    'celebrate' => (int) ($reactionCounts['celebrate'] ?? 0),
                ],
                'can_report' => $viewer ? $viewer->id !== $article->user_id : false,
            ],
            'comments' => $commentThread->forArticle($article, $viewer),
            'related' => $this->relatedArticles($request, $article),
            'viewer' => [
                'is_authenticated' => (bool) $viewer,
                'reaction' => $viewerReaction,
                'bookmarked' => $viewer ? $article->bookmarks()->where('user_id', $viewer->id)->exists() : false,
            ],
            'meta' => $this->articleMeta($article),
        ]);
    }

    /**
     * Meta, JSON-LD (Article + BreadcrumbList), dan tautan versi Markdown untuk AI.
     *
     * @return array<string, mixed>
     */
    private function articleMeta(Article $article): array
    {
        $url = route('articles.show', $article->slug);
        $breadcrumbs = [['name' => 'Beranda', 'url' => url('/')]];
        if ($article->category) {
            $breadcrumbs[] = [
                'name' => $article->category->name,
                'url' => route('articles.index', ['category' => $article->category->slug]),
            ];
        }
        $breadcrumbs[] = ['name' => $article->title, 'url' => $url];

        return PageMeta::make(
            ($article->meta_title ?: $article->title).' — Expertia.id',
            $article->meta_description ?: ($article->excerpt ?: (string) $article->content),
            $article->canonical_url ?: $url,
            $article->thumbnail ? Storage::url($article->thumbnail) : null,
            'article',
            article: [
                'published_time' => $article->published_at?->toIso8601String(),
                'modified_time' => $article->updated_at?->toIso8601String(),
                'section' => $article->category?->name,
                'author' => $article->author?->profile_slug ? route('authors.show', $article->author->profile_slug) : null,
                'tags' => $article->tags->pluck('name')->all(),
            ],
            jsonLd: [StructuredData::article($article), StructuredData::breadcrumbs($breadcrumbs)],
            alternates: [[
                'type' => 'text/markdown',
                'href' => route('articles.markdown', $article->slug),
                'title' => 'Versi Markdown',
            ]],
        );
    }

    /**
     * Tiga artikel terbit lain, rubrik yang sama didahulukan: tautan internal untuk
     * pembaca dan crawler.
     *
     * @return list<array<string, mixed>>
     */
    private function relatedArticles(Request $request, Article $article): array
    {
        return Article::query()
            ->with(['author:id,name,profile_slug', 'category:id,name,slug', 'tags:id,name,slug'])
            ->publiclyVisible()
            ->whereNotNull('published_at')
            ->whereKeyNot($article->id)
            ->orderByRaw('CASE WHEN category_id = ? THEN 0 ELSE 1 END', [$article->category_id])
            ->latest('published_at')
            ->limit(3)
            ->get()
            ->map(fn (Article $related) => ArticleCardResource::make($related)->resolve($request))
            ->all();
    }

    private function categoryOptions(): Collection
    {
        return Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    /**
     * Pencatatan view (3–4 query tulis plus hitung skor) dijalankan setelah respons
     * terkirim agar pembaca tidak menunggu. Bot dan pratinjau tautan tidak dihitung.
     */
    private function trackViewAfterResponse(Request $request, Article $article): void
    {
        if (BotDetector::isBot($request->userAgent())) {
            return;
        }

        $viewerId = $request->user()?->id;
        $sessionId = $request->session()->getId();
        $ipHash = $request->ip() ? hash('sha256', (string) $request->ip()) : null;
        $userAgent = substr((string) $request->userAgent(), 0, 255);

        defer(function () use ($request, $article, $viewerId, $sessionId, $ipHash, $userAgent): void {
            $alreadyCounted = ArticleView::query()
                ->where('article_id', $article->id)
                ->where('viewed_at', '>=', now()->subDay())
                ->when($viewerId, fn ($query) => $query->where('user_id', $viewerId))
                ->when(! $viewerId && $sessionId, fn ($query) => $query->where('session_id', $sessionId))
                ->when(! $viewerId && ! $sessionId && $ipHash, fn ($query) => $query->where('ip_hash', $ipHash))
                ->exists();

            if ($alreadyCounted) {
                return;
            }

            ArticleView::create([
                'article_id' => $article->id,
                'user_id' => $viewerId,
                'session_id' => $sessionId,
                'ip_hash' => $ipHash,
                'user_agent' => $userAgent,
                'viewed_at' => now(),
            ]);

            $article->increment('view_count');
            app(EventTrackingService::class)->track($request, 'article.view', $article, [
                'article_slug' => $article->slug,
            ]);
            app(ArticleScoringService::class)->scoreArticle($article->fresh());
        });
    }
}
