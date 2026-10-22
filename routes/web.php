<?php

use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Author\ArticleController as AuthorArticleController;
use App\Http\Controllers\Author\InsightController as AuthorInsightController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Editor\ModerationController;
use App\Http\Controllers\Editor\ReviewQueueController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\ArticleController as PublicArticleController;
use App\Http\Controllers\Public\AuthorProfileController;
use App\Http\Controllers\Public\CommentController;
use App\Http\Controllers\Public\DiscoveryController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\ReactionController;
use App\Http\Controllers\Public\ReportController;
use App\Http\Controllers\Public\ShareController;
use App\Http\Controllers\User\BookmarkController;
use App\Http\Controllers\User\FollowController;
use App\Http\Controllers\User\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/kontributor', [PageController::class, 'contributor'])->name('kontributor');
Route::get('/tentang', [PageController::class, 'about'])->name('tentang');
Route::get('/kebijakan-editorial', [PageController::class, 'editorialPolicy'])->name('kebijakan.editorial');
Route::get('/kebijakan-privasi', [PageController::class, 'privacyPolicy'])->name('kebijakan.privasi');
Route::get('/kontak', [PageController::class, 'contact'])->name('kontak');

// Untuk mesin pencari dan AI (lihat DiscoveryController).
Route::get('/robots.txt', [DiscoveryController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [DiscoveryController::class, 'sitemap'])->name('sitemap');
Route::get('/feed.xml', [DiscoveryController::class, 'feed'])->name('feed');
Route::get('/llms.txt', [DiscoveryController::class, 'llms'])->name('llms');
Route::get('/llms-full.txt', [DiscoveryController::class, 'llmsFull'])->name('llms.full');

Route::get('/articles', [PublicArticleController::class, 'index'])->name('articles.index');
Route::get('/trending', [PublicArticleController::class, 'trending'])->name('articles.trending');
// Harus sebelum route articles.show agar ".md" tidak terbaca sebagai bagian slug.
Route::get('/articles/{article:slug}.md', [DiscoveryController::class, 'articleMarkdown'])
    ->where('article', '[A-Za-z0-9-]+')
    ->name('articles.markdown');
Route::get('/articles/{article:slug}', [PublicArticleController::class, 'show'])->name('articles.show');
Route::get('/authors/{author:profile_slug}', [AuthorProfileController::class, 'show'])->name('authors.show');
// Publik agar tombol Bagikan juga tercatat untuk pengunjung yang belum masuk.
Route::post('/articles/{article:slug}/shares', [ShareController::class, 'store'])->middleware('throttle:shares')->name('articles.shares.store');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/me/bookmarks', [BookmarkController::class, 'index'])->name('me.bookmarks.index');
    Route::get('/me/feed', [PublicArticleController::class, 'feed'])->name('me.feed');
    Route::post('/me/bookmarks', [BookmarkController::class, 'store'])->middleware('throttle:interactions')->name('me.bookmarks.store');
    Route::delete('/me/bookmarks/{article:slug}', [BookmarkController::class, 'destroy'])->name('me.bookmarks.destroy');

    Route::get('/me/notifications', [NotificationController::class, 'index'])->name('me.notifications.index');
    Route::patch('/me/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('me.notifications.read-all');
    Route::patch('/me/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('me.notifications.read');

    Route::post('/authors/{author:profile_slug}/follow', [FollowController::class, 'store'])->middleware('throttle:interactions')->name('authors.follow');
    Route::delete('/authors/{author:profile_slug}/follow', [FollowController::class, 'destroy'])->name('authors.unfollow');

    Route::post('/articles/{article:slug}/comments', [CommentController::class, 'store'])->middleware('throttle:comments')->name('articles.comments.store');
    Route::delete('/articles/{article:slug}/comments/{comment}', [CommentController::class, 'destroy'])->name('articles.comments.destroy');
    Route::post('/articles/{article:slug}/reactions', [ReactionController::class, 'store'])->middleware('throttle:interactions')->name('articles.reactions.store');
    Route::delete('/articles/{article:slug}/reactions', [ReactionController::class, 'destroy'])->name('articles.reactions.destroy');
    Route::post('/articles/{article:slug}/reports', [ReportController::class, 'storeArticle'])->middleware('throttle:reports')->name('articles.reports.store');
    Route::post('/articles/{article:slug}/comments/{comment}/reports', [ReportController::class, 'storeComment'])->middleware('throttle:reports')->name('articles.comments.reports.store');
});

Route::middleware(['auth', 'verified', 'role_or_permission:author|verified-expert|editor|admin|super-admin'])->prefix('author')->name('author.')->group(function () {
    Route::get('/articles', [AuthorArticleController::class, 'index'])->middleware('can:articles.create')->name('articles.index');
    Route::get('/articles/create', [AuthorArticleController::class, 'create'])->middleware('can:articles.create')->name('articles.create');
    Route::post('/articles', [AuthorArticleController::class, 'store'])->middleware('can:articles.create')->name('articles.store');
    Route::get('/articles/{article}/edit', [AuthorArticleController::class, 'edit'])->middleware('can:articles.update.own')->name('articles.edit');
    Route::put('/articles/{article}', [AuthorArticleController::class, 'update'])->middleware('can:articles.update.own')->name('articles.update');
    Route::patch('/articles/{article}/submit', [AuthorArticleController::class, 'submit'])->middleware('can:articles.submit')->name('articles.submit');
    Route::delete('/articles/{article}', [AuthorArticleController::class, 'destroy'])->middleware('can:articles.delete')->name('articles.destroy');
    // Membatalkan hide/archive dari moderasi; hak yang sama dengan archive_article.
    Route::patch('/articles/{article}/republish', [AuthorArticleController::class, 'republish'])->middleware('can:reports.subject_action.archive')->name('articles.republish');
    Route::get('/insights', AuthorInsightController::class)->middleware('can:analytics.view.author')->name('insights.index');
    Route::get('/insights/export', [AuthorInsightController::class, 'export'])->middleware('can:analytics.view.author')->name('insights.export');
    Route::get('/insights/export-articles', [AuthorInsightController::class, 'exportArticles'])->middleware('can:analytics.view.author')->name('insights.export-articles');
});

Route::middleware(['auth', 'verified', 'role_or_permission:editor|admin|super-admin'])->prefix('editor')->name('editor.')->group(function () {
    Route::get('/reviews', [ReviewQueueController::class, 'index'])->middleware('can:articles.review')->name('reviews.index');
    Route::get('/reviews/{article}/preview', [ReviewQueueController::class, 'preview'])->middleware('can:articles.review')->name('reviews.preview');
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

    // Akun kontributor dibuat redaksi setelah kurasi; pendaftaran author publik ditutup.
    Route::get('/contributors/create', [RegisteredUserController::class, 'createAuthor'])->middleware('can:users.assign_role')->name('contributors.create');
    Route::post('/contributors', [RegisteredUserController::class, 'storeAuthor'])->middleware('can:users.assign_role')->name('contributors.store');

    Route::get('/categories', [AdminCategoryController::class, 'index'])->middleware('can:categories.manage')->name('categories.index');
    Route::get('/categories/create', [AdminCategoryController::class, 'create'])->middleware('can:categories.manage')->name('categories.create');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->middleware('can:categories.manage')->name('categories.store');
    Route::get('/categories/{category}/edit', [AdminCategoryController::class, 'edit'])->middleware('can:categories.manage')->name('categories.edit');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->middleware('can:categories.manage')->name('categories.update');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->middleware('can:categories.manage')->name('categories.destroy');

    Route::get('/users', [AdminUserController::class, 'index'])->middleware('can:users.assign_role')->name('users.index');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->middleware('can:users.assign_role')->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->middleware('can:users.assign_role')->name('users.update');

    Route::get('/roles', [RolePermissionController::class, 'index'])->middleware('can:system.roles.manage')->name('roles.index');
    Route::get('/roles/{role}/edit', [RolePermissionController::class, 'edit'])->middleware('can:system.roles.manage')->name('roles.edit');
    Route::put('/roles/{role}', [RolePermissionController::class, 'update'])->middleware('can:system.roles.manage')->name('roles.update');
});

require __DIR__.'/auth.php';
