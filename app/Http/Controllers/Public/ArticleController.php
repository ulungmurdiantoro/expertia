<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleView;
use App\Services\ArticleScoringService;
use App\Services\ArticleSearchService;
use App\Services\EventTrackingService;
use Illuminate\Http\Request;
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
            ->through(fn (Article $article) => [
                'id' => $article->id,
                'public_id' => $article->public_id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'thumbnail_url' => $article->thumbnail ? Storage::url($article->thumbnail) : null,
                'published_at' => optional($article->published_at)?->toDateTimeString(),
                'author' => $article->author,
                'category' => $article->category,
                'tags' => $article->tags,
                'view_count' => $article->view_count,
                'comment_count' => $article->comment_count,
                'score_trending' => (float) $article->score_trending,
            ]);

        return Inertia::render('Public/Articles/Index', [
            'articles' => $articles,
            'filters' => $filters,
            'pageMeta' => [
                'title' => 'Artikel',
                'subtitle' => 'Kumpulan artikel yang sudah dipublikasikan di Expertia.',
                'highlight' => 'Jelajahi',
                'mode' => 'latest',
            ],
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
            ->through(fn (Article $article) => [
                'id' => $article->id,
                'public_id' => $article->public_id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'thumbnail_url' => $article->thumbnail ? Storage::url($article->thumbnail) : null,
                'published_at' => optional($article->published_at)?->toDateTimeString(),
                'author' => $article->author,
                'category' => $article->category,
                'tags' => $article->tags,
                'view_count' => $article->view_count,
                'comment_count' => $article->comment_count,
                'score_trending' => (float) $article->score_trending,
            ]);

        return Inertia::render('Public/Articles/Index', [
            'articles' => $articles,
            'filters' => $filters,
            'pageMeta' => [
                'title' => 'Trending',
                'subtitle' => 'Artikel dengan momentum tertinggi berdasarkan aktivitas terbaru.',
                'highlight' => 'Now',
                'mode' => 'trending',
            ],
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
            ->where('status', 'published')
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
            ->through(fn (Article $article) => [
                'id' => $article->id,
                'public_id' => $article->public_id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'thumbnail_url' => $article->thumbnail ? Storage::url($article->thumbnail) : null,
                'published_at' => optional($article->published_at)?->toDateTimeString(),
                'author' => $article->author,
                'category' => $article->category,
                'tags' => $article->tags,
                'view_count' => $article->view_count,
                'comment_count' => $article->comment_count,
                'score_trending' => (float) $article->score_trending,
            ]);

        return Inertia::render('Public/Articles/Index', [
            'articles' => $articles,
            'filters' => [
                'q' => $q,
                'category' => $category,
                'tag' => '',
                'sort' => $sort,
            ],
            'pageMeta' => [
                'title' => 'Feed Personal',
                'subtitle' => 'Kombinasi artikel dari author yang Anda ikuti dan seed rekomendasi.',
                'highlight' => 'For You',
                'mode' => 'feed',
            ],
        ]);
    }

    public function show(Request $request, Article $article, EventTrackingService $eventTrackingService, ArticleScoringService $scoringService): Response
    {
        $article->load(['author:id,name,profile_slug,bio,avatar', 'category:id,name,slug', 'tags:id,name,slug']);

        abort_if($article->status !== 'published', 404);
        $this->trackView($request, $article, $eventTrackingService, $scoringService);
        $article->refresh();

        $viewer = request()->user();
        $reactionCounts = $article->reactions()
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');
        $viewerReaction = $viewer
            ? $article->reactions()->where('user_id', $viewer->id)->value('type')
            : null;
        $comments = $article->comments()
            ->where('status', 'approved')
            ->whereNull('parent_id')
            ->with([
                'author:id,name,profile_slug',
                'replies' => fn ($query) => $query
                    ->where('status', 'approved')
                    ->with('author:id,name,profile_slug'),
            ])
            ->latest()
            ->get()
            ->map(fn ($comment) => [
                'id' => $comment->id,
                'content' => $comment->content,
                'created_at' => $comment->created_at->toDateTimeString(),
                'author' => $comment->author,
                'can_delete' => $viewer
                    ? ($viewer->id === $comment->user_id || $viewer->id === $article->user_id || $viewer->can('comments.moderate'))
                    : false,
                'can_reply' => (bool) $viewer,
                'can_report' => $viewer ? $viewer->id !== $comment->user_id : false,
                'replies' => $comment->replies->map(fn ($reply) => [
                    'id' => $reply->id,
                    'content' => $reply->content,
                    'created_at' => $reply->created_at->toDateTimeString(),
                    'author' => $reply->author,
                    'can_delete' => $viewer
                        ? ($viewer->id === $reply->user_id || $viewer->id === $article->user_id || $viewer->can('comments.moderate'))
                        : false,
                    'can_report' => $viewer ? $viewer->id !== $reply->user_id : false,
                ])->values(),
            ])
            ->values();

        return Inertia::render('Public/Articles/Show', [
            'article' => [
                'id' => $article->id,
                'public_id' => $article->public_id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'content' => $article->content,
                'thumbnail_url' => $article->thumbnail ? Storage::url($article->thumbnail) : null,
                'thumbnail_alt' => $article->thumbnail_alt,
                'published_at' => optional($article->published_at)?->toDateTimeString(),
                'author' => $article->author,
                'category' => $article->category,
                'tags' => $article->tags,
                'view_count' => $article->view_count,
                'comment_count' => $article->comment_count,
                'bookmark_count' => $article->bookmark_count,
                'share_count' => $article->share_count,
                'reaction_count' => $article->like_count,
                'reactions' => [
                    'like' => (int) ($reactionCounts['like'] ?? 0),
                    'love' => (int) ($reactionCounts['love'] ?? 0),
                    'insightful' => (int) ($reactionCounts['insightful'] ?? 0),
                    'celebrate' => (int) ($reactionCounts['celebrate'] ?? 0),
                ],
                'can_report' => $viewer ? $viewer->id !== $article->user_id : false,
            ],
            'comments' => $comments,
            'viewer' => [
                'is_authenticated' => (bool) $viewer,
                'reaction' => $viewerReaction,
            ],
        ]);
    }

    private function trackView(
        Request $request,
        Article $article,
        EventTrackingService $eventTrackingService,
        ArticleScoringService $scoringService
    ): void {
        $viewer = $request->user();
        $sessionId = $request->session()->getId();
        $ipHash = $request->ip() ? hash('sha256', (string) $request->ip()) : null;
        $cutoff = now()->subDay();

        $existing = ArticleView::query()
            ->where('article_id', $article->id)
            ->where('viewed_at', '>=', $cutoff)
            ->when($viewer, fn ($query) => $query->where('user_id', $viewer->id))
            ->when(! $viewer && $sessionId, fn ($query) => $query->where('session_id', $sessionId))
            ->when(! $viewer && ! $sessionId && $ipHash, fn ($query) => $query->where('ip_hash', $ipHash))
            ->exists();

        if ($existing) {
            return;
        }

        ArticleView::create([
            'article_id' => $article->id,
            'user_id' => $viewer?->id,
            'session_id' => $sessionId,
            'ip_hash' => $ipHash,
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'viewed_at' => now(),
        ]);

        $article->increment('view_count');
        $eventTrackingService->track($request, 'article.view', $article, [
            'article_slug' => $article->slug,
        ]);
        $scoringService->scoreArticle($article->fresh());
    }
}
