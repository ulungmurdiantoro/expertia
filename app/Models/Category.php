<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory, HasPublicId;

    protected $fillable = [
        'public_id',
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Ditautkan dari menu/beranda (config expertia.menu_rubrics): slug tetap, selalu aktif.
     */
    public function isMenuRubric(): bool
    {
        return in_array($this->slug, config('expertia.menu_rubrics', []), true);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
