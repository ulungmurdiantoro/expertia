<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use HasFactory, HasPublicId, SoftDeletes;

    protected $fillable = [
        'public_id',
        'user_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'writing_source',
        'thumbnail',
        'thumbnail_alt',
        'thumbnail_source',
        'status',
        'editorial_status',
        'visibility',
        'meta_title',
        'meta_description',
        'canonical_url',
        'submitted_at',
        'reviewed_at',
        'published_at',
        'scheduled_at',
        'view_count',
        'like_count',
        'comment_count',
        'bookmark_count',
        'share_count',
        'score_hotness',
        'score_trending',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ArticleReview::class);
    }

    public function latestReview(): HasOne
    {
        return $this->hasOne(ArticleReview::class)->latestOfMany('reviewed_at');
    }

    public function views(): HasMany
    {
        return $this->hasMany(ArticleView::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(ArticleReaction::class);
    }

    public function recommendationSeeds(): HasMany
    {
        return $this->hasMany(RecommendationSeed::class);
    }
}
