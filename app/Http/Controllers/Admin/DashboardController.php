<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Report;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'metrics' => [
                'total_users' => User::count(),
                'active_authors' => User::role('author')->where('status', 'active')->count(),
                'published_articles' => Article::where('status', 'published')->count(),
                'pending_reviews' => Article::where('editorial_status', 'pending_review')->count(),
                'pending_reports' => Report::where('status', 'pending')->count(),
                'pending_comments' => Comment::where('status', 'pending')->count(),
            ],
        ]);
    }
}
