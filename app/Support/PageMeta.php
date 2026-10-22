<?php

namespace App\Support;

use Illuminate\Support\Str;

class PageMeta
{
    public const ROBOTS_INDEX = 'index, follow, max-image-preview:large, max-snippet:-1';

    public const ROBOTS_NOINDEX = 'noindex, follow';

    /**
     * Meta tag halaman publik. Dicetak di app.blade.php (bukan oleh Vue) karena
     * WhatsApp, LinkedIn, dan sebagian crawler AI tidak menjalankan JavaScript.
     *
     * @param  array{published_time?:string|null,modified_time?:string|null,section?:string|null,tags?:list<string>,author_url?:string|null}  $article
     * @param  list<array<string, mixed>>  $jsonLd  Objek schema.org; dicetak sebagai <script type="application/ld+json">.
     * @param  list<array{type:string,href:string,title?:string}>  $alternates  <link rel="alternate"> tambahan (mis. versi Markdown).
     * @return array<string, mixed>
     */
    public static function make(
        string $title,
        string $description,
        ?string $url = null,
        ?string $image = null,
        string $type = 'website',
        string $robots = self::ROBOTS_INDEX,
        array $article = [],
        array $jsonLd = [],
        array $alternates = [],
    ): array {
        return [
            'title' => $title,
            'description' => Str::limit(Str::squish(strip_tags($description)), 300),
            'url' => $url ?? url()->current(),
            'image' => url($image ?? config('expertia.logo')),
            'type' => $type,
            'robots' => $robots,
            'article' => array_filter($article, fn ($value) => filled($value)),
            'json_ld' => array_values($jsonLd),
            'alternates' => array_values($alternates),
        ];
    }
}
