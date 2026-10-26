<?php

namespace Tests\Feature\Public;

use App\Models\Article;
use App\Models\Comment;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private User $reader;

    private Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->author = User::factory()->create();
        $this->author->assignRole('author');
        $this->reader = User::factory()->create();
        $this->reader->assignRole('user');
        $this->article = Article::factory()->published()->for($this->author, 'author')->create();
    }

    private function comment(User $user, string $status = 'approved'): Comment
    {
        return Comment::create([
            'article_id' => $this->article->id,
            'user_id' => $user->id,
            'content' => 'Komentar uji',
            'status' => $status,
        ]);
    }

    public function test_same_reader_cannot_stack_pending_reports_on_one_article(): void
    {
        $report = fn () => $this->actingAs($this->reader)
            ->post(route('articles.reports.store', $this->article->slug), ['reason' => 'Spam']);

        $report()->assertSessionHas('success', 'Laporan artikel berhasil dikirim.');
        $report()->assertSessionHas('success', 'Anda sudah melaporkan artikel ini. Laporan sedang ditinjau redaksi.');
        $this->assertSame(1, Report::count());

        // Setelah ditangani redaksi, pembaca boleh melapor lagi.
        Report::query()->update(['status' => 'resolved']);
        $report()->assertSessionHas('success', 'Laporan artikel berhasil dikirim.');
        $this->assertSame(2, Report::count());
    }

    public function test_own_content_and_hidden_comments_cannot_be_reported(): void
    {
        $this->actingAs($this->author)
            ->post(route('articles.reports.store', $this->article->slug), ['reason' => 'Spam'])
            ->assertForbidden();

        $ownComment = $this->comment($this->reader);
        $this->actingAs($this->reader)
            ->post(route('articles.comments.reports.store', [$this->article->slug, $ownComment->id]), ['reason' => 'Spam'])
            ->assertForbidden();

        $hiddenComment = $this->comment($this->author, 'hidden');
        $this->actingAs($this->reader)
            ->post(route('articles.comments.reports.store', [$this->article->slug, $hiddenComment->id]), ['reason' => 'Spam'])
            ->assertNotFound();

        $this->assertSame(0, Report::count());
    }
}
