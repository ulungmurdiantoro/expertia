<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

/**
 * Aturan akses artikel di area penulis. Editor dan admin (articles.update.any) boleh
 * mengubah artikel apa pun di status apa pun; author hanya naskah miliknya yang belum
 * masuk review atau belum tayang.
 */
class ArticlePolicy
{
    /**
     * Membuka halaman edit (bisa hanya-baca bila artikel sedang dikunci).
     */
    public function view(User $user, Article $article): bool
    {
        return $user->can('articles.update.any') || $article->user_id === $user->id;
    }

    public function update(User $user, Article $article): bool
    {
        if ($user->can('articles.update.any')) {
            return true;
        }

        return $article->user_id === $user->id
            && $user->can('articles.update.own')
            && $article->isEditableByAuthor();
    }

    public function submit(User $user, Article $article): bool
    {
        $isAllowed = $user->can('articles.update.any')
            || ($article->user_id === $user->id && $user->can('articles.submit'));

        return $isAllowed && $article->canBeSubmitted();
    }
}
