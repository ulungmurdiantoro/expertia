<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleView extends Model
{
    use HasFactory, MassPrunable;

    public $timestamps = false;

    protected $fillable = [
        'article_id',
        'user_id',
        'session_id',
        'ip_hash',
        'user_agent',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Dihapus oleh model:prune setelah setahun. Insights memakai paling jauh 180 hari (90 hari + periode pembanding).
     */
    public function prunable(): Builder
    {
        return static::where('viewed_at', '<', now()->subDays(365));
    }
}
