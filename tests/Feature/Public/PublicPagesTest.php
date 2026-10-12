<?php

namespace Tests\Feature\Public;

use App\Models\Article;
use App\Models\ArticleView;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

    private User $author;

    private Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->author = User::factory()->create([
            'email' => 'penulis-rahasia@example.com',
            'whatsapp_number' => '081299998888',
            'bio' => 'Peneliti ekonomi syariah.',
        ]);
        $this->author->assignRole('author');

        $this->article = Article::factory()->published()->for($this->author, 'author')->create([
            'category_id' => Category::factory()->create(['slug' => 'riset-inovasi', 'name' => 'Riset & Inovasi'])->id,
            'title' => 'Wakaf Uang Belum Populer',
            'excerpt' => 'Ringkasan penelitian wakaf.',
        ]);
    }

    public function test_public_pages_render(): void
    {
        foreach (['/', '/articles', '/articles?category=riset-inovasi', '/trending', '/kontributor', '/tentang', '/kebijakan-editorial', '/kebijakan-privasi', '/kontak'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get(route('articles.show', $this->article->slug))->assertOk();
        $this->get(route('authors.show', $this->author->profile_slug))->assertOk();
    }

    public function test_unpublished_article_is_not_found(): void
    {
        $draft = Article::factory()->for($this->author, 'author')->create();

        $this->get(route('articles.show', $draft->slug))->assertNotFound();
    }

    public function test_author_contact_details_never_reach_public_pages(): void
    {
        $pages = [
            '/',
            '/articles',
            route('articles.show', $this->article->slug),
            route('authors.show', $this->author->profile_slug),
        ];

        foreach ($pages as $url) {
            $this->get($url, ['User-Agent' => self::BROWSER])
                ->assertOk()
                ->assertDontSee('penulis-rahasia@example.com')
                ->assertDontSee('081299998888')
                ->assertDontSee('whatsapp_number');
        }
    }

    public function test_meta_tags_are_rendered_by_the_server(): void
    {
        $this->get(route('articles.show', $this->article->slug))
            ->assertSee('<meta property="og:title" content="Wakaf Uang Belum Populer — Expertia.id">', false)
            ->assertSee('<meta name="description" content="Ringkasan penelitian wakaf.">', false)
            ->assertSee('<link rel="canonical" href="'.route('articles.show', $this->article->slug).'">', false);
    }

    public function test_reader_views_are_counted_but_bots_are_not(): void
    {
        $url = route('articles.show', $this->article->slug);

        $this->get($url, ['User-Agent' => 'WhatsApp/2.23.20 A'])->assertOk();
        $this->get($url, ['User-Agent' => 'Googlebot/2.1'])->assertOk();
        $this->assertSame(0, ArticleView::count());

        $this->get($url, ['User-Agent' => self::BROWSER])->assertOk();
        $this->assertSame(1, ArticleView::count());
        $this->assertSame(1, (int) $this->article->fresh()->view_count);
    }

    public function test_guests_can_share_and_each_session_counts_once(): void
    {
        $url = route('articles.shares.store', $this->article->slug);

        $this->postJson($url, ['channel' => 'copy_link'])->assertOk()->assertJson(['share_count' => 1]);
        $this->postJson($url, ['channel' => 'copy_link'])->assertOk()->assertJson(['share_count' => 1]);
    }

    public function test_comment_spam_is_throttled_with_a_form_error(): void
    {
        $reader = User::factory()->create();
        $url = route('articles.comments.store', $this->article->slug);

        for ($i = 1; $i <= 5; $i++) {
            $this->actingAs($reader)->post($url, ['content' => "Komentar {$i}"])->assertSessionHasNoErrors();
        }

        $this->actingAs($reader)
            ->post($url, ['content' => 'Komentar keenam'], ['X-Inertia' => 'true'])
            ->assertSessionHasErrors('content');
    }
}
