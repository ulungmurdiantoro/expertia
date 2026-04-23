<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public function log(
        ?int $userId,
        string $action,
        ?Model $subject = null,
        array $meta = [],
        ?Request $request = null
    ): void {
        ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'meta_json' => [
                ...$meta,
                'path' => $request?->path(),
                'method' => $request?->method(),
                'user_agent' => $request ? substr((string) $request->userAgent(), 0, 255) : null,
            ],
            'ip_hash' => $request?->ip() ? hash('sha256', (string) $request->ip()) : null,
            'created_at' => now(),
        ]);
    }
}
