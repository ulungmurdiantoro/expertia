<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Author\ArticleController as AuthorArticleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Editor\ReviewQueueController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\ArticleController as PublicArticleController;
use App\Http\Controllers\Public\AuthorProfileController;
use App\Http\Controllers\Public\CommentController;
use App\Http\Controllers\Public\ReportController;
use App\Http\Controllers\Editor\ModerationController;
use App\Http\Controllers\User\BookmarkController;
use App\Http\Controllers\User\FollowController;
use App\Http\Controllers\User\NotificationController;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

Route::get('/', function () {
    $featured = null;
    $latest = collect();
    $opinion = collect();
    $popularCategories = collect();

    if (Schema::hasTable('articles') && Schema::hasTable('categories')) {
        $baseQuery = Article::query()
            ->with(['author:id,name,profile_slug', 'category:id,name,slug'])
            ->where('status', 'published')
            ->whereNotNull('published_at');

        $featured = (clone $baseQuery)
            ->latest('published_at')
            ->first();

        $latest = (clone $baseQuery)
            ->when($featured !== null, fn ($query) => $query->where('id', '!=', $featured->id))
            ->latest('published_at')
            ->limit(8)
            ->get();

        $opinion = (clone $baseQuery)
            ->whereHas('category', fn ($query) => $query->whereIn('slug', ['opini', 'opinion']))
            ->latest('published_at')
            ->limit(4)
            ->get();

        $popularCategories = Category::query()
            ->where('is_active', true)
            ->withCount([
                'articles as published_articles_count' => fn ($query) => $query
                    ->where('status', 'published')
                    ->whereNotNull('published_at'),
            ])
            ->orderByDesc('published_articles_count')
            ->limit(8)
            ->get();
    }

    $toArticleCard = fn (Article $article) => [
        'id' => $article->id,
        'title' => $article->title,
        'slug' => $article->slug,
        'excerpt' => $article->excerpt,
        'thumbnail_url' => $article->thumbnail ? Storage::url($article->thumbnail) : null,
        'thumbnail_alt' => $article->thumbnail_alt,
        'published_at' => optional($article->published_at)?->toDateTimeString(),
        'author' => $article->author,
        'category' => $article->category,
        'view_count' => $article->view_count,
        'comment_count' => $article->comment_count,
    ];

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'featured' => $featured ? $toArticleCard($featured) : null,
        'latest' => $latest->map($toArticleCard)->values(),
        'opinion' => $opinion->map($toArticleCard)->values(),
        'popularCategories' => $popularCategories->map(fn (Category $category) => [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'published_articles_count' => $category->published_articles_count,
        ])->values(),
    ]);
});

Route::get('/articles', [PublicArticleController::class, 'index'])->name('articles.index');
Route::get('/articles/{article:slug}', [PublicArticleController::class, 'show'])->name('articles.show');
Route::get('/authors/{author:profile_slug}', [AuthorProfileController::class, 'show'])->name('authors.show');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/me/bookmarks', [BookmarkController::class, 'index'])->name('me.bookmarks.index');
    Route::post('/me/bookmarks', [BookmarkController::class, 'store'])->name('me.bookmarks.store');
    Route::delete('/me/bookmarks/{article:slug}', [BookmarkController::class, 'destroy'])->name('me.bookmarks.destroy');

    Route::get('/me/notifications', [NotificationController::class, 'index'])->name('me.notifications.index');

    Route::post('/authors/{author:profile_slug}/follow', [FollowController::class, 'store'])->name('authors.follow');
    Route::delete('/authors/{author:profile_slug}/follow', [FollowController::class, 'destroy'])->name('authors.unfollow');

    Route::post('/articles/{article:slug}/comments', [CommentController::class, 'store'])->name('articles.comments.store');
    Route::delete('/articles/{article:slug}/comments/{comment}', [CommentController::class, 'destroy'])->name('articles.comments.destroy');
    Route::post('/articles/{article:slug}/reports', [ReportController::class, 'storeArticle'])->name('articles.reports.store');
    Route::post('/articles/{article:slug}/comments/{comment}/reports', [ReportController::class, 'storeComment'])->name('articles.comments.reports.store');
});

Route::middleware(['auth', 'verified', 'role_or_permission:author|admin'])->prefix('author')->name('author.')->group(function () {
    Route::get('/articles', [AuthorArticleController::class, 'index'])->name('articles.index');
    Route::get('/articles/create', [AuthorArticleController::class, 'create'])->name('articles.create');
    Route::post('/articles', [AuthorArticleController::class, 'store'])->name('articles.store');
    Route::get('/articles/{article}/edit', [AuthorArticleController::class, 'edit'])->name('articles.edit');
    Route::put('/articles/{article}', [AuthorArticleController::class, 'update'])->name('articles.update');
    Route::patch('/articles/{article}/submit', [AuthorArticleController::class, 'submit'])->name('articles.submit');
});

Route::middleware(['auth', 'verified', 'role_or_permission:editor|admin'])->prefix('editor')->name('editor.')->group(function () {
    Route::get('/reviews', [ReviewQueueController::class, 'index'])->name('reviews.index');
    Route::patch('/reviews/{article}/approve', [ReviewQueueController::class, 'approve'])->name('reviews.approve');
    Route::patch('/reviews/{article}/reject', [ReviewQueueController::class, 'reject'])->name('reviews.reject');
    Route::patch('/reviews/{article}/request-revision', [ReviewQueueController::class, 'requestRevision'])->name('reviews.request-revision');

    Route::get('/moderation/comments', [ModerationController::class, 'comments'])->name('moderation.comments.index');
    Route::patch('/moderation/comments/bulk', [ModerationController::class, 'bulkUpdateCommentStatus'])->name('moderation.comments.bulk-update');
    Route::patch('/moderation/comments/{comment}', [ModerationController::class, 'updateCommentStatus'])->name('moderation.comments.update');
    Route::get('/moderation/reports', [ModerationController::class, 'reports'])->name('moderation.reports.index');
    Route::patch('/moderation/reports/bulk', [ModerationController::class, 'bulkUpdateReportStatus'])->name('moderation.reports.bulk-update');
    Route::patch('/moderation/reports/bulk-subject-action', [ModerationController::class, 'bulkApplySubjectAction'])->name('moderation.reports.bulk-subject-action');
    Route::get('/moderation/reports/{report}', [ModerationController::class, 'showReport'])->name('moderation.reports.show');
    Route::patch('/moderation/reports/{report}', [ModerationController::class, 'updateReportStatus'])->name('moderation.reports.update');
    Route::patch('/moderation/reports/{report}/subject-action', [ModerationController::class, 'applySubjectAction'])->name('moderation.reports.subject-action');
});

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');
});

require __DIR__.'/auth.php';
