<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    comments: Object,
    filters: Object,
});

const filterForm = useForm({
    q: props.filters?.q ?? '',
    status: props.filters?.status ?? '',
    article: props.filters?.article ?? '',
});

const statusForm = useForm({
    status: 'approved',
});
const bulkForm = useForm({
    ids: [],
    status: 'approved',
});
const selectedIds = ref([]);

const applyFilters = () => {
    router.get(route('editor.moderation.comments.index'), filterForm.data(), {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.reset();
    applyFilters();
};

const updateStatus = (id, status) => {
    statusForm.status = status;
    statusForm.patch(route('editor.moderation.comments.update', id), {
        preserveScroll: true,
    });
};

const toggleAll = (event) => {
    if (event.target.checked) {
        selectedIds.value = props.comments.data.map((comment) => comment.id);
        return;
    }

    selectedIds.value = [];
};

const submitBulk = () => {
    if (!selectedIds.value.length) {
        return;
    }

    bulkForm.ids = selectedIds.value;
    bulkForm.patch(route('editor.moderation.comments.bulk-update'), {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = [];
        },
    });
};
</script>

<template>
    <Head title="Moderasi Komentar" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Moderasi Komentar</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl space-y-4 sm:px-6 lg:px-8">
                <form @submit.prevent="applyFilters" class="grid gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-4">
                    <input v-model="filterForm.q" type="text" placeholder="Cari komentar / author" class="rounded-md border-gray-300" />
                    <select v-model="filterForm.status" class="rounded-md border-gray-300">
                        <option value="">Semua status</option>
                        <option value="pending">pending</option>
                        <option value="approved">approved</option>
                        <option value="hidden">hidden</option>
                        <option value="spam">spam</option>
                    </select>
                    <input v-model="filterForm.article" type="text" placeholder="Judul artikel" class="rounded-md border-gray-300" />
                    <div class="flex gap-2">
                        <button type="submit" class="rounded bg-indigo-600 px-3 py-2 text-sm text-white">Filter</button>
                        <button type="button" @click="resetFilters" class="rounded bg-gray-600 px-3 py-2 text-sm text-white">Reset</button>
                    </div>
                </form>

                <div class="flex flex-wrap items-center gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" @change="toggleAll" />
                        Pilih semua di halaman ini
                    </label>
                    <select v-model="bulkForm.status" class="rounded-md border-gray-300 text-sm">
                        <option value="approved">approved</option>
                        <option value="hidden">hidden</option>
                        <option value="spam">spam</option>
                        <option value="pending">pending</option>
                    </select>
                    <button @click="submitBulk" class="rounded bg-indigo-600 px-3 py-2 text-sm text-white">
                        Apply Bulk
                    </button>
                    <span class="text-xs text-gray-500">Terpilih: {{ selectedIds.length }}</span>
                </div>

                <article
                    v-for="comment in comments.data"
                    :key="comment.id"
                    class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <label class="inline-flex items-center gap-2 text-xs text-gray-500">
                                <input
                                    type="checkbox"
                                    :value="comment.id"
                                    v-model="selectedIds"
                                />
                                pilih
                            </label>
                            <p class="text-sm font-semibold text-gray-900">{{ comment.author?.name }}</p>
                            <p class="mt-1 text-sm text-gray-700">{{ comment.content }}</p>
                            <p class="mt-2 text-xs text-gray-500">
                                Artikel: {{ comment.article?.title }} | Status: {{ comment.status }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button @click="updateStatus(comment.id, 'approved')" class="rounded bg-emerald-600 px-3 py-1 text-xs text-white">Approve</button>
                            <button @click="updateStatus(comment.id, 'hidden')" class="rounded bg-amber-500 px-3 py-1 text-xs text-white">Hide</button>
                            <button @click="updateStatus(comment.id, 'spam')" class="rounded bg-rose-600 px-3 py-1 text-xs text-white">Spam</button>
                            <button @click="updateStatus(comment.id, 'pending')" class="rounded bg-gray-600 px-3 py-1 text-xs text-white">Pending</button>
                        </div>
                    </div>
                </article>

                <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <Link
                        :href="comments.prev_page_url || '#'"
                        :class="comments.prev_page_url ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-400'"
                        class="rounded px-3 py-1 text-sm"
                    >
                        Previous
                    </Link>
                    <p class="text-sm text-gray-600">Halaman {{ comments.current_page }} / {{ comments.last_page }}</p>
                    <Link
                        :href="comments.next_page_url || '#'"
                        :class="comments.next_page_url ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-400'"
                        class="rounded px-3 py-1 text-sm"
                    >
                        Next
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
