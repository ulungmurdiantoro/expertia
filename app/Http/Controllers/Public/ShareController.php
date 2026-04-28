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
    public function store(
        Request $request,
        Article $article,
        EventTrackingService $eventTrackingService,
        ArticleScoringService $articleScoringService
    ): JsonResponse {
        abort_if($article->status !== 'published', 404);

        $validated = $request->validate([
            'channel' => ['nullable', 'string', 'max:50'],
        ]);

        $article->increment('share_count');
        $article->refresh();

        $eventTrackingService->track($request, 'article.share', $article, [
            'channel' => $validated['channel'] ?? 'web',
        ]);
        $articleScoringService->scoreArticle($article);

        return response()->json([
            'share_count' => $article->share_count,
        ]);
    }
}
