<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    users: Object,
    filters: Object,
    roleOptions: Array,
});

const filterForm = useForm({
    q: props.filters?.q ?? '',
    role: props.filters?.role ?? '',
});

const applyFilters = () => {
    router.get(route('admin.users.index'), filterForm.data(), {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.q = '';
    filterForm.role = '';
    applyFilters();
};
</script>

<template>
    <Head title="Pengguna" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Pengguna</h2>
                <Link :href="route('admin.contributors.create')" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white">
                    Tambah Kontributor
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl space-y-4 sm:px-6 lg:px-8">
                <form @submit.prevent="applyFilters" class="grid gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-4">
                    <input
                        v-model="filterForm.q"
                        type="text"
                        placeholder="Cari nama, email, atau instansi"
                        class="rounded-md border-gray-300 md:col-span-2"
                    />
                    <select v-model="filterForm.role" class="rounded-md border-gray-300">
                        <option value="">Semua role</option>
                        <option v-for="role in roleOptions" :key="role.value" :value="role.value">{{ role.label }}</option>
                    </select>
                    <div class="flex gap-2">
                        <button type="submit" class="rounded bg-indigo-600 px-3 py-2 text-sm text-white">Filter</button>
                        <button type="button" @click="resetFilters" class="rounded bg-gray-600 px-3 py-2 text-sm text-white">Reset</button>
                    </div>
                </form>

                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Nama</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Role</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Instansi</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700">Artikel</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Terdaftar</th>
                                <th class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users.data" :key="user.id" class="border-t border-gray-100">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-900">{{ user.name }}</p>
                                    <p class="text-xs text-gray-500">{{ user.email }}</p>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ user.roles.join(', ') || '-' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ user.institution || '-' }}</td>
                                <td class="px-4 py-3 text-right text-gray-700">{{ user.articles_count }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ user.created_at }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a
                                            v-if="user.profile_url && user.articles_count > 0"
                                            :href="user.profile_url"
                                            target="_blank"
                                            rel="noopener"
                                            class="rounded border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                        >
                                            Profil publik
                                        </a>
                                        <Link
                                            v-if="user.can_edit"
                                            :href="route('admin.users.edit', user.id)"
                                            class="rounded bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white"
                                        >
                                            Edit
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">Tidak ada pengguna yang cocok dengan filter.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <Link
                        :href="users.prev_page_url || '#'"
                        :class="users.prev_page_url ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-400'"
                        class="rounded px-3 py-1 text-sm"
                    >
                        Previous
                    </Link>
                    <p class="text-sm text-gray-600">Halaman {{ users.current_page }} / {{ users.last_page }} · {{ users.total }} pengguna</p>
                    <Link
                        :href="users.next_page_url || '#'"
                        :class="users.next_page_url ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-400'"
                        class="rounded px-3 py-1 text-sm"
                    >
                        Next
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
