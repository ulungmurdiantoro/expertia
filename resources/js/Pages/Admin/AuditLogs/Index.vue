<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    logs: Object,
    filters: Object,
});

const filterForm = useForm({
    q: props.filters?.q ?? '',
    action: props.filters?.action ?? '',
    subject: props.filters?.subject ?? '',
    start_date: props.filters?.start_date ?? '',
    end_date: props.filters?.end_date ?? '',
});

const applyFilters = () => {
    router.get(route('admin.audit-logs.index'), filterForm.data(), {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.reset();
    applyFilters();
};
</script>

<template>
    <Head title="Audit Logs" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Audit Logs</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl space-y-4 sm:px-6 lg:px-8">
                <form @submit.prevent="applyFilters" class="grid gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-6">
                    <input v-model="filterForm.q" type="text" placeholder="Cari user/action/subject" class="rounded-md border-gray-300" />
                    <input v-model="filterForm.action" type="text" placeholder="Filter action" class="rounded-md border-gray-300" />
                    <input v-model="filterForm.subject" type="text" placeholder="Filter subject type" class="rounded-md border-gray-300" />
                    <input v-model="filterForm.start_date" type="date" class="rounded-md border-gray-300" />
                    <input v-model="filterForm.end_date" type="date" class="rounded-md border-gray-300" />
                    <div class="flex gap-2">
                        <button type="submit" class="rounded bg-indigo-600 px-3 py-2 text-sm text-white">Filter</button>
                        <button type="button" @click="resetFilters" class="rounded bg-gray-600 px-3 py-2 text-sm text-white">Reset</button>
                    </div>
                </form>

                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Waktu</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">User</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Action</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Subject</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Meta</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in logs.data" :key="log.id" class="border-t border-gray-100">
                                <td class="px-4 py-3 text-gray-700">{{ log.created_at }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ log.user?.name || '-' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ log.action }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ log.subject_type }}#{{ log.subject_id }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    <pre class="max-w-xs overflow-x-auto whitespace-pre-wrap text-xs">{{ JSON.stringify(log.meta || {}, null, 2) }}</pre>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <Link
                        :href="logs.prev_page_url || '#'"
                        :class="logs.prev_page_url ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-400'"
                        class="rounded px-3 py-1 text-sm"
                    >
                        Previous
                    </Link>
                    <p class="text-sm text-gray-600">Halaman {{ logs.current_page }} / {{ logs.last_page }}</p>
                    <Link
                        :href="logs.next_page_url || '#'"
                        :class="logs.next_page_url ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-400'"
                        class="rounded px-3 py-1 text-sm"
                    >
                        Next
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
