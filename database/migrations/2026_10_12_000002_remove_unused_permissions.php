<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Menghapus permission yang tidak pernah dicek oleh kode (set rancangan content.*,
 * editorial.*, community.*, profile.*, partner.*, dll.). Tidak mengubah perilaku
 * aplikasi; hanya merapikan daftar di halaman Role & Permission.
 */
return new class extends Migration
{
    /**
     * Salinan RoleAndPermissionSeeder::PERMISSIONS saat migrasi ini dibuat.
     */
    private const KEEP = [
        'articles.create',
        'articles.update.own',
        'articles.update.any',
        'articles.submit',
        'articles.review',
        'articles.publish',
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

    public function up(): void
    {
        $tables = config('permission.table_names');
        $pivotKey = config('permission.column_names.permission_pivot_key') ?? 'permission_id';

        if (! Schema::hasTable($tables['permissions'])) {
            return;
        }

        DB::transaction(function () use ($tables, $pivotKey): void {
            // Pastikan users.assign_role ada (sebelumnya hanya dimiliki admin lewat set rancangan).
            $now = now();
            foreach (self::KEEP as $name) {
                DB::table($tables['permissions'])->insertOrIgnore([
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // Halaman "Tambah Kontributor" memakai users.assign_role.
            $assignRoleId = DB::table($tables['permissions'])->where('name', 'users.assign_role')->value('id');
            $roleKey = config('permission.column_names.role_pivot_key') ?? 'role_id';
            DB::table($tables['roles'])
                ->whereIn('name', ['admin', 'super-admin'])
                ->pluck('id')
                ->each(fn ($roleId) => DB::table($tables['role_has_permissions'])->insertOrIgnore([
                    $pivotKey => $assignRoleId,
                    $roleKey => $roleId,
                ]));

            $unusedIds = DB::table($tables['permissions'])
                ->whereNotIn('name', self::KEEP)
                ->pluck('id');

            if ($unusedIds->isEmpty()) {
                return;
            }

            DB::table($tables['role_has_permissions'])->whereIn($pivotKey, $unusedIds)->delete();
            DB::table($tables['model_has_permissions'])->whereIn($pivotKey, $unusedIds)->delete();
            DB::table($tables['permissions'])->whereIn('id', $unusedIds)->delete();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Jalankan ulang seeder versi lama bila daftar rancangan dibutuhkan kembali.
    }
};
