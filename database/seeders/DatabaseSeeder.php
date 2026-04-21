<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        $admin = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'username' => 'admin',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'status' => 'active',
            ]
        );

        if (blank($admin->username)) {
            $admin->username = 'admin';
        }

        if (blank($admin->profile_slug)) {
            $admin->profile_slug = User::generateUniqueProfileSlug(
                (string) ($admin->username ?: $admin->name ?: 'admin'),
                $admin->id
            );
        }

        if ($admin->isDirty()) {
            $admin->save();
        }

        User::query()
            ->where(fn ($query) => $query->whereNull('profile_slug')->orWhere('profile_slug', ''))
            ->orderBy('id')
            ->each(function (User $user): void {
                $user->profile_slug = User::generateUniqueProfileSlug(
                    (string) ($user->username ?: $user->name ?: 'user'),
                    $user->id
                );

                $user->save();
            });

        $admin->assignRole('admin');
    }
}