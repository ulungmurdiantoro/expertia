<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    notifications: Object,
});
</script>

<template>
    <Head title="Notifikasi" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Notifikasi</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl sm:px-6 lg:px-8">
                <div class="space-y-3">
                    <div
                        v-for="notification in notifications.data"
                        :key="notification.id"
                        class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
                    >
                        <p class="font-semibold text-gray-900">{{ notification.title }}</p>
                        <p class="mt-1 text-sm text-gray-600">{{ notification.body }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

