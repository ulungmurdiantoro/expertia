<?php

namespace App\Http\Middleware;

use App\Models\Article;
use App\Models\Comment;
use App\Models\Report;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $isEditorScope = $user && $user->hasAnyRole(['editor', 'moderator', 'admin', 'super-admin']);

        return [
            ...parent::share($request),
            // Dibutuhkan route() saat SSR (resources/js/ssr.js). SSR hanya terjadi pada
            // muatan halaman pertama, jadi navigasi Inertia berikutnya tidak membawanya.
            'ziggy' => fn () => $request->header('X-Inertia') ? null : [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'auth' => [
                'user' => $user,
                'roles' => $user?->getRoleNames()->values() ?? [],
                'permissions' => $user?->getAllPermissions()->pluck('name')->values() ?? [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            // Hanya untuk tim redaksi; tidak ikut di props halaman publik.
            'moderation' => $isEditorScope ? [
                'pending_reviews' => fn () => Article::where('editorial_status', 'pending_review')->count(),
                'pending_comments' => fn () => Comment::where('status', 'pending')->count(),
                'pending_reports' => fn () => Report::where('status', 'pending')->count(),
            ] : null,
            'notifications' => [
                'unread_count' => fn () => $user ? UserNotification::where('user_id', $user->id)->whereNull('read_at')->count() : 0,
            ],
        ];
    }
}
