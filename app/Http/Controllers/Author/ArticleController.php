<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\EventTrackingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function index(): Response
    {
        $user = request()->user();

        $articles = Article::query()
            ->with('category:id,name')
            ->when(!$user->hasAnyRole(['admin', 'super-admin']), fn ($query) => $query->where('user_id', $user->id))
            ->latest()
            ->paginate(15)
            ->through(fn (Article $article) => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'status' => $article->status,
                'editorial_status' => $article->editorial_status,
                'category' => $article->category,
                'updated_at' => $article->updated_at->toDateTimeString(),
            ]);

        return Inertia::render('Author/Articles/Index', [
            'articles' => $articles,
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

    public function edit(Article $article): Response
    {
        $this->authorizeOwnership($article);

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

        return Inertia::render('Author/Articles/Edit', [
            'article' => $articlePayload,
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateArticleRequest $request, Article $article): RedirectResponse
    {
        $this->authorizeOwnership($article);

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

        if (($payload['title'] ?? null) && $payload['title'] !== $article->title) {
            $payload['slug'] = $this->makeUniqueSlug($payload['title'], $article->id);
        }

        $article->update($payload);

        return back()->with('success', 'Artikel berhasil diperbarui.');
    }

    public function submit(Article $article, EventTrackingService $eventTrackingService): RedirectResponse
    {
        $this->authorizeOwnership($article);

        $article->update([
            'status' => 'in_review',
            'editorial_status' => 'pending_review',
            'submitted_at' => now(),
        ]);

        $reviewers = User::query()
            ->role(['editor', 'admin', 'super-admin'])
            ->where('id', '!=', $article->user_id)
            ->get(['id']);

        foreach ($reviewers as $reviewer) {
            UserNotification::create([
                'user_id' => $reviewer->id,
                'type' => 'article.submitted',
                'title' => 'Artikel baru menunggu review',
                'body' => "Artikel '{$article->title}' telah dikirim untuk review.",
                'data_json' => [
                    'article_id' => $article->id,
                    'article_slug' => $article->slug,
                ],
            ]);
        }

        $eventTrackingService->track(request(), 'article.submitted_for_review', $article);

        return back()->with('success', 'Artikel dikirim untuk review editor.');
    }

    private function authorizeOwnership(Article $article): void
    {
        $user = request()->user();
        abort_unless($article->user_id === $user->id || $user->hasAnyRole(['admin', 'super-admin']), 403);
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
     * @param array<string,mixed> $payload
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
