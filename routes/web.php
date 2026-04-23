<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Author\ArticleController as AuthorArticleController;
use App\Http\Controllers\Author\InsightController as AuthorInsightController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Editor\ReviewQueueController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\ArticleController as PublicArticleController;
use App\Http\Controllers\Public\AuthorProfileController;
use App\Http\Controllers\Public\CommentController;
use App\Http\Controllers\Public\ReactionController;
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
Route::get('/trending', [PublicArticleController::class, 'trending'])->name('articles.trending');
Route::get('/articles/{article:slug}', [PublicArticleController::class, 'show'])->name('articles.show');
Route::get('/authors/{author:profile_slug}', [AuthorProfileController::class, 'show'])->name('authors.show');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/me/bookmarks', [BookmarkController::class, 'index'])->name('me.bookmarks.index');
    Route::get('/me/feed', [PublicArticleController::class, 'feed'])->name('me.feed');
    Route::post('/me/bookmarks', [BookmarkController::class, 'store'])->name('me.bookmarks.store');
    Route::delete('/me/bookmarks/{article:slug}', [BookmarkController::class, 'destroy'])->name('me.bookmarks.destroy');

    Route::get('/me/notifications', [NotificationController::class, 'index'])->name('me.notifications.index');
    Route::patch('/me/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('me.notifications.read-all');
    Route::patch('/me/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('me.notifications.read');

    Route::post('/authors/{author:profile_slug}/follow', [FollowController::class, 'store'])->name('authors.follow');
    Route::delete('/authors/{author:profile_slug}/follow', [FollowController::class, 'destroy'])->name('authors.unfollow');

    Route::post('/articles/{article:slug}/comments', [CommentController::class, 'store'])->name('articles.comments.store');
    Route::delete('/articles/{article:slug}/comments/{comment}', [CommentController::class, 'destroy'])->name('articles.comments.destroy');
    Route::post('/articles/{article:slug}/reactions', [ReactionController::class, 'store'])->name('articles.reactions.store');
    Route::delete('/articles/{article:slug}/reactions', [ReactionController::class, 'destroy'])->name('articles.reactions.destroy');
    Route::post('/articles/{article:slug}/reports', [ReportController::class, 'storeArticle'])->name('articles.reports.store');
    Route::post('/articles/{article:slug}/comments/{comment}/reports', [ReportController::class, 'storeComment'])->name('articles.comments.reports.store');
});

Route::middleware(['auth', 'verified', 'role_or_permission:author|verified-expert|admin|super-admin'])->prefix('author')->name('author.')->group(function () {
    Route::get('/articles', [AuthorArticleController::class, 'index'])->middleware('can:articles.create')->name('articles.index');
    Route::get('/articles/create', [AuthorArticleController::class, 'create'])->middleware('can:articles.create')->name('articles.create');
    Route::post('/articles', [AuthorArticleController::class, 'store'])->middleware('can:articles.create')->name('articles.store');
    Route::get('/articles/{article}/edit', [AuthorArticleController::class, 'edit'])->middleware('can:articles.update.own')->name('articles.edit');
    Route::put('/articles/{article}', [AuthorArticleController::class, 'update'])->middleware('can:articles.update.own')->name('articles.update');
    Route::patch('/articles/{article}/submit', [AuthorArticleController::class, 'submit'])->middleware('can:articles.submit')->name('articles.submit');
    Route::get('/insights', AuthorInsightController::class)->middleware('can:analytics.view.author')->name('insights.index');
    Route::get('/insights/export', [AuthorInsightController::class, 'export'])->middleware('can:analytics.view.author')->name('insights.export');
    Route::get('/insights/export-articles', [AuthorInsightController::class, 'exportArticles'])->middleware('can:analytics.view.author')->name('insights.export-articles');
});

Route::middleware(['auth', 'verified', 'role_or_permission:editor|admin|super-admin'])->prefix('editor')->name('editor.')->group(function () {
    Route::get('/reviews', [ReviewQueueController::class, 'index'])->middleware('can:articles.review')->name('reviews.index');
    Route::patch('/reviews/{article}/approve', [ReviewQueueController::class, 'approve'])->middleware('can:articles.publish')->name('reviews.approve');
    Route::patch('/reviews/{article}/reject', [ReviewQueueController::class, 'reject'])->middleware('can:articles.review')->name('reviews.reject');
    Route::patch('/reviews/{article}/request-revision', [ReviewQueueController::class, 'requestRevision'])->middleware('can:articles.review')->name('reviews.request-revision');
});

Route::middleware(['auth', 'verified', 'role_or_permission:editor|moderator|admin|super-admin'])->prefix('editor')->name('editor.')->group(function () {
    Route::get('/moderation/comments', [ModerationController::class, 'comments'])->middleware('can:comments.moderate')->name('moderation.comments.index');
    Route::patch('/moderation/comments/bulk', [ModerationController::class, 'bulkUpdateCommentStatus'])->middleware('can:comments.moderate')->name('moderation.comments.bulk-update');
    Route::patch('/moderation/comments/{comment}', [ModerationController::class, 'updateCommentStatus'])->middleware('can:comments.moderate')->name('moderation.comments.update');
    Route::get('/moderation/reports', [ModerationController::class, 'reports'])->middleware('can:reports.handle')->name('moderation.reports.index');
    Route::patch('/moderation/reports/bulk', [ModerationController::class, 'bulkUpdateReportStatus'])->middleware('can:reports.handle')->name('moderation.reports.bulk-update');
    Route::patch('/moderation/reports/bulk-subject-action', [ModerationController::class, 'bulkApplySubjectAction'])->middleware('can:reports.subject_action.basic')->name('moderation.reports.bulk-subject-action');
    Route::get('/moderation/reports/{report}', [ModerationController::class, 'showReport'])->middleware('can:reports.handle')->name('moderation.reports.show');
    Route::patch('/moderation/reports/{report}', [ModerationController::class, 'updateReportStatus'])->middleware('can:reports.handle')->name('moderation.reports.update');
    Route::patch('/moderation/reports/{report}/subject-action', [ModerationController::class, 'applySubjectAction'])->middleware('can:reports.subject_action.basic')->name('moderation.reports.subject-action');
});

Route::middleware(['auth', 'verified', 'role:admin|super-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->middleware('can:analytics.view.admin')->name('dashboard');
    Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->middleware('can:system.audit_logs.view')->name('audit-logs.index');

    Route::get('/roles', [RolePermissionController::class, 'index'])->middleware('can:system.roles.manage')->name('roles.index');
    Route::get('/roles/{role}/edit', [RolePermissionController::class, 'edit'])->middleware('can:system.roles.manage')->name('roles.edit');
    Route::put('/roles/{role}', [RolePermissionController::class, 'update'])->middleware('can:system.roles.manage')->name('roles.update');
});

require __DIR__.'/auth.php';
