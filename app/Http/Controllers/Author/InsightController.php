<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleView;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Comment;
use App\Models\TrackingEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

class InsightController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $days = $this->resolveDays($request);
        $categoryId = $this->resolveCategoryId($request);

        $articleBase = $this->articleBaseQuery($user->id, $categoryId);

        $articleIds = (clone $articleBase)->pluck('id');
        $categories = Category::query()
            ->whereHas('articles', fn ($query) => $query->where('user_id', $user->id))
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])
            ->values();

        $stats = [
            'total_articles' => (clone $articleBase)->count(),
            'published_articles' => (clone $articleBase)->where('status', 'published')->count(),
            'in_review_articles' => (clone $articleBase)->where('editorial_status', 'pending_review')->count(),
            'draft_articles' => (clone $articleBase)->where('status', 'draft')->count(),
            'total_views' => (clone $articleBase)->sum('view_count'),
            'total_comments' => (clone $articleBase)->sum('comment_count'),
            'total_bookmarks' => (clone $articleBase)->sum('bookmark_count'),
            'total_reactions' => (clone $articleBase)->sum('like_count'),
        ];

        $topArticles = Article::query()
            ->whereIn('id', $articleIds)
            ->with('category:id,name,slug')
            ->orderByDesc('score_trending')
            ->limit(8)
            ->get([
                'id',
                'category_id',
                'title',
                'slug',
                'status',
                'view_count',
                'comment_count',
                'bookmark_count',
                'like_count',
                'score_trending',
                'published_at',
            ])
            ->map(fn (Article $article) => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'category' => $article->category,
                'status' => $article->status,
                'view_count' => $article->view_count,
                'comment_count' => $article->comment_count,
                'bookmark_count' => $article->bookmark_count,
                'reaction_count' => $article->like_count,
                'score_trending' => (float) $article->score_trending,
                'published_at' => optional($article->published_at)?->toDateTimeString(),
            ])
            ->values();

        $dailyViews = ArticleView::query()
            ->whereIn('article_id', $articleIds)
            ->where('viewed_at', '>=', now()->subDays($days))
            ->selectRaw("DATE(viewed_at) as day, COUNT(*) as total")
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'day' => $row->day,
                'total' => (int) $row->total,
            ]);

        $dailyInteractions = TrackingEvent::query()
            ->whereIn('article_id', $articleIds)
            ->whereIn('event_type', [
                'article.bookmark',
                'article.reaction.added',
                'comment.created',
            ])
            ->where('occurred_at', '>=', now()->subDays($days))
            ->selectRaw("DATE(occurred_at) as day, COUNT(*) as total")
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'day' => $row->day,
                'total' => (int) $row->total,
            ]);

        $dailyViewsSeries = $this->buildDailySeries($days, $dailyViews->pluck('total', 'day')->all());
        $dailyInteractionsSeries = $this->buildDailySeries($days, $dailyInteractions->pluck('total', 'day')->all());

        $interactionBreakdown = [
            'bookmarks' => Bookmark::query()->whereIn('article_id', $articleIds)->count(),
            'comments' => Comment::query()->whereIn('article_id', $articleIds)->where('status', 'approved')->count(),
            'reactions' => TrackingEvent::query()
                ->whereIn('article_id', $articleIds)
                ->where('event_type', 'article.reaction.added')
                ->count(),
        ];

        $kpiComparison = $this->buildKpiComparison($articleIds, $days);

        return Inertia::render('Author/Insights/Index', [
            'stats' => $stats,
            'top_articles' => $topArticles,
            'daily_views' => $dailyViewsSeries,
            'daily_interactions' => $dailyInteractionsSeries,
            'interaction_breakdown' => $interactionBreakdown,
            'kpi_comparison' => $kpiComparison,
            'categories' => $categories,
            'filters' => [
                'days' => $days,
                'category_id' => $categoryId,
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        $days = $this->resolveDays($request);
        $categoryId = $this->resolveCategoryId($request);
        $articleIds = $this->articleBaseQuery($user->id, $categoryId)->pluck('id');

        $views = ArticleView::query()
            ->whereIn('article_id', $articleIds)
            ->where('viewed_at', '>=', now()->subDays($days))
            ->selectRaw("DATE(viewed_at) as day, COUNT(*) as total")
            ->groupBy('day')
            ->pluck('total', 'day')
            ->all();

        $interactions = TrackingEvent::query()
            ->whereIn('article_id', $articleIds)
            ->whereIn('event_type', ['article.bookmark', 'article.reaction.added', 'comment.created'])
            ->where('occurred_at', '>=', now()->subDays($days))
            ->selectRaw("DATE(occurred_at) as day, COUNT(*) as total")
            ->groupBy('day')
            ->pluck('total', 'day')
            ->all();

        $dailyViewsSeries = $this->buildDailySeries($days, $views);
        $dailyInteractionsSeries = $this->buildDailySeries($days, $interactions);

        $fileName = "creator-insights-summary-{$days}d-".now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($dailyViewsSeries, $dailyInteractionsSeries): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['date', 'views', 'interactions']);
            foreach ($dailyViewsSeries as $index => $row) {
                fputcsv($handle, [
                    $row['day'],
                    $row['total'],
                    $dailyInteractionsSeries[$index]['total'] ?? 0,
                ]);
            }
            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportArticles(Request $request): StreamedResponse
    {
        $user = $request->user();
        $days = $this->resolveDays($request);
        $categoryId = $this->resolveCategoryId($request);

        $articles = $this->articleBaseQuery($user->id, $categoryId)
            ->with('category:id,name,slug')
            ->orderByDesc('score_trending')
            ->get([
                'id',
                'category_id',
                'title',
                'slug',
                'status',
                'view_count',
                'comment_count',
                'bookmark_count',
                'like_count',
                'score_trending',
                'published_at',
            ]);

        $articleIds = $articles->pluck('id');

        $viewsInRange = ArticleView::query()
            ->whereIn('article_id', $articleIds)
            ->where('viewed_at', '>=', now()->subDays($days))
            ->selectRaw('article_id, COUNT(*) as total')
            ->groupBy('article_id')
            ->pluck('total', 'article_id');

        $interactionsInRange = TrackingEvent::query()
            ->whereIn('article_id', $articleIds)
            ->whereIn('event_type', ['article.bookmark', 'article.reaction.added', 'comment.created'])
            ->where('occurred_at', '>=', now()->subDays($days))
            ->selectRaw('article_id, COUNT(*) as total')
            ->groupBy('article_id')
            ->pluck('total', 'article_id');

        $fileName = "creator-insights-articles-{$days}d-".now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($articles, $viewsInRange, $interactionsInRange): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'article_id',
                'title',
                'slug',
                'category',
                'status',
                'published_at',
                'views_total',
                'comments_total',
                'bookmarks_total',
                'reactions_total',
                'score_trending',
                'views_in_range',
                'interactions_in_range',
            ]);

            foreach ($articles as $article) {
                fputcsv($handle, [
                    $article->id,
                    $article->title,
                    $article->slug,
                    $article->category?->name,
                    $article->status,
                    optional($article->published_at)?->toDateTimeString(),
                    $article->view_count,
                    $article->comment_count,
                    $article->bookmark_count,
                    $article->like_count,
                    (float) $article->score_trending,
                    (int) ($viewsInRange[$article->id] ?? 0),
                    (int) ($interactionsInRange[$article->id] ?? 0),
                ]);
            }
            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function resolveDays(Request $request): int
    {
        $days = (int) $request->query('days', 30);
        $allowed = [7, 30, 90];

        return in_array($days, $allowed, true) ? $days : 30;
    }

    private function resolveCategoryId(Request $request): ?int
    {
        $categoryId = (int) $request->query('category_id', 0);

        return $categoryId > 0 ? $categoryId : null;
    }

    /**
     * @param array<string,int|string> $totalsByDate
     * @return array<int,array{day:string,total:int}>
     */
    private function buildDailySeries(int $days, array $totalsByDate): array
    {
        $series = [];
        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $day = now()->subDays($offset)->toDateString();
            $series[] = [
                'day' => $day,
                'total' => (int) ($totalsByDate[$day] ?? 0),
            ];
        }

        return $series;
    }

    private function articleBaseQuery(int $userId, ?int $categoryId = null): Builder
    {
        return Article::query()
            ->where('user_id', $userId)
            ->when($categoryId !== null, fn ($query) => $query->where('category_id', $categoryId));
    }

    private function buildKpiComparison($articleIds, int $days): array
    {
        $periodStart = now()->subDays($days);
        $previousStart = now()->subDays($days * 2);

        $currentArticles = Article::query()
            ->whereIn('id', $articleIds)
            ->where('created_at', '>=', $periodStart)
            ->count();
        $previousArticles = Article::query()
            ->whereIn('id', $articleIds)
            ->whereBetween('created_at', [$previousStart, $periodStart])
            ->count();

        $currentViews = ArticleView::query()
            ->whereIn('article_id', $articleIds)
            ->where('viewed_at', '>=', $periodStart)
            ->count();
        $previousViews = ArticleView::query()
            ->whereIn('article_id', $articleIds)
            ->whereBetween('viewed_at', [$previousStart, $periodStart])
            ->count();

        $currentComments = Comment::query()
            ->whereIn('article_id', $articleIds)
            ->where('status', 'approved')
            ->where('created_at', '>=', $periodStart)
            ->count();
        $previousComments = Comment::query()
            ->whereIn('article_id', $articleIds)
            ->where('status', 'approved')
            ->whereBetween('created_at', [$previousStart, $periodStart])
            ->count();

        $currentBookmarks = Bookmark::query()
            ->whereIn('article_id', $articleIds)
            ->where('created_at', '>=', $periodStart)
            ->count();
        $previousBookmarks = Bookmark::query()
            ->whereIn('article_id', $articleIds)
            ->whereBetween('created_at', [$previousStart, $periodStart])
            ->count();

        $currentReactions = TrackingEvent::query()
            ->whereIn('article_id', $articleIds)
            ->where('event_type', 'article.reaction.added')
            ->where('occurred_at', '>=', $periodStart)
            ->count();
        $previousReactions = TrackingEvent::query()
            ->whereIn('article_id', $articleIds)
            ->where('event_type', 'article.reaction.added')
            ->whereBetween('occurred_at', [$previousStart, $periodStart])
            ->count();

        $currentEngagement = $currentBookmarks + $currentReactions;
        $previousEngagement = $previousBookmarks + $previousReactions;

        return [
            'articles' => $this->toComparisonPayload($currentArticles, $previousArticles),
            'views' => $this->toComparisonPayload($currentViews, $previousViews),
            'comments' => $this->toComparisonPayload($currentComments, $previousComments),
            'engagement' => $this->toComparisonPayload($currentEngagement, $previousEngagement),
        ];
    }

    private function toComparisonPayload(int $current, int $previous): array
    {
        if ($previous === 0) {
            $growth = $current > 0 ? 100.0 : 0.0;
        } else {
            $growth = (($current - $previous) / $previous) * 100;
        }

        $direction = 'flat';
        if ($current > $previous) {
            $direction = 'up';
        } elseif ($current < $previous) {
            $direction = 'down';
        }

        return [
            'current' => $current,
            'previous' => $previous,
            'growth_pct' => round($growth, 1),
            'direction' => $direction,
        ];
    }
}
