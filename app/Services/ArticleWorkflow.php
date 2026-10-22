<?php

namespace App\Services;

use App\Enums\ArticleStatus;
use App\Enums\EditorialStatus;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\ArticleReview;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Satu-satunya tempat yang mengubah status artikel di alur redaksi:
 * draft → (submit) → pending_review → approve | reject | revision_requested.
 *
 * Transisi yang tidak sah melempar ValidationException pada kunci "status",
 * sehingga Inertia menampilkannya sebagai pesan error, bukan halaman 500.
 */
class ArticleWorkflow
{
    public function __construct(
        private readonly EventTrackingService $events,
        private readonly ArticleScoringService $scoring,
        private readonly AuditLogger $audit,
    ) {}

    public function submit(Article $article, Request $request): void
    {
        $this->guard(
            $article->canBeSubmitted(),
            'Hanya draft atau naskah yang ditolak yang bisa diajukan ke review.',
        );

        $article->update([
            'status' => ArticleStatus::InReview->value,
            'editorial_status' => EditorialStatus::PendingReview->value,
            'submitted_at' => now(),
        ]);

        $reviewers = User::query()
            ->role(['editor', 'admin', 'super-admin'])
            ->where('id', '!=', $article->user_id)
            ->get(['id']);

        foreach ($reviewers as $reviewer) {
            UserNotification::create([
                'user_id' => $reviewer->id,
                'type' => 'article.submitted',
                'title' => 'Artikel baru menunggu review',
                'body' => "Artikel '{$article->title}' telah dikirim untuk review.",
                'data_json' => [
                    'article_id' => $article->id,
                    'article_slug' => $article->slug,
                ],
            ]);
        }

        $this->events->track($request, 'article.submitted_for_review', $article);
    }

    /**
     * @return bool true bila artikel dijadwalkan (belum langsung tayang).
     */
    public function approve(Article $article, Request $request): bool
    {
        $this->guardPendingReview($article);

        $isScheduledFuture = $article->scheduled_at && $article->scheduled_at->isFuture();
        $nextStatus = $isScheduledFuture ? ArticleStatus::Scheduled : ArticleStatus::Published;

        $article->update([
            'status' => $nextStatus->value,
            'editorial_status' => EditorialStatus::Approved->value,
            'reviewed_at' => now(),
            'published_at' => $isScheduledFuture ? null : ($article->published_at ?? now()),
        ]);

        $this->recordReview($article, $request, 'approve', 'editorial.article.approved', meta: ['status' => $nextStatus->value]);

        UserNotification::create([
            'user_id' => $article->user_id,
            'type' => $isScheduledFuture ? 'article.scheduled' : 'article.approved',
            'title' => $isScheduledFuture ? 'Artikel dijadwalkan tayang' : 'Artikel disetujui',
            'body' => $isScheduledFuture
                ? "Artikel '{$article->title}' disetujui dan dijadwalkan tayang pada {$article->scheduled_at?->timezone(config('expertia.timezone'))->format('Y-m-d H:i T')}."
                : "Artikel '{$article->title}' disetujui dan dipublikasikan.",
            'data_json' => [
                'article_id' => $article->id,
                'article_slug' => $article->slug,
                'scheduled_at' => optional($article->scheduled_at)?->toDateTimeString(),
            ],
        ]);

        $this->events->track($request, 'editorial.article.approve', $article);

        if (! $isScheduledFuture) {
            $this->scoring->scoreArticle($article->fresh());
        }

        return $isScheduledFuture;
    }

    public function reject(Article $article, Request $request, ?string $note): void
    {
        $this->guardPendingReview($article);

        $article->update([
            'status' => ArticleStatus::Rejected->value,
            'editorial_status' => EditorialStatus::Rejected->value,
            'reviewed_at' => now(),
        ]);

        $this->recordReview($article, $request, 'reject', 'editorial.article.rejected', $note, ['note' => $note]);

        UserNotification::create([
            'user_id' => $article->user_id,
            'type' => 'article.rejected',
            'title' => 'Artikel ditolak',
            'body' => "Artikel '{$article->title}' ditolak oleh editor.",
            'data_json' => [
                'article_id' => $article->id,
                'article_slug' => $article->slug,
                'note' => $note,
            ],
        ]);

        $this->events->track($request, 'editorial.article.reject', $article, ['note' => $note]);
    }

    public function requestRevision(Article $article, Request $request, string $note): void
    {
        $this->guardPendingReview($article);

        $article->update([
            'status' => ArticleStatus::Draft->value,
            'editorial_status' => EditorialStatus::RevisionRequested->value,
            'reviewed_at' => now(),
        ]);

        $this->recordReview($article, $request, 'revision_requested', 'editorial.article.revision_requested', $note, ['note' => $note]);

        UserNotification::create([
            'user_id' => $article->user_id,
            'type' => 'article.revision_requested',
            'title' => 'Revisi artikel diminta',
            'body' => "Editor meminta revisi untuk artikel '{$article->title}'.",
            'data_json' => [
                'article_id' => $article->id,
                'article_slug' => $article->slug,
                'note' => $note,
            ],
        ]);

        $this->events->track($request, 'editorial.article.request_revision', $article, ['note' => $note]);
    }

    /**
     * Dipanggil scheduler untuk artikel yang sudah disetujui dan waktunya tayang.
     */
    public function publishScheduled(Article $article): void
    {
        if (! $article->hasEditorialStatus(EditorialStatus::Approved) || ! $article->hasStatus(ArticleStatus::Scheduled)) {
            return;
        }

        $article->update([
            'status' => ArticleStatus::Published->value,
            'published_at' => $article->published_at ?? now(),
        ]);

        $this->scoring->scoreArticle($article);

        UserNotification::create([
            'user_id' => $article->user_id,
            'type' => 'article.scheduled.published',
            'title' => 'Artikel terjadwal sudah tayang',
            'body' => "Artikel '{$article->title}' telah dipublikasikan otomatis.",
            'data_json' => [
                'article_id' => $article->id,
                'article_slug' => $article->slug,
            ],
        ]);

        $this->audit->log(
            userId: null,
            action: 'scheduler.article.published',
            subject: $article,
            meta: [
                'scheduled_at' => optional($article->scheduled_at)?->toDateTimeString(),
                'published_at' => optional($article->published_at)?->toDateTimeString(),
            ],
        );
    }

    private function guardPendingReview(Article $article): void
    {
        $this->guard(
            $article->hasEditorialStatus(EditorialStatus::PendingReview),
            'Artikel ini tidak sedang menunggu review, jadi tidak bisa diputuskan. Muat ulang halaman untuk melihat status terbarunya.',
        );
    }

    private function guard(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function recordReview(Article $article, Request $request, string $action, string $logAction, ?string $note = null, array $meta = []): void
    {
        ArticleReview::create([
            'article_id' => $article->id,
            'editor_id' => $request->user()->id,
            'action' => $action,
            'note' => $note,
            'reviewed_at' => now(),
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => $logAction,
            'subject_type' => Article::class,
            'subject_id' => $article->id,
            'meta_json' => $meta,
            'created_at' => now(),
        ]);
    }
}
