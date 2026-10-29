<?php

namespace App\Services;

use App\Http\Resources\ArticleCardResource;
use App\Http\Resources\PublicAuthorResource;
use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Blok tambahan di halaman 1 daftar artikel (tanpa pencarian): sorotan per rubrik,
 * artikel populer, topik, dan kontributor. Halaman berikutnya dan hasil pencarian
 * tetap berupa daftar biasa.
 */
class ArticleShowcaseService
{
    public const AUTHOR_COLUMNS = 'author:id,name,profile_slug,avatar,position,institution';

    /**
     * @return array<string, mixed>
     */
    public function forRubric(Request $request, Category $category): array
    {
        $inRubric = fn (Builder $query) => $query->publiclyVisible()
            ->whereNotNull('published_at')
            ->where('category_id', $category->id);

        $contributors = User::query()
            ->whereHas('articles', $inRubric)
            ->withCount(['articles as rubric_articles_count' => $inRubric])
            ->orderByDesc('rubric_articles_count')
            ->orderBy('name')
            ->get(['id', 'name', 'profile_slug', 'avatar', 'position', 'institution']);

        return [
            'kind' => 'rubric',
            'article_count' => $inRubric(Article::query())->count(),
            'contributor_count' => $contributors->count(),
            'contributors' => $contributors->take(5)
                ->map(fn (User $user) => [
                    ...PublicAuthorResource::make($user)->resolve($request),
                    'article_count' => (int) $user->rubric_articles_count,
                ])
                ->values()
                ->all(),
            'popular' => $this->popular($request, $inRubric, 4),
            'topics' => $this->topics($inRubric, 8),
        ];
    }

    /**
     * @param  list<string>  $rubricSlugs
     * @param  list<int>  $excludeIds
     * @return array<string, mixed>
     */
    public function forHub(Request $request, array $rubricSlugs, array $excludeIds = []): array
    {
        $published = fn (Builder $query) => $query->publiclyVisible()->whereNotNull('published_at');

        $categories = Category::query()
            ->whereIn('slug', $rubricSlugs)
            ->where('is_active', true)
            ->get(['id', 'name', 'slug'])
            ->keyBy('slug');

        $sections = [];
        foreach ($rubricSlugs as $slug) {
            $category = $categories->get($slug);
            if (! $category) {
                continue;
            }

            $inRubric = fn (Builder $query) => $published($query)->where('category_id', $category->id);

            $sections[] = [
                'slug' => $slug,
                'name' => $category->name,
                'article_count' => $inRubric(Article::query())->count(),
                'articles' => $inRubric($this->cardQuery())
                    ->whereKeyNot($excludeIds)
                    ->latest('published_at')
                    ->limit(4)
                    ->get()
                    ->map(fn (Article $article) => ArticleCardResource::make($article)->resolve($request))
                    ->all(),
            ];
        }

        $newContributors = User::query()
            ->whereHas('articles', $published)
            ->latest('created_at')
            ->limit(3)
            ->get(['id', 'name', 'profile_slug', 'avatar', 'position', 'institution'])
            ->map(fn (User $user) => PublicAuthorResource::make($user)->resolve($request))
            ->all();

        return [
            'kind' => 'hub',
            'sections' => $sections,
            'popular' => $this->popular($request, $published, 3),
            'topics' => $this->topics($published, 6),
            'new_contributors' => $newContributors,
        ];
    }

    private function cardQuery(): Builder
    {
        return Article::query()->with([self::AUTHOR_COLUMNS, 'category:id,name,slug', 'tags:id,name,slug']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function popular(Request $request, \Closure $scope, int $limit): array
    {
        return $scope($this->cardQuery())
            ->where('view_count', '>', 0)
            ->orderByDesc('view_count')
            ->latest('published_at')
            ->limit($limit)
            ->get()
            ->map(fn (Article $article) => ArticleCardResource::make($article)->resolve($request))
            ->all();
    }

    /**
     * @return list<array{name: string, slug: string}>
     */
    private function topics(\Closure $scope, int $limit): array
    {
        return Tag::query()
            ->whereHas('articles', $scope)
            ->withCount(['articles as published_count' => $scope])
            ->orderByDesc('published_count')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'slug'])
            ->map(fn (Tag $tag) => ['name' => $tag->name, 'slug' => $tag->slug])
            ->all();
    }
}
