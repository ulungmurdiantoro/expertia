<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value_text',
        'value_json',
    ];

    protected function casts(): array
    {
        return [
            'value_json' => 'array',
        ];
    }
}
