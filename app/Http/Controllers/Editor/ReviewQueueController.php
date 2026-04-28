<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\ArticleReview;
use App\Models\Category;
use App\Models\UserNotification;
use App\Services\ArticleScoringService;
use App\Services\EventTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
                'submitted_at' => optional($article->submitted_at)?->toDateTimeString(),
                'published_at' => optional($article->published_at)?->toDateTimeString(),
                'scheduled_at' => optional($article->scheduled_at)?->toDateTimeString(),
                'reviewed_at' => optional($article->reviewed_at)?->toDateTimeString(),
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
                    'reviewed_at' => optional($article->latestReview->reviewed_at)?->toDateTimeString(),
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

    public function preview(Article $article): Response
    {
        $article->load(['author:id,name,profile_slug,bio,avatar', 'category:id,name,slug', 'tags:id,name,slug']);

        $reactionCounts = $article->reactions()
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

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
                'can_delete' => false,
                'can_reply' => false,
                'can_report' => false,
                'replies' => $comment->replies->map(fn ($reply) => [
                    'id' => $reply->id,
                    'content' => $reply->content,
                    'created_at' => $reply->created_at->toDateTimeString(),
                    'author' => $reply->author,
                    'can_delete' => false,
                    'can_report' => false,
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
                'status' => $article->status,
                'editorial_status' => $article->editorial_status,
                'is_preview' => true,
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
                'can_report' => false,
            ],
            'comments' => $comments,
            'viewer' => [
                'is_authenticated' => false,
                'reaction' => null,
            ],
        ]);
    }

    public function approve(
        Article $article,
        EventTrackingService $eventTrackingService,
        ArticleScoringService $articleScoringService
    ): RedirectResponse {
        $isScheduledFuture = $article->scheduled_at && $article->scheduled_at->isFuture();
        $nextStatus = $isScheduledFuture ? 'scheduled' : 'published';
        $article->update([
            'status' => $nextStatus,
            'editorial_status' => 'approved',
            'reviewed_at' => now(),
            'published_at' => $isScheduledFuture ? null : ($article->published_at ?? now()),
        ]);

        ArticleReview::create([
            'article_id' => $article->id,
            'editor_id' => request()->user()->id,
            'action' => 'approve',
            'reviewed_at' => now(),
        ]);
        ActivityLog::create([
            'user_id' => request()->user()->id,
            'action' => 'editorial.article.approved',
            'subject_type' => Article::class,
            'subject_id' => $article->id,
            'meta_json' => ['status' => $nextStatus],
            'created_at' => now(),
        ]);

        UserNotification::create([
            'user_id' => $article->user_id,
            'type' => $isScheduledFuture ? 'article.scheduled' : 'article.approved',
            'title' => $isScheduledFuture ? 'Artikel dijadwalkan tayang' : 'Artikel disetujui',
            'body' => $isScheduledFuture
                ? "Artikel '{$article->title}' disetujui dan dijadwalkan tayang pada {$article->scheduled_at?->toDateTimeString()}."
                : "Artikel '{$article->title}' disetujui dan dipublikasikan.",
            'data_json' => [
                'article_id' => $article->id,
                'article_slug' => $article->slug,
                'scheduled_at' => optional($article->scheduled_at)?->toDateTimeString(),
            ],
        ]);
        $eventTrackingService->track(request(), 'editorial.article.approve', $article);
        if (! $isScheduledFuture) {
            $articleScoringService->scoreArticle($article->fresh());
        }

        return back()->with(
            'success',
            $isScheduledFuture
                ? 'Artikel disetujui dan dijadwalkan tayang otomatis.'
                : 'Artikel disetujui dan dipublikasikan.'
        );
    }

    public function reject(Request $request, Article $article, EventTrackingService $eventTrackingService): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $article->update([
            'status' => 'rejected',
            'editorial_status' => 'rejected',
            'reviewed_at' => now(),
        ]);

        ArticleReview::create([
            'article_id' => $article->id,
            'editor_id' => request()->user()->id,
            'action' => 'reject',
            'note' => $validated['note'] ?? null,
            'reviewed_at' => now(),
        ]);
        ActivityLog::create([
            'user_id' => request()->user()->id,
            'action' => 'editorial.article.rejected',
            'subject_type' => Article::class,
            'subject_id' => $article->id,
            'meta_json' => ['note' => $validated['note'] ?? null],
            'created_at' => now(),
        ]);

        UserNotification::create([
            'user_id' => $article->user_id,
            'type' => 'article.rejected',
            'title' => 'Artikel ditolak',
            'body' => "Artikel '{$article->title}' ditolak oleh editor.",
            'data_json' => [
                'article_id' => $article->id,
                'article_slug' => $article->slug,
                'note' => $validated['note'] ?? null,
            ],
        ]);
        $eventTrackingService->track($request, 'editorial.article.reject', $article, [
            'note' => $validated['note'] ?? null,
        ]);

        return back()->with('success', 'Artikel ditolak.');
    }

    public function requestRevision(Request $request, Article $article, EventTrackingService $eventTrackingService): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $article->update([
            'status' => 'draft',
            'editorial_status' => 'revision_requested',
            'reviewed_at' => now(),
        ]);

        ArticleReview::create([
            'article_id' => $article->id,
            'editor_id' => request()->user()->id,
            'action' => 'revision_requested',
            'note' => $validated['note'],
            'reviewed_at' => now(),
        ]);
        ActivityLog::create([
            'user_id' => request()->user()->id,
            'action' => 'editorial.article.revision_requested',
            'subject_type' => Article::class,
            'subject_id' => $article->id,
            'meta_json' => ['note' => $validated['note']],
            'created_at' => now(),
        ]);

        UserNotification::create([
            'user_id' => $article->user_id,
            'type' => 'article.revision_requested',
            'title' => 'Revisi artikel diminta',
            'body' => "Editor meminta revisi untuk artikel '{$article->title}'.",
            'data_json' => [
                'article_id' => $article->id,
                'article_slug' => $article->slug,
                'note' => $validated['note'],
            ],
        ]);
        $eventTrackingService->track($request, 'editorial.article.request_revision', $article, [
            'note' => $validated['note'],
        ]);

        return back()->with('success', 'Permintaan revisi dikirim ke author.');
    }
}
