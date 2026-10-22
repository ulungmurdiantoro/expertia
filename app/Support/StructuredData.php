<?php

namespace App\Support;

use App\Models\Article;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Objek schema.org (JSON-LD) untuk mesin pencari dan AI: siapa penerbitnya,
 * siapa penulisnya, kapan terbit, dan apa kepakarannya.
 */
class StructuredData
{
    /**
     * @return array<string, mixed>
     */
    public static function organization(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'NewsMediaOrganization',
            '@id' => url('/').'#organization',
            'name' => config('expertia.name'),
            'url' => url('/'),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => url(config('expertia.logo')),
            ],
            'description' => config('expertia.description'),
            'sameAs' => config('expertia.same_as'),
            'parentOrganization' => [
                '@type' => 'Organization',
                'name' => config('expertia.publisher'),
            ],
            'publishingPrinciples' => route('kebijakan.editorial'),
            'ethicsPolicy' => route('kebijakan.editorial'),
            'correctionsPolicy' => route('kebijakan.editorial').'#koreksi',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => url('/').'#website',
            'name' => config('expertia.name'),
            'url' => url('/'),
            'inLanguage' => 'id-ID',
            'publisher' => ['@id' => url('/').'#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => route('articles.index').'?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * Halaman statis (Tentang, Kontak, kebijakan, program kontributor).
     *
     * @return array<string, mixed>
     */
    public static function webPage(string $type, string $name, string $description, ?string $url = null): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => $type,
            'name' => $name,
            'description' => $description,
            'url' => $url ?? url()->current(),
            'inLanguage' => 'id-ID',
            'isPartOf' => ['@id' => url('/').'#website'],
            'publisher' => ['@id' => url('/').'#organization'],
        ];
    }

    /**
     * Article dengan author, publisher, dan tanggal; relasi author, category,
     * dan tags sebaiknya sudah dimuat.
     *
     * @return array<string, mixed>
     */
    public static function article(Article $article): array
    {
        $url = route('articles.show', $article->slug);

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            '@id' => $url.'#article',
            'headline' => Str::limit($article->title, 107),
            'description' => $article->meta_description ?: $article->excerpt,
            'image' => $article->thumbnail ? [url(Storage::url($article->thumbnail))] : null,
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => ($article->updated_at ?? $article->published_at)?->toIso8601String(),
            'author' => $article->author ? self::personReference($article->author) : null,
            'publisher' => [
                '@type' => 'NewsMediaOrganization',
                '@id' => url('/').'#organization',
                'name' => config('expertia.name'),
                'logo' => ['@type' => 'ImageObject', 'url' => url(config('expertia.logo'))],
            ],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
            'articleSection' => $article->category?->name,
            'keywords' => $article->tags?->pluck('name')->implode(', ') ?: null,
            'inLanguage' => 'id-ID',
            'isAccessibleForFree' => true,
            'url' => $url,
        ], fn ($value) => $value !== null);
    }

    /**
     * Halaman profil penulis: Person dengan afiliasi dan bidang kepakaran.
     *
     * @param  list<string>  $expertise
     * @return array<string, mixed>
     */
    public static function profilePage(User $author, array $expertise): array
    {
        $url = route('authors.show', $author->profile_slug);

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            'url' => $url,
            'inLanguage' => 'id-ID',
            'mainEntity' => array_filter([
                '@type' => 'Person',
                '@id' => $url.'#person',
                'name' => $author->name,
                'url' => $url,
                'image' => $author->avatar_url,
                'description' => $author->bio,
                'jobTitle' => $author->position,
                'affiliation' => $author->institution
                    ? ['@type' => 'Organization', 'name' => $author->institution]
                    : null,
                'knowsAbout' => $expertise ?: null,
            ], fn ($value) => $value !== null),
        ];
    }

    /**
     * @param  list<array{name:string,url:string}>  $items
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn (array $item, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function personReference(User $author): array
    {
        return array_filter([
            '@type' => 'Person',
            'name' => $author->name,
            'url' => $author->profile_slug ? route('authors.show', $author->profile_slug) : null,
        ], fn ($value) => $value !== null);
    }
}
