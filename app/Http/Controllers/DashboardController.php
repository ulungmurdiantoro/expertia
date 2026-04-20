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

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('editor')) {
            return redirect()->route('editor.reviews.index');
        }

        if ($user->hasRole('author')) {
            return redirect()->route('author.articles.index');
        }

        return Inertia::render('Dashboard');
    }
}
