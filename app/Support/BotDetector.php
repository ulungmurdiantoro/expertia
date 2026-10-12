<?php

namespace App\Support;

class BotDetector
{
    /**
     * Crawler, pratinjau tautan (WhatsApp, LinkedIn, Telegram), dan klien otomatis
     * tidak dihitung sebagai pembaca agar angka view dan trending tidak menggelembung.
     */
    private const PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|linkedin|telegram|discord|preview|headless|lighthouse|curl|wget|python|httpclient|okhttp|go-http|java\/|monitor|uptime/i';

    public static function isBot(?string $userAgent): bool
    {
        $userAgent = trim((string) $userAgent);

        return $userAgent === '' || preg_match(self::PATTERN, $userAgent) === 1;
    }
}
