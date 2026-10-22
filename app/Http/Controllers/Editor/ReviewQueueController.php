<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleDetailResource;
use App\Models\Article;
use App\Models\ArticleReview;
use App\Models\Category;
use App\Services\ArticleCommentThread;
use App\Services\ArticleWorkflow;
use App\Support\SiteTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewQueueController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', 'pending_review'),
            'category' => (string) $request->query('category', ''),
            'age' => (string) $request->query('age', ''),
        ];

        $articles = Article::query()
            ->with([
                'author:id,name,email,profile_slug',
                'category:id,name,slug',
                'latestReview.editor:id,name,email',
            ])
            ->withCount(['comments', 'bookmarks', 'reactions'])
            ->when($filters['status'] !== '', fn ($query) => $query->where('editorial_status', $filters['status']))
            ->when($filters['category'] !== '', fn ($query) => $query->where('category_id', $filters['category']))
            ->when($filters['age'] !== '', function ($query) use ($filters) {
                if ($filters['age'] === 'fresh') {
                    $query->where('submitted_at', '>', now()->subDay());
                }

                if ($filters['age'] === 'waiting_24h') {
                    $query->where('submitted_at', '<=', now()->subDay())
                        ->where('submitted_at', '>', now()->subHours(48));
                }

                if ($filters['age'] === 'waiting_48h') {
                    $query->where('submitted_at', '<=', now()->subHours(48));
                }
            })
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $q = $filters['q'];

                $query->where(function ($inner) use ($q) {
                    $inner->where('title', 'like', "%{$q}%")
                        ->orWhere('excerpt', 'like', "%{$q}%")
                        ->orWhereHas('author', fn ($author) => $author->where('name', 'like', "%{$q}%"));
                });
            })
            ->orderByRaw("CASE WHEN editorial_status = 'pending_review' THEN 0 ELSE 1 END ASC")
            ->oldest('submitted_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Article $article) => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'status' => $article->status,
                'editorial_status' => $article->editorial_status,
                'submitted_at' => SiteTime::format($article->submitted_at),
                'published_at' => SiteTime::format($article->published_at),
                'scheduled_at' => SiteTime::format($article->scheduled_at),
                'reviewed_at' => SiteTime::format($article->reviewed_at),
                'queue_age_hours' => $article->submitted_at ? (int) $article->submitted_at->diffInHours(now()) : null,
                'category' => $article->category,
                'author' => $article->author,
                'engagement' => [
                    'views' => $article->view_count,
                    'comments' => $article->comments_count,
                    'bookmarks' => $article->bookmarks_count,
                    'reactions' => $article->reactions_count,
                ],
                'latest_review' => $article->latestReview ? [
                    'action' => $article->latestReview->action,
                    'note' => $article->latestReview->note,
                    'reviewed_at' => SiteTime::format($article->latestReview->reviewed_at),
                    'editor' => $article->latestReview->editor,
                ] : null,
            ]);

        return Inertia::render('Editor/Reviews/Index', [
            'articles' => $articles,
            'filters' => $filters,
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'queueStats' => [
                'pending' => Article::where('editorial_status', 'pending_review')->count(),
                'approved_today' => ArticleReview::where('action', 'approve')->whereDate('reviewed_at', today())->count(),
                'rejected_today' => ArticleReview::where('action', 'reject')->whereDate('reviewed_at', today())->count(),
                'revision_today' => ArticleReview::where('action', 'revision_requested')->whereDate('reviewed_at', today())->count(),
                'waiting_48h' => Article::where('editorial_status', 'pending_review')
                    ->where('submitted_at', '<=', now()->subHours(48))
                    ->count(),
            ],
        ]);
    }

    public function preview(Request $request, Article $article, ArticleCommentThread $commentThread): Response
    {
        $article->load(['author:id,name,profile_slug,bio,avatar', 'category:id,name,slug', 'tags:id,name,slug']);

        return Inertia::render('Public/Articles/Show', [
            'article' => [
                ...ArticleDetailResource::make($article)->resolve($request),
                'status' => $article->status,
                'editorial_status' => $article->editorial_status,
                'is_preview' => true,
                'reactions' => ['like' => 0, 'love' => 0, 'insightful' => 0, 'celebrate' => 0],
                'can_report' => false,
            ],
            'comments' => $commentThread->forArticle($article, $request->user(), interactive: false),
            'viewer' => [
                'is_authenticated' => false,
                'reaction' => null,
            ],
        ]);
    }

    public function approve(Request $request, Article $article, ArticleWorkflow $workflow): RedirectResponse
    {
        $isScheduled = $workflow->approve($article, $request);

        return back()->with(
            'success',
            $isScheduled
                ? 'Artikel disetujui dan dijadwalkan tayang otomatis.'
                : 'Artikel disetujui dan dipublikasikan.'
        );
    }

    public function reject(Request $request, Article $article, ArticleWorkflow $workflow): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $workflow->reject($article, $request, $validated['note'] ?? null);

        return back()->with('success', 'Artikel ditolak.');
    }

    public function requestRevision(Request $request, Article $article, ArticleWorkflow $workflow): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $workflow->requestRevision($article, $request, $validated['note']);

        return back()->with('success', 'Permintaan revisi dikirim ke author.');
    }
}
