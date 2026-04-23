<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackActivityLog
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $shouldTrack = $request->user()
            && in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            && $response->getStatusCode() < 500;

        if (!$shouldTrack) {
            return $response;
        }

        $subject = collect($request->route()?->parameters() ?? [])
            ->first(fn ($param) => $param instanceof Model);

        app(AuditLogger::class)->log(
            userId: $request->user()?->id,
            action: $this->resolveAction($request),
            subject: $subject instanceof Model ? $subject : null,
            meta: [
                'status_code' => $response->getStatusCode(),
                'route_name' => $request->route()?->getName(),
            ],
            request: $request
        );

        return $response;
    }

    private function resolveAction(Request $request): string
    {
        $routeName = (string) $request->route()?->getName();

        if ($routeName !== '') {
            return 'http.'.strtolower($request->method()).'.'.$routeName;
        }

        $path = trim((string) $request->path(), '/');

        return 'http.'.strtolower($request->method()).'.'.($path !== '' ? str_replace('/', '.', $path) : 'root');
    }
}
