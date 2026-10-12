<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Menyaring HTML isi artikel (keluaran editor TipTap) sebelum disimpan. Isi artikel
 * dirender dengan v-html, termasuk di preview editor, jadi tanpa penyaringan ini
 * author bisa menyisipkan script yang berjalan di sesi editor atau admin.
 */
class ArticleHtmlSanitizer
{
    private ?HtmlSanitizer $sanitizer = null;

    /**
     * Sama dengan deteksi di Public/Articles/Show.vue: teks tanpa tag dirender
     * sebagai teks biasa (di-escape oleh frontend), jadi tidak perlu disaring.
     */
    public static function looksLikeHtml(?string $value): bool
    {
        return $value !== null && preg_match('/<[a-z][\s\S]*>/i', $value) === 1;
    }

    public function sanitize(?string $html): ?string
    {
        if (! self::looksLikeHtml($html)) {
            return $html;
        }

        return $this->sanitizer()->sanitize($html);
    }

    private function sanitizer(): HtmlSanitizer
    {
        return $this->sanitizer ??= new HtmlSanitizer(
            (new HtmlSanitizerConfig)
                ->allowSafeElements()
                ->allowElement('div')
                ->allowElement('span')
                ->allowElement('aside')
                ->allowElement('figure')
                ->allowElement('figcaption')
                // allowAttribute() menimpa izin per elemen, jadi semua elemen yang boleh ber-class ditulis di sini.
                ->allowAttribute('class', ['div', 'span', 'aside', 'figure', 'p', 'h2', 'h3', 'h4', 'ul', 'ol', 'blockquote'])
                ->allowAttribute('style', ['p', 'h1', 'h2', 'h3', 'h4'])
                ->withAttributeSanitizer(new TextAlignStyleSanitizer)
                ->allowLinkSchemes(['https', 'http', 'mailto'])
                ->allowRelativeLinks()
                ->allowMediaSchemes(['https', 'http'])
                ->allowRelativeMedias()
                ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow')
                // Bawaan Symfony memotong input di 20.000 karakter; artikel panjang bisa melebihinya.
                ->withMaxInputLength(1_000_000)
        );
    }
}
