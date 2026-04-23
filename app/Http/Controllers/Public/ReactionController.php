<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleReaction;
use App\Services\ArticleScoringService;
use App\Services\EventTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReactionController extends Controller
{
    public function store(
        Request $request,
        Article $article,
        EventTrackingService $eventTrackingService,
        ArticleScoringService $articleScoringService
    ): RedirectResponse {
        abort_if($article->status !== 'published', 404);

        $validated = $request->validate([
            'type' => ['required', 'in:like,love,insightful,celebrate'],
        ]);

        ArticleReaction::query()
            ->where('article_id', $article->id)
            ->where('user_id', $request->user()->id)
            ->where('type', '!=', $validated['type'])
            ->delete();

        ArticleReaction::firstOrCreate([
            'article_id' => $article->id,
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
        ]);

        $article->update([
            'like_count' => $article->reactions()->count(),
        ]);
        $eventTrackingService->track($request, 'article.reaction.added', $article, [
            'reaction_type' => $validated['type'],
        ]);
        $articleScoringService->scoreArticle($article->fresh());

        return back()->with('success', 'Reaksi disimpan.');
    }

    public function destroy(
        Request $request,
        Article $article,
        EventTrackingService $eventTrackingService,
        ArticleScoringService $articleScoringService
    ): RedirectResponse {
        abort_if($article->status !== 'published', 404);

        $request->validate([
            'type' => ['nullable', 'in:like,love,insightful,celebrate'],
        ]);

        $reactionType = (string) $request->input('type', '');

        $query = ArticleReaction::query()
            ->where('article_id', $article->id)
            ->where('user_id', $request->user()->id);

        if ($reactionType !== '') {
            $query->where('type', $reactionType);
        }

        $deleted = $query->delete();
        if ($deleted > 0) {
            $article->update([
                'like_count' => $article->reactions()->count(),
            ]);
            $eventTrackingService->track($request, 'article.reaction.removed', $article, [
                'reaction_type' => $reactionType !== '' ? $reactionType : 'all',
            ]);
            $articleScoringService->scoreArticle($article->fresh());
        }

        return back()->with('success', 'Reaksi dihapus.');
    }
}
