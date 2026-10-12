<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Models\Article;
use App\Models\ArticleReview;
use App\Models\Category;
use App\Services\ArticleWorkflow;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
            'editorial_status' => (string) $request->query('editorial_status', ''),
            'category' => (string) $request->query('category', ''),
        ];

        $articles = Article::query()
            ->with(['category:id,name', 'latestReview.editor:id,name,email'])
            ->withCount(['comments', 'bookmarks', 'reactions'])
            ->when(! $user->can('articles.update.any'), fn ($query) => $query->where('user_id', $user->id))
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['editorial_status'] !== '', fn ($query) => $query->where('editorial_status', $filters['editorial_status']))
            ->when($filters['category'] !== '', fn ($query) => $query->where('category_id', $filters['category']))
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $q = $filters['q'];

                $query->where(function ($inner) use ($q): void {
                    $inner->where('title', 'like', "%{$q}%")
                        ->orWhere('excerpt', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Article $article) => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'status' => $article->status,
                'editorial_status' => $article->editorial_status,
                'submitted_at' => optional($article->submitted_at)?->toDateTimeString(),
                'published_at' => optional($article->published_at)?->toDateTimeString(),
                'scheduled_at' => optional($article->scheduled_at)?->toDateTimeString(),
                'category' => $article->category,
                'engagement' => [
                    'views' => $article->view_count,
                    'comments' => $article->comments_count,
                    'bookmarks' => $article->bookmarks_count,
                    'reactions' => $article->reactions_count,
                ],
                'latest_review' => $article->latestReview ? [
                    'action' => $article->latestReview->action,
                    'note' => $article->latestReview->note,
                    'reviewed_at' => optional($article->latestReview->reviewed_at)?->toDateTimeString(),
                    'editor' => $article->latestReview->editor,
                ] : null,
                'updated_at' => $article->updated_at->toDateTimeString(),
            ]);

        $statsQuery = Article::query()
            ->when(! $user->can('articles.update.any'), fn ($query) => $query->where('user_id', $user->id));

        return Inertia::render('Author/Articles/Index', [
            'articles' => $articles,
            'filters' => $filters,
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'stats' => [
                'total' => (clone $statsQuery)->count(),
                'draft' => (clone $statsQuery)->where('status', 'draft')->count(),
                'in_review' => (clone $statsQuery)->where('status', 'in_review')->count(),
                'published' => (clone $statsQuery)->where('status', 'published')->count(),
                'scheduled' => (clone $statsQuery)->where('status', 'scheduled')->count(),
                'revision_requested' => (clone $statsQuery)->where('editorial_status', 'revision_requested')->count(),
                'total_views' => (clone $statsQuery)->sum('view_count'),
                'total_bookmarks' => (clone $statsQuery)->sum('bookmark_count'),
                'reviews_today' => ArticleReview::query()
                    ->whereIn('article_id', (clone $statsQuery)->pluck('id'))
                    ->whereDate('reviewed_at', today())
                    ->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Author/Articles/Create', [
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreArticleRequest $request): RedirectResponse
    {
        $payload = $this->normalizeSchedulePayload($request->validated());
        $user = $request->user();
        $thumbnail = $request->file('thumbnail');

        if ($thumbnail instanceof UploadedFile) {
            $payload['thumbnail'] = $thumbnail->store('articles/thumbnails', 'public');
        }

        $article = Article::create([
            ...$payload,
            'user_id' => $user->id,
            'slug' => $this->makeUniqueSlug($payload['title']),
            'status' => 'draft',
            'editorial_status' => 'draft',
        ]);

        return redirect()->route('author.articles.edit', $article)->with('success', 'Draft artikel dibuat.');
    }

    public function edit(Request $request, Article $article): Response
    {
        Gate::authorize('view', $article);

        $articlePayload = $article->only([
            'id',
            'title',
            'slug',
            'excerpt',
            'content',
            'writing_source',
            'category_id',
            'thumbnail',
            'thumbnail_alt',
            'thumbnail_source',
            'status',
            'editorial_status',
            'meta_title',
            'meta_description',
        ]);
        $articlePayload['scheduled_at'] = optional($article->scheduled_at)
            ?->timezone(config('app.timezone'))
            ?->format('Y-m-d\TH:i');
        $articlePayload['scheduled_timezone'] = config('app.timezone');
        $articlePayload['thumbnail_url'] = $article->thumbnail ? Storage::url($article->thumbnail) : null;
        $articlePayload['can_update'] = $request->user()->can('update', $article);
        $articlePayload['can_submit'] = $request->user()->can('submit', $article);

        return Inertia::render('Author/Articles/Edit', [
            'article' => $articlePayload,
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateArticleRequest $request, Article $article): RedirectResponse
    {
        Gate::authorize('update', $article);

        $payload = $this->normalizeSchedulePayload($request->validated());
        $thumbnail = $request->file('thumbnail');

        if ($thumbnail instanceof UploadedFile) {
            if ($article->thumbnail) {
                Storage::disk('public')->delete($article->thumbnail);
            }

            $payload['thumbnail'] = $thumbnail->store('articles/thumbnails', 'public');
        } else {
            unset($payload['thumbnail']);
        }

        // URL artikel yang sudah disetujui tidak boleh berubah walau judulnya diedit.
        if (! $article->hasFrozenSlug() && ($payload['title'] ?? null) && $payload['title'] !== $article->title) {
            $payload['slug'] = $this->makeUniqueSlug($payload['title'], $article->id);
        }

        $article->update($payload);

        return back()->with('success', 'Artikel berhasil diperbarui.');
    }

    public function submit(Request $request, Article $article, ArticleWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('view', $article);

        $workflow->submit($article, $request);

        return back()->with('success', 'Artikel dikirim untuk review editor.');
    }

    private function makeUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $counter = 1;

        while (
            Article::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function normalizeSchedulePayload(array $payload): array
    {
        $timezone = (string) ($payload['scheduled_timezone'] ?? config('app.timezone', 'UTC'));

        if (blank($payload['scheduled_at'] ?? null)) {
            $payload['scheduled_at'] = null;
            unset($payload['scheduled_timezone']);

            return $payload;
        }

        try {
            $scheduledAt = Carbon::parse((string) $payload['scheduled_at'], $timezone);
        } catch (\Throwable) {
            $scheduledAt = Carbon::parse((string) $payload['scheduled_at'], config('app.timezone', 'UTC'));
        }

        $payload['scheduled_at'] = $scheduledAt->utc()->toDateTimeString();
        unset($payload['scheduled_timezone']);

        return $payload;
    }
}
