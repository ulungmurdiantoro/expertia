<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
                'read_at' => optional($notification->read_at)?->toDateTimeString(),
                'created_at' => $notification->created_at->toDateTimeString(),
                'data' => $notification->data_json,
            ]);

        return Inertia::render('User/Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }
}
