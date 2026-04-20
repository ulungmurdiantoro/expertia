<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    reports: Object,
    filters: Object,
});

const filterForm = useForm({
    q: props.filters?.q ?? '',
    status: props.filters?.status ?? '',
    subject: props.filters?.subject ?? '',
});

const statusForm = useForm({
    status: 'resolved',
    resolution_note: '',
});

const actionForm = useForm({
    action: 'hide_comment',
    resolution_note: '',
});
const bulkForm = useForm({
    ids: [],
    status: 'resolved',
    resolution_note: '',
});
const bulkActionForm = useForm({
    ids: [],
    action: 'hide_comment',
    resolution_note: '',
});
const selectedIds = ref([]);

const applyFilters = () => {
    router.get(route('editor.moderation.reports.index'), filterForm.data(), {
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
    statusForm.patch(route('editor.moderation.reports.update', id), {
        preserveScroll: true,
    });
};

const applySubjectAction = (report) => {
    const actionByType = {
        article: 'hide_article',
        comment: 'hide_comment',
    };

    actionForm.action = actionByType[report.subject_key] ?? 'hide_comment';
    actionForm.patch(route('editor.moderation.reports.subject-action', report.id), {
        preserveScroll: true,
    });
};

const toggleAll = (event) => {
    if (event.target.checked) {
        selectedIds.value = props.reports.data.map((report) => report.id);
        return;
    }

    selectedIds.value = [];
};

const submitBulk = () => {
    if (!selectedIds.value.length) {
        return;
    }

    bulkForm.ids = selectedIds.value;
    bulkForm.patch(route('editor.moderation.reports.bulk-update'), {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = [];
        },
    });
};

const submitBulkSubjectAction = () => {
    if (!selectedIds.value.length) {
        return;
    }

    bulkActionForm.ids = selectedIds.value;
    bulkActionForm.patch(route('editor.moderation.reports.bulk-subject-action'), {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = [];
        },
    });
};
</script>

<template>
    <Head title="Moderasi Laporan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Moderasi Laporan</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl space-y-4 sm:px-6 lg:px-8">
                <form @submit.prevent="applyFilters" class="grid gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-4">
                    <input v-model="filterForm.q" type="text" placeholder="Cari reason / reporter" class="rounded-md border-gray-300" />
                    <select v-model="filterForm.status" class="rounded-md border-gray-300">
                        <option value="">Semua status</option>
                        <option value="pending">pending</option>
                        <option value="resolved">resolved</option>
                        <option value="dismissed">dismissed</option>
                    </select>
                    <select v-model="filterForm.subject" class="rounded-md border-gray-300">
                        <option value="">Semua subject</option>
                        <option value="article">article</option>
                        <option value="comment">comment</option>
                    </select>
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
                        <option value="resolved">resolved</option>
                        <option value="dismissed">dismissed</option>
                        <option value="pending">pending</option>
                    </select>
                    <button @click="submitBulk" class="rounded bg-indigo-600 px-3 py-2 text-sm text-white">
                        Apply Bulk
                    </button>
                    <select v-model="bulkActionForm.action" class="rounded-md border-gray-300 text-sm">
                        <option value="hide_comment">hide_comment</option>
                        <option value="approve_comment">approve_comment</option>
                        <option value="spam_comment">spam_comment</option>
                        <option value="hide_article">hide_article</option>
                        <option value="archive_article">archive_article</option>
                    </select>
                    <button @click="submitBulkSubjectAction" class="rounded bg-rose-600 px-3 py-2 text-sm text-white">
                        Apply Bulk Subject Action
                    </button>
                    <span class="text-xs text-gray-500">Terpilih: {{ selectedIds.length }}</span>
                </div>

                <article
                    v-for="report in reports.data"
                    :key="report.id"
                    class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <label class="inline-flex items-center gap-2 text-xs text-gray-500">
                                <input
                                    type="checkbox"
                                    :value="report.id"
                                    v-model="selectedIds"
                                />
                                pilih
                            </label>
                            <p class="text-sm font-semibold text-gray-900">{{ report.subject_type }} - {{ report.subject_summary }}</p>
                            <p class="mt-1 text-sm text-gray-700">Reason: {{ report.reason }}</p>
                            <p class="mt-1 text-sm text-gray-700">{{ report.note }}</p>
                            <p class="mt-2 text-xs text-gray-500">Reporter: {{ report.reporter?.name }} | Status: {{ report.status }}</p>
                            <Link :href="route('editor.moderation.reports.show', report.id)" class="mt-2 inline-block text-xs font-medium text-indigo-600">
                                Lihat detail
                            </Link>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button @click="updateStatus(report.id, 'resolved')" class="rounded bg-emerald-600 px-3 py-1 text-xs text-white">Resolve</button>
                            <button @click="updateStatus(report.id, 'dismissed')" class="rounded bg-gray-700 px-3 py-1 text-xs text-white">Dismiss</button>
                            <button @click="updateStatus(report.id, 'pending')" class="rounded bg-amber-500 px-3 py-1 text-xs text-white">Pending</button>
                            <button
                                v-if="report.can_apply_subject_action"
                                @click="applySubjectAction(report)"
                                class="rounded bg-rose-600 px-3 py-1 text-xs text-white"
                            >
                                Action ke konten
                            </button>
                        </div>
                    </div>
                </article>

                <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <Link
                        :href="reports.prev_page_url || '#'"
                        :class="reports.prev_page_url ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-400'"
                        class="rounded px-3 py-1 text-sm"
                    >
                        Previous
                    </Link>
                    <p class="text-sm text-gray-600">Halaman {{ reports.current_page }} / {{ reports.last_page }}</p>
                    <Link
                        :href="reports.next_page_url || '#'"
                        :class="reports.next_page_url ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-400'"
                        class="rounded px-3 py-1 text-sm"
                    >
                        Next
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
