<?php

namespace App\Providers;

use App\Support\ArticleHtmlSanitizer;
use App\Support\PublicPagesSsrGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Inertia\Ssr\Gateway;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ArticleHtmlSanitizer::class);
        $this->app->singleton(Gateway::class, PublicPagesSsrGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $this->configureRateLimiting();
        $this->configureCanonicalUrls();
    }

    /**
     * Semua URL buatan aplikasi (canonical, sitemap, RSS, JSON-LD) memakai APP_URL,
     * bukan host atau skema dari request (www, http di belakang proxy).
     */
    private function configureCanonicalUrls(): void
    {
        $appUrl = (string) config('app.url');

        if ($this->app->isProduction() && str_starts_with($appUrl, 'https://')) {
            URL::forceRootUrl($appUrl);
            URL::forceScheme('https');
        }
    }

    /**
     * Batas untuk endpoint publik yang menulis data. Pesan ke pengguna diatur di bootstrap/app.php.
     */
    private function configureRateLimiting(): void
    {
        $actor = fn (Request $request): string => $request->user()?->id
            ? 'user:'.$request->user()->id
            : 'ip:'.$request->ip();

        RateLimiter::for('register', fn (Request $request) => Limit::perHour(10)->by('register:'.$request->ip()));

        RateLimiter::for('comments', fn (Request $request) => [
            Limit::perMinute(5)->by('comments-minute:'.$actor($request)),
            Limit::perDay(100)->by('comments-day:'.$actor($request)),
        ]);

        RateLimiter::for('reports', fn (Request $request) => Limit::perHour(20)->by('reports:'.$actor($request)));

        RateLimiter::for('interactions', fn (Request $request) => Limit::perMinute(60)->by('interactions:'.$actor($request)));

        RateLimiter::for('shares', fn (Request $request) => Limit::perMinute(10)->by('shares:'.$request->ip()));
    }
}
