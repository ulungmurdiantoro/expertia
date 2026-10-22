<?php

namespace Tests\Feature\Author;

use App\Models\Article;
use App\Models\ArticleView;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class InsightsTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_views_are_grouped_by_local_day(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        Carbon::setTestNow('2026-10-22 10:00:00'); // 17.00 WIB

        $author = User::factory()->create();
        $author->assignRole('author');
        $article = Article::factory()->published()->for($author, 'author')->create();

        // 21 Okt 18.00 UTC = 22 Okt 01.00 WIB: harus masuk hari 22, bukan 21.
        ArticleView::create(['article_id' => $article->id, 'session_id' => 's1', 'viewed_at' => '2026-10-21 18:00:00']);
        // 21 Okt 16.00 UTC = 21 Okt 23.00 WIB.
        ArticleView::create(['article_id' => $article->id, 'session_id' => 's2', 'viewed_at' => '2026-10-21 16:00:00']);

        $this->actingAs($author)
            ->get(route('author.insights.index', ['days' => 7]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('daily_views', 7)
                ->where('daily_views.6', ['day' => '2026-10-22', 'total' => 1])
                ->where('daily_views.5', ['day' => '2026-10-21', 'total' => 1]));

        Carbon::setTestNow();
    }
}
