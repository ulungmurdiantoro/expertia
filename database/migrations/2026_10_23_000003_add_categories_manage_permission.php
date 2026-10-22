<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permission categories.manage (menu Kategori) untuk Admin dan Super Admin di database
 * yang sudah berjalan; instalasi baru mendapatkannya dari RoleAndPermissionSeeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = config('permission.table_names');

        if (! Schema::hasTable($tables['permissions'])) {
            return;
        }

        $permissionKey = config('permission.column_names.permission_pivot_key') ?? 'permission_id';
        $roleKey = config('permission.column_names.role_pivot_key') ?? 'role_id';

        DB::table($tables['permissions'])->insertOrIgnore([
            'name' => 'categories.manage',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissionId = DB::table($tables['permissions'])->where('name', 'categories.manage')->value('id');

        DB::table($tables['roles'])
            ->whereIn('name', ['admin', 'super-admin'])
            ->pluck('id')
            ->each(fn ($roleId) => DB::table($tables['role_has_permissions'])->insertOrIgnore([
                $permissionKey => $permissionId,
                $roleKey => $roleId,
            ]));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $tables = config('permission.table_names');
        $permissionKey = config('permission.column_names.permission_pivot_key') ?? 'permission_id';

        $permissionId = DB::table($tables['permissions'])->where('name', 'categories.manage')->value('id');

        if ($permissionId) {
            DB::table($tables['role_has_permissions'])->where($permissionKey, $permissionId)->delete();
            DB::table($tables['model_has_permissions'])->where($permissionKey, $permissionId)->delete();
            DB::table($tables['permissions'])->where('id', $permissionId)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
