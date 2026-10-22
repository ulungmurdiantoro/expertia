<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'article_id',
        'user_id',
        'parent_id',
        'content',
        'status',
        'moderated_by',
        'moderated_at',
    ];

    protected function casts(): array
    {
        return [
            'moderated_at' => 'datetime',
        ];
    }

    /**
     * Komentar yang tampil di halaman artikel (lihat ArticleCommentThread): disetujui,
     * dan bila berupa balasan, induknya komentar tingkat atas yang disetujui dan belum dihapus.
     */
    public function scopeVisibleInThread(Builder $query): void
    {
        $query->where('status', 'approved')
            ->where(fn (Builder $inner) => $inner
                ->whereNull('parent_id')
                ->orWhereHas('parent', fn (Builder $parent) => $parent->where('status', 'approved')->whereNull('parent_id')));
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
