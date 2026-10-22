<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    categories: Array,
});

const deleteCategory = (category) => {
    if (!window.confirm(`Hapus kategori "${category.name}"?`)) {
        return;
    }

    router.delete(route('admin.categories.destroy', category.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Kategori" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Kategori</h2>
                <Link :href="route('admin.categories.create')" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white">
                    Tambah Kategori
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl space-y-4 sm:px-6 lg:px-8">
                <p class="text-sm text-gray-600">
                    Kategori adalah rubrik artikel; penulis memilihnya saat menulis. Kategori nonaktif tidak bisa dipilih untuk
                    naskah baru, tetapi artikel di dalamnya tetap tayang. Rubrik bertanda <strong>Menu</strong> ditautkan dari menu
                    dan beranda, jadi selalu aktif dan slug-nya tetap.
                </p>

                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Nama</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Slug</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700">Artikel tayang</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700">Semua artikel</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                                <th class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="category in categories" :key="category.id" class="border-t border-gray-100">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <p class="font-medium text-gray-900">{{ category.name }}</p>
                                        <span v-if="category.is_menu_rubric" class="rounded bg-indigo-100 px-1.5 py-0.5 text-xs font-semibold text-indigo-700">Menu</span>
                                    </div>
                                    <p v-if="category.description" class="mt-1 max-w-xl text-xs text-gray-500">{{ category.description }}</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-600">{{ category.slug }}</td>
                                <td class="px-4 py-3 text-right text-gray-700">{{ category.published_articles_count }}</td>
                                <td class="px-4 py-3 text-right text-gray-700">{{ category.articles_count }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="category.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'"
                                        class="rounded px-2 py-0.5 text-xs font-semibold"
                                    >
                                        {{ category.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a
                                            v-if="category.published_articles_count > 0"
                                            :href="category.public_url"
                                            target="_blank"
                                            rel="noopener"
                                            class="rounded border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                        >
                                            Lihat
                                        </a>
                                        <Link
                                            :href="route('admin.categories.edit', category.id)"
                                            class="rounded bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            v-if="category.can_delete"
                                            type="button"
                                            class="rounded border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50"
                                            @click="deleteCategory(category)"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="categories.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">Belum ada kategori.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
