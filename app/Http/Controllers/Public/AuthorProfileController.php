<?php

namespace App\Http\Controllers\Public;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleCardResource;
use App\Models\Article;
use App\Models\User;
use App\Support\PageMeta;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuthorProfileController extends Controller
{
    public function show(Request $request, User $author): Response
    {
        $author->loadCount('followers');
        $viewer = $request->user();

        $articles = $author->articles()
            ->with(['category:id,name,slug', 'tags:id,name,slug'])
            ->where('status', ArticleStatus::Published->value)
            ->latest('published_at')
            ->paginate(10)
            ->through(fn (Article $article) => ArticleCardResource::make($article)->resolve($request));

        // Hanya data profil publik; email dan nomor WhatsApp tidak pernah dikirim ke halaman publik.
        return Inertia::render('Public/Authors/Show', [
            'author' => [
                'id' => $author->id,
                'name' => $author->name,
                'username' => $author->username,
                'profile_slug' => $author->profile_slug,
                'bio' => $author->bio,
                'avatar' => $author->avatar,
                'avatar_url' => $author->avatar_url,
                'institution' => $author->institution,
                'position' => $author->position,
                'expertise_areas' => collect($author->expertise_areas ?? [])
                    ->map(fn ($area) => trim((string) preg_replace('/^Other:\s*/i', '', (string) $area)))
                    ->filter()
                    ->values(),
                'followers_count' => $author->followers_count,
            ],
            'articles' => $articles,
            'viewer' => [
                'is_authenticated' => (bool) $viewer,
                'is_following' => $viewer ? $viewer->follows()->where('author_id', $author->id)->exists() : false,
                'is_own_profile' => $viewer ? $viewer->id === $author->id : false,
            ],
            'meta' => PageMeta::make(
                "{$author->name} — Expertia.id",
                $author->bio ?: "Profil dan karya {$author->name} di Expertia.",
                route('authors.show', $author->profile_slug),
                $author->avatar_url,
                'profile',
            ),
        ]);
    }
}
