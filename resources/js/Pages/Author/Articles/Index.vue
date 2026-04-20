<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    articles: Object,
});
</script>

<template>
    <Head title="Artikel Saya" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Artikel Saya</h2>
                <Link :href="route('author.articles.create')" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white">
                    Tulis Artikel
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl sm:px-6 lg:px-8">
                <div class="space-y-4">
                    <article
                        v-for="article in articles.data"
                        :key="article.id"
                        class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
                    >
                        <Link :href="route('author.articles.edit', article.id)" class="text-lg font-semibold text-gray-900">
                            {{ article.title }}
                        </Link>
                        <p class="mt-2 text-sm text-gray-600">
                            Status: {{ article.status }} • Editorial: {{ article.editorial_status }}
                        </p>
                    </article>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

