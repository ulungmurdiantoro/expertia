<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Login dan registrasi pembaca berakhir di /dashboard, jadi halaman ini harus selalu bisa dibuka.
 */
class ReaderDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_reader_dashboard_renders_article_cards_with_author(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $author = User::factory()->create(['name' => 'Penulis Pakar']);
        $author->assignRole('author');
        $article = Article::factory()->published()->for($author, 'author')->create();

        $reader = User::factory()->create();
        $reader->assignRole('user');
        $reader->bookmarks()->create(['article_id' => $article->id]);

        $this->actingAs($reader)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->where('recentBookmarks.0.article.author.name', 'Penulis Pakar')
                ->missing('recentBookmarks.0.article.author.email'));
    }
}
