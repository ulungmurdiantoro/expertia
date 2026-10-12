<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Penanda bahwa cron `php artisan schedule:run` berjalan di server. Tanpa cron,
 * artikel terjadwal tidak pernah tayang dan log tidak pernah dipangkas; dashboard
 * admin menampilkan peringatan bila penanda ini basi.
 */
class SchedulerHeartbeat
{
    private const KEY = 'scheduler:last_heartbeat';

    private const STALE_AFTER_MINUTES = 10;

    public static function beat(): void
    {
        Cache::forever(self::KEY, now()->toIso8601String());
    }

    public static function lastBeat(): ?Carbon
    {
        $value = Cache::get(self::KEY);

        return $value ? Carbon::parse($value) : null;
    }

    public static function isHealthy(): bool
    {
        $lastBeat = self::lastBeat();

        return $lastBeat !== null && $lastBeat->gt(now()->subMinutes(self::STALE_AFTER_MINUTES));
    }
}
