<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        $superAdmin = $this->upsertSeedUser(
            name: 'Super Admin',
            email: 'superadmin@example.com',
            preferredUsername: 'superadmin',
            institution: 'Expertia HQ'
        );
        $superAdmin->syncRoles(['super-admin']);

        $this->seedRoleUser('admin', 'Admin Platform', 'admin@example.com', 'admin');
        $this->seedRoleUser('partner-manager', 'Partner Manager', 'partner.manager@example.com', 'partner-manager', 'Mitra Riset Nusantara');
        $this->seedRoleUser('editor', 'Editor Desk', 'editor@example.com', 'editor');
        $this->seedRoleUser('moderator', 'Moderator Komunitas', 'moderator@example.com', 'moderator');
        $this->seedRoleUser('verified-expert', 'Dr. Verified Expert', 'verified.expert@example.com', 'verified-expert', 'Institut Inovasi Indonesia');
        $this->seedRoleUser('author', 'Author Kontributor', 'author@example.com', 'author');
        $this->seedRoleUser('subscriber', 'Subscriber Premium', 'subscriber@example.com', 'subscriber');
        $this->seedRoleUser('user', 'User Reguler', 'user@example.com', 'user');

        // Kompatibilitas akun demo lama
        $legacyAdmin = $this->upsertSeedUser(
            name: 'Test User',
            email: 'test@example.com',
            preferredUsername: 'admin-legacy'
        );
        $legacyAdmin->syncRoles(['admin']);

        $this->call(ContentDummySeeder::class);
    }

    private function seedRoleUser(
        string $role,
        string $name,
        string $email,
        string $username,
        ?string $institution = null
    ): void {
        $user = $this->upsertSeedUser(
            name: $name,
            email: $email,
            preferredUsername: $username,
            institution: $institution
        );
        $user->syncRoles([$role]);
    }

    private function upsertSeedUser(
        string $name,
        string $email,
        string $preferredUsername,
        ?string $institution = null
    ): User {
        $user = User::firstOrNew(['email' => $email]);
        $user->name = $name;
        $user->password = Hash::make('password123');
        $user->email_verified_at = now();
        $user->status = 'active';
        $user->institution = $institution;

        $this->fillIdentityFields($user, $preferredUsername);

        return $user;
    }

    private function fillIdentityFields(User $user, string $preferredUsername): void
    {
        $currentUsername = (string) ($user->username ?? '');
        $usernameTakenByOtherUser = $currentUsername !== '' && User::query()
            ->when($user->exists, fn ($query) => $query->where('id', '!=', $user->id))
            ->where('username', $currentUsername)
            ->exists();

        if ($currentUsername === '' || $usernameTakenByOtherUser) {
            $user->username = $this->buildUniqueUsername($preferredUsername, $user->id);
        }

        $currentSlug = (string) ($user->profile_slug ?? '');
        $slugTakenByOtherUser = $currentSlug !== '' && User::query()
            ->when($user->exists, fn ($query) => $query->where('id', '!=', $user->id))
            ->where('profile_slug', $currentSlug)
            ->exists();

        if ($currentSlug === '' || $slugTakenByOtherUser) {
            $user->profile_slug = User::generateUniqueProfileSlug(
                (string) ($user->username ?: $user->name ?: 'user'),
                $user->id
            );
        }

        if (blank($user->public_id)) {
            $user->public_id = (string) Str::uuid();
        }

        $user->save();
    }

    private function buildUniqueUsername(string $base, ?int $ignoreId = null): string
    {
        $base = Str::slug($base, '');
        $base = $base !== '' ? $base : 'user';
        $candidate = $base;
        $suffix = 2;

        while (
            User::query()
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('username', $candidate)
                ->exists()
        ) {
            $candidate = "{$base}{$suffix}";
            $suffix++;
        }

        return $candidate;
    }
}
