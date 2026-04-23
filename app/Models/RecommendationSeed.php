<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class RecommendationSeed extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'article_id',
        'source',
        'score',
        'reason',
        'meta_json',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'meta_json' => 'array',
            'generated_at' => 'datetime',
            'score' => 'float',
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
}
