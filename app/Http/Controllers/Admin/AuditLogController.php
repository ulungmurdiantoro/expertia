<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $this->extractFilters($request);

        $logs = $this->queryLogs($filters)
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (ActivityLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'subject_type' => class_basename((string) $log->subject_type),
                'subject_id' => $log->subject_id,
                'meta' => $log->meta_json,
                'user' => $log->user,
                'created_at' => optional($log->created_at)?->toDateTimeString(),
            ]);

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'filters' => $filters,
        ]);
    }

    /**
     * @return array{q:string,action:string,subject:string,start_date:string,end_date:string}
     */
    private function extractFilters(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'action' => trim((string) $request->query('action', '')),
            'subject' => trim((string) $request->query('subject', '')),
            'start_date' => trim((string) $request->query('start_date', '')),
            'end_date' => trim((string) $request->query('end_date', '')),
        ];
    }

    /**
     * @param array{q:string,action:string,subject:string,start_date:string,end_date:string} $filters
     */
    private function queryLogs(array $filters): Builder
    {
        return ActivityLog::query()
            ->with('user:id,name,email')
            ->when($filters['action'] !== '', fn ($query) => $query->where('action', 'like', "%{$filters['action']}%"))
            ->when($filters['subject'] !== '', fn ($query) => $query->where('subject_type', 'like', "%{$filters['subject']}%"))
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $q = $filters['q'];

                $query->where(function ($inner) use ($q) {
                    $inner->where('action', 'like', "%{$q}%")
                        ->orWhere('subject_type', 'like', "%{$q}%")
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($filters['start_date'] !== '', fn ($query) => $query->whereDate('created_at', '>=', $filters['start_date']))
            ->when($filters['end_date'] !== '', fn ($query) => $query->whereDate('created_at', '<=', $filters['end_date']));
    }
}
