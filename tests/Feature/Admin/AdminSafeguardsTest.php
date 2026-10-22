<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminSafeguardsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super-admin');
    }

    public function test_super_admin_cannot_lock_everyone_out_of_role_management(): void
    {
        $role = Role::findByName('super-admin');

        $this->actingAs($this->superAdmin)
            ->from(route('admin.roles.edit', $role))
            ->put(route('admin.roles.update', $role), ['permissions' => ['articles.create']])
            ->assertSessionHasErrors('permissions');

        $this->assertTrue($role->fresh()->hasPermissionTo('system.roles.manage'));
    }

    public function test_other_roles_can_still_be_edited_freely(): void
    {
        $role = Role::findByName('moderator');

        $this->actingAs($this->superAdmin)
            ->put(route('admin.roles.update', $role), ['permissions' => ['comments.moderate']])
            ->assertSessionHasNoErrors();

        $this->assertSame(['comments.moderate'], $role->fresh()->permissions->pluck('name')->all());
    }

    public function test_dashboard_counts_verified_experts_as_active_authors(): void
    {
        User::factory()->create()->assignRole('author');
        User::factory()->create()->assignRole('verified-expert');

        $this->actingAs($this->superAdmin)
            ->get(route('admin.dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('metrics.active_authors', 2));
    }
}
