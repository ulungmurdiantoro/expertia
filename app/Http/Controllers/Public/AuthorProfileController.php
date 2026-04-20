<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AuthorProfileController extends Controller
{
    public function show(User $author): Response
    {
        $author->loadCount('followers');
        $viewer = request()->user();

        $articles = $author->articles()
            ->where('status', 'published')
            ->latest('published_at')
            ->paginate(10)
            ->through(fn ($article) => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'thumbnail_url' => $article->thumbnail ? Storage::url($article->thumbnail) : null,
                'published_at' => optional($article->published_at)?->toDateTimeString(),
            ]);

        return Inertia::render('Public/Authors/Show', [
            'author' => [
                'id' => $author->id,
                'name' => $author->name,
                'username' => $author->username,
                'profile_slug' => $author->profile_slug,
                'bio' => $author->bio,
                'avatar' => $author->avatar,
                'followers_count' => $author->followers_count,
            ],
            'articles' => $articles,
            'viewer' => [
                'is_authenticated' => (bool) $viewer,
                'is_following' => $viewer ? $viewer->follows()->where('author_id', $author->id)->exists() : false,
                'is_own_profile' => $viewer ? $viewer->id === $author->id : false,
            ],
        ]);
    }
}
