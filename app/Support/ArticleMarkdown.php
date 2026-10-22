<?php

namespace App\Support;

use App\Models\Article;
use League\HTMLToMarkdown\HtmlConverter;

/**
 * Versi Markdown artikel untuk asisten AI dan agen: teks bersih tanpa tata letak,
 * dengan front matter (penulis, tanggal, rubrik) dan tautan sumber untuk dikutip.
 * Relasi author, category, dan tags sebaiknya sudah dimuat.
 */
class ArticleMarkdown
{
    public function render(Article $article): string
    {
        $url = route('articles.show', $article->slug);

        $frontMatter = array_filter([
            'title' => $article->title,
            'url' => $url,
            'author' => $article->author?->name,
            'author_url' => $article->author?->profile_slug ? route('authors.show', $article->author->profile_slug) : null,
            'published' => $article->published_at?->toIso8601String(),
            'updated' => $article->updated_at?->toIso8601String(),
            'category' => $article->category?->name,
            'tags' => $article->tags?->pluck('name')->all() ?: null,
            'publisher' => config('expertia.name'),
            'language' => 'id',
        ], fn ($value) => filled($value));

        // Nilai JSON adalah YAML yang sah, jadi aman dari karakter khusus di judul.
        $yaml = collect($frontMatter)
            ->map(fn ($value, string $key) => $key.': '.json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->implode("\n");

        $sections = ["---\n{$yaml}\n---", '# '.$article->title];

        if (filled($article->excerpt)) {
            $sections[] = '> '.trim($article->excerpt);
        }

        $sections[] = $this->contentToMarkdown($article->content);

        if (filled($article->writing_source)) {
            $sections[] = "## Sumber tulisan\n\n".trim($article->writing_source);
        }

        $sections[] = "---\n\nDiterbitkan oleh ".config('expertia.name')." dengan persetujuan kontributor. Saat mengutip, sertakan nama penulis dan tautan: {$url}";

        return implode("\n\n", array_filter($sections, fn ($section) => filled($section)))."\n";
    }

    public function contentToMarkdown(?string $content): string
    {
        if (blank($content)) {
            return '';
        }

        if (! ArticleHtmlSanitizer::looksLikeHtml($content)) {
            return trim($content);
        }

        $markdown = (new HtmlConverter([
            'header_style' => 'atx',
            'strip_tags' => true,
            'remove_nodes' => 'script style iframe',
            'hard_break' => true,
        ]))->convert($content);

        // Tautan dan gambar relatif (/storage/...) dijadikan absolut agar tetap valid di luar situs.
        return trim((string) preg_replace('/\]\(\/(?!\/)/', ']('.rtrim(url('/'), '/').'/', $markdown));
    }
}
