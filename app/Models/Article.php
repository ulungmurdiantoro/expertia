<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use App\Enums\EditorialStatus;
use App\Models\Concerns\HasPublicId;
use App\Support\ArticleHtmlSanitizer;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    /**
     * Kolom statistik. Perubahannya bukan perubahan isi, jadi tidak boleh menggeser
     * updated_at yang dipakai sebagai dateModified (JSON-LD), lastmod (sitemap), dan
     * article:modified_time; tanpa ini setiap kunjungan membuat artikel tampak baru diubah.
     */
    public const STATISTIC_COLUMNS = [
        'view_count',
        'like_count',
        'comment_count',
        'bookmark_count',
        'share_count',
        'score_hotness',
        'score_trending',
    ];

    /**
     * save() dan update() yang hanya mengubah kolom statistik tidak menyentuh updated_at.
     */
    public function save(array $options = [])
    {
        $dirty = $this->exists ? array_keys($this->getDirty()) : [];

        if ($dirty !== [] && array_diff($dirty, self::STATISTIC_COLUMNS) === []) {
            return static::withoutTimestamps(fn () => parent::save($options));
        }

        return parent::save($options);
    }

    /**
     * increment()/decrement() pada kolom statistik tidak menyentuh updated_at.
     */
    protected function incrementOrDecrement($column, $amount, $extra, $method)
    {
        if ($this->exists && $extra === [] && in_array($column, self::STATISTIC_COLUMNS, true)) {
            return static::withoutTimestamps(fn () => parent::incrementOrDecrement($column, $amount, $extra, $method));
        }

        return parent::incrementOrDecrement($column, $amount, $extra, $method);
    }

    /**
     * Isi artikel selalu disaring saat disimpan, dari jalur mana pun (controller, seeder, tinker).
     */
    protected function content(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => app(ArticleHtmlSanitizer::class)->sanitize($value),
        );
    }

    public function hasStatus(ArticleStatus ...$statuses): bool
    {
        return in_array($this->status, array_map(fn (ArticleStatus $status) => $status->value, $statuses), true);
    }

    public function hasEditorialStatus(EditorialStatus ...$statuses): bool
    {
        return in_array($this->editorial_status, array_map(fn (EditorialStatus $status) => $status->value, $statuses), true);
    }

    public function isPublished(): bool
    {
        return $this->hasStatus(ArticleStatus::Published);
    }

    /**
     * Author hanya boleh mengubah naskah yang belum masuk antrean review atau belum tayang;
     * setelah itu perubahan lewat redaksi (lihat Kebijakan Editorial: koreksi).
     */
    public function isEditableByAuthor(): bool
    {
        return $this->hasStatus(ArticleStatus::Draft, ArticleStatus::Rejected);
    }

    public function canBeSubmitted(): bool
    {
        return $this->hasStatus(ArticleStatus::Draft, ArticleStatus::Rejected);
    }

    /**
     * Slug dibekukan begitu artikel disetujui agar URL yang sudah dibagikan tidak berubah.
     */
    public function hasFrozenSlug(): bool
    {
        return $this->published_at !== null
            || $this->hasStatus(ArticleStatus::Published, ArticleStatus::Scheduled, ArticleStatus::Archived);
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
