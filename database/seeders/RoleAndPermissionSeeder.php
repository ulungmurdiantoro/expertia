<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Hanya permission yang benar-benar dicek oleh kode (route middleware `can:`,
     * policy, atau controller). Permission rancangan fitur yang belum dibangun
     * (premium, partner, dsb.) dihapus oleh migrasi 2026_10_12_000002; tambahkan
     * kembali di sini bersamaan dengan kode yang memeriksanya.
     */
    public const PERMISSIONS = [
        'articles.create',
        'articles.update.own',
        'articles.update.any',
        'articles.submit',
        'articles.review',
        'articles.publish',
        'articles.delete',
        'categories.manage',
        'comments.moderate',
        'reports.handle',
        'reports.subject_action.basic',
        'reports.subject_action.archive',
        'analytics.view.author',
        'analytics.view.admin',
        'users.assign_role',
        'system.audit_logs.view',
        'system.roles.manage',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hindari write ke tabel cache saat seeding (stabil untuk SQLite).
        config(['cache.default' => 'array']);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $authorPermissions = [
            'articles.create',
            'articles.update.own',
            'articles.submit',
            'analytics.view.author',
        ];

        $rolePermissions = [
            'super-admin' => self::PERMISSIONS,
            'admin' => array_values(array_diff(self::PERMISSIONS, ['system.roles.manage'])),
            'editor' => [
                'articles.create',
                'articles.update.own',
                'articles.update.any',
                'articles.submit',
                'articles.review',
                'articles.publish',
                'comments.moderate',
                'reports.handle',
                'reports.subject_action.basic',
                'analytics.view.author',
            ],
            'moderator' => [
                'comments.moderate',
                'reports.handle',
                'reports.subject_action.basic',
            ],
            'verified-expert' => $authorPermissions,
            'author' => $authorPermissions,
            // Role berikut belum punya fitur khusus; komentar, bookmark, dan follow
            // cukup dengan login.
            'partner-manager' => [],
            'subscriber' => [],
            'user' => [],
        ];

        foreach ($rolePermissions as $roleName => $rolePermissionNames) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissionNames);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
