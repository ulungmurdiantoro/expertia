<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    roles: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Role & Permission" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Role & Permission</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl space-y-4 sm:px-6 lg:px-8">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-sm text-gray-600">
                        Kelola matrix permission per role. Gunakan tombol <strong>Edit</strong> untuk mengubah hak akses.
                    </p>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Role</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Jumlah Permission</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Jumlah User</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">User Preview</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="role in roles" :key="role.id" class="border-t border-gray-100">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ role.name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ role.permissions_count }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ role.users_count }}</td>
                                <td class="px-4 py-3 text-gray-700">
                                    <p v-if="!role.users_preview.length" class="text-gray-400">-</p>
                                    <p v-for="user in role.users_preview" :key="user.id" class="text-xs">
                                        {{ user.name }} ({{ user.email }})
                                    </p>
                                </td>
                                <td class="px-4 py-3">
                                    <Link
                                        :href="route('admin.roles.edit', role.id)"
                                        class="inline-flex items-center rounded bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500"
                                    >
                                        Edit
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

