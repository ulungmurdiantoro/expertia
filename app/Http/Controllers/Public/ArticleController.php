<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function index(): Response
    {
        $articles = Article::query()
            ->with(['author:id,name,profile_slug', 'category:id,name,slug'])
            ->where('status', 'published')
            ->latest('published_at')
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
                'view_count' => $article->view_count,
                'comment_count' => $article->comment_count,
            ]);

        return Inertia::render('Public/Articles/Index', [
            'articles' => $articles,
        ]);
    }

    public function show(Request $request, Article $article): Response
    {
        $article->load(['author:id,name,profile_slug,bio,avatar', 'category:id,name,slug', 'tags:id,name,slug']);

        abort_if($article->status !== 'published', 404);
        $this->trackView($request, $article);
        $article->refresh();

        $viewer = request()->user();
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
                'can_report' => $viewer ? $viewer->id !== $article->user_id : false,
            ],
            'comments' => $comments,
            'viewer' => [
                'is_authenticated' => (bool) $viewer,
            ],
        ]);
    }

    private function trackView(Request $request, Article $article): void
    {
        $viewer = $request->user();
        $sessionId = $request->session()->getId();
        $ipHash = $request->ip() ? hash('sha256', (string) $request->ip()) : null;
        $cutoff = now()->subDay();

        $existing = ArticleView::query()
            ->where('article_id', $article->id)
            ->where('viewed_at', '>=', $cutoff)
            ->when($viewer, fn ($query) => $query->where('user_id', $viewer->id))
            ->when(!$viewer && $sessionId, fn ($query) => $query->where('session_id', $sessionId))
            ->when(!$viewer && !$sessionId && $ipHash, fn ($query) => $query->where('ip_hash', $ipHash))
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
    }
}
