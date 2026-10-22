<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Support\ArticleMarkdown;
use App\Support\RobotsTxt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Berkas untuk mesin pencari dan AI: robots.txt, sitemap.xml, feed.xml (RSS),
 * llms.txt, llms-full.txt, dan versi Markdown tiap artikel.
 */
class DiscoveryController extends Controller
{
    /** Sitemap, RSS, dan llms.txt di-cache sebentar; artikel baru muncul paling lambat 15 menit. */
    private const CACHE_SECONDS = 900;

    /** Batas artikel di RSS dan llms-full.txt agar ukurannya wajar. */
    private const FULL_TEXT_LIMIT = 200;

    private const CACHE_KEYS = ['discovery:sitemap', 'discovery:feed', 'discovery:llms', 'discovery:llms-full'];

    /**
     * Dipanggil saat artikel ditarik dari publik agar isinya tidak bertahan di RSS
     * dan llms-full.txt sampai cache kedaluwarsa.
     */
    public static function forgetCache(): void
    {
        foreach (self::CACHE_KEYS as $key) {
            Cache::forget($key);
        }
    }

    public function robots(): Response
    {
        return $this->text(RobotsTxt::render());
    }

    public function sitemap(): Response
    {
        $xml = Cache::remember('discovery:sitemap', self::CACHE_SECONDS, function (): string {
            $articles = $this->publishedArticles()->get(['id', 'slug', 'thumbnail', 'thumbnail_alt', 'title', 'updated_at', 'published_at']);
            $latest = $articles->max('updated_at');

            $entries = [
                $this->sitemapEntry(url('/'), $latest),
                $this->sitemapEntry(route('articles.index'), $latest),
                ...collect(['kontributor', 'tentang', 'kebijakan.editorial', 'kebijakan.privasi', 'kontak'])
                    ->map(fn (string $name) => $this->sitemapEntry(route($name))),
                ...$this->categoriesWithArticles()->map(fn (Category $category) => $this->sitemapEntry(
                    route('articles.index', ['category' => $category->slug]),
                )),
                ...$articles->map(fn (Article $article) => $this->sitemapEntry(
                    route('articles.show', $article->slug),
                    $article->updated_at,
                    $article->thumbnail ? url(Storage::url($article->thumbnail)) : null,
                    $article->thumbnail_alt ?: $article->title,
                )),
                ...$this->authorsWithArticles()->map(fn (User $author) => $this->sitemapEntry(
                    route('authors.show', $author->profile_slug),
                )),
            ];

            return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\n"
                .implode("\n", $entries)."\n"
                .'</urlset>'."\n";
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function feed(): Response
    {
        $xml = Cache::remember('discovery:feed', self::CACHE_SECONDS, function (): string {
            $articles = $this->publishedArticles()
                ->with(['author:id,name', 'category:id,name'])
                ->limit(50)
                ->get();

            $items = $articles->map(function (Article $article): string {
                $url = route('articles.show', $article->slug);

                return implode("\n", array_filter([
                    '    <item>',
                    '      <title>'.$this->xml($article->title).'</title>',
                    '      <link>'.$this->xml($url).'</link>',
                    '      <guid isPermaLink="true">'.$this->xml($url).'</guid>',
                    '      <pubDate>'.$article->published_at->toRfc2822String().'</pubDate>',
                    $article->author ? '      <dc:creator>'.$this->xml($article->author->name).'</dc:creator>' : null,
                    $article->category ? '      <category>'.$this->xml($article->category->name).'</category>' : null,
                    '      <description>'.$this->xml((string) $article->excerpt).'</description>',
                    '      <content:encoded><![CDATA['.str_replace(']]>', ']]]]><![CDATA[>', (string) $article->content).']]></content:encoded>',
                    '    </item>',
                ]));
            });

            return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                .'<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:content="http://purl.org/rss/1.0/modules/content/">'."\n"
                ."  <channel>\n"
                .'    <title>'.$this->xml(config('expertia.name')).'</title>'."\n"
                .'    <link>'.$this->xml(url('/')).'</link>'."\n"
                .'    <description>'.$this->xml(config('expertia.description')).'</description>'."\n"
                ."    <language>id</language>\n"
                .'    <atom:link href="'.$this->xml(route('feed')).'" rel="self" type="application/rss+xml" />'."\n"
                .($articles->isNotEmpty() ? '    <lastBuildDate>'.$articles->max('published_at')->toRfc2822String().'</lastBuildDate>'."\n" : '')
                .$items->implode("\n")."\n"
                ."  </channel>\n"
                ."</rss>\n";
        });

        return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    /**
     * Ringkasan situs untuk model bahasa (format llmstxt.org).
     */
    public function llms(): Response
    {
        $text = Cache::remember('discovery:llms', self::CACHE_SECONDS, function (): string {
            $articles = $this->publishedArticles()->with('author:id,name')->limit(100)->get();

            $articleLines = $articles->map(fn (Article $article) => sprintf(
                '- [%s](%s): %s%s',
                $this->markdownText($article->title),
                route('articles.markdown', $article->slug),
                $article->author ? 'oleh '.$article->author->name.'. ' : '',
                Str::limit(Str::squish((string) $article->excerpt), 200),
            ));

            $categoryLines = $this->categoriesWithArticles()->map(fn (Category $category) => sprintf(
                '- [%s](%s)%s',
                $this->markdownText($category->name),
                route('articles.index', ['category' => $category->slug]),
                filled($category->description) ? ': '.Str::squish($category->description) : '',
            ));

            return implode("\n", [
                '# '.config('expertia.name'),
                '',
                '> '.config('expertia.tagline').'. '.config('expertia.description'),
                '',
                'Expertia mengolah hasil riset, paten, inovasi, kajian, dan pengalaman pakar Indonesia menjadi artikel berbahasa Indonesia yang mudah dipahami. Setiap naskah disusun bersama kontributornya dan terbit setelah mendapat persetujuan mereka. AI dipakai sebagai alat bantu penyuntingan, tidak untuk membuat data, kutipan, atau temuan. Saat mengutip, sertakan nama penulis dan tautan ke artikel aslinya.',
                '',
                'Setiap artikel tersedia dalam Markdown dengan menambahkan `.md` di akhir URL-nya.',
                '',
                '## Artikel',
                '',
                ...($articleLines->isNotEmpty() ? $articleLines->all() : ['- Belum ada artikel terbit.']),
                '',
                '## Rubrik',
                '',
                ...($categoryLines->isNotEmpty() ? $categoryLines->all() : ['- Belum ada rubrik dengan artikel terbit.']),
                '',
                '## Tentang Expertia',
                '',
                '- [Tentang Expertia]('.route('tentang').'): misi, cara kerja, dan pengelola ('.config('expertia.publisher').').',
                '- [Kebijakan Editorial]('.route('kebijakan.editorial').'): verifikasi kontributor dan karya, penggunaan AI, persetujuan penulis, koreksi, dan hak jawab.',
                '- [Kebijakan Privasi]('.route('kebijakan.privasi').'): data yang dikumpulkan dan yang tidak pernah ditampilkan.',
                '- [Program Kontributor]('.route('kontributor').'): cara pakar mengirim riset, paten, atau kajian untuk dipublikasikan.',
                '- [Kontak]('.route('kontak').'): redaksi, koreksi, dan kerja sama.',
                '',
                '## Optional',
                '',
                '- [Semua artikel lengkap dalam satu berkas]('.route('llms.full').')',
                '- [Sitemap]('.route('sitemap').')',
                '- [RSS]('.route('feed').')',
                '',
            ]);
        });

        return $this->text($text, 'text/markdown');
    }

    public function llmsFull(ArticleMarkdown $markdown): Response
    {
        $text = Cache::remember('discovery:llms-full', self::CACHE_SECONDS, function () use ($markdown): string {
            $articles = $this->publishedArticles()
                ->with(['author:id,name,profile_slug', 'category:id,name,slug', 'tags:id,name,slug'])
                ->limit(self::FULL_TEXT_LIMIT)
                ->get();

            $header = '# '.config('expertia.name')." — semua artikel\n\n> ".config('expertia.description')."\n";

            return $header."\n".$articles->map(fn (Article $article) => $markdown->render($article))->implode("\n\n");
        });

        return $this->text($text, 'text/markdown');
    }

    public function articleMarkdown(Article $article, ArticleMarkdown $markdown): Response
    {
        abort_unless($article->isPubliclyVisible(), 404);

        $article->load(['author:id,name,profile_slug', 'category:id,name,slug', 'tags:id,name,slug']);

        return $this->text($markdown->render($article), 'text/markdown')
            ->header('Link', '<'.route('articles.show', $article->slug).'>; rel="canonical"');
    }

    /**
     * @return Builder<Article>
     */
    private function publishedArticles(): Builder
    {
        return Article::query()
            ->publiclyVisible()
            ->whereNotNull('published_at')
            ->latest('published_at');
    }

    /**
     * @return Collection<int, Category>
     */
    private function categoriesWithArticles(): Collection
    {
        return Category::query()
            ->where('is_active', true)
            ->whereHas('articles', fn ($query) => $query->publiclyVisible()->whereNotNull('published_at'))
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description']);
    }

    /**
     * @return Collection<int, User>
     */
    private function authorsWithArticles(): Collection
    {
        return User::query()
            ->whereNotNull('profile_slug')
            ->whereHas('articles', fn ($query) => $query->publiclyVisible()->whereNotNull('published_at'))
            ->get(['id', 'profile_slug']);
    }

    private function sitemapEntry(string $url, $lastModified = null, ?string $image = null, ?string $imageTitle = null): string
    {
        return implode("\n", array_filter([
            '  <url>',
            '    <loc>'.$this->xml($url).'</loc>',
            $lastModified ? '    <lastmod>'.$lastModified->toAtomString().'</lastmod>' : null,
            $image ? '    <image:image><image:loc>'.$this->xml($image).'</image:loc><image:title>'.$this->xml((string) $imageTitle).'</image:title></image:image>' : null,
            '  </url>',
        ]));
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function markdownText(string $value): string
    {
        return str_replace(['[', ']'], ['\[', '\]'], $value);
    }

    private function text(string $body, string $type = 'text/plain'): Response
    {
        return response($body, 200, [
            'Content-Type' => $type.'; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
