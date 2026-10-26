<?php

namespace Tests\Feature\Editorial;

use App\Models\Article;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ArticleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->author = User::factory()->create();
        $this->author->assignRole('author');

        $this->editor = User::factory()->create();
        $this->editor->assignRole('editor');
    }

    private function updatePayload(Article $article, array $overrides = []): array
    {
        return [
            'title' => $article->title,
            'excerpt' => $article->excerpt,
            'content' => $article->content,
            ...$overrides,
        ];
    }

    public function test_author_cannot_edit_a_published_article(): void
    {
        $article = Article::factory()->published()->for($this->author, 'author')->create();

        $this->actingAs($this->author)
            ->put(route('author.articles.update', $article), $this->updatePayload($article, ['title' => 'Judul diganti diam-diam']))
            ->assertForbidden();

        $this->assertNotSame('Judul diganti diam-diam', $article->fresh()->title);
    }

    public function test_author_cannot_edit_while_article_is_in_review(): void
    {
        $article = Article::factory()->pendingReview()->for($this->author, 'author')->create();

        $this->actingAs($this->author)
            ->put(route('author.articles.update', $article), $this->updatePayload($article))
            ->assertForbidden();
    }

    public function test_slug_follows_title_while_draft(): void
    {
        $article = Article::factory()->for($this->author, 'author')->create();

        $this->actingAs($this->author)
            ->put(route('author.articles.update', $article), $this->updatePayload($article, ['title' => 'Judul Baru Draft']))
            ->assertRedirect();

        $this->assertSame('judul-baru-draft', $article->fresh()->slug);
    }

    public function test_slug_is_frozen_after_publication_even_when_editor_changes_title(): void
    {
        $article = Article::factory()->published()->for($this->author, 'author')->create(['slug' => 'url-yang-sudah-dibagikan']);

        $this->actingAs($this->editor)
            ->put(route('author.articles.update', $article), $this->updatePayload($article, ['title' => 'Wakaf Uang Belum Populer']))
            ->assertRedirect();

        $fresh = $article->fresh();
        $this->assertSame('Wakaf Uang Belum Populer', $fresh->title);
        $this->assertSame('url-yang-sudah-dibagikan', $fresh->slug);
    }

    public function test_draft_that_fails_validation_returns_readable_errors(): void
    {
        $this->actingAs($this->author)
            ->post(route('author.articles.store'), [
                'title' => '',
                'thumbnail' => UploadedFile::fake()->image('foto-ponsel.jpg')->size(3000),
            ])
            ->assertSessionHasErrors([
                'title' => 'Judul artikel wajib diisi.',
                'thumbnail' => 'Ukuran thumbnail maksimal 2 MB.',
            ]);

        $this->assertSame(0, Article::count());
    }

    public function test_title_without_latin_characters_still_gets_a_slug(): void
    {
        $this->actingAs($this->author)
            ->post(route('author.articles.store'), ['title' => '“?!”'])
            ->assertSessionHasNoErrors();

        $this->assertSame('artikel', Article::query()->sole()->slug);
    }

    public function test_author_can_submit_a_draft_for_review(): void
    {
        $article = Article::factory()->for($this->author, 'author')->create();

        $this->actingAs($this->author)
            ->patch(route('author.articles.submit', $article))
            ->assertSessionHasNoErrors();

        $this->assertSame('pending_review', $article->fresh()->editorial_status);
    }

    public function test_published_article_cannot_be_resubmitted(): void
    {
        $article = Article::factory()->published()->for($this->author, 'author')->create();

        $this->actingAs($this->author)
            ->patch(route('author.articles.submit', $article))
            ->assertSessionHasErrors('status');

        $this->assertSame('published', $article->fresh()->status);
    }

    public function test_editor_can_approve_an_article_waiting_for_review(): void
    {
        $article = Article::factory()->pendingReview()->for($this->author, 'author')->create();

        $this->actingAs($this->editor)
            ->patch(route('editor.reviews.approve', $article))
            ->assertSessionHasNoErrors();

        $fresh = $article->fresh();
        $this->assertSame('published', $fresh->status);
        $this->assertNotNull($fresh->published_at);
    }

    public function test_draft_that_was_never_submitted_cannot_be_approved(): void
    {
        $article = Article::factory()->for($this->author, 'author')->create();

        $this->actingAs($this->editor)
            ->patch(route('editor.reviews.approve', $article))
            ->assertSessionHasErrors('status');

        $this->assertSame('draft', $article->fresh()->status);
    }

    public function test_revision_request_returns_article_to_author(): void
    {
        $article = Article::factory()->pendingReview()->for($this->author, 'author')->create();

        $this->actingAs($this->editor)
            ->patch(route('editor.reviews.request-revision', $article), ['note' => 'Tambahkan sumber data.'])
            ->assertSessionHasNoErrors();

        $fresh = $article->fresh();
        $this->assertSame('draft', $fresh->status);
        $this->assertSame('revision_requested', $fresh->editorial_status);
        $this->assertTrue($this->author->can('update', $fresh));
    }

    public function test_scheduled_article_is_published_by_the_scheduler(): void
    {
        $article = Article::factory()->for($this->author, 'author')->create([
            'status' => 'scheduled',
            'editorial_status' => 'approved',
            'scheduled_at' => now()->subMinute(),
        ]);

        $this->artisan('articles:publish-scheduled')->assertSuccessful();

        $this->assertSame('published', $article->fresh()->status);
    }
}
