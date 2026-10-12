<?php

namespace App\Services;

use App\Http\Resources\PublicAuthorResource;
use App\Models\Article;
use App\Models\Comment;
use App\Models\User;

/**
 * Komentar yang sudah disetujui beserta balasannya, untuk halaman baca dan preview editor.
 */
class ArticleCommentThread
{
    /**
     * @param  bool  $interactive  false di preview editor: tombol balas/lapor/hapus disembunyikan.
     * @return list<array<string, mixed>>
     */
    public function forArticle(Article $article, ?User $viewer, bool $interactive = true): array
    {
        return $article->comments()
            ->where('status', 'approved')
            ->whereNull('parent_id')
            ->with([
                'author:id,name,profile_slug',
                'replies' => fn ($query) => $query
                    ->where('status', 'approved')
                    ->with('author:id,name,profile_slug'),
            ])
            ->latest()
            ->get()
            ->map(fn (Comment $comment) => [
                ...$this->present($comment, $article, $viewer, $interactive),
                'can_reply' => $interactive && $viewer !== null,
                'replies' => $comment->replies
                    ->map(fn (Comment $reply) => $this->present($reply, $article, $viewer, $interactive))
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Comment $comment, Article $article, ?User $viewer, bool $interactive): array
    {
        // Hindari query tambahan per komentar saat policy membaca pemilik artikel.
        $comment->setRelation('article', $article);

        return [
            'id' => $comment->id,
            'content' => $comment->content,
            'created_at' => $comment->created_at?->toIso8601String(),
            'author' => $comment->author ? PublicAuthorResource::make($comment->author)->resolve() : null,
            'can_delete' => $interactive && $viewer?->can('delete', $comment) === true,
            'can_report' => $interactive && $viewer?->can('report', $comment) === true,
        ];
    }
}
