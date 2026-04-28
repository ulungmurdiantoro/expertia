<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    articles: Object,
    filters: Object,
    categories: Array,
    stats: Object,
});

const filterForm = useForm({
    q: props.filters?.q ?? '',
    status: props.filters?.status ?? '',
    editorial_status: props.filters?.editorial_status ?? '',
    category: props.filters?.category ?? '',
});

const applyFilters = () => {
    router.get(route('author.articles.index'), filterForm.data(), {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.q = '';
    filterForm.status = '';
    filterForm.editorial_status = '';
    filterForm.category = '';
    applyFilters();
};

const statCards = computed(() => [
    { label: 'Total', value: props.stats?.total ?? 0, tone: 'border-indigo-200 bg-indigo-50 text-indigo-800' },
    { label: 'Draft', value: props.stats?.draft ?? 0, tone: 'border-gray-200 bg-white text-gray-800' },
    { label: 'In Review', value: props.stats?.in_review ?? 0, tone: 'border-amber-200 bg-amber-50 text-amber-800' },
    { label: 'Published', value: props.stats?.published ?? 0, tone: 'border-emerald-200 bg-emerald-50 text-emerald-800' },
    { label: 'Scheduled', value: props.stats?.scheduled ?? 0, tone: 'border-sky-200 bg-sky-50 text-sky-800' },
    { label: 'Butuh Revisi', value: props.stats?.revision_requested ?? 0, tone: 'border-rose-200 bg-rose-50 text-rose-800' },
    { label: 'Total Views', value: props.stats?.total_views ?? 0, tone: 'border-violet-200 bg-violet-50 text-violet-800' },
    { label: 'Bookmarks', value: props.stats?.total_bookmarks ?? 0, tone: 'border-cyan-200 bg-cyan-50 text-cyan-800' },
]);

const statusClass = (status) => ({
    draft: 'bg-gray-100 text-gray-700',
    in_review: 'bg-amber-100 text-amber-800',
    scheduled: 'bg-sky-100 text-sky-800',
    published: 'bg-emerald-100 text-emerald-800',
    rejected: 'bg-rose-100 text-rose-800',
    archived: 'bg-zinc-100 text-zinc-700',
}[status] || 'bg-gray-100 text-gray-700');
</script>

<template>
    <Head title="Artikel Saya" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Artikel Saya</h2>
                <Link :href="route('author.articles.create')" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white">
                    Tulis Artikel
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl space-y-5 sm:px-6 lg:px-8">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="card in statCards"
                        :key="card.label"
                        :class="card.tone"
                        class="rounded-lg border p-4 shadow-sm"
                    >
                        <p class="text-xs font-semibold uppercase tracking-wide">{{ card.label }}</p>
                        <p class="mt-2 text-2xl font-semibold">{{ card.value }}</p>
                    </div>
                </div>

                <form @submit.prevent="applyFilters" class="grid gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm lg:grid-cols-6">
                    <input v-model="filterForm.q" type="text" placeholder="Cari judul / ringkasan" class="rounded-md border-gray-300 lg:col-span-2" />
                    <select v-model="filterForm.status" class="rounded-md border-gray-300">
                        <option value="">Semua status</option>
                        <option value="draft">draft</option>
                        <option value="in_review">in_review</option>
                        <option value="scheduled">scheduled</option>
                        <option value="published">published</option>
                        <option value="rejected">rejected</option>
                    </select>
                    <select v-model="filterForm.editorial_status" class="rounded-md border-gray-300">
                        <option value="">Semua editorial</option>
                        <option value="draft">draft</option>
                        <option value="pending_review">pending_review</option>
                        <option value="approved">approved</option>
                        <option value="revision_requested">revision_requested</option>
                        <option value="rejected">rejected</option>
                    </select>
                    <select v-model="filterForm.category" class="rounded-md border-gray-300">
                        <option value="">Semua kategori</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                    <div class="flex gap-2">
                        <button type="submit" class="rounded bg-indigo-600 px-3 py-2 text-sm font-medium text-white">Filter</button>
                        <button type="button" @click="resetFilters" class="rounded bg-gray-700 px-3 py-2 text-sm font-medium text-white">Reset</button>
                    </div>
                </form>

                <div v-if="articles.data.length" class="space-y-4">
                    <article
                        v-for="article in articles.data"
                        :key="article.id"
                        class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
                    >
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span :class="statusClass(article.status)" class="rounded px-2 py-0.5 text-xs font-semibold">{{ article.status }}</span>
                                    <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">{{ article.editorial_status }}</span>
                                    <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">{{ article.category?.name || 'Tanpa kategori' }}</span>
                                </div>

                                <Link :href="route('author.articles.edit', article.id)" class="mt-3 block text-lg font-semibold text-gray-900 hover:text-indigo-600">
                                    {{ article.title }}
                                </Link>
                                <p v-if="article.excerpt" class="mt-2 max-w-4xl text-sm leading-6 text-gray-600">{{ article.excerpt }}</p>

                                <div class="mt-4 grid gap-2 text-xs text-gray-600 sm:grid-cols-4">
                                    <span>Views: <strong>{{ article.engagement.views }}</strong></span>
                                    <span>Komentar: <strong>{{ article.engagement.comments }}</strong></span>
                                    <span>Bookmark: <strong>{{ article.engagement.bookmarks }}</strong></span>
                                    <span>Reaksi: <strong>{{ article.engagement.reactions }}</strong></span>
                                </div>

                                <div v-if="article.latest_review" class="mt-4 rounded-md bg-gray-50 p-3 text-sm text-gray-700">
                                    Review terakhir: <strong>{{ article.latest_review.action }}</strong>
                                    oleh {{ article.latest_review.editor?.name || '-' }}
                                    <span v-if="article.latest_review.note">- {{ article.latest_review.note }}</span>
                                </div>
                            </div>

                            <div class="flex shrink-0 flex-wrap gap-2 lg:max-w-xs lg:justify-end">
                                <Link :href="route('author.articles.edit', article.id)" class="rounded bg-indigo-600 px-3 py-2 text-sm font-medium text-white">
                                    Edit
                                </Link>
                                <Link
                                    v-if="article.status === 'published'"
                                    :href="route('articles.show', article.slug)"
                                    class="rounded border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Lihat Publik
                                </Link>
                                <span class="rounded border border-gray-200 px-3 py-2 text-xs text-gray-500">
                                    Update {{ article.updated_at }}
                                </span>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-else class="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center text-gray-600">
                    Belum ada artikel yang cocok dengan filter.
                </div>

                <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <Link
                        :href="articles.prev_page_url || '#'"
                        :class="articles.prev_page_url ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-400'"
                        class="rounded px-3 py-1 text-sm"
                    >
                        Previous
                    </Link>
                    <p class="text-sm text-gray-600">Halaman {{ articles.current_page }} / {{ articles.last_page }}</p>
                    <Link
                        :href="articles.next_page_url || '#'"
                        :class="articles.next_page_url ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-400'"
                        class="rounded px-3 py-1 text-sm"
                    >
                        Next
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
