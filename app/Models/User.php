<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

/**
 * Email wajib diverifikasi (MustVerifyEmail) sebelum memakai fitur yang butuh login,
 * kecuali halaman Profile untuk memperbaiki alamat email.
 */
class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
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
        'position',
        'gender',
        'whatsapp_number',
        'institution_unit',
        'linkedin_url',
        'expertise_areas',
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
        'whatsapp_number',
        'gender',
    ];

    protected $appends = [
        'avatar_url',
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
            'expertise_areas' => 'array',
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

    /**
     * Menghapus user ikut menghapus permanen (cascade) artikel, komentar pembaca di
     * artikel itu, dan riwayat review. Karena itu hanya akun pembaca tanpa jejak
     * redaksi yang boleh menghapus dirinya sendiri; lainnya lewat redaksi.
     */
    public function canDeleteOwnAccount(): bool
    {
        return ! $this->hasAnyRole(['author', 'verified-expert', 'editor', 'moderator', 'admin', 'super-admin'])
            && ! $this->articles()->withTrashed()->exists()
            && ! ArticleReview::query()->where('editor_id', $this->id)->exists();
    }

    /**
     * Selama verifikasi email dimatikan (config expertia.require_email_verification), semua
     * akun dianggap terverifikasi: middleware 'verified' meloloskan, dan listener Registered
     * tidak mengirim email verifikasi.
     */
    public function hasVerifiedEmail(): bool
    {
        return ! config('expertia.require_email_verification') || parent::hasVerifiedEmail();
    }

    /**
     * Email notifikasi (verifikasi dan lainnya) memakai teks bahasa Indonesia dari lang/id.json.
     */
    public function preferredLocale(): string
    {
        return 'id';
    }

    /**
     * Halaman /authors/{slug} dan tombol follow hanya untuk kontributor: akun yang bisa
     * menulis, atau yang pernah menerbitkan artikel (mis. role-nya sudah diganti).
     * Pakai withExists('articles as has_public_articles') di daftar agar tidak N+1.
     */
    public function hasPublicProfile(): bool
    {
        return $this->can('articles.create')
            || (bool) ($this->has_public_articles ?? $this->articles()->publiclyVisible()->exists());
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

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEvent::class);
    }

    public function recommendationSeeds(): HasMany
    {
        return $this->hasMany(RecommendationSeed::class);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }

        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }

        return Storage::disk('public')->url($this->avatar);
    }
}
