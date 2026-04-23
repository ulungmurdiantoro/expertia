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
        // Hindari write ke tabel cache saat seeding (stabil untuk SQLite).
        config(['cache.default' => 'array']);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Konten - Baca
            'content.read.free',
            'content.read.premium',
            'content.download.attachments',

            // Konten - Tulis & Kelola
            'content.write.create',
            'content.write.edit.own',
            'content.write.edit.any',
            'content.submit.to_editor',
            'content.draft.save',
            'content.delete.own',
            'content.delete.any',

            // Editorial - Kurasi
            'editorial.review.incoming',
            'editorial.approve',
            'editorial.reject',
            'editorial.request_revision',
            'editorial.feature',
            'editorial.manage.taxonomy',

            // Komunitas
            'community.comment.create',
            'community.comment.reply',
            'community.follow_author',
            'community.bookmark',
            'community.report',
            'community.comment.moderate',
            'community.comment.ban_user',

            // Profil & Pakar
            'profile.author_public_view',
            'profile.expert.badge.display',
            'profile.expert.affiliation.display',
            'profile.edit.own',
            'profile.expert.verify',

            // Partner & Institusi
            'partner.experts.view',
            'partner.experts.onboard',
            'partner.profile.manage',
            'partner.analytics.view',

            // Analytics
            'analytics.content.own',
            'analytics.platform.view',
            'analytics.user_behavior.view',
            'analytics.export',

            // Manajemen User
            'users.view',
            'users.edit',
            'users.assign_role',
            'users.suspend',
            'users.delete',

            // Sistem & Konfigurasi
            'system.settings.manage',
            'system.billing.manage',
            'system.integrations.manage',
            'system.roles.manage',
            'system.audit_logs.view',
            'system.maintenance.manage',

            // Legacy permission untuk kompatibilitas kode saat ini
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
            'analytics.view.author',
            'analytics.view.admin',
        ];

        foreach (array_unique($permissions) as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $rolePermissions = [
            'super-admin' => Permission::query()->pluck('name')->all(),
            'admin' => [
                'content.read.free',
                'content.read.premium',
                'content.download.attachments',
                'content.write.create',
                'content.write.edit.own',
                'content.write.edit.any',
                'content.submit.to_editor',
                'content.draft.save',
                'content.delete.own',
                'content.delete.any',
                'editorial.review.incoming',
                'editorial.approve',
                'editorial.reject',
                'editorial.request_revision',
                'editorial.feature',
                'editorial.manage.taxonomy',
                'community.comment.create',
                'community.comment.reply',
                'community.follow_author',
                'community.bookmark',
                'community.report',
                'community.comment.moderate',
                'community.comment.ban_user',
                'profile.author_public_view',
                'profile.expert.affiliation.display',
                'profile.edit.own',
                'profile.expert.verify',
                'partner.experts.view',
                'partner.experts.onboard',
                'partner.profile.manage',
                'partner.analytics.view',
                'analytics.content.own',
                'analytics.platform.view',
                'analytics.user_behavior.view',
                'analytics.export',
                'users.view',
                'users.edit',
                'users.assign_role',
                'users.suspend',
                'system.settings.manage',
                'system.audit_logs.view',
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
                'analytics.view.author',
                'analytics.view.admin',
            ],
            'partner-manager' => [
                'content.read.free',
                'content.read.premium',
                'content.download.attachments',
                'community.comment.create',
                'community.comment.reply',
                'community.follow_author',
                'community.bookmark',
                'community.report',
                'profile.edit.own',
                'partner.experts.view',
                'partner.experts.onboard',
                'partner.profile.manage',
                'partner.analytics.view',
                'analytics.export',
            ],
            'editor' => [
                'content.read.free',
                'content.read.premium',
                'content.download.attachments',
                'content.write.create',
                'content.write.edit.own',
                'content.write.edit.any',
                'content.submit.to_editor',
                'content.draft.save',
                'content.delete.own',
                'editorial.review.incoming',
                'editorial.approve',
                'editorial.reject',
                'editorial.request_revision',
                'editorial.feature',
                'editorial.manage.taxonomy',
                'community.comment.create',
                'community.comment.reply',
                'community.follow_author',
                'community.bookmark',
                'community.report',
                'profile.author_public_view',
                'profile.edit.own',
                'analytics.content.own',
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
                'content.read.free',
                'content.read.premium',
                'community.comment.create',
                'community.comment.reply',
                'community.follow_author',
                'community.bookmark',
                'community.report',
                'community.comment.moderate',
                'community.comment.ban_user',
                'profile.edit.own',
                'comments.moderate',
                'reports.handle',
                'reports.subject_action.basic',
            ],
            'verified-expert' => [
                'content.read.free',
                'content.read.premium',
                'content.download.attachments',
                'content.write.create',
                'content.write.edit.own',
                'content.submit.to_editor',
                'content.draft.save',
                'content.delete.own',
                'community.comment.create',
                'community.comment.reply',
                'community.follow_author',
                'community.bookmark',
                'community.report',
                'profile.author_public_view',
                'profile.expert.badge.display',
                'profile.expert.affiliation.display',
                'profile.edit.own',
                'analytics.content.own',
                'articles.create',
                'articles.update.own',
                'articles.submit',
                'analytics.view.author',
            ],
            'author' => [
                'content.read.free',
                'content.read.premium',
                'content.download.attachments',
                'content.write.create',
                'content.write.edit.own',
                'content.submit.to_editor',
                'content.draft.save',
                'content.delete.own',
                'community.comment.create',
                'community.comment.reply',
                'community.follow_author',
                'community.bookmark',
                'community.report',
                'profile.author_public_view',
                'profile.edit.own',
                'analytics.content.own',
                'articles.create',
                'articles.update.own',
                'articles.submit',
                'analytics.view.author',
            ],
            'subscriber' => [
                'content.read.free',
                'content.read.premium',
                'content.download.attachments',
                'community.comment.create',
                'community.comment.reply',
                'community.follow_author',
                'community.bookmark',
                'community.report',
                'profile.edit.own',
            ],
            'user' => [
                'content.read.free',
                'community.comment.create',
                'community.comment.reply',
                'community.follow_author',
                'community.bookmark',
                'community.report',
                'profile.edit.own',
            ],
        ];

        foreach ($rolePermissions as $roleName => $rolePermissionNames) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissionNames);
        }
    }
}
