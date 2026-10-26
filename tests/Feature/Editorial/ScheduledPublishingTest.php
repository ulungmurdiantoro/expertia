<?php

namespace Tests\Feature\Editorial;

use App\Models\Article;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ScheduledPublishingTest extends TestCase
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

    public function test_scheduler_publishes_due_scheduled_articles(): void
    {
        $article = Article::factory()->for($this->author, 'author')->create([
            'status' => 'scheduled',
            'editorial_status' => 'approved',
            'scheduled_at' => now()->subMinute(),
        ]);

        $this->artisan('articles:publish-scheduled')->assertSuccessful();

        $article->refresh();
        $this->assertSame('published', $article->status);
        $this->assertNotNull($article->published_at);
    }

    public function test_scheduler_leaves_archived_articles_alone(): void
    {
        // Pernah dijadwalkan, sudah tayang, lalu diarsipkan moderator.
        $article = Article::factory()->published()->for($this->author, 'author')->create([
            'status' => 'archived',
            'visibility' => Article::VISIBILITY_PRIVATE,
            'scheduled_at' => now()->subDays(3),
        ]);

        $this->artisan('articles:publish-scheduled')->assertSuccessful();

        $this->assertSame('archived', $article->fresh()->status);
        $this->assertFalse(UserNotification::query()->where('type', 'article.scheduled.published')->exists());
    }

    public function test_schedule_survives_opening_and_saving_the_edit_form(): void
    {
        $this->actingAs($this->author)->post(route('author.articles.store'), [
            'title' => 'Naskah Terjadwal',
            'scheduled_at' => '2026-11-01T09:00',
            'scheduled_timezone' => 'Asia/Jakarta',
        ]);
        $article = Article::query()->sole();
        $this->assertSame('2026-11-01 02:00:00', $article->scheduled_at->toDateTimeString());

        // Form edit menerima waktu absolut; browser yang mengubahnya ke jam lokal.
        $this->actingAs($this->author)
            ->get(route('author.articles.edit', $article))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('article.scheduled_at', '2026-11-01T02:00:00+00:00')
                ->missing('article.scheduled_timezone'));

        // Yang dikirim browser WIB saat menyimpan ulang tanpa mengubah jadwal.
        $this->actingAs($this->author)->put(route('author.articles.update', $article), [
            'title' => 'Naskah Terjadwal',
            'scheduled_at' => '2026-11-01T09:00',
            'scheduled_timezone' => 'Asia/Jakarta',
        ])->assertSessionHasNoErrors();

        $this->assertSame('2026-11-01 02:00:00', $article->fresh()->scheduled_at->toDateTimeString());
    }

    public function test_editor_cannot_clear_the_schedule_of_a_scheduled_article(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('editor');
        $scheduledAt = now()->addDay()->startOfMinute();
        $article = Article::factory()->for($this->author, 'author')->create([
            'status' => 'scheduled',
            'editorial_status' => 'approved',
            'scheduled_at' => $scheduledAt,
        ]);

        $this->actingAs($editor)->put(route('author.articles.update', $article), [
            'title' => $article->title,
            'scheduled_at' => '',
        ])->assertSessionHasErrors('scheduled_at');

        $this->assertTrue($scheduledAt->equalTo($article->fresh()->scheduled_at));
    }

    public function test_scheduled_approval_notice_shows_local_time(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('editor');
        $article = Article::factory()->pendingReview()->for($this->author, 'author')->create([
            'scheduled_at' => now()->addDays(2)->setTime(2, 0),
        ]);

        $this->actingAs($editor)->patch(route('editor.reviews.approve', $article))->assertSessionHasNoErrors();

        $notice = UserNotification::query()->where('type', 'article.scheduled')->sole();
        $this->assertStringContainsString(now()->addDays(2)->format('Y-m-d').' 09:00 WIB', $notice->body);
    }
}
