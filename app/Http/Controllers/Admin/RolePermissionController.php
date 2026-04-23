<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionController extends Controller
{
    public function index(): Response
    {
        $roles = Role::query()
            ->with(['permissions:id,name', 'users:id,name,email'])
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions_count' => $role->permissions->count(),
                'permissions' => $role->permissions->pluck('name')->sort()->values(),
                'users_count' => $role->users->count(),
                'users_preview' => $role->users->take(3)->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])->values(),
            ])
            ->values();

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
        ]);
    }

    public function edit(Role $role): Response
    {
        $role->load('permissions:id,name');

        $permissions = Permission::query()
            ->orderBy('name')
            ->pluck('name')
            ->values();

        return Inertia::render('Admin/Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->values(),
            ],
            'permissions' => $permissions,
            'permission_groups' => $this->groupPermissions($permissions->all()),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($validated['permissions']);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Permission untuk role '{$role->name}' berhasil diperbarui.");
    }

    /**
     * @param list<string> $permissions
     * @return array<int, array{label:string,items:list<string>}>
     */
    private function groupPermissions(array $permissions): array
    {
        $grouped = [];

        foreach ($permissions as $permission) {
            $segments = explode('.', $permission);
            $label = strtoupper($segments[0] ?? 'LAINNYA');
            $grouped[$label] ??= [];
            $grouped[$label][] = $permission;
        }

        ksort($grouped);

        return collect($grouped)
            ->map(fn (array $items, string $label) => [
                'label' => $label,
                'items' => collect($items)->sort()->values()->all(),
            ])
            ->values()
            ->all();
    }
}

