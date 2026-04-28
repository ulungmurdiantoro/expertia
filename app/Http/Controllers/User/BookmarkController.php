<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\ArticleScoringService;
use App\Services\EventTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookmarkController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'sort' => trim((string) $request->query('sort', 'latest')),
        ];

        $bookmarks = $request->user()->bookmarks()
            ->with(['article.author:id,name,profile_slug', 'article.category:id,name,slug'])
            ->whereHas('article', function ($query) use ($filters): void {
                $query->where('status', 'published')
                    ->when($filters['q'] !== '', function ($inner) use ($filters): void {
                        $q = $filters['q'];

                        $inner->where(function ($search) use ($q): void {
                            $search->where('title', 'like', "%{$q}%")
                                ->orWhere('excerpt', 'like', "%{$q}%")
                                ->orWhereHas('author', fn ($author) => $author->where('name', 'like', "%{$q}%"));
                        });
                    });
            })
            ->when($filters['sort'] === 'oldest', fn ($query) => $query->oldest())
            ->when($filters['sort'] !== 'oldest', fn ($query) => $query->latest())
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($bookmark) => [
                'id' => $bookmark->id,
                'created_at' => $bookmark->created_at->toDateTimeString(),
                'article' => [
                    'id' => $bookmark->article?->id,
                    'title' => $bookmark->article?->title,
                    'slug' => $bookmark->article?->slug,
                    'excerpt' => $bookmark->article?->excerpt,
                    'published_at' => optional($bookmark->article?->published_at)?->toDateTimeString(),
                    'view_count' => $bookmark->article?->view_count,
                    'comment_count' => $bookmark->article?->comment_count,
                    'author' => $bookmark->article?->author,
                    'category' => $bookmark->article?->category,
                ],
            ]);

        return Inertia::render('User/Bookmarks/Index', [
            'bookmarks' => $bookmarks,
            'filters' => $filters,
            'stats' => [
                'total' => $request->user()->bookmarks()->count(),
                'this_week' => $request->user()->bookmarks()->where('created_at', '>=', now()->subWeek())->count(),
            ],
        ]);
    }

    public function store(
        Request $request,
        EventTrackingService $eventTrackingService,
        ArticleScoringService $articleScoringService
    ): RedirectResponse {
        $validated = $request->validate([
            'article_id' => ['required', 'integer', 'exists:articles,id'],
        ]);

        $bookmark = $request->user()->bookmarks()->firstOrCreate($validated);
        $article = Article::query()->find($validated['article_id']);

        if ($bookmark->wasRecentlyCreated && $article) {
            $article->update([
                'bookmark_count' => $article->bookmarks()->count(),
            ]);
            $eventTrackingService->track($request, 'article.bookmark', $article);
            $articleScoringService->scoreArticle($article->fresh());
        }

        return back()->with('success', 'Artikel ditambahkan ke bookmark.');
    }

    public function destroy(
        Request $request,
        Article $article,
        EventTrackingService $eventTrackingService,
        ArticleScoringService $articleScoringService
    ): RedirectResponse {
        $deleted = $request->user()->bookmarks()->where('article_id', $article->id)->delete();
        if ($deleted > 0) {
            $article->update([
                'bookmark_count' => $article->bookmarks()->count(),
            ]);
            $eventTrackingService->track($request, 'article.unbookmark', $article);
            $articleScoringService->scoreArticle($article->fresh());
        }

        return back()->with('success', 'Bookmark dihapus.');
    }
}
