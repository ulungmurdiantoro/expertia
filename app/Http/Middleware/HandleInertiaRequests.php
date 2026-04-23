<?php

namespace App\Http\Middleware;

use App\Models\Article;
use App\Models\Comment;
use App\Models\Report;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Inertia\Middleware;

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
            'auth' => [
                'user' => $user,
                'roles' => $user?->getRoleNames()->values() ?? [],
                'permissions' => $user?->getAllPermissions()->pluck('name')->values() ?? [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
            'moderation' => [
                'pending_reviews' => fn () => $isEditorScope ? Article::where('editorial_status', 'pending_review')->count() : 0,
                'pending_comments' => fn () => $isEditorScope ? Comment::where('status', 'pending')->count() : 0,
                'pending_reports' => fn () => $isEditorScope ? Report::where('status', 'pending')->count() : 0,
            ],
            'notifications' => [
                'unread_count' => fn () => $user ? UserNotification::where('user_id', $user->id)->whereNull('read_at')->count() : 0,
            ],
        ];
    }
}
