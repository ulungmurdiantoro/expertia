<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Comment;
use App\Models\UserNotification;
use App\Services\ArticleScoringService;
use App\Services\EventTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(
        Request $request,
        Article $article,
        EventTrackingService $eventTrackingService,
        ArticleScoringService $articleScoringService
    ): RedirectResponse
    {
        abort_if($article->status !== 'published', 404);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        $parent = null;
        if (!empty($validated['parent_id'])) {
            $parent = Comment::query()
                ->where('id', $validated['parent_id'])
                ->where('article_id', $article->id)
                ->firstOrFail();

            $validated['parent_id'] = $parent->id;
        }

        $comment = Comment::create([
            'article_id' => $article->id,
            'user_id' => $request->user()->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'content' => $validated['content'],
            'status' => 'pending',
        ]);
        $eventTrackingService->track($request, 'comment.created', $article, [
            'comment_id' => $comment->id,
            'is_reply' => (bool) $comment->parent_id,
        ]);

        if ($article->user_id !== $request->user()->id) {
            UserNotification::create([
                'user_id' => $article->user_id,
                'type' => 'comment.created',
                'title' => 'Komentar baru di artikel Anda',
                'body' => "{$request->user()->name} berkomentar di artikel '{$article->title}'.",
                'data_json' => [
                    'article_id' => $article->id,
                    'article_slug' => $article->slug,
                    'comment_id' => $comment->id,
                ],
            ]);
        }

        if ($parent && $parent->user_id !== $request->user()->id) {
            UserNotification::create([
                'user_id' => $parent->user_id,
                'type' => 'comment.reply.created',
                'title' => 'Balasan komentar baru',
                'body' => "{$request->user()->name} membalas komentar Anda.",
                'data_json' => [
                    'article_id' => $article->id,
                    'article_slug' => $article->slug,
                    'comment_id' => $parent->id,
                    'reply_id' => $comment->id,
                ],
            ]);
        }

        $articleScoringService->scoreArticle($article->fresh());

        return back()->with('success', 'Komentar terkirim dan menunggu moderasi.');
    }

    public function destroy(
        Request $request,
        Article $article,
        Comment $comment,
        EventTrackingService $eventTrackingService,
        ArticleScoringService $articleScoringService
    ): RedirectResponse
    {
        abort_unless($comment->article_id === $article->id, 404);

        $isOwner = $comment->user_id === $request->user()->id;
        $isArticleOwner = $article->user_id === $request->user()->id;
        $canModerate = $request->user()->can('comments.moderate');
        abort_unless($isOwner || $isArticleOwner || $canModerate, 403);

        if (!$comment->trashed()) {
            $wasApproved = $comment->status === 'approved';
            $comment->delete();
            if ($wasApproved && $article->comment_count > 0) {
                $article->decrement('comment_count');
            }
            $eventTrackingService->track($request, 'comment.deleted', $article, [
                'comment_id' => $comment->id,
            ]);
            $articleScoringService->scoreArticle($article->fresh());
        }

        return back()->with('success', 'Komentar dihapus.');
    }
}
