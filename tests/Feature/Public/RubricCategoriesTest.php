<?php

namespace Tests\Feature\Public;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Rubrik di menu, footer, dan beranda adalah kategori di database. Bila slug di frontend
 * tidak punya kategori, tautannya selalu menampilkan daftar kosong.
 */
class RubricCategoriesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    private function frontendRubricSlugs(): array
    {
        preg_match('/export const rubrics = \[(.*?)\];/s', (string) file_get_contents(resource_path('js/site.js')), $block);
        preg_match_all("/slug: '([^']+)'/", $block[1] ?? '', $rubrics);

        $linked = [];
        foreach (['js/Components/PublicHeader.vue', 'js/Components/PublicFooter.vue'] as $file) {
            preg_match_all("/category: '([^']+)'/", (string) file_get_contents(resource_path($file)), $matches);
            $linked = [...$linked, ...$matches[1]];
        }

        return array_values(array_unique([...$rubrics[1], ...$linked]));
    }

    public function test_every_rubric_linked_from_the_site_exists_as_an_active_category(): void
    {
        $slugs = $this->frontendRubricSlugs();
        $this->assertContains('riset-inovasi', $slugs);
        $this->assertContains('perspektif-pakar', $slugs);

        foreach ($slugs as $slug) {
            $this->assertTrue(
                Category::query()->where('slug', $slug)->where('is_active', true)->exists(),
                "Rubrik '{$slug}' ditautkan dari situs tetapi tidak ada sebagai kategori aktif.",
            );
        }
    }

    public function test_rubric_link_lists_the_articles_of_that_rubric(): void
    {
        Article::factory()->published()->create([
            'category_id' => Category::query()->where('slug', 'perspektif-pakar')->value('id'),
            'title' => 'Analisis dari Lapangan',
        ]);

        $this->get('/articles?category=perspektif-pakar')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pageMeta.title', 'Perspektif Pakar')
                ->has('articles.data', 1)
                ->where('articles.data.0.title', 'Analisis dari Lapangan'));
    }

    public function test_writers_can_pick_the_rubrics(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $author = User::factory()->create();
        $author->assignRole('author');

        $this->actingAs($author)
            ->get(route('author.articles.create'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('categories', fn ($categories) => collect($categories)->pluck('name')->intersect([
                    'Riset & Inovasi', 'Perspektif Pakar', 'Profil Pakar', 'Kebijakan & Solusi',
                ])->count() === 4));
    }

    public function test_migration_keeps_an_existing_category_with_the_same_slug(): void
    {
        Category::query()->where('slug', 'riset-inovasi')->update(['name' => 'Riset dan Inovasi']);

        (require database_path('migrations/2026_10_23_000002_create_rubric_categories.php'))->up();

        $this->assertSame(1, Category::query()->where('slug', 'riset-inovasi')->count());
        $this->assertSame('Riset dan Inovasi', Category::query()->where('slug', 'riset-inovasi')->value('name'));
    }
}
