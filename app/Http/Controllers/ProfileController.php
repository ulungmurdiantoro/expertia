<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Article;
use App\Models\ArticleReaction;
use App\Models\Comment;
use App\Services\ArticleScoringService;
use App\Support\ImageOptimizer;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail && config('expertia.require_email_verification'),
            'status' => session('status'),
            'canDeleteAccount' => $user->canDeleteOwnAccount(),
            // Hanya akun yang bisa menulis yang punya profil kontributor untuk dilengkapi.
            'contributorProfile' => $user->can('articles.create') ? [
                'position' => $user->position,
                'bio' => $user->bio,
                'bio_max_length' => ProfileUpdateRequest::BIO_MAX_LENGTH,
                'public_url' => $user->profile_slug ? route('authors.show', $user->profile_slug) : null,
            ] : null,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $user->fill(Arr::except($validated, ['avatar', 'remove_avatar']));

        if ($request->boolean('remove_avatar') && $user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->avatar = null;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->avatar = app(ImageOptimizer::class)->store($request->file('avatar'), 'avatars', ImageOptimizer::AVATAR_WIDTH);
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request, ArticleScoringService $scoring): RedirectResponse
    {
        abort_unless($request->user()->canDeleteOwnAccount(), 403);

        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $affectedArticleIds = $user->comments()->withTrashed()->pluck('article_id')
            ->merge($user->bookmarks()->pluck('article_id'))
            ->merge(ArticleReaction::query()->where('user_id', $user->id)->pluck('article_id'))
            ->unique();

        // Cascade parent_id ikut menghapus balasan orang lain di bawah komentar akun ini.
        // Balasan di bawah komentar yang tampil dijadikan komentar biasa agar tidak hilang.
        Comment::query()
            ->whereIn('parent_id', $user->comments()->where('status', 'approved')->select('id'))
            ->where('user_id', '!=', $user->id)
            ->update(['parent_id' => null]);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Article::query()->whereIn('id', $affectedArticleIds)->get()->each(function (Article $article) use ($scoring): void {
            $article->refreshInteractionCounts();
            $scoring->scoreArticle($article);
        });

        return Redirect::to('/');
    }
}
