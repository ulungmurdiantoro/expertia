<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\SiteTime;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class NotificationController extends Controller
{
    /** Jenis notifikasi yang menunjuk ke sebuah artikel (data_json.article_id). */
    private const ARTICLE_TYPES = [
        'article.submitted',
        'article.approved',
        'article.scheduled',
        'article.scheduled.published',
        'article.rejected',
        'article.revision_requested',
        'comment.created',
        'comment.reply.created',
    ];

    public function index(Request $request): Response
    {
        $notifications = $request->user()->userNotifications()
            ->latest()
            ->paginate(20)
            ->through(fn ($notification) => [
                'id' => $notification->id,
                'public_id' => $notification->public_id,
                'type' => $notification->type,
                'title' => $notification->title,
                'body' => $notification->body,
                'read_at' => SiteTime::format($notification->read_at),
                'created_at' => SiteTime::format($notification->created_at),
                'data' => $notification->data_json,
                // Tujuan pastinya ditentukan saat dibuka (open), karena status artikel bisa sudah berubah.
                'has_link' => $this->linkedArticleId($notification) !== null,
            ]);

        return Inertia::render('User/Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, UserNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if ($notification->read_at === null) {
            $notification->update([
                'read_at' => now(),
            ]);
        }

        return back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    /**
     * Tandai dibaca lalu buka konten yang dimaksud notifikasi.
     */
    public function open(Request $request, UserNotification $notification): SymfonyResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        $url = $this->targetUrl($notification, $request->user());

        // Inertia::location (muat penuh) agar #komentar-h di URL tetap dipakai untuk menggulung halaman.
        return $url !== null
            ? Inertia::location($url)
            : back()->with('error', 'Konten untuk notifikasi ini sudah tidak tersedia.');
    }

    private function targetUrl(UserNotification $notification, User $user): ?string
    {
        $articleId = $this->linkedArticleId($notification);
        $article = $articleId ? Article::query()->find($articleId) : null;
        if (! $article) {
            return null;
        }

        $publicUrl = $article->isPubliclyVisible() ? route('articles.show', $article->slug) : null;
        $editUrl = $user->can('view', $article) ? route('author.articles.edit', $article) : null;

        return match ($notification->type) {
            // Untuk editor: halaman preview berisi tombol keputusan.
            'article.submitted' => $user->can('articles.review') ? route('editor.reviews.preview', $article) : null,
            'comment.created', 'comment.reply.created' => $publicUrl ? $publicUrl.'#komentar-h' : null,
            'article.approved', 'article.scheduled.published' => $publicUrl ?? $editUrl,
            // Ditolak, diminta revisi, atau dijadwalkan: penulis membuka naskahnya.
            default => $editUrl ?? $publicUrl,
        };
    }

    private function linkedArticleId(UserNotification $notification): ?int
    {
        $articleId = $notification->data_json['article_id'] ?? null;

        return in_array($notification->type, self::ARTICLE_TYPES, true) && is_numeric($articleId) ? (int) $articleId : null;
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()
            ->userNotifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
