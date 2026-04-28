<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'metrics' => [
                'total_users' => User::count(),
                'active_authors' => User::role('author')->where('status', 'active')->count(),
                'active_editors' => User::role('editor')->where('status', 'active')->count(),
                'published_articles' => Article::where('status', 'published')->count(),
                'draft_articles' => Article::where('status', 'draft')->count(),
                'scheduled_articles' => Article::where('status', 'scheduled')->count(),
                'pending_reviews' => Article::where('editorial_status', 'pending_review')->count(),
                'pending_reports' => Report::where('status', 'pending')->count(),
                'pending_comments' => Comment::where('status', 'pending')->count(),
                'overdue_reviews' => Article::where('editorial_status', 'pending_review')
                    ->where('submitted_at', '<=', now()->subHours(48))
                    ->count(),
                'overdue_reports' => Report::where('status', 'pending')
                    ->where('created_at', '<=', now()->subDay())
                    ->count(),
            ],
            'queues' => [
                'reviews' => $this->reviewQueueSummary(),
                'moderation' => $this->moderationQueueSummary(),
            ],
            'articleStatus' => $this->countByColumn(Article::class, 'status'),
            'editorialStatus' => $this->countByColumn(Article::class, 'editorial_status'),
            'roleDistribution' => Role::query()
                ->withCount('users')
                ->orderByDesc('users_count')
                ->get(['id', 'name'])
                ->map(fn (Role $role) => [
                    'name' => $role->name,
                    'users_count' => $role->users_count,
                ]),
            'topCategories' => Category::query()
                ->withCount('articles')
                ->orderByDesc('articles_count')
                ->limit(6)
                ->get(['id', 'name', 'slug']),
            'topAuthors' => User::query()
                ->withCount([
                    'articles',
                    'articles as published_articles_count' => fn ($query) => $query->where('status', 'published'),
                ])
                ->having('articles_count', '>', 0)
                ->orderByDesc('published_articles_count')
                ->limit(6)
                ->get(['id', 'name', 'email', 'profile_slug'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_slug' => $user->profile_slug,
                    'articles_count' => $user->articles_count,
                    'published_articles_count' => $user->published_articles_count,
                ]),
            'recentArticles' => Article::query()
                ->with(['author:id,name,email', 'category:id,name,slug'])
                ->latest('updated_at')
                ->limit(8)
                ->get()
                ->map(fn (Article $article) => [
                    'id' => $article->id,
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'status' => $article->status,
                    'editorial_status' => $article->editorial_status,
                    'updated_at' => optional($article->updated_at)?->toDateTimeString(),
                    'author' => $article->author,
                    'category' => $article->category,
                ]),
            'recentReports' => Report::query()
                ->with('reporter:id,name,email')
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (Report $report) => [
                    'id' => $report->id,
                    'reason' => $report->reason,
                    'status' => $report->status,
                    'subject_type' => class_basename($report->reportable_type),
                    'reporter' => $report->reporter,
                    'created_at' => optional($report->created_at)?->toDateTimeString(),
                ]),
            'recentActivity' => ActivityLog::query()
                ->with('user:id,name,email')
                ->latest('created_at')
                ->limit(8)
                ->get()
                ->map(fn (ActivityLog $log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'subject_type' => class_basename((string) $log->subject_type),
                    'subject_id' => $log->subject_id,
                    'user' => $log->user,
                    'created_at' => optional($log->created_at)?->toDateTimeString(),
                ]),
        ]);
    }

    /**
     * @return array<string,int>
     */
    private function countByColumn(string $modelClass, string $column): array
    {
        return $modelClass::query()
            ->select($column, DB::raw('count(*) as total'))
            ->groupBy($column)
            ->pluck('total', $column)
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /**
     * @return array<string,int>
     */
    private function reviewQueueSummary(): array
    {
        $base = Article::query()->where('editorial_status', 'pending_review');

        return [
            'fresh' => (clone $base)->where('submitted_at', '>', now()->subDay())->count(),
            'waiting_24h' => (clone $base)
                ->where('submitted_at', '<=', now()->subDay())
                ->where('submitted_at', '>', now()->subHours(48))
                ->count(),
            'waiting_48h' => (clone $base)->where('submitted_at', '<=', now()->subHours(48))->count(),
        ];
    }

    /**
     * @return array<string,int>
     */
    private function moderationQueueSummary(): array
    {
        return [
            'pending_comments' => Comment::where('status', 'pending')->count(),
            'hidden_comments' => Comment::where('status', 'hidden')->count(),
            'spam_comments' => Comment::where('status', 'spam')->count(),
            'pending_reports' => Report::where('status', 'pending')->count(),
            'resolved_reports' => Report::where('status', 'resolved')->count(),
            'dismissed_reports' => Report::where('status', 'dismissed')->count(),
        ];
    }
}
