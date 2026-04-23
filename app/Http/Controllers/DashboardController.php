<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response|RedirectResponse
    {
        $user = request()->user();

        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('editor')) {
            return redirect()->route('editor.reviews.index');
        }

        if ($user->hasRole('moderator')) {
            return redirect()->route('editor.moderation.comments.index');
        }

        if ($user->hasAnyRole(['author', 'verified-expert'])) {
            return redirect()->route('author.articles.index');
        }

        return Inertia::render('Dashboard');
    }
}
