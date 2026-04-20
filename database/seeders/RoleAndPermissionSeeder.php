<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'articles.create',
            'articles.update.own',
            'articles.update.any',
            'articles.submit',
            'articles.review',
            'articles.publish',
            'articles.archive',
            'comments.moderate',
            'reports.handle',
            'reports.subject_action.basic',
            'reports.subject_action.archive',
            'users.suspend',
            'analytics.view.author',
            'analytics.view.admin',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $userRole = Role::firstOrCreate(['name' => 'user']);
        $authorRole = Role::firstOrCreate(['name' => 'author']);
        $editorRole = Role::firstOrCreate(['name' => 'editor']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        $userRole->syncPermissions([]);

        $authorRole->syncPermissions([
            'articles.create',
            'articles.update.own',
            'articles.submit',
            'analytics.view.author',
        ]);

        $editorRole->syncPermissions([
            'articles.review',
            'articles.publish',
            'comments.moderate',
            'reports.handle',
            'reports.subject_action.basic',
        ]);

        $adminRole->syncPermissions(Permission::all());
    }
}
