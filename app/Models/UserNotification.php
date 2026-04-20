<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    use HasFactory, HasPublicId;

    protected $fillable = [
        'public_id',
        'user_id',
        'type',
        'title',
        'body',
        'data_json',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data_json' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
