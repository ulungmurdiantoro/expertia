<?php

namespace Tests\Feature\Editorial;

use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ArticleModerationTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private User $moderator;

    private User $reader;

    private Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->author = User::factory()->create();
        $this->author->assignRole('author');

        $this->moderator = User::factory()->create();
        $this->moderator->assignRole('moderator');

        $this->reader = User::factory()->create();

        $this->article = Article::factory()->published()->for($this->author, 'author')->create([
            'category_id' => Category::query()->where('slug', 'riset-inovasi')->firstOrFail()->id,
            'title' => 'Artikel Terlapor Moderasi',
            'slug' => 'artikel-terlapor-moderasi',
        ]);
    }

    private function applyReportAction(string $action): Report
    {
        $report = Report::create([
            'reporter_id' => $this->reader->id,
            'reportable_type' => Article::class,
            'reportable_id' => $this->article->id,
            'reason' => 'Konten menyesatkan',
            'status' => 'pending',
        ]);

        $this->actingAs($this->moderator)
            ->patch(route('editor.moderation.reports.subject-action', $report), ['action' => $action])
            ->assertRedirect();

        return $report->fresh();
    }

    public function test_hidden_article_disappears_from_every_public_surface(): void
    {
        // Isi cache discovery dulu: artikel yang disembunyikan tidak boleh bertahan di sana.
        foreach (['/feed.xml', '/llms.txt', '/llms-full.txt', '/sitemap.xml'] as $url) {
            $this->get($url)->assertSee('artikel-terlapor-moderasi');
        }

        $report = $this->applyReportAction('hide_article');

        $this->assertSame(Article::VISIBILITY_PRIVATE, $this->article->fresh()->visibility);
        $this->assertSame('published', $this->article->fresh()->status);
        $this->assertSame('resolved', $report->status);

        $this->app['auth']->forgetGuards();

        $this->get(route('articles.show', $this->article->slug))->assertNotFound();
        $this->get(route('articles.markdown', $this->article->slug))->assertNotFound();

        foreach (['/', '/articles', '/articles?category=riset-inovasi', '/trending', '/feed.xml', '/llms.txt', '/llms-full.txt', '/sitemap.xml'] as $url) {
            $this->get($url)->assertOk()->assertDontSee('artikel-terlapor-moderasi');
        }

        $this->get(route('authors.show', $this->author->profile_slug))
            ->assertOk()
            ->assertDontSee('artikel-terlapor-moderasi');
    }

    public function test_readers_cannot_interact_with_hidden_article(): void
    {
        $this->article->update(['visibility' => Article::VISIBILITY_PRIVATE]);

        $this->actingAs($this->reader);

        $this->post(route('articles.comments.store', $this->article->slug), ['content' => 'Komentar'])->assertNotFound();
        $this->post(route('articles.reactions.store', $this->article->slug), ['type' => 'like'])->assertNotFound();
        $this->postJson(route('articles.shares.store', $this->article->slug), ['channel' => 'whatsapp'])->assertNotFound();
        $this->post(route('articles.reports.store', $this->article->slug), ['reason' => 'Spam'])->assertNotFound();
    }

    public function test_hidden_article_leaves_reader_dashboard(): void
    {
        $this->reader->bookmarks()->create(['article_id' => $this->article->id]);
        $this->article->update(['visibility' => Article::VISIBILITY_PRIVATE]);

        $this->actingAs($this->reader)
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('recommendedArticles', 0)
                ->has('recentBookmarks', 0));
    }

    public function test_admin_can_republish_hidden_or_archived_article(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        foreach (['hide' => ['visibility' => Article::VISIBILITY_PRIVATE], 'archive' => ['status' => 'archived', 'visibility' => Article::VISIBILITY_PRIVATE]] as $takedown) {
            $this->article->update($takedown);

            $this->actingAs($admin)
                ->patch(route('author.articles.republish', $this->article))
                ->assertSessionHasNoErrors()
                ->assertRedirect();

            $this->assertTrue($this->article->fresh()->isPubliclyVisible());
        }

        $this->assertSame(2, ActivityLog::query()->where('action', 'article.republished')->count());

        $this->app['auth']->forgetGuards();
        $this->get(route('articles.show', $this->article->slug))->assertOk();
    }

    public function test_republish_is_admin_only_and_only_for_taken_down_articles(): void
    {
        $this->article->update(['visibility' => Article::VISIBILITY_PRIVATE]);

        $this->actingAs($this->moderator)
            ->patch(route('author.articles.republish', $this->article))
            ->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $draft = Article::factory()->for($this->author, 'author')->create();

        $this->actingAs($admin)
            ->patch(route('author.articles.republish', $draft))
            ->assertSessionHasErrors('status');

        $this->assertSame('draft', $draft->fresh()->status);
    }

    public function test_hidden_article_drops_out_of_reader_bookmarks(): void
    {
        $this->reader->bookmarks()->create(['article_id' => $this->article->id]);
        $this->article->update(['visibility' => Article::VISIBILITY_PRIVATE]);

        $this->actingAs($this->reader)
            ->get(route('me.bookmarks.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('bookmarks.data', 0));
    }

    public function test_author_dashboard_flags_hidden_article(): void
    {
        $this->applyReportAction('hide_article');

        $this->actingAs($this->author)
            ->get(route('author.articles.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('articles.data.0.is_hidden_by_moderator', true)
                ->where('articles.data.0.is_publicly_visible', false));
    }

    public function test_archive_also_removes_article_from_public(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->moderator = $admin;

        $this->applyReportAction('archive_article');

        $this->app['auth']->forgetGuards();

        $this->get(route('articles.show', $this->article->slug))->assertNotFound();
        $this->get('/feed.xml')->assertDontSee('artikel-terlapor-moderasi');
    }
}
