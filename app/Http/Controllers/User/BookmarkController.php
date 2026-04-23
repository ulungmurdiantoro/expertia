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
        $bookmarks = $request->user()->bookmarks()
            ->with(['article.author:id,name,profile_slug'])
            ->latest()
            ->paginate(15)
            ->through(fn ($bookmark) => [
                'id' => $bookmark->id,
                'created_at' => $bookmark->created_at->toDateTimeString(),
                'article' => [
                    'title' => $bookmark->article?->title,
                    'slug' => $bookmark->article?->slug,
                    'excerpt' => $bookmark->article?->excerpt,
                    'author' => $bookmark->article?->author,
                ],
            ]);

        return Inertia::render('User/Bookmarks/Index', [
            'bookmarks' => $bookmarks,
        ]);
    }

    public function store(
        Request $request,
        EventTrackingService $eventTrackingService,
        ArticleScoringService $articleScoringService
    ): RedirectResponse
    {
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
    ): RedirectResponse
    {
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
