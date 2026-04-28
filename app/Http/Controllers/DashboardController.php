<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response|RedirectResponse
    {
        $user = request()->user();

        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('editor')) {
            return redirect()->route('editor.reviews.index');
        }

        if ($user->hasRole('moderator')) {
            return redirect()->route('editor.moderation.comments.index');
        }

        if ($user->hasAnyRole(['author', 'verified-expert'])) {
            return redirect()->route('author.articles.index');
        }

        $bookmarkedArticleIds = $user->bookmarks()->pluck('article_id');
        $followedAuthorIds = $user->follows()->pluck('users.id');
        $recommendedArticleIds = $user->recommendationSeeds()
            ->orderByDesc('score')
            ->limit(50)
            ->pluck('article_id');

        $recommendedArticles = Article::query()
            ->with(['author:id,name,profile_slug', 'category:id,name,slug'])
            ->where('status', 'published')
            ->where(function ($query) use ($recommendedArticleIds, $followedAuthorIds): void {
                $query->whereIn('id', $recommendedArticleIds)
                    ->orWhereIn('user_id', $followedAuthorIds);
            })
            ->when($bookmarkedArticleIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $bookmarkedArticleIds))
            ->orderByDesc('score_trending')
            ->latest('published_at')
            ->limit(6)
            ->get()
            ->map(fn (Article $article) => $this->articleCard($article));

        if ($recommendedArticles->isEmpty()) {
            $recommendedArticles = Article::query()
                ->with(['author:id,name,profile_slug', 'category:id,name,slug'])
                ->where('status', 'published')
                ->when($bookmarkedArticleIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $bookmarkedArticleIds))
                ->orderByDesc('score_trending')
                ->latest('published_at')
                ->limit(6)
                ->get()
                ->map(fn (Article $article) => $this->articleCard($article));
        }

        return Inertia::render('Dashboard', [
            'stats' => [
                'bookmarks' => $user->bookmarks()->count(),
                'following' => $user->follows()->count(),
                'unread_notifications' => $user->userNotifications()->whereNull('read_at')->count(),
                'viewed_articles' => $user->trackingEvents()
                    ->where('event_type', 'article.view')
                    ->distinct('article_id')
                    ->count('article_id'),
            ],
            'recentBookmarks' => $user->bookmarks()
                ->with(['article.author:id,name,profile_slug', 'article.category:id,name,slug'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn ($bookmark) => [
                    'id' => $bookmark->id,
                    'created_at' => $bookmark->created_at->toDateTimeString(),
                    'article' => $bookmark->article ? $this->articleCard($bookmark->article) : null,
                ])
                ->filter(fn ($bookmark) => $bookmark['article'] !== null)
                ->values(),
            'readingHistory' => $user->trackingEvents()
                ->with(['article.author:id,name,profile_slug', 'article.category:id,name,slug'])
                ->where('event_type', 'article.view')
                ->whereNotNull('article_id')
                ->latest('occurred_at')
                ->limit(6)
                ->get()
                ->unique('article_id')
                ->map(fn ($event) => [
                    'occurred_at' => optional($event->occurred_at)?->toDateTimeString(),
                    'article' => $event->article ? $this->articleCard($event->article) : null,
                ])
                ->filter(fn ($event) => $event['article'] !== null)
                ->values(),
            'followingAuthors' => $user->follows()
                ->withCount([
                    'articles as published_articles_count' => fn ($query) => $query->where('status', 'published'),
                    'followers',
                ])
                ->orderBy('name')
                ->limit(6)
                ->get(['users.id', 'users.name', 'users.profile_slug', 'users.avatar', 'users.bio', 'users.institution'])
                ->map(fn ($author) => [
                    'id' => $author->id,
                    'name' => $author->name,
                    'profile_slug' => $author->profile_slug,
                    'avatar_url' => $author->avatar_url,
                    'bio' => $author->bio,
                    'institution' => $author->institution,
                    'published_articles_count' => $author->published_articles_count,
                    'followers_count' => $author->followers_count,
                ]),
            'recommendedArticles' => $recommendedArticles,
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function articleCard(Article $article): array
    {
        return [
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => $article->excerpt,
            'thumbnail_url' => $article->thumbnail ? Storage::url($article->thumbnail) : null,
            'published_at' => optional($article->published_at)?->toDateTimeString(),
            'author' => $article->author,
            'category' => $article->category,
            'view_count' => $article->view_count,
            'comment_count' => $article->comment_count,
            'score_trending' => (float) $article->score_trending,
        ];
    }
}
