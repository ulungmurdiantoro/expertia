<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    articles: Object,
    filters: Object,
    categories: Array,
    queueStats: Object,
});

const approvalForm = useForm({});
const actionForms = ref({});
const openNotes = ref({});

const filterForm = useForm({
    q: props.filters?.q ?? '',
    status: props.filters?.status ?? 'pending_review',
    category: props.filters?.category ?? '',
    age: props.filters?.age ?? '',
});

const approve = (id) => approvalForm.patch(route('editor.reviews.approve', id));

const formFor = (id) => {
    if (!actionForms.value[id]) {
        actionForms.value[id] = useForm({ note: '' });
    }

    return actionForms.value[id];
};

const reject = (id) => {
    formFor(id).patch(route('editor.reviews.reject', id), {
        preserveScroll: true,
        onSuccess: () => {
            openNotes.value[id] = null;
            formFor(id).reset();
        },
    });
};

const requestRevision = (id) => {
    formFor(id).patch(route('editor.reviews.request-revision', id), {
        preserveScroll: true,
        onSuccess: () => {
            openNotes.value[id] = null;
            formFor(id).reset();
        },
    });
};

const applyFilters = () => {
    router.get(route('editor.reviews.index'), filterForm.data(), {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.q = '';
    filterForm.status = 'pending_review';
    filterForm.category = '';
    filterForm.age = '';
    applyFilters();
};

const statCards = computed(() => [
    { label: 'Pending', value: props.queueStats?.pending ?? 0, tone: 'border-amber-200 bg-amber-50 text-amber-800' },
    { label: 'Menunggu 48j+', value: props.queueStats?.waiting_48h ?? 0, tone: 'border-rose-200 bg-rose-50 text-rose-800' },
    { label: 'Approve hari ini', value: props.queueStats?.approved_today ?? 0, tone: 'border-emerald-200 bg-emerald-50 text-emerald-800' },
    { label: 'Revisi hari ini', value: props.queueStats?.revision_today ?? 0, tone: 'border-sky-200 bg-sky-50 text-sky-800' },
    { label: 'Reject hari ini', value: props.queueStats?.rejected_today ?? 0, tone: 'border-gray-200 bg-white text-gray-800' },
]);

const statusClass = (status) => ({
    pending_review: 'bg-amber-100 text-amber-800',
    approved: 'bg-emerald-100 text-emerald-800',
    rejected: 'bg-rose-100 text-rose-800',
    revision_requested: 'bg-sky-100 text-sky-800',
    draft: 'bg-gray-100 text-gray-700',
}[status] || 'bg-gray-100 text-gray-700');
</script>

<template>
    <Head title="Review Artikel" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Review Artikel</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl space-y-5 sm:px-6 lg:px-8">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
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
                    <input v-model="filterForm.q" type="text" placeholder="Cari judul / author" class="rounded-md border-gray-300 lg:col-span-2" />
                    <select v-model="filterForm.status" class="rounded-md border-gray-300">
                        <option value="">Semua status</option>
                        <option value="pending_review">pending_review</option>
                        <option value="approved">approved</option>
                        <option value="revision_requested">revision_requested</option>
                        <option value="rejected">rejected</option>
                    </select>
                    <select v-model="filterForm.category" class="rounded-md border-gray-300">
                        <option value="">Semua kategori</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                    <select v-model="filterForm.age" class="rounded-md border-gray-300">
                        <option value="">Semua umur</option>
                        <option value="fresh">&lt; 24 jam</option>
                        <option value="waiting_24h">24-48 jam</option>
                        <option value="waiting_48h">&gt; 48 jam</option>
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
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span :class="statusClass(article.editorial_status)" class="rounded px-2 py-0.5 text-xs font-semibold">
                                        {{ article.editorial_status }}
                                    </span>
                                    <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">{{ article.category?.name || 'Tanpa kategori' }}</span>
                                    <span v-if="article.queue_age_hours !== null" class="text-xs text-gray-500">Menunggu {{ article.queue_age_hours }} jam</span>
                                </div>
                                <h3 class="mt-3 text-lg font-semibold text-gray-900">{{ article.title }}</h3>
                                <p class="mt-1 text-sm text-gray-600">Oleh {{ article.author?.name }} | Submitted {{ article.submitted_at || '-' }}</p>
                                <p v-if="article.excerpt" class="mt-3 max-w-4xl text-sm leading-6 text-gray-700">{{ article.excerpt }}</p>

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

                            <div class="flex shrink-0 flex-wrap gap-2 xl:max-w-xs xl:justify-end">
                                <Link :href="route('editor.reviews.preview', article.id)" class="rounded border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Preview
                                </Link>
                                <button @click="approve(article.id)" class="rounded bg-emerald-600 px-3 py-2 text-sm font-medium text-white" :disabled="approvalForm.processing">
                                    Approve
                                </button>
                                <button @click="openNotes[article.id] = openNotes[article.id] === 'reject' ? null : 'reject'" class="rounded bg-rose-600 px-3 py-2 text-sm font-medium text-white">
                                    Reject
                                </button>
                                <button @click="openNotes[article.id] = openNotes[article.id] === 'revision' ? null : 'revision'" class="rounded bg-amber-500 px-3 py-2 text-sm font-medium text-white">
                                    Request Revision
                                </button>
                            </div>
                        </div>

                        <div v-if="openNotes[article.id]" class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <label class="text-sm font-medium text-gray-700">
                                Catatan {{ openNotes[article.id] === 'reject' ? 'penolakan' : 'revisi' }}
                            </label>
                            <textarea
                                v-model="formFor(article.id).note"
                                rows="3"
                                class="mt-2 w-full rounded-md border-gray-300 text-sm"
                                placeholder="Tulis catatan yang akan masuk ke notifikasi author"
                            ></textarea>
                            <p v-if="formFor(article.id).errors.note" class="mt-1 text-sm text-rose-600">{{ formFor(article.id).errors.note }}</p>
                            <div class="mt-3 flex gap-2">
                                <button
                                    v-if="openNotes[article.id] === 'reject'"
                                    @click="reject(article.id)"
                                    class="rounded bg-rose-600 px-3 py-2 text-sm font-medium text-white"
                                >
                                    Kirim Reject
                                </button>
                                <button
                                    v-else
                                    @click="requestRevision(article.id)"
                                    class="rounded bg-amber-500 px-3 py-2 text-sm font-medium text-white"
                                >
                                    Kirim Revisi
                                </button>
                                <button @click="openNotes[article.id] = null" class="rounded bg-gray-700 px-3 py-2 text-sm font-medium text-white">
                                    Batal
                                </button>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-else class="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center text-gray-600">
                    Tidak ada artikel yang cocok dengan filter.
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
