<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Waktu untuk ditampilkan di dashboard. Database dan app.timezone memakai UTC; string
 * "Y-m-d H:i:s" tanpa zona yang dikirim ke halaman harus sudah dalam jam redaksi
 * (config expertia.timezone), karena halaman menampilkannya apa adanya.
 */
class SiteTime
{
    public static function format(?CarbonInterface $value): ?string
    {
        return $value?->copy()->setTimezone(config('expertia.timezone'))->toDateTimeString();
    }
}
