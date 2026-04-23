<?php

namespace App\Services;

use App\Models\Article;
use App\Models\TrackingEvent;
use Illuminate\Http\Request;

class EventTrackingService
{
    public function track(Request $request, string $eventType, ?Article $article = null, array $meta = []): void
    {
        TrackingEvent::create([
            'user_id' => $request->user()?->id,
            'article_id' => $article?->id,
            'event_type' => $eventType,
            'event_context' => $request->route()?->getName(),
            'session_id' => $request->session()->getId(),
            'ip_hash' => $request->ip() ? hash('sha256', (string) $request->ip()) : null,
            'meta_json' => $meta,
            'occurred_at' => now(),
        ]);
    }
}
