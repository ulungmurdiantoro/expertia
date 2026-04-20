<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\HasPublicId;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasPublicId;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'name',
        'username',
        'profile_slug',
        'email',
        'password',
        'avatar',
        'bio',
        'website',
        'institution',
        'social_links',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'social_links' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if (blank($user->profile_slug)) {
                $user->profile_slug = self::generateUniqueProfileSlug(
                    (string) ($user->username ?: $user->name ?: 'user')
                );
            }
        });

        static::updating(function (self $user): void {
            if (blank($user->profile_slug)) {
                $user->profile_slug = self::generateUniqueProfileSlug(
                    (string) ($user->username ?: $user->name ?: 'user'),
                    $user->id
                );
            }
        });
    }

    public static function generateUniqueProfileSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source);
        $base = $base !== '' ? $base : 'user';

        $candidate = $base;
        $suffix = 2;

        while (
            static::query()
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('profile_slug', $candidate)
                ->exists()
        ) {
            $candidate = "{$base}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function follows(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'follows',
            'follower_id',
            'author_id'
        )->withTimestamps();
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'follows',
            'author_id',
            'follower_id'
        )->withTimestamps();
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }
}
