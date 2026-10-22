<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identitas situs untuk mesin pencari dan AI
    |--------------------------------------------------------------------------
    |
    | Dipakai structured data (JSON-LD), RSS, llms.txt, dan versi Markdown
    | artikel. Kontak untuk tampilan ada di resources/js/site.js.
    |
    */

    'name' => 'Expertia.id',
    'tagline' => 'Media Kepakaran Indonesia',
    'description' => 'Riset, paten, inovasi, dan perspektif para pakar Indonesia, disajikan dengan jelas untuk masyarakat, industri, dan pengambil keputusan.',
    'publisher' => 'PT Padma Nusa Akademi',
    'logo' => '/assets/logo/LOGO-EXPERTIA-003.png',
    'same_as' => [
        'https://www.instagram.com/expertia.id/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Crawler AI
    |--------------------------------------------------------------------------
    |
    | Crawler pencarian/jawaban AI (OAI-SearchBot, ChatGPT-User, Claude-SearchBot,
    | Claude-User, PerplexityBot, ...) selalu diizinkan di robots.txt.
    |
    | allow_training mengatur crawler yang mengumpulkan data untuk melatih model
    | (GPTBot, ClaudeBot, Google-Extended, CCBot, ...). Hak cipta naskah tetap pada
    | kontributor dan izin yang diberikan ke Expertia adalah izin publikasi; pastikan
    | izin itu mencakup pelatihan AI sebelum membiarkan ini aktif.
    |
    */

    'ai' => [
        'allow_training' => (bool) env('AI_ALLOW_TRAINING', true),
    ],

];
