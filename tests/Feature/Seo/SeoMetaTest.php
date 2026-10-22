<?php

namespace Tests\Feature\Seo;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Support\PublicPagesSsrGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SeoMetaTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

    private User $author;

    private Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create([
            'name' => 'Agung Yulianto',
            'profile_slug' => 'agung-yulianto',
            'institution' => 'Universitas Contoh',
            'expertise_areas' => ['Ekonomi Syariah'],
        ]);

        $this->article = Article::factory()->published()->for($this->author, 'author')->create([
            'category_id' => Category::query()->where('slug', 'riset-inovasi')->firstOrFail()->id,
            'title' => 'Wakaf Uang Belum Populer',
            'slug' => 'wakaf-uang-belum-populer',
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function jsonLd(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

        return array_map(fn (string $json) => json_decode($json, true), $matches[1]);
    }

    public function test_pages_declare_indonesian_language(): void
    {
        $this->get('/')->assertSee('<html lang="id">', false);
    }

    public function test_article_page_has_article_structured_data_and_meta(): void
    {
        $html = $this->get(route('articles.show', $this->article->slug))->assertOk()->getContent();

        $schemas = collect($this->jsonLd($html))->keyBy('@type');
        $this->assertSame('Wakaf Uang Belum Populer', $schemas['Article']['headline']);
        $this->assertSame('Agung Yulianto', $schemas['Article']['author']['name']);
        $this->assertSame(route('authors.show', 'agung-yulianto'), $schemas['Article']['author']['url']);
        $this->assertSame($this->article->published_at->toIso8601String(), $schemas['Article']['datePublished']);
        $this->assertSame('Riset & Inovasi', $schemas['Article']['articleSection']);
        $this->assertCount(3, $schemas['BreadcrumbList']['itemListElement']);

        $this->assertStringContainsString('<meta property="og:locale" content="id_ID">', $html);
        $this->assertStringContainsString('<meta property="article:published_time" content="'.$this->article->published_at->toIso8601String().'">', $html);
        $this->assertStringContainsString('<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">', $html);
        $this->assertStringContainsString('href="'.route('articles.markdown', $this->article->slug).'"', $html);
        $this->assertStringContainsString('type="application/rss+xml"', $html);
        // Satu judul saja walau SSR aktif atau tidak.
        $this->assertSame(1, substr_count($html, '<title'));
    }

    public function test_reading_an_article_does_not_change_its_modified_date(): void
    {
        $modifiedAt = $this->article->fresh()->updated_at;
        $this->travel(2)->hours();

        $this->get(route('articles.show', $this->article->slug), ['User-Agent' => self::BROWSER])->assertOk();
        $this->post(route('articles.shares.store', $this->article->slug))->assertOk();

        $fresh = $this->article->fresh();
        $this->assertSame(1, (int) $fresh->view_count);
        $this->assertSame(1, (int) $fresh->share_count);
        $this->assertTrue($fresh->updated_at->equalTo($modifiedAt), 'updated_at tidak boleh bergeser karena view/share');
    }

    public function test_editing_content_still_updates_modified_date(): void
    {
        $modifiedAt = $this->article->fresh()->updated_at;
        $this->travel(2)->hours();

        $this->article->update(['title' => 'Judul Baru']);

        $this->assertTrue($this->article->fresh()->updated_at->gt($modifiedAt));
    }

    public function test_search_results_are_noindex_and_pagination_has_own_canonical(): void
    {
        Article::factory()->published()->count(12)->for($this->author, 'author')->create();

        $this->get('/articles?q=wakaf')
            ->assertSee('<meta name="robots" content="noindex, follow">', false);

        $this->get('/articles?page=2')
            ->assertSee('<link rel="canonical" href="'.route('articles.index', ['page' => 2]).'">', false)
            ->assertSee('<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">', false);
    }

    public function test_author_profile_has_person_data_and_empty_profiles_are_noindex(): void
    {
        $html = $this->get(route('authors.show', 'agung-yulianto'))->getContent();
        $profile = collect($this->jsonLd($html))->firstWhere('@type', 'ProfilePage');
        $this->assertSame('Agung Yulianto', $profile['mainEntity']['name']);
        $this->assertSame('Universitas Contoh', $profile['mainEntity']['affiliation']['name']);
        $this->assertSame(['Ekonomi Syariah'], $profile['mainEntity']['knowsAbout']);

        $reader = User::factory()->create(['profile_slug' => 'pembaca-biasa']);
        $this->get(route('authors.show', $reader->profile_slug))
            ->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    public function test_login_page_is_not_indexed(): void
    {
        $this->get('/login')->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_article_page_suggests_related_articles_from_same_rubric_first(): void
    {
        $sameRubric = Article::factory()->published()->for($this->author, 'author')->create([
            'category_id' => $this->article->category_id,
            'published_at' => now()->subWeeks(3),
        ]);
        Article::factory()->published()->for($this->author, 'author')->create(['published_at' => now()->subHour()]);

        $this->get(route('articles.show', $this->article->slug))
            ->assertInertia(fn ($page) => $page
                ->where('related.0.id', $sameRubric->id)
                ->has('related', 2));
    }

    public function test_www_host_redirects_permanently_to_canonical_domain_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['app.url' => 'https://expertia.id']);

        $this->get('http://www.expertia.id/kontak?x=1')
            ->assertStatus(301)
            ->assertRedirect('https://expertia.id/kontak?x=1');
    }

    public function test_ssr_is_only_used_for_public_pages(): void
    {
        Http::fake();
        config(['inertia.ssr.enabled' => true, 'inertia.ssr.ensure_bundle_exists' => false]);

        $this->assertTrue(PublicPagesSsrGateway::isPublicComponent('Welcome'));
        $this->assertTrue(PublicPagesSsrGateway::isPublicComponent('Public/Articles/Show'));
        $this->assertFalse(PublicPagesSsrGateway::isPublicComponent('Admin/Dashboard'));

        $this->assertNull(app(PublicPagesSsrGateway::class)->dispatch(['component' => 'Author/Articles/Edit', 'props' => []]));
        Http::assertNothingSent();
    }
}
