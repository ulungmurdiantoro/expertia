<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\ExpertiseAreas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

/**
 * Admin mengubah nama, role, dan profil kontributor akun lain. Email dan password
 * tetap diurus pemilik akun di halaman Profile.
 */
class UserController extends Controller
{
    /** Role yang bisa dipilih di form, dengan label yang dipakai di panduan. */
    private const ROLE_LABELS = [
        'user' => 'Pembaca',
        'author' => 'Kontributor',
        'verified-expert' => 'Kontributor Terverifikasi',
        'editor' => 'Editor',
        'moderator' => 'Moderator',
        'admin' => 'Admin',
        'super-admin' => 'Super Admin',
    ];

    /** Hanya Super Admin yang boleh mengelola akun dan role setingkat ini. */
    private const PRIVILEGED_ROLES = ['admin', 'super-admin'];

    public function index(Request $request): Response
    {
        $actor = $request->user();
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'role' => trim((string) $request->query('role', '')),
        ];

        $users = User::query()
            ->with('roles:id,name')
            ->withCount('articles')
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $query->where(function ($inner) use ($filters): void {
                    $inner->where('name', 'like', "%{$filters['q']}%")
                        ->orWhere('email', 'like', "%{$filters['q']}%")
                        ->orWhere('institution', 'like', "%{$filters['q']}%");
                });
            })
            ->when($filters['role'] !== '', fn ($query) => $query->whereHas('roles', fn ($roles) => $roles->where('name', $filters['role'])))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->map(fn (string $role) => $this->roleLabel($role))->values(),
                'institution' => $user->institution,
                'articles_count' => $user->articles_count,
                'profile_url' => $user->profile_slug ? route('authors.show', $user->profile_slug) : null,
                'created_at' => $user->created_at?->toDateString(),
                'can_edit' => $this->canManage($actor, $user),
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $filters,
            'roleOptions' => $this->roleOptions(array_keys(self::ROLE_LABELS)),
        ]);
    }

    public function edit(Request $request, User $user): Response
    {
        $actor = $request->user();
        abort_unless($this->canManage($actor, $user), 403);

        $expertise = ExpertiseAreas::toInput($user->expertise_areas);

        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name ?? 'user',
                'gender' => $user->gender,
                'whatsapp_number' => $user->whatsapp_number,
                'position' => $user->position,
                'institution' => $user->institution,
                'institution_unit' => $user->institution_unit,
                'linkedin_url' => $user->linkedin_url,
                'bio' => $user->bio,
                'expertise_topics' => $expertise['topics'],
                'expertise_other' => $expertise['other'],
                'profile_url' => $user->profile_slug ? route('authors.show', $user->profile_slug) : null,
            ],
            'roleOptions' => $this->roleOptions($this->assignableRoles($actor)),
            'canChangeRole' => ! $actor->is($user),
            'expertiseOptions' => $this->expertiseOptionsFor($user),
            'bioMaxLength' => ProfileUpdateRequest::BIO_MAX_LENGTH,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($this->canManage($actor, $user), 403);

        $canChangeRole = ! $actor->is($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => [Rule::excludeIf(! $canChangeRole), 'required', Rule::in($this->assignableRoles($actor))],
            'gender' => ['nullable', Rule::in(['Pria', 'Wanita'])],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'position' => ['nullable', 'string', 'max:255'],
            'institution' => ['nullable', 'string', 'max:255'],
            'institution_unit' => ['nullable', 'string', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'bio' => ['nullable', 'string', 'max:'.ProfileUpdateRequest::BIO_MAX_LENGTH],
            'expertise_topics' => ['nullable', 'array'],
            'expertise_topics.*' => ['string', Rule::in($this->expertiseOptionsFor($user))],
            'expertise_other' => ['nullable', 'string', 'max:255'],
        ]);

        $user->fill([
            ...collect($validated)->except(['role', 'expertise_topics', 'expertise_other'])->all(),
            'expertise_areas' => ExpertiseAreas::fromInput($validated['expertise_topics'] ?? [], $validated['expertise_other'] ?? null),
        ]);
        $changedFields = array_keys($user->getDirty());
        $user->save();

        $previousRoles = $user->getRoleNames()->values()->all();
        $roleChanged = $canChangeRole && $previousRoles !== [$validated['role']];
        if ($roleChanged) {
            $user->syncRoles([$validated['role']]);
        }

        if ($changedFields !== [] || $roleChanged) {
            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'user.updated_by_admin',
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'meta_json' => array_filter([
                    'fields' => $changedFields ?: null,
                    'role_from' => $roleChanged ? $previousRoles : null,
                    'role_to' => $roleChanged ? $validated['role'] : null,
                ], fn ($value) => $value !== null),
                'created_at' => now(),
            ]);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Data {$user->name} diperbarui.");
    }

    private function canManage(User $actor, User $target): bool
    {
        return $actor->is($target)
            || $actor->hasRole('super-admin')
            || ! $target->hasAnyRole(self::PRIVILEGED_ROLES);
    }

    /**
     * @return list<string>
     */
    private function assignableRoles(User $actor): array
    {
        $roles = array_keys(self::ROLE_LABELS);

        if (! $actor->hasRole('super-admin')) {
            $roles = array_values(array_diff($roles, self::PRIVILEGED_ROLES));
        }

        // Hanya role yang memang ada di database.
        return Role::query()->whereIn('name', $roles)->pluck('name')
            ->sortBy(fn (string $role) => array_search($role, array_keys(self::ROLE_LABELS), true))
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $roles
     * @return list<array{value:string,label:string}>
     */
    private function roleOptions(array $roles): array
    {
        return array_map(fn (string $role) => ['value' => $role, 'label' => $this->roleLabel($role)], $roles);
    }

    private function roleLabel(string $role): string
    {
        return self::ROLE_LABELS[$role] ?? $role;
    }

    /**
     * Pilihan aktif ditambah topik lama milik user yang mungkin sudah dinonaktifkan,
     * agar menyimpan form tidak menghapusnya diam-diam.
     *
     * @return list<string>
     */
    private function expertiseOptionsFor(User $user): array
    {
        return array_values(array_unique([
            ...ExpertiseAreas::options(),
            ...ExpertiseAreas::toInput($user->expertise_areas)['topics'],
        ]));
    }
}
