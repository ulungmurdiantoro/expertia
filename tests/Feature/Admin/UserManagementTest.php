<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $superAdmin;

    private User $contributor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super-admin');

        $this->contributor = User::factory()->create([
            'name' => 'Agung Yulianto',
            'institution' => 'Universitas Lama',
            'expertise_areas' => ['SPMI', 'Other: Wakaf uang'],
        ]);
        $this->contributor->assignRole('author');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(User $user, array $overrides = []): array
    {
        return [
            'name' => $user->name,
            'role' => $user->getRoleNames()->first(),
            ...$overrides,
        ];
    }

    public function test_admin_can_update_a_contributor_name_role_and_profile(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['q' => 'Agung']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.roles', ['Kontributor'])
                ->where('users.data.0.can_edit', true));

        $this->actingAs($this->admin)
            ->get(route('admin.users.edit', $this->contributor))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('user.expertise_topics', ['SPMI'])
                ->where('user.expertise_other', 'Wakaf uang')
                ->where('canChangeRole', true));

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $this->contributor), [
                'name' => 'Dr. Agung Yulianto',
                'role' => 'verified-expert',
                'position' => 'Dosen Ekonomi Syariah',
                'institution' => 'Universitas Baru',
                'institution_unit' => 'Fakultas Ekonomi',
                'bio' => 'Meneliti wakaf uang.',
                'expertise_topics' => ['AMI'],
                'expertise_other' => 'Filantropi Islam',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.users.index'));

        $this->contributor->refresh();
        $this->assertSame('Dr. Agung Yulianto', $this->contributor->name);
        $this->assertTrue($this->contributor->hasRole('verified-expert'));
        $this->assertFalse($this->contributor->hasRole('author'));
        $this->assertSame('Universitas Baru', $this->contributor->institution);
        $this->assertSame('Dosen Ekonomi Syariah', $this->contributor->position);
        $this->assertSame(['AMI', 'Other: Filantropi Islam'], $this->contributor->expertise_areas);

        $log = ActivityLog::query()->where('action', 'user.updated_by_admin')->sole();
        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertSame($this->contributor->id, $log->subject_id);
        $this->assertSame(['author'], $log->meta_json['role_from']);
        $this->assertSame('verified-expert', $log->meta_json['role_to']);
        $this->assertContains('institution', $log->meta_json['fields']);
    }

    public function test_reader_can_be_promoted_to_editor(): void
    {
        $reader = User::factory()->create();
        $reader->assignRole('user');

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $reader), $this->payload($reader, ['role' => 'editor']))
            ->assertSessionHasNoErrors();

        $this->assertSame(['editor'], $reader->fresh()->getRoleNames()->all());
    }

    public function test_admin_cannot_grant_admin_roles(): void
    {
        foreach (['admin', 'super-admin'] as $role) {
            $this->actingAs($this->admin)
                ->from(route('admin.users.edit', $this->contributor))
                ->put(route('admin.users.update', $this->contributor), $this->payload($this->contributor, ['role' => $role]))
                ->assertSessionHasErrors('role');
        }

        $this->assertSame(['author'], $this->contributor->fresh()->getRoleNames()->all());
    }

    public function test_admin_cannot_manage_other_admins(): void
    {
        $otherAdmin = User::factory()->create(['name' => 'Admin Lain']);
        $otherAdmin->assignRole('admin');

        foreach ([$otherAdmin, $this->superAdmin] as $target) {
            $this->actingAs($this->admin)->get(route('admin.users.edit', $target))->assertForbidden();
            $this->actingAs($this->admin)
                ->put(route('admin.users.update', $target), $this->payload($target, ['name' => 'Diganti', 'role' => 'user']))
                ->assertForbidden();
        }

        $this->assertSame('Admin Lain', $otherAdmin->fresh()->name);
        $this->assertTrue($this->superAdmin->fresh()->hasRole('super-admin'));
    }

    public function test_super_admin_can_grant_admin(): void
    {
        $this->actingAs($this->superAdmin)
            ->put(route('admin.users.update', $this->contributor), $this->payload($this->contributor, ['role' => 'admin']))
            ->assertSessionHasNoErrors();

        $this->assertTrue($this->contributor->fresh()->hasRole('admin'));
    }

    public function test_nobody_changes_their_own_role(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.users.edit', $this->superAdmin))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('canChangeRole', false));

        $this->actingAs($this->superAdmin)
            ->put(route('admin.users.update', $this->superAdmin), $this->payload($this->superAdmin, ['name' => 'Nama Baru', 'role' => 'user']))
            ->assertSessionHasNoErrors();

        $this->superAdmin->refresh();
        $this->assertSame('Nama Baru', $this->superAdmin->name);
        $this->assertTrue($this->superAdmin->hasRole('super-admin'));
    }

    public function test_editor_cannot_open_user_management(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('editor');

        $this->actingAs($editor)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($editor)
            ->put(route('admin.users.update', $this->contributor), $this->payload($this->contributor, ['role' => 'admin']))
            ->assertForbidden();
    }

    public function test_admin_menu_permissions_exclude_role_management(): void
    {
        // Menu Role & Permission hanya tampil bila permission ini ada (AuthenticatedLayout.vue).
        $this->actingAs($this->admin)
            ->get(route('admin.users.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.permissions', fn ($permissions) => collect($permissions)->contains('users.assign_role')
                    && ! collect($permissions)->contains('system.roles.manage')));

        $this->actingAs($this->admin)->get(route('admin.roles.index'))->assertForbidden();
    }
}
