<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Di produksi, akses lewat host lain (mis. www.expertia.id) diarahkan permanen ke
 * host APP_URL agar mesin pencari tidak melihat dua salinan setiap halaman.
 * Pengalihan http -> https dilakukan di nginx (lihat deploy/nginx-redirects.conf).
 */
class RedirectToCanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $canonicalHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! app()->isProduction() || ! $canonicalHost || $request->getHost() === $canonicalHost) {
            return $next($request);
        }

        return redirect()->to(rtrim((string) config('app.url'), '/').$request->getRequestUri(), 301);
    }
}
