<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function storeArticle(Request $request, Article $article): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => Article::class,
            'reportable_id' => $article->id,
            'reason' => $validated['reason'],
            'note' => $validated['note'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Laporan artikel berhasil dikirim.');
    }

    public function storeComment(Request $request, Article $article, Comment $comment): RedirectResponse
    {
        abort_unless($comment->article_id === $article->id, 404);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => Comment::class,
            'reportable_id' => $comment->id,
            'reason' => $validated['reason'],
            'note' => $validated['note'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Laporan komentar berhasil dikirim.');
    }
}
