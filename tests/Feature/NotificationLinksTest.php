<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class NotificationLinksTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->author = User::factory()->create();
        $this->author->assignRole('author');
    }

    private function notify(User $user, string $type, Article $article): UserNotification
    {
        return UserNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => 'Judul',
            'body' => 'Isi',
            'data_json' => ['article_id' => $article->id, 'article_slug' => $article->slug],
        ]);
    }

    public function test_opening_a_notification_marks_it_read_and_goes_to_the_right_page(): void
    {
        $published = Article::factory()->published()->for($this->author, 'author')->create();
        $draft = Article::factory()->for($this->author, 'author')->create();

        $approved = $this->notify($this->author, 'article.approved', $published);
        $this->actingAs($this->author)
            ->get(route('me.notifications.open', $approved))
            ->assertRedirect(route('articles.show', $published->slug));
        $this->assertNotNull($approved->fresh()->read_at);

        $this->actingAs($this->author)
            ->get(route('me.notifications.open', $this->notify($this->author, 'comment.created', $published)))
            ->assertRedirect(route('articles.show', $published->slug).'#komentar-h');

        $this->actingAs($this->author)
            ->get(route('me.notifications.open', $this->notify($this->author, 'article.revision_requested', $draft)))
            ->assertRedirect(route('author.articles.edit', $draft));

        $editor = User::factory()->create();
        $editor->assignRole('editor');
        $this->actingAs($editor)
            ->get(route('me.notifications.open', $this->notify($editor, 'article.submitted', $draft)))
            ->assertRedirect(route('editor.reviews.preview', $draft));
    }

    public function test_notification_list_marks_which_items_have_links(): void
    {
        $article = Article::factory()->published()->for($this->author, 'author')->create();
        $this->notify($this->author, 'article.approved', $article);
        $this->travel(1)->minutes();
        UserNotification::create(['user_id' => $this->author->id, 'type' => 'follow.created', 'title' => 'Follower baru', 'body' => 'x']);

        $this->actingAs($this->author)
            ->get(route('me.notifications.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('notifications.data.0.has_link', false)
                ->where('notifications.data.1.has_link', true));
    }

    public function test_deleted_article_and_other_users_notifications_are_handled(): void
    {
        $article = Article::factory()->published()->for($this->author, 'author')->create();
        $notification = $this->notify($this->author, 'article.approved', $article);
        $article->delete();

        $this->actingAs($this->author)
            ->from(route('me.notifications.index'))
            ->get(route('me.notifications.open', $notification))
            ->assertRedirect(route('me.notifications.index'))
            ->assertSessionHas('error', 'Konten untuk notifikasi ini sudah tidak tersedia.');

        $stranger = User::factory()->create();
        $this->actingAs($stranger)->get(route('me.notifications.open', $notification))->assertForbidden();
    }
}
