<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    /**
     * Penulis komentar, pemilik artikel, dan moderator boleh menghapus komentar.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id
            || $comment->article?->user_id === $user->id
            || $user->can('comments.moderate');
    }

    public function report(User $user, Comment $comment): bool
    {
        return $comment->user_id !== $user->id;
    }
}
