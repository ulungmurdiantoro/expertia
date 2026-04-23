<?php

namespace App\Services;

use App\Models\Article;

class ArticleScoringService
{
    public function recalculateAll(int $chunk = 200): int
    {
        $processed = 0;

        Article::query()
            ->where('status', 'published')
            ->chunkById($chunk, function ($articles) use (&$processed): void {
                foreach ($articles as $article) {
                    $this->scoreArticle($article);
                    $processed++;
                }
            });

        return $processed;
    }

    public function scoreArticle(Article $article): void
    {
        $publishedAt = $article->published_at ?? $article->created_at ?? now();
        $ageHours = max(1.0, (float) $publishedAt->diffInHours(now()));

        $views = (int) $article->view_count;
        $comments = (int) $article->comment_count;
        $bookmarks = (int) $article->bookmark_count;
        $likes = (int) $article->like_count;
        $shares = (int) $article->share_count;

        $views48h = $article->views()
            ->where('viewed_at', '>=', now()->subHours(48))
            ->count();

        $baseHotness = ($views * 0.35) + ($comments * 2.5) + ($bookmarks * 2.0) + ($likes * 1.5) + ($shares * 3.0);
        $decay = pow($ageHours + 2, 1.2);
        $trending = ($baseHotness / $decay) + ($views48h * 0.5);

        $article->update([
            'score_hotness' => round($baseHotness, 4),
            'score_trending' => round($trending, 4),
        ]);
    }
}
