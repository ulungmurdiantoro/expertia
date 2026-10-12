<?php

use App\Support\SchedulerHeartbeat;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Semua jadwal di bawah butuh cron di server: * * * * * php artisan schedule:run
Schedule::call(fn () => SchedulerHeartbeat::beat())->everyMinute()->name('scheduler-heartbeat');
Schedule::command('articles:publish-scheduled --limit=100')->everyFiveMinutes();
Schedule::command('articles:recalculate-scores --chunk=250')->hourly();
// Memangkas article_views, tracking_events, dan activity_logs lama (lihat prunable() di model).
Schedule::command('model:prune')->daily();
