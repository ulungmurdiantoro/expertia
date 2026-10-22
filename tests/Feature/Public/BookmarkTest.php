<?php

namespace Tests\Feature\Public;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class BookmarkTest extends TestCase
{
    use RefreshDatabase;

    private User $reader;

    private Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reader = User::factory()->create();
        $this->article = Article::factory()->published()->create();
    }

    public function test_reader_can_save_and_unsave_article_from_its_page(): void
    {
        $this->actingAs($this->reader);

        $this->get(route('articles.show', $this->article->slug))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('article.id', $this->article->id)
                ->where('viewer.bookmarked', false));

        $this->from(route('articles.show', $this->article->slug))
            ->post(route('me.bookmarks.store'), ['article_id' => $this->article->id])
            ->assertRedirect(route('articles.show', $this->article->slug));

        $this->assertDatabaseHas('bookmarks', ['user_id' => $this->reader->id, 'article_id' => $this->article->id]);
        $this->assertSame(1, $this->article->fresh()->bookmark_count);

        $this->get(route('articles.show', $this->article->slug))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('viewer.bookmarked', true));

        $this->get(route('me.bookmarks.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('bookmarks.data', 1)
                ->where('bookmarks.data.0.article.slug', $this->article->slug));

        $this->delete(route('me.bookmarks.destroy', $this->article->slug))->assertRedirect();

        $this->assertDatabaseMissing('bookmarks', ['user_id' => $this->reader->id, 'article_id' => $this->article->id]);
        $this->assertSame(0, $this->article->fresh()->bookmark_count);
    }

    public function test_saving_twice_keeps_a_single_bookmark(): void
    {
        $this->actingAs($this->reader);

        $this->post(route('me.bookmarks.store'), ['article_id' => $this->article->id]);
        $this->post(route('me.bookmarks.store'), ['article_id' => $this->article->id]);

        $this->assertSame(1, $this->reader->bookmarks()->count());
        $this->assertSame(1, $this->article->fresh()->bookmark_count);
    }

    public function test_articles_not_visible_to_public_cannot_be_bookmarked(): void
    {
        $draft = Article::factory()->create();
        $hidden = Article::factory()->published()->create(['visibility' => Article::VISIBILITY_PRIVATE]);

        $this->actingAs($this->reader);

        $this->post(route('me.bookmarks.store'), ['article_id' => $draft->id])->assertNotFound();
        $this->post(route('me.bookmarks.store'), ['article_id' => $hidden->id])->assertNotFound();

        $this->assertSame(0, $this->reader->bookmarks()->count());
    }

    public function test_guest_must_log_in_to_bookmark(): void
    {
        $this->get(route('articles.show', $this->article->slug))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('viewer.bookmarked', false));

        $this->post(route('me.bookmarks.store'), ['article_id' => $this->article->id])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('bookmarks', 0);
    }
}
