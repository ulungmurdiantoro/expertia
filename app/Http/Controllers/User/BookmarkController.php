<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Article;
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'article_id' => ['required', 'integer', 'exists:articles,id'],
        ]);

        $request->user()->bookmarks()->firstOrCreate($validated);

        return back()->with('success', 'Artikel ditambahkan ke bookmark.');
    }

    public function destroy(Request $request, Article $article): RedirectResponse
    {
        $request->user()->bookmarks()->where('article_id', $article->id)->delete();

        return back()->with('success', 'Bookmark dihapus.');
    }
}
