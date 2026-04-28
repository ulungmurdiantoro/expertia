<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: Object,
    recentBookmarks: Array,
    readingHistory: Array,
    followingAuthors: Array,
    recommendedArticles: Array,
});

const statCards = computed(() => [
    { label: 'Bookmark', value: props.stats?.bookmarks ?? 0, tone: 'border-indigo-200 bg-indigo-50 text-indigo-800' },
    { label: 'Following', value: props.stats?.following ?? 0, tone: 'border-sky-200 bg-sky-50 text-sky-800' },
    { label: 'Notifikasi Baru', value: props.stats?.unread_notifications ?? 0, tone: 'border-amber-200 bg-amber-50 text-amber-800' },
    { label: 'Artikel Dibaca', value: props.stats?.viewed_articles ?? 0, tone: 'border-emerald-200 bg-emerald-50 text-emerald-800' },
]);

const formatDate = (value) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Dashboard Pembaca</h2>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route('me.feed')" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white">
                        Feed Saya
                    </Link>
                    <Link :href="route('articles.index')" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Jelajahi Artikel
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl space-y-5 sm:px-6 lg:px-8">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
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

                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-gray-900">Rekomendasi Untuk Anda</h3>
                        <Link :href="route('me.feed')" class="text-sm font-medium text-indigo-600">Lihat feed</Link>
                    </div>

                    <div v-if="recommendedArticles.length" class="mt-4 grid gap-4 lg:grid-cols-3">
                        <article
                            v-for="article in recommendedArticles"
                            :key="article.id"
                            class="rounded-lg border border-gray-100 p-4"
                        >
                            <p class="text-xs font-medium text-gray-500">{{ article.category?.name || 'Umum' }} | {{ formatDate(article.published_at) }}</p>
                            <Link :href="route('articles.show', article.slug)" class="mt-2 block text-lg font-semibold leading-snug text-gray-900 hover:text-indigo-600">
                                {{ article.title }}
                            </Link>
                            <p class="mt-2 line-clamp-3 text-sm leading-6 text-gray-600">{{ article.excerpt }}</p>
                            <p class="mt-3 text-xs text-gray-500">Oleh {{ article.author?.name || 'Redaksi' }} | {{ article.view_count }} views</p>
                        </article>
                    </div>

                    <div v-else class="mt-4 rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
                        Belum ada rekomendasi. Mulai follow author atau bookmark artikel untuk membentuk feed Anda.
                    </div>
                </section>

                <div class="grid gap-5 xl:grid-cols-3">
                    <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm xl:col-span-1">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-base font-semibold text-gray-900">Bookmark Terbaru</h3>
                            <Link :href="route('me.bookmarks.index')" class="text-sm font-medium text-indigo-600">Kelola</Link>
                        </div>
                        <div class="mt-4 space-y-3">
                            <article v-for="bookmark in recentBookmarks" :key="bookmark.id" class="rounded-md bg-gray-50 p-3">
                                <Link :href="route('articles.show', bookmark.article.slug)" class="font-medium text-gray-900 hover:text-indigo-600">
                                    {{ bookmark.article.title }}
                                </Link>
                                <p class="mt-1 text-xs text-gray-500">{{ bookmark.article.author?.name || 'Redaksi' }} | {{ formatDate(bookmark.created_at) }}</p>
                            </article>
                            <p v-if="recentBookmarks.length === 0" class="text-sm text-gray-500">Belum ada bookmark.</p>
                        </div>
                    </section>

                    <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm xl:col-span-1">
                        <h3 class="text-base font-semibold text-gray-900">Riwayat Baca</h3>
                        <div class="mt-4 space-y-3">
                            <article v-for="item in readingHistory" :key="`${item.article.id}-${item.occurred_at}`" class="rounded-md bg-gray-50 p-3">
                                <Link :href="route('articles.show', item.article.slug)" class="font-medium text-gray-900 hover:text-indigo-600">
                                    {{ item.article.title }}
                                </Link>
                                <p class="mt-1 text-xs text-gray-500">{{ item.article.category?.name || 'Umum' }} | {{ formatDate(item.occurred_at) }}</p>
                            </article>
                            <p v-if="readingHistory.length === 0" class="text-sm text-gray-500">Belum ada riwayat baca.</p>
                        </div>
                    </section>

                    <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm xl:col-span-1">
                        <h3 class="text-base font-semibold text-gray-900">Author Diikuti</h3>
                        <div class="mt-4 space-y-3">
                            <article v-for="author in followingAuthors" :key="author.id" class="flex items-center justify-between gap-3 rounded-md bg-gray-50 p-3">
                                <div class="min-w-0">
                                    <Link :href="route('authors.show', author.profile_slug)" class="font-medium text-gray-900 hover:text-indigo-600">
                                        {{ author.name }}
                                    </Link>
                                    <p class="mt-1 text-xs text-gray-500">{{ author.published_articles_count }} artikel | {{ author.followers_count }} follower</p>
                                </div>
                                <Link :href="route('authors.show', author.profile_slug)" class="shrink-0 rounded border border-gray-300 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-white">
                                    Profil
                                </Link>
                            </article>
                            <p v-if="followingAuthors.length === 0" class="text-sm text-gray-500">Belum mengikuti author.</p>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
