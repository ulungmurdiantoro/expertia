<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use App\Support\SiteTime;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = $request->user()->userNotifications()
            ->latest()
            ->paginate(20)
            ->through(fn ($notification) => [
                'id' => $notification->id,
                'public_id' => $notification->public_id,
                'type' => $notification->type,
                'title' => $notification->title,
                'body' => $notification->body,
                'read_at' => SiteTime::format($notification->read_at),
                'created_at' => SiteTime::format($notification->created_at),
                'data' => $notification->data_json,
            ]);

        return Inertia::render('User/Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, UserNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if ($notification->read_at === null) {
            $notification->update([
                'read_at' => now(),
            ]);
        }

        return back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()
            ->userNotifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
