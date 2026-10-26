<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Report;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function storeArticle(Request $request, Article $article): RedirectResponse
    {
        abort_unless($article->isPubliclyVisible(), 404);
        abort_if($article->user_id === $request->user()->id, 403);

        return $this->storeReport($request, $article, 'artikel');
    }

    public function storeComment(Request $request, Article $article, Comment $comment): RedirectResponse
    {
        // Hanya komentar yang tampil di halaman yang bisa dilaporkan.
        abort_unless(
            $article->isPubliclyVisible()
                && $comment->article_id === $article->id
                && Comment::query()->whereKey($comment->id)->visibleInThread()->exists(),
            404,
        );
        Gate::authorize('report', $comment);

        return $this->storeReport($request, $comment, 'komentar');
    }

    /**
     * Satu laporan yang masih pending per pelapor per konten, agar antrean moderasi tidak
     * dipenuhi laporan ganda. Setelah laporan ditangani, pelapor boleh melapor lagi.
     */
    private function storeReport(Request $request, Model $reportable, string $label): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $alreadyPending = Report::query()
            ->where('reporter_id', $request->user()->id)
            ->where('reportable_type', $reportable::class)
            ->where('reportable_id', $reportable->getKey())
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            return back()->with('success', "Anda sudah melaporkan {$label} ini. Laporan sedang ditinjau redaksi.");
        }

        Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => $reportable::class,
            'reportable_id' => $reportable->getKey(),
            'reason' => $validated['reason'],
            'note' => $validated['note'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Laporan '.$label.' berhasil dikirim.');
    }
}
