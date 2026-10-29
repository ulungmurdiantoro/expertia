<?php

namespace Tests\Feature\Public;

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Halaman 1 daftar artikel dan halaman rubrik membawa blok sorotan; pencarian dan
 * halaman berikutnya tetap daftar biasa.
 */
class ArticleShowcaseTest extends TestCase
{
    use RefreshDatabase;

    private function rubricId(string $slug): int
    {
        return Category::query()->where('slug', $slug)->value('id');
    }

    public function test_rubric_page_lists_contributors_topics_and_popular_articles(): void
    {
        $expert = User::factory()->create(['name' => 'Laila Maharani', 'position' => 'Dokter', 'institution' => 'RS Daerah']);
        $tag = Tag::query()->create(['name' => 'Kesehatan', 'slug' => 'kesehatan']);

        $popular = Article::factory()->published()->create([
            'user_id' => $expert->id,
            'category_id' => $this->rubricId('perspektif-pakar'),
            'view_count' => 40,
        ]);
        $popular->tags()->attach($tag);
        Article::factory()->published()->create([
            'user_id' => $expert->id,
            'category_id' => $this->rubricId('perspektif-pakar'),
        ]);
        // Artikel rubrik lain tidak ikut dihitung.
        Article::factory()->published()->create(['category_id' => $this->rubricId('riset-inovasi'), 'view_count' => 99]);

        $this->get('/articles?category=perspektif-pakar')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('showcase.kind', 'rubric')
                ->where('showcase.article_count', 2)
                ->where('showcase.contributor_count', 1)
                ->where('showcase.contributors.0.name', 'Laila Maharani')
                ->where('showcase.contributors.0.position', 'Dokter')
                ->where('showcase.contributors.0.article_count', 2)
                ->where('showcase.topics.0.slug', 'kesehatan')
                ->has('showcase.popular', 1)
                ->where('showcase.popular.0.id', $popular->id)
                ->where('articles.data.0.author.institution', 'RS Daerah'));
    }

    public function test_articles_page_groups_rubrics_without_repeating_the_spotlight(): void
    {
        $riset = Article::factory()->published()->count(5)->sequence(
            fn ($sequence) => ['published_at' => now()->subDays($sequence->index)],
        )->create(['category_id' => $this->rubricId('riset-inovasi')]);

        $this->get('/articles')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('showcase.kind', 'hub')
                ->where('showcase.sections', function ($sections) use ($riset) {
                    $sections = collect($sections)->keyBy('slug');
                    $shown = collect($sections['riset-inovasi']['articles'])->pluck('id');

                    // Tiga artikel terbaru sudah jadi sorotan; blok rubrik memuat sisanya.
                    return $sections['riset-inovasi']['article_count'] === 5
                        && $shown->intersect($riset->take(3)->pluck('id'))->isEmpty()
                        && $shown->count() === 2
                        && $sections['kebijakan-solusi']['article_count'] === 0;
                }));
    }

    public function test_search_and_later_pages_have_no_showcase(): void
    {
        Article::factory()->published()->count(13)->create(['category_id' => $this->rubricId('riset-inovasi')]);

        foreach (['/articles?q=apa', '/articles?page=2', '/articles?category=riset-inovasi&sort=hot', '/articles?category=riset-inovasi&page=2'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page->where('showcase', null));
        }
    }
}
