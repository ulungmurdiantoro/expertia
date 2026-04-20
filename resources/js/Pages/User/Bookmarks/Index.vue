<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    bookmarks: Object,
});
</script>

<template>
    <Head title="Bookmark Saya" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Bookmark Saya</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl sm:px-6 lg:px-8">
                <div class="space-y-4">
                    <article
                        v-for="bookmark in bookmarks.data"
                        :key="bookmark.id"
                        class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
                    >
                        <Link :href="route('articles.show', bookmark.article.slug)" class="text-lg font-semibold text-gray-900">
                            {{ bookmark.article.title }}
                        </Link>
                        <p class="mt-2 text-sm text-gray-600">{{ bookmark.article.excerpt }}</p>
                    </article>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

