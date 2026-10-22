<?php

namespace App\Support;

/**
 * Isi robots.txt. Disajikan oleh route /robots.txt dan ditulis ke public/robots.txt
 * saat deploy (php artisan expertia:robots), karena konfigurasi nginx standar
 * Laravel tidak meneruskan /robots.txt ke PHP.
 */
class RobotsTxt
{
    /**
     * Area yang butuh login atau tidak berguna di hasil pencarian.
     * Catatan: /author/ (dashboard penulis) berbeda dengan /authors/ (profil publik).
     */
    private const PRIVATE_PATHS = [
        '/admin',
        '/editor',
        '/author/',
        '/dashboard',
        '/me/',
        '/profile',
        '/login',
        '/register',
        '/forgot-password',
        '/reset-password',
        '/verify-email',
        '/confirm-password',
    ];

    /** Asisten dan mesin jawaban AI yang mengambil halaman untuk dikutip dengan tautan. */
    private const AI_ANSWER_AGENTS = [
        'OAI-SearchBot',
        'ChatGPT-User',
        'Claude-SearchBot',
        'Claude-User',
        'PerplexityBot',
        'Perplexity-User',
        'DuckAssistBot',
        'MistralAI-User',
        'Applebot',
    ];

    /** Crawler yang mengumpulkan data untuk melatih model AI. */
    private const AI_TRAINING_AGENTS = [
        'GPTBot',
        'ClaudeBot',
        'anthropic-ai',
        'Google-Extended',
        'Applebot-Extended',
        'CCBot',
        'meta-externalagent',
        'Amazonbot',
    ];

    public static function render(): string
    {
        // Server uji/staging tidak boleh terindeks.
        if (! app()->isProduction()) {
            return "# Bukan server produksi: jangan diindeks.\nUser-agent: *\nDisallow: /\n";
        }

        $privateRules = implode("\n", array_map(fn (string $path) => "Disallow: {$path}", self::PRIVATE_PATHS));
        $allowTraining = (bool) config('expertia.ai.allow_training');

        $lines = [
            '# '.config('expertia.name').': konten publik terbuka untuk mesin pencari dan asisten AI.',
            '# Ringkasan situs untuk AI: '.route('llms'),
            '# Setiap artikel juga tersedia dalam Markdown: tambahkan .md di akhir URL artikel.',
            '',
            'User-agent: *',
            'Allow: /',
            $privateRules,
            '',
            '# Asisten dan mesin jawaban AI (mengutip dengan tautan ke sumber)',
            ...array_map(fn (string $agent) => "User-agent: {$agent}", self::AI_ANSWER_AGENTS),
            'Allow: /',
            $privateRules,
            '',
            '# Pengumpulan data untuk pelatihan model AI (config expertia.ai.allow_training)',
            ...array_map(fn (string $agent) => "User-agent: {$agent}", self::AI_TRAINING_AGENTS),
            ...($allowTraining ? ['Allow: /', $privateRules] : ['Disallow: /']),
            '',
            'Sitemap: '.route('sitemap'),
            '',
        ];

        return implode("\n", $lines);
    }
}
