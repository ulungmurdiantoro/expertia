<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\DiscoveryController;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kategori (rubrik) artikel. Slug menjadi alamat /articles?category=<slug>, jadi slug
 * dikunci begitu dipakai artikel atau ditautkan dari menu.
 */
class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::query()
            ->withCount([
                'articles',
                'articles as published_articles_count' => fn ($query) => $query->publiclyVisible(),
            ])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'is_active' => $category->is_active,
                'articles_count' => $category->articles_count,
                'published_articles_count' => $category->published_articles_count,
                'is_menu_rubric' => $category->isMenuRubric(),
                'can_delete' => $this->deleteBlocker($category) === null,
                'public_url' => route('articles.index', ['category' => $category->slug]),
            ]);

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Categories/Form', [
            'category' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Slug kosong dibuat dari nama, lalu ikut divalidasi (unik, format).
        $request->merge(['slug' => $request->filled('slug') ? $request->input('slug') : Str::slug((string) $request->input('name'))]);

        $category = Category::create($this->validated($request));
        DiscoveryController::forgetCache();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', "Kategori \"{$category->name}\" ditambahkan.");
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('Admin/Categories/Form', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'is_active' => $category->is_active,
                'is_menu_rubric' => $category->isMenuRubric(),
                'slug_lock_reason' => $this->slugLockReason($category),
            ],
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $this->validated($request, $category);

        $slugLockReason = $this->slugLockReason($category);
        if ($slugLockReason !== null && $validated['slug'] !== $category->slug) {
            throw ValidationException::withMessages(['slug' => "Slug tidak bisa diubah: {$slugLockReason}."]);
        }

        if ($category->isMenuRubric() && ! $validated['is_active']) {
            throw ValidationException::withMessages(['is_active' => 'Rubrik yang ditautkan dari menu dan beranda harus tetap aktif.']);
        }

        $category->update($validated);
        DiscoveryController::forgetCache();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', "Kategori \"{$category->name}\" diperbarui.");
    }

    public function destroy(Category $category): RedirectResponse
    {
        $blocker = $this->deleteBlocker($category);
        if ($blocker !== null) {
            return back()->with('error', "Kategori \"{$category->name}\" tidak bisa dihapus: {$blocker}.");
        }

        $category->delete();
        DiscoveryController::forgetCache();

        return back()->with('success', "Kategori \"{$category->name}\" dihapus.");
    }

    /**
     * @return array{name:string,slug:string,description:?string,is_active:bool}
     */
    private function validated(Request $request, ?Category $category = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category?->id)],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('categories', 'slug')->ignore($category?->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ], [
            'slug.regex' => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung (mis. riset-terapan).',
            'slug.required' => 'Slug tidak bisa dibuat dari nama ini; isi slug secara manual.',
        ]);

        return [
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    private function slugLockReason(Category $category): ?string
    {
        if ($category->isMenuRubric()) {
            return 'kategori ini ditautkan dari menu dan beranda';
        }

        if ($this->hasArticles($category)) {
            return 'sudah dipakai artikel, dan tautan lama ke kategori ini akan rusak';
        }

        return null;
    }

    private function deleteBlocker(Category $category): ?string
    {
        if ($category->isMenuRubric()) {
            return 'kategori ini ditautkan dari menu dan beranda';
        }

        if ($this->hasArticles($category)) {
            return 'masih dipakai artikel; nonaktifkan saja agar tidak bisa dipilih lagi';
        }

        return null;
    }

    private function hasArticles(Category $category): bool
    {
        // Di halaman daftar jumlahnya sudah dimuat lewat withCount.
        return ($category->articles_count ?? $category->articles()->count()) > 0;
    }
}
