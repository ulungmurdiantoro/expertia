<?php

namespace Tests\Feature\Public;

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use App\Services\ArticleCommentThread;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * comment_count harus sama dengan jumlah komentar yang benar-benar tampil di halaman artikel.
 */
class CommentCountTest extends TestCase
{
    use RefreshDatabase;

    private Article $article;

    private User $reader;

    private Comment $parent;

    private Comment $reply;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->article = Article::factory()->published()->create();
        $this->reader = User::factory()->create();
        $this->parent = $this->comment();
        $this->reply = $this->comment(['parent_id' => $this->parent->id]);
        $this->article->refreshCommentCount();
    }

    private function comment(array $attributes = []): Comment
    {
        return Comment::create([
            'article_id' => $this->article->id,
            'user_id' => $this->reader->id,
            'content' => 'Komentar',
            'status' => 'approved',
            ...$attributes,
        ]);
    }

    private function shownCount(): int
    {
        return collect(app(ArticleCommentThread::class)->forArticle($this->article->fresh(), null))
            ->sum(fn (array $comment) => 1 + count($comment['replies']));
    }

    public function test_reply_to_a_reply_is_attached_to_the_top_level_comment(): void
    {
        $this->actingAs($this->reader)
            ->post(route('articles.comments.store', $this->article->slug), ['content' => 'Balasan', 'parent_id' => $this->reply->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($this->parent->id, Comment::query()->latest('id')->first()->parent_id);
    }

    public function test_cannot_reply_to_a_comment_that_is_not_shown(): void
    {
        $hidden = $this->comment(['status' => 'hidden']);

        $this->actingAs($this->reader)
            ->post(route('articles.comments.store', $this->article->slug), ['content' => 'Balasan', 'parent_id' => $hidden->id])
            ->assertNotFound();
    }

    public function test_deleting_a_parent_comment_drops_its_replies_from_the_count(): void
    {
        $this->assertSame(2, $this->article->fresh()->comment_count);

        $this->actingAs($this->reader)
            ->delete(route('articles.comments.destroy', [$this->article->slug, $this->parent->id]));

        $this->assertSame(0, $this->shownCount());
        $this->assertSame(0, $this->article->fresh()->comment_count);
    }

    public function test_moderating_a_parent_comment_recounts_its_replies(): void
    {
        $moderator = User::factory()->create();
        $moderator->assignRole('moderator');

        $this->actingAs($moderator)
            ->patch(route('editor.moderation.comments.update', $this->parent), ['status' => 'hidden']);
        $this->assertSame(0, $this->article->fresh()->comment_count);
        $this->assertSame(0, $this->shownCount());

        $this->actingAs($moderator)
            ->patch(route('editor.moderation.comments.update', $this->parent), ['status' => 'approved']);
        $this->assertSame(2, $this->article->fresh()->comment_count);
        $this->assertSame(2, $this->shownCount());
    }

    public function test_recount_migration_fixes_stored_counts(): void
    {
        $this->parent->delete();
        $this->article->update(['comment_count' => 7]);

        (require database_path('migrations/2026_10_23_000001_recount_article_comment_counts.php'))->up();

        $this->assertSame(0, $this->article->fresh()->comment_count);
    }
}
