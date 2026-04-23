<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModerationController extends Controller
{
    public function comments(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
            'article' => trim((string) $request->query('article', '')),
            'priority' => (string) $request->query('priority', ''),
            'sla' => (string) $request->query('sla', ''),
        ];

        $comments = Comment::query()
            ->with(['author:id,name,email', 'article:id,title,slug'])
            ->whereIn('status', ['pending', 'approved', 'hidden', 'spam'])
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['article'] !== '', fn ($query) => $query->whereHas('article', fn ($article) => $article->where('title', 'like', "%{$filters['article']}%")))
            ->when($filters['priority'] !== '', function ($query) use ($filters) {
                if ($filters['priority'] === 'high') {
                    $query->where('status', 'pending')->where(function ($inner) {
                        foreach ($this->priorityKeywords() as $keyword) {
                            $inner->orWhere('content', 'like', "%{$keyword}%");
                        }
                    });
                }

                if ($filters['priority'] === 'medium') {
                    $query->where('status', 'pending')->where(function ($inner) {
                        foreach ($this->priorityKeywords() as $keyword) {
                            $inner->where('content', 'not like', "%{$keyword}%");
                        }
                    });
                }

                if ($filters['priority'] === 'low') {
                    $query->whereIn('status', ['approved', 'hidden', 'spam']);
                }
            })
            ->when($filters['sla'] !== '', function ($query) use ($filters) {
                if ($filters['sla'] === 'fresh') {
                    $query->where('status', 'pending')->where('created_at', '>', now()->subDay());
                }

                if ($filters['sla'] === 'overdue') {
                    $query->where('status', 'pending')->where('created_at', '<=', now()->subDay());
                }

                if ($filters['sla'] === 'critical') {
                    $query->where('status', 'pending')->where('created_at', '<=', now()->subHours(72));
                }
            })
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $q = $filters['q'];

                $query->where(function ($inner) use ($q) {
                    $inner->where('content', 'like', "%{$q}%")
                        ->orWhereHas('author', fn ($author) => $author->where('name', 'like', "%{$q}%"));
                });
            })
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END ASC")
            ->oldest('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Comment $comment) => [
                'id' => $comment->id,
                'content' => $comment->content,
                'status' => $comment->status,
                'created_at' => $comment->created_at->toDateTimeString(),
                'author' => $comment->author,
                'article' => $comment->article,
                'priority' => $this->resolveCommentPriority($comment),
                'sla' => $this->resolveCommentSla($comment),
            ]);

        return Inertia::render('Editor/Moderation/Comments', [
            'comments' => $comments,
            'filters' => $filters,
        ]);
    }

    public function updateCommentStatus(Request $request, Comment $comment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:approved,hidden,spam,pending'],
        ]);

        $this->transitionCommentStatus($comment, $validated['status'], $request->user()->id);
        $this->logModerationAction(
            userId: $request->user()->id,
            action: 'moderation.comment.status_updated',
            subjectType: Comment::class,
            subjectId: $comment->id,
            meta: ['status' => $validated['status']]
        );

        return back()->with('success', "Status komentar diubah ke {$validated['status']}.");
    }

    public function bulkUpdateCommentStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:comments,id'],
            'status' => ['required', 'in:approved,hidden,spam,pending'],
        ]);

        $comments = Comment::query()
            ->whereIn('id', $validated['ids'])
            ->get();

        foreach ($comments as $comment) {
            $this->transitionCommentStatus($comment, $validated['status'], $request->user()->id);
        }

        $this->logModerationAction(
            userId: $request->user()->id,
            action: 'moderation.comment.bulk_status_updated',
            subjectType: Comment::class,
            subjectId: 0,
            meta: [
                'status' => $validated['status'],
                'ids' => $validated['ids'],
            ]
        );

        return back()->with('success', 'Bulk update komentar berhasil diterapkan.');
    }

    public function reports(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
            'subject' => (string) $request->query('subject', ''),
            'priority' => (string) $request->query('priority', ''),
            'sla' => (string) $request->query('sla', ''),
        ];

        $reports = Report::query()
            ->with(['reporter:id,name,email', 'reportable'])
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['subject'] !== '', fn ($query) => $query->where('reportable_type', $this->subjectToClass($filters['subject'])))
            ->when($filters['priority'] !== '', function ($query) use ($filters) {
                if ($filters['priority'] === 'high') {
                    $query->where('status', 'pending')->where(function ($inner) {
                        foreach ($this->priorityKeywords() as $keyword) {
                            $inner->orWhere('reason', 'like', "%{$keyword}%")
                                ->orWhere('note', 'like', "%{$keyword}%");
                        }
                    });
                }

                if ($filters['priority'] === 'medium') {
                    $query->where('status', 'pending');
                }

                if ($filters['priority'] === 'low') {
                    $query->whereIn('status', ['resolved', 'dismissed']);
                }
            })
            ->when($filters['sla'] !== '', function ($query) use ($filters) {
                if ($filters['sla'] === 'fresh') {
                    $query->where('status', 'pending')->where('created_at', '>', now()->subDay());
                }

                if ($filters['sla'] === 'overdue') {
                    $query->where('status', 'pending')->where('created_at', '<=', now()->subDay());
                }

                if ($filters['sla'] === 'critical') {
                    $query->where('status', 'pending')->where('created_at', '<=', now()->subHours(72));
                }
            })
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $q = $filters['q'];

                $query->where(function ($inner) use ($q) {
                    $inner->where('reason', 'like', "%{$q}%")
                        ->orWhere('note', 'like', "%{$q}%")
                        ->orWhereHas('reporter', fn ($reporter) => $reporter->where('name', 'like', "%{$q}%"));
                });
            })
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END ASC")
            ->oldest('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(function (Report $report): array {
                $subjectType = class_basename($report->reportable_type);
                $subjectKey = $this->classToSubject($report->reportable_type);

                return [
                    'id' => $report->id,
                    'reason' => $report->reason,
                    'note' => $report->note,
                    'status' => $report->status,
                    'created_at' => $report->created_at->toDateTimeString(),
                    'reporter' => $report->reporter,
                    'subject_type' => $subjectType,
                    'subject_key' => $subjectKey,
                    'subject_summary' => $this->resolveSubjectSummary($report),
                    'can_apply_subject_action' => (bool) $report->reportable,
                    'priority' => $this->resolveReportPriority($report),
                    'sla' => $this->resolveReportSla($report),
                ];
            });

        return Inertia::render('Editor/Moderation/Reports', [
            'reports' => $reports,
            'filters' => $filters,
        ]);
    }

    public function showReport(Report $report): Response
    {
        $report->load(['reporter:id,name,email', 'handler:id,name,email', 'reportable']);

        $detail = [
            'id' => $report->id,
            'reason' => $report->reason,
            'note' => $report->note,
            'status' => $report->status,
            'resolution_note' => $report->resolution_note,
            'created_at' => $report->created_at->toDateTimeString(),
            'handled_at' => optional($report->handled_at)?->toDateTimeString(),
            'reporter' => $report->reporter,
            'handler' => $report->handler,
            'subject_type' => class_basename($report->reportable_type),
            'subject_key' => $this->classToSubject($report->reportable_type),
            'subject_summary' => $this->resolveSubjectSummary($report),
            'subject' => $this->resolveSubjectPayload($report),
        ];

        return Inertia::render('Editor/Moderation/ReportShow', [
            'report' => $detail,
        ]);
    }

    public function updateReportStatus(Request $request, Report $report): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,resolved,dismissed'],
            'resolution_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $report->update([
            'status' => $validated['status'],
            'handled_by' => $request->user()->id,
            'handled_at' => now(),
            'resolution_note' => $validated['resolution_note'] ?? null,
        ]);
        $this->logModerationAction(
            userId: $request->user()->id,
            action: 'moderation.report.status_updated',
            subjectType: Report::class,
            subjectId: $report->id,
            meta: ['status' => $validated['status']]
        );

        return back()->with('success', "Status laporan diubah ke {$validated['status']}.");
    }

    public function bulkUpdateReportStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:reports,id'],
            'status' => ['required', 'in:pending,resolved,dismissed'],
            'resolution_note' => ['nullable', 'string', 'max:1000'],
        ]);

        Report::query()
            ->whereIn('id', $validated['ids'])
            ->update([
                'status' => $validated['status'],
                'handled_by' => $request->user()->id,
                'handled_at' => now(),
                'resolution_note' => $validated['resolution_note'] ?? null,
            ]);

        $this->logModerationAction(
            userId: $request->user()->id,
            action: 'moderation.report.bulk_status_updated',
            subjectType: Report::class,
            subjectId: 0,
            meta: [
                'status' => $validated['status'],
                'ids' => $validated['ids'],
            ]
        );

        return back()->with('success', 'Bulk update laporan berhasil diterapkan.');
    }

    public function bulkApplySubjectAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:reports,id'],
            'action' => ['required', 'in:hide_comment,approve_comment,spam_comment,hide_article,archive_article'],
            'resolution_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $reports = Report::query()
            ->with('reportable')
            ->whereIn('id', $validated['ids'])
            ->get();

        $appliedCount = 0;
        foreach ($reports as $report) {
            $this->ensureSubjectActionPermission($validated['action']);

            $applied = $this->applyActionToReport(
                report: $report,
                action: $validated['action'],
                userId: $request->user()->id,
                resolutionNote: $validated['resolution_note'] ?? null
            );

            if ($applied) {
                $appliedCount++;
            }
        }

        $this->logModerationAction(
            userId: $request->user()->id,
            action: 'moderation.report.bulk_subject_action_applied',
            subjectType: Report::class,
            subjectId: 0,
            meta: [
                'action' => $validated['action'],
                'ids' => $validated['ids'],
                'applied_count' => $appliedCount,
            ]
        );

        return back()->with('success', "Bulk subject action selesai. {$appliedCount} laporan diproses.");
    }

    public function applySubjectAction(Request $request, Report $report): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:hide_comment,approve_comment,spam_comment,hide_article,archive_article'],
            'resolution_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $action = $validated['action'];
        $this->ensureSubjectActionPermission($action);
        $applied = $this->applyActionToReport(
            report: $report,
            action: $action,
            userId: $request->user()->id,
            resolutionNote: $validated['resolution_note'] ?? null
        );
        $this->logModerationAction(
            userId: $request->user()->id,
            action: 'moderation.report.subject_action_applied',
            subjectType: Report::class,
            subjectId: $report->id,
            meta: [
                'action' => $action,
                'applied' => $applied,
            ]
        );

        if (!$applied) {
            return back()->with('success', 'Aksi tidak cocok dengan tipe konten terlapor.');
        }

        return back()->with('success', 'Aksi moderasi diterapkan dari laporan.');
    }

    private function transitionCommentStatus(Comment $comment, string $next, int $moderatorId): void
    {
        $previous = $comment->status;

        $comment->update([
            'status' => $next,
            'moderated_by' => $moderatorId,
            'moderated_at' => now(),
        ]);

        $article = $comment->article;
        if ($article) {
            if ($previous !== 'approved' && $next === 'approved') {
                $article->increment('comment_count');
            }

            if ($previous === 'approved' && $next !== 'approved' && $article->comment_count > 0) {
                $article->decrement('comment_count');
            }
        }
    }

    private function resolveSubjectSummary(Report $report): string
    {
        if (!$report->reportable) {
            return 'Konten tidak ditemukan';
        }

        if ($report->reportable_type === Article::class) {
            return "Artikel: {$report->reportable->title}";
        }

        if ($report->reportable_type === Comment::class) {
            return 'Komentar: '.str($report->reportable->content)->limit(80);
        }

        return 'Konten dilaporkan';
    }

    private function resolveSubjectPayload(Report $report): ?array
    {
        if (!$report->reportable) {
            return null;
        }

        if ($report->reportable_type === Article::class) {
            /** @var Article $article */
            $article = $report->reportable;

            return [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'status' => $article->status,
                'visibility' => $article->visibility,
                'excerpt' => $article->excerpt,
            ];
        }

        if ($report->reportable_type === Comment::class) {
            /** @var Comment $comment */
            $comment = $report->reportable;

            return [
                'id' => $comment->id,
                'content' => $comment->content,
                'status' => $comment->status,
                'article_id' => $comment->article_id,
            ];
        }

        return null;
    }

    private function subjectToClass(string $subject): ?string
    {
        return match ($subject) {
            'article' => Article::class,
            'comment' => Comment::class,
            default => null,
        };
    }

    private function classToSubject(?string $class): string
    {
        return match ($class) {
            Article::class => 'article',
            Comment::class => 'comment',
            default => 'unknown',
        };
    }

    private function applyActionToReport(Report $report, string $action, int $userId, ?string $resolutionNote = null): bool
    {
        if (!$report->reportable) {
            return false;
        }

        $applied = false;

        if ($report->reportable_type === Comment::class) {
            /** @var Comment $comment */
            $comment = $report->reportable;

            if ($action === 'hide_comment') {
                $this->transitionCommentStatus($comment, 'hidden', $userId);
                $applied = true;
            }

            if ($action === 'approve_comment') {
                $this->transitionCommentStatus($comment, 'approved', $userId);
                $applied = true;
            }

            if ($action === 'spam_comment') {
                $this->transitionCommentStatus($comment, 'spam', $userId);
                $applied = true;
            }
        }

        if ($report->reportable_type === Article::class) {
            /** @var Article $article */
            $article = $report->reportable;

            if ($action === 'hide_article') {
                $article->update([
                    'visibility' => 'private',
                ]);
                $applied = true;
            }

            if ($action === 'archive_article') {
                $article->update([
                    'status' => 'archived',
                    'visibility' => 'private',
                ]);
                $applied = true;
            }
        }

        if ($applied) {
            $report->update([
                'status' => 'resolved',
                'handled_by' => $userId,
                'handled_at' => now(),
                'resolution_note' => $resolutionNote ?? "Action applied: {$action}",
            ]);
        }

        return $applied;
    }

    private function logModerationAction(
        int $userId,
        string $action,
        string $subjectType,
        int $subjectId,
        array $meta = []
    ): void {
        ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'meta_json' => $meta,
            'created_at' => now(),
        ]);
    }

    private function ensureSubjectActionPermission(string $action): void
    {
        $user = request()->user();

        if (in_array($action, ['archive_article'], true)) {
            abort_unless($user->can('reports.subject_action.archive'), 403);

            return;
        }

        abort_unless($user->can('reports.subject_action.basic'), 403);
    }

    /**
     * @return array<int,string>
     */
    private function priorityKeywords(): array
    {
        return ['spam', 'scam', 'penipuan', 'judi', 'porn', 'kebencian', 'hoax'];
    }

    private function resolveCommentPriority(Comment $comment): string
    {
        if ($comment->status !== 'pending') {
            return 'low';
        }

        $content = str((string) $comment->content)->lower()->toString();
        foreach ($this->priorityKeywords() as $keyword) {
            if (str_contains($content, $keyword)) {
                return 'high';
            }
        }

        return 'medium';
    }

    /**
     * @return array{label:string,hours:int,is_overdue:bool}
     */
    private function resolveCommentSla(Comment $comment): array
    {
        if ($comment->status !== 'pending') {
            return ['label' => 'closed', 'hours' => 0, 'is_overdue' => false];
        }

        $hours = (int) $comment->created_at->diffInHours(now());
        if ($hours >= 72) {
            return ['label' => 'critical', 'hours' => $hours, 'is_overdue' => true];
        }

        if ($hours >= 24) {
            return ['label' => 'overdue', 'hours' => $hours, 'is_overdue' => true];
        }

        return ['label' => 'fresh', 'hours' => $hours, 'is_overdue' => false];
    }

    private function resolveReportPriority(Report $report): string
    {
        if ($report->status !== 'pending') {
            return 'low';
        }

        $payload = str("{$report->reason} {$report->note}")->lower()->toString();
        foreach ($this->priorityKeywords() as $keyword) {
            if (str_contains($payload, $keyword)) {
                return 'high';
            }
        }

        return 'medium';
    }

    /**
     * @return array{label:string,hours:int,is_overdue:bool}
     */
    private function resolveReportSla(Report $report): array
    {
        if ($report->status !== 'pending') {
            return ['label' => 'closed', 'hours' => 0, 'is_overdue' => false];
        }

        $hours = (int) $report->created_at->diffInHours(now());
        if ($hours >= 72) {
            return ['label' => 'critical', 'hours' => $hours, 'is_overdue' => true];
        }

        if ($hours >= 24) {
            return ['label' => 'overdue', 'hours' => $hours, 'is_overdue' => true];
        }

        return ['label' => 'fresh', 'hours' => $hours, 'is_overdue' => false];
    }
}
