<?php

namespace Tests\Feature\Editorial;

use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ArticleDeletionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $author;

    private Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->author = User::factory()->create();
        $this->author->assignRole('author');

        $this->article = Article::factory()->published()->for($this->author, 'author')->create([
            'title' => 'Artikel Yang Akan Dihapus',
            'slug' => 'artikel-yang-akan-dihapus',
        ]);
    }

    public function test_admin_deletes_article_from_site_and_lists(): void
    {
        $this->get('/feed.xml')->assertSee('artikel-yang-akan-dihapus');

        $this->actingAs($this->admin)
            ->get(route('author.articles.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('abilities.delete', true));

        $this->actingAs($this->admin)
            ->delete(route('author.articles.destroy', $this->article))
            ->assertRedirect(route('author.articles.index'));

        $this->assertSoftDeleted($this->article);
        $log = ActivityLog::query()->where('action', 'article.deleted')->sole();
        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertSame('artikel-yang-akan-dihapus', $log->meta_json['slug']);

        $this->actingAs($this->admin)
            ->get(route('author.articles.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('articles.data', 0));

        $this->app['auth']->forgetGuards();
        $this->get(route('articles.show', 'artikel-yang-akan-dihapus'))->assertNotFound();
        $this->get('/feed.xml')->assertDontSee('artikel-yang-akan-dihapus');
    }

    public function test_only_roles_with_delete_permission_can_delete(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('editor');

        foreach ([$this->author, $editor] as $user) {
            $this->actingAs($user)
                ->get(route('author.articles.index'))
                ->assertInertia(fn (AssertableInertia $page) => $page->where('abilities.delete', false));

            $this->actingAs($user)->delete(route('author.articles.destroy', $this->article))->assertForbidden();
        }

        $this->assertNotSoftDeleted($this->article);
    }

    public function test_new_article_with_title_of_deleted_one_gets_its_own_slug(): void
    {
        $this->actingAs($this->admin)->delete(route('author.articles.destroy', $this->article));

        $this->actingAs($this->author)
            ->post(route('author.articles.store'), ['title' => 'Artikel Yang Akan Dihapus'])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame('artikel-yang-akan-dihapus-1', Article::query()->sole()->slug);
    }
}
