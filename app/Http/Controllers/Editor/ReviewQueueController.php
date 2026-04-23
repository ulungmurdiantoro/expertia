<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\ArticleReview;
use App\Models\UserNotification;
use App\Services\ArticleScoringService;
use App\Services\EventTrackingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewQueueController extends Controller
{
    public function index(): Response
    {
        $articles = Article::query()
            ->with('author:id,name,email')
            ->where('editorial_status', 'pending_review')
            ->latest('submitted_at')
            ->paginate(15)
            ->through(fn (Article $article) => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'submitted_at' => optional($article->submitted_at)?->toDateTimeString(),
                'author' => $article->author,
            ]);

        return Inertia::render('Editor/Reviews/Index', [
            'articles' => $articles,
        ]);
    }

    public function approve(
        Article $article,
        EventTrackingService $eventTrackingService,
        ArticleScoringService $articleScoringService
    ): \Illuminate\Http\RedirectResponse
    {
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
            'meta_json' => ['status' => 'published'],
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
        if (!$isScheduledFuture) {
            $articleScoringService->scoreArticle($article->fresh());
        }

        return back()->with(
            'success',
            $isScheduledFuture
                ? 'Artikel disetujui dan dijadwalkan tayang otomatis.'
                : 'Artikel disetujui dan dipublikasikan.'
        );
    }

    public function reject(Request $request, Article $article, EventTrackingService $eventTrackingService): \Illuminate\Http\RedirectResponse
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

    public function requestRevision(Request $request, Article $article, EventTrackingService $eventTrackingService): \Illuminate\Http\RedirectResponse
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
