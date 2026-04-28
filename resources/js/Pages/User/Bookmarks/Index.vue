<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    bookmarks: Object,
    filters: Object,
    stats: Object,
});

const filterForm = useForm({
    q: props.filters?.q ?? '',
    sort: props.filters?.sort ?? 'latest',
});

const removeForm = useForm({});

const applyFilters = () => {
    router.get(route('me.bookmarks.index'), filterForm.data(), {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.q = '';
    filterForm.sort = 'latest';
    applyFilters();
};

const removeBookmark = (slug) => {
    removeForm.delete(route('me.bookmarks.destroy', slug), {
        preserveScroll: true,
    });
};

const formatDate = (value) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
};
</script>

<template>
    <Head title="Bookmark Saya" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Bookmark Saya</h2>
                <Link :href="route('articles.index')" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white">
                    Jelajahi Artikel
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl space-y-5 sm:px-6 lg:px-8">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 text-indigo-800 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide">Total Bookmark</p>
                        <p class="mt-2 text-2xl font-semibold">{{ stats.total }}</p>
                    </div>
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide">Ditambah Minggu Ini</p>
                        <p class="mt-2 text-2xl font-semibold">{{ stats.this_week }}</p>
                    </div>
                </div>

                <form @submit.prevent="applyFilters" class="grid gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-4">
                    <input v-model="filterForm.q" type="text" placeholder="Cari judul / author" class="rounded-md border-gray-300 md:col-span-2" />
                    <select v-model="filterForm.sort" class="rounded-md border-gray-300">
                        <option value="latest">Terbaru</option>
                        <option value="oldest">Terlama</option>
                    </select>
                    <div class="flex gap-2">
                        <button type="submit" class="rounded bg-indigo-600 px-3 py-2 text-sm font-medium text-white">Filter</button>
                        <button type="button" @click="resetFilters" class="rounded bg-gray-700 px-3 py-2 text-sm font-medium text-white">Reset</button>
                    </div>
                </form>

                <div v-if="bookmarks.data.length" class="grid gap-4 lg:grid-cols-2">
                    <article
                        v-for="bookmark in bookmarks.data"
                        :key="bookmark.id"
                        class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500">
                                    {{ bookmark.article.category?.name || 'Umum' }} | Bookmark {{ formatDate(bookmark.created_at) }}
                                </p>
                                <Link :href="route('articles.show', bookmark.article.slug)" class="mt-2 block text-lg font-semibold text-gray-900 hover:text-indigo-600">
                                    {{ bookmark.article.title }}
                                </Link>
                                <p class="mt-2 line-clamp-3 text-sm leading-6 text-gray-600">{{ bookmark.article.excerpt }}</p>
                                <p class="mt-3 text-xs text-gray-500">
                                    {{ bookmark.article.author?.name || 'Redaksi' }} | {{ bookmark.article.view_count || 0 }} views | {{ bookmark.article.comment_count || 0 }} komentar
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="removeBookmark(bookmark.article.slug)"
                                :disabled="removeForm.processing"
                                class="shrink-0 rounded border border-rose-200 px-3 py-2 text-xs font-medium text-rose-700 hover:bg-rose-50 disabled:opacity-50"
                            >
                                Hapus
                            </button>
                        </div>
                    </article>
                </div>

                <div v-else class="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center text-gray-600">
                    Tidak ada bookmark yang cocok.
                </div>

                <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <Link
                        :href="bookmarks.prev_page_url || '#'"
                        :class="bookmarks.prev_page_url ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-400'"
                        class="rounded px-3 py-1 text-sm"
                    >
                        Previous
                    </Link>
                    <p class="text-sm text-gray-600">Halaman {{ bookmarks.current_page }} / {{ bookmarks.last_page }}</p>
                    <Link
                        :href="bookmarks.next_page_url || '#'"
                        :class="bookmarks.next_page_url ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-400'"
                        class="rounded px-3 py-1 text-sm"
                    >
                        Next
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
