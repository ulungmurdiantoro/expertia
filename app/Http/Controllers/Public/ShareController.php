<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\ArticleScoringService;
use App\Services\EventTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShareController extends Controller
{
    /**
     * Dipanggil lewat fetch dari tombol "Bagikan" (juga oleh pengunjung yang belum masuk).
     * Satu sesi hanya menambah share_count sekali per artikel agar skor trending
     * tidak bisa digelembungkan dengan klik berulang.
     */
    public function store(
        Request $request,
        Article $article,
        EventTrackingService $eventTrackingService,
        ArticleScoringService $articleScoringService
    ): JsonResponse {
        abort_unless($article->isPubliclyVisible(), 404);

        $validated = $request->validate([
            'channel' => ['nullable', 'string', 'max:50'],
        ]);

        $sharedArticleIds = $request->session()->get('shared_article_ids', []);

        if (! in_array($article->id, $sharedArticleIds, true)) {
            $request->session()->put('shared_article_ids', [...$sharedArticleIds, $article->id]);

            $article->increment('share_count');

            $eventTrackingService->track($request, 'article.share', $article, [
                'channel' => $validated['channel'] ?? 'web',
            ]);
            $articleScoringService->scoreArticle($article->fresh());
        }

        return response()->json([
            'share_count' => (int) $article->fresh()->share_count,
        ]);
    }
}
