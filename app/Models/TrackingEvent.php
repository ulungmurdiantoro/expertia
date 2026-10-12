<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingEvent extends Model
{
    use HasFactory, HasPublicId, MassPrunable;

    protected $fillable = [
        'public_id',
        'user_id',
        'article_id',
        'event_type',
        'event_context',
        'session_id',
        'ip_hash',
        'meta_json',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'meta_json' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Dihapus oleh model:prune setelah setahun. Insights memakai paling jauh 180 hari (90 hari + periode pembanding).
     */
    public function prunable(): Builder
    {
        return static::where('occurred_at', '<', now()->subDays(365));
    }
}
