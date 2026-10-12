<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory, MassPrunable;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'meta_json',
        'ip_hash',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta_json' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log audit disimpan dua tahun lalu dihapus oleh model:prune.
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(730));
    }
}
