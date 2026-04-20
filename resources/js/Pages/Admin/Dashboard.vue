<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    metrics: Object,
});
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Admin Dashboard</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl sm:px-6 lg:px-8">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-gray-500">Total Users</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ metrics.total_users }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-gray-500">Published Articles</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ metrics.published_articles }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-gray-500">Pending Reviews</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ metrics.pending_reviews }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-gray-500">Pending Reports</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ metrics.pending_reports }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-gray-500">Pending Comments</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ metrics.pending_comments }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

