<?php

namespace Tests\Feature\Seo;

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoveryTest extends TestCase
{
    use RefreshDatabase;

    private Article $published;

    private Article $draft;

    protected function setUp(): void
    {
        parent::setUp();

        $author = User::factory()->create(['name' => 'Agung Yulianto', 'profile_slug' => 'agung-yulianto']);
        $category = Category::query()->where('slug', 'riset-inovasi')->firstOrFail();

        $this->published = Article::factory()->published()->for($author, 'author')->create([
            'category_id' => $category->id,
            'title' => 'Wakaf Uang Belum Populer',
            'slug' => 'wakaf-uang-belum-populer',
            'excerpt' => 'Keyakinan mendorong niat berwakaf.',
            'content' => '<h2>Empat faktor</h2><p>Sikap <strong>berpengaruh</strong>. <a href="/kontak">Hubungi kami</a>.</p><ul><li>Literasi</li></ul>',
            'writing_source' => 'Diadaptasi dari penelitian Agung Yulianto dkk.',
        ]);
        $this->published->tags()->attach(Tag::create(['name' => 'Ekonomi', 'slug' => 'ekonomi']));

        $this->draft = Article::factory()->for($author, 'author')->create(['slug' => 'naskah-belum-terbit']);
    }

    private function asProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
    }

    public function test_robots_blocks_everything_outside_production(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee("User-agent: *\nDisallow: /", false);
    }

    public function test_robots_in_production_welcomes_search_engines_and_ai(): void
    {
        $this->asProduction();

        $robots = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString('Sitemap: '.route('sitemap'), $robots);
        $this->assertStringContainsString(route('llms'), $robots);
        $this->assertStringContainsString('User-agent: OAI-SearchBot', $robots);
        $this->assertStringContainsString('User-agent: Claude-SearchBot', $robots);
        $this->assertStringContainsString('User-agent: PerplexityBot', $robots);
        $this->assertStringContainsString('Disallow: /admin', $robots);
        // /author/ (dashboard) diblokir, /authors/ (profil publik) tidak.
        $this->assertStringContainsString('Disallow: /author/', $robots);
        $this->assertStringNotContainsString('Disallow: /authors', $robots);
        $this->assertMatchesRegularExpression('/User-agent: GPTBot\n(User-agent: [^\n]+\n)*Allow: \//', $robots);
    }

    public function test_ai_training_crawlers_can_be_blocked_with_one_setting(): void
    {
        $this->asProduction();
        config(['expertia.ai.allow_training' => false]);

        $robots = $this->get('/robots.txt')->getContent();

        $this->assertMatchesRegularExpression('/User-agent: GPTBot\n(User-agent: [^\n]+\n)*Disallow: \/\n/', $robots);
        // Asisten AI yang mengutip dengan tautan tetap diizinkan.
        $this->assertMatchesRegularExpression('/User-agent: OAI-SearchBot\n(User-agent: [^\n]+\n)*Allow: \//', $robots);
    }

    public function test_robots_command_writes_static_file_for_nginx(): void
    {
        $path = public_path('robots.txt');
        $existing = is_file($path) ? file_get_contents($path) : null;

        try {
            $this->artisan('expertia:robots')->assertSuccessful();
            $this->assertStringContainsString('User-agent: *', (string) file_get_contents($path));
        } finally {
            $existing === null ? @unlink($path) : file_put_contents($path, $existing);
        }
    }

    public function test_sitemap_lists_public_urls_but_not_drafts(): void
    {
        $response = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml, 'sitemap.xml harus XML yang valid');

        $locations = collect();
        foreach ($xml->url as $url) {
            $locations->push((string) $url->loc);
        }
        $this->assertTrue($locations->contains(route('articles.show', 'wakaf-uang-belum-populer')));
        $this->assertTrue($locations->contains(route('authors.show', 'agung-yulianto')));
        $this->assertTrue($locations->contains(route('articles.index', ['category' => 'riset-inovasi'])));
        $this->assertTrue($locations->contains(route('kebijakan.editorial')));
        $this->assertFalse($locations->contains(route('articles.show', 'naskah-belum-terbit')));
    }

    public function test_rss_feed_contains_published_articles_with_full_content(): void
    {
        $response = $this->get('/feed.xml')->assertOk();

        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml, 'feed.xml harus XML yang valid');
        $this->assertCount(1, $xml->channel->item);
        $this->assertSame('Wakaf Uang Belum Populer', (string) $xml->channel->item[0]->title);
        $this->assertStringContainsString('Empat faktor', $response->getContent());
    }

    public function test_llms_txt_summarises_the_site_for_language_models(): void
    {
        $text = $this->get('/llms.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->getContent();

        $this->assertStringStartsWith('# Expertia.id', $text);
        $this->assertStringContainsString('['.'Wakaf Uang Belum Populer]('.route('articles.markdown', 'wakaf-uang-belum-populer').')', $text);
        $this->assertStringContainsString(route('kebijakan.editorial'), $text);
        $this->assertStringContainsString(route('llms.full'), $text);
        $this->assertStringNotContainsString('naskah-belum-terbit', $text);
    }

    public function test_llms_full_contains_every_published_article_in_markdown(): void
    {
        $text = $this->get('/llms-full.txt')->assertOk()->getContent();

        $this->assertStringContainsString('# Wakaf Uang Belum Populer', $text);
        $this->assertStringContainsString('## Empat faktor', $text);
    }

    public function test_article_is_available_as_clean_markdown(): void
    {
        $markdown = $this->get('/articles/wakaf-uang-belum-populer.md')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertHeader('Link', '<'.route('articles.show', 'wakaf-uang-belum-populer').'>; rel="canonical"')
            ->getContent();

        $this->assertStringStartsWith("---\ntitle: \"Wakaf Uang Belum Populer\"", $markdown);
        $this->assertStringContainsString('author: "Agung Yulianto"', $markdown);
        $this->assertStringContainsString('category: "Riset & Inovasi"', $markdown);
        $this->assertStringContainsString('tags: ["Ekonomi"]', $markdown);
        $this->assertStringContainsString('## Empat faktor', $markdown);
        $this->assertStringContainsString('**berpengaruh**', $markdown);
        $this->assertStringContainsString('['.'Hubungi kami]('.url('/kontak').')', $markdown);
        $this->assertStringContainsString('## Sumber tulisan', $markdown);
        $this->assertDoesNotMatchRegularExpression('/<(p|h2|strong|ul|li)[ >]/', $markdown);
    }

    public function test_unpublished_article_has_no_markdown_version(): void
    {
        $this->get('/articles/naskah-belum-terbit.md')->assertNotFound();
    }
}
