<?php

namespace Tests\Feature\Admin;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    private function rubric(string $slug = 'riset-inovasi'): Category
    {
        return Category::query()->where('slug', $slug)->firstOrFail();
    }

    public function test_admin_adds_a_category_that_writers_and_readers_can_use(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Teknologi Pangan',
                'description' => 'Inovasi pangan dari laboratorium ke meja makan.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.categories.index'));

        $category = Category::query()->where('name', 'Teknologi Pangan')->sole();
        $this->assertSame('teknologi-pangan', $category->slug);
        $this->assertTrue($category->is_active);
        $this->assertNotNull($category->public_id);

        $author = User::factory()->create();
        $author->assignRole('author');
        $this->actingAs($author)
            ->get(route('author.articles.create'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('categories', fn ($categories) => collect($categories)->pluck('name')->contains('Teknologi Pangan')));

        Article::factory()->published()->create(['category_id' => $category->id, 'title' => 'Beras Tahan Banjir']);
        $this->app['auth']->forgetGuards();
        $this->get('/articles?category=teknologi-pangan')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pageMeta.title', 'Teknologi Pangan')
                ->where('articles.data.0.title', 'Beras Tahan Banjir'));
    }

    public function test_name_and_slug_must_be_unique_and_well_formed(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.categories.create'))
            ->post(route('admin.categories.store'), ['name' => 'Riset & Inovasi'])
            ->assertSessionHasErrors(['name', 'slug']);

        $this->actingAs($this->admin)
            ->from(route('admin.categories.create'))
            ->post(route('admin.categories.store'), ['name' => 'Kategori Baru', 'slug' => 'Bukan Slug!'])
            ->assertSessionHasErrors('slug');
    }

    public function test_menu_rubrics_keep_their_slug_and_stay_active(): void
    {
        $rubric = $this->rubric();

        $this->actingAs($this->admin)
            ->get(route('admin.categories.edit', $rubric))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('category.is_menu_rubric', true)
                ->whereNot('category.slug_lock_reason', null));

        $this->actingAs($this->admin)
            ->from(route('admin.categories.edit', $rubric))
            ->put(route('admin.categories.update', $rubric), ['name' => 'Riset & Inovasi', 'slug' => 'riset', 'is_active' => true])
            ->assertSessionHasErrors('slug');

        $this->actingAs($this->admin)
            ->from(route('admin.categories.edit', $rubric))
            ->put(route('admin.categories.update', $rubric), ['name' => 'Riset & Inovasi', 'slug' => 'riset-inovasi', 'is_active' => false])
            ->assertSessionHasErrors('is_active');

        // Nama dan deskripsi tetap boleh diubah.
        $this->actingAs($this->admin)
            ->put(route('admin.categories.update', $rubric), [
                'name' => 'Riset dan Inovasi',
                'slug' => 'riset-inovasi',
                'description' => 'Deskripsi baru.',
                'is_active' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Riset dan Inovasi', $rubric->fresh()->name);
        $this->assertSame('riset-inovasi', $rubric->fresh()->slug);
    }

    public function test_slug_is_locked_once_articles_use_the_category(): void
    {
        $category = Category::factory()->create(['name' => 'Agrikultur', 'slug' => 'agrikultur']);

        $this->actingAs($this->admin)
            ->put(route('admin.categories.update', $category), ['name' => 'Agrikultur', 'slug' => 'pertanian', 'is_active' => true])
            ->assertSessionHasNoErrors();
        $this->assertSame('pertanian', $category->fresh()->slug);

        Article::factory()->published()->create(['category_id' => $category->id]);

        $this->actingAs($this->admin)
            ->from(route('admin.categories.edit', $category))
            ->put(route('admin.categories.update', $category), ['name' => 'Agrikultur', 'slug' => 'agri', 'is_active' => true])
            ->assertSessionHasErrors('slug');
        $this->assertSame('pertanian', $category->fresh()->slug);
    }

    public function test_deactivated_category_is_no_longer_offered_to_writers(): void
    {
        $category = Category::factory()->create(['name' => 'Kategori Lama', 'slug' => 'kategori-lama']);

        $this->actingAs($this->admin)
            ->put(route('admin.categories.update', $category), ['name' => 'Kategori Lama', 'slug' => 'kategori-lama', 'is_active' => false])
            ->assertSessionHasNoErrors();

        $author = User::factory()->create();
        $author->assignRole('author');
        $this->actingAs($author)
            ->get(route('author.articles.create'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('categories', fn ($categories) => ! collect($categories)->pluck('name')->contains('Kategori Lama')));
    }

    public function test_only_unused_non_menu_categories_can_be_deleted(): void
    {
        $unused = Category::factory()->create(['slug' => 'tidak-dipakai']);
        $used = Category::factory()->create(['slug' => 'dipakai']);
        Article::factory()->create(['category_id' => $used->id]);

        $this->actingAs($this->admin)->delete(route('admin.categories.destroy', $unused))->assertSessionHas('success');
        $this->assertNull($unused->fresh());

        foreach ([$used, $this->rubric()] as $category) {
            $this->actingAs($this->admin)->delete(route('admin.categories.destroy', $category))->assertSessionHas('error');
            $this->assertNotNull($category->fresh());
        }

        $this->actingAs($this->admin)
            ->get(route('admin.categories.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('categories', fn ($categories) => collect($categories)->firstWhere('slug', 'dipakai')['can_delete'] === false
                    && collect($categories)->firstWhere('slug', 'riset-inovasi')['can_delete'] === false));
    }

    public function test_editors_cannot_manage_categories(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('editor');

        $this->actingAs($editor)->get(route('admin.categories.index'))->assertForbidden();
        $this->actingAs($editor)->post(route('admin.categories.store'), ['name' => 'Coba'])->assertForbidden();
        $this->assertFalse(Category::query()->where('name', 'Coba')->exists());
    }
}
