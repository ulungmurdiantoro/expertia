<script setup>
import PublicFooter from '@/Components/PublicFooter.vue';
import PublicHeader from '@/Components/PublicHeader.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    author: Object,
    articles: Object,
    viewer: Object,
});

const followForm = useForm({});

const formatDate = (value) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
};

const follow = () => {
    followForm.post(route('authors.follow', props.author.profile_slug));
};

const unfollow = () => {
    followForm.delete(route('authors.unfollow', props.author.profile_slug));
};
</script>

<template>
    <Head :title="`Profil ${author.name}`" />

    <div class="min-h-screen bg-[#f8fafc] text-zinc-900">
        <PublicHeader />
        <div class="mx-auto max-w-screen-2xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col items-start justify-between gap-5 sm:flex-row sm:items-center">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#E80EB5]">Profil Penulis</p>
                        <h1 class="mt-2 font-serif text-4xl font-bold text-zinc-900">{{ author.name }}</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-zinc-600">{{ author.bio || 'Belum ada bio.' }}</p>
                        <p class="mt-3 text-xs text-zinc-500">{{ author.followers_count }} followers</p>
                    </div>
                    <div v-if="viewer.is_authenticated && !viewer.is_own_profile">
                        <button
                            v-if="!viewer.is_following"
                            @click="follow"
                            :disabled="followForm.processing"
                            class="rounded-md bg-[#1BD6FF] px-4 py-2 text-sm font-medium text-white hover:bg-[#15bfdc] disabled:opacity-50"
                        >
                            Follow
                        </button>
                        <button
                            v-else
                            @click="unfollow"
                            :disabled="followForm.processing"
                            class="rounded-md bg-zinc-700 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 disabled:opacity-50"
                        >
                            Unfollow
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-8 flex items-center justify-between border-b border-zinc-200 pb-3">
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-[#FF7950]">Artikel Penulis</h2>
                <Link :href="route('articles.index')" class="text-sm font-medium text-zinc-600 hover:text-[#1BD6FF]">Lihat Semua Artikel</Link>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <article
                    v-for="article in articles.data"
                    :key="article.id"
                    class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm"
                >
                    <img
                        v-if="article.thumbnail_url"
                        :src="article.thumbnail_url"
                        :alt="article.title"
                        class="mb-4 h-40 w-full rounded-lg object-cover"
                    />
                    <Link :href="route('articles.show', article.slug)" class="font-serif text-2xl font-semibold leading-tight text-zinc-900 hover:text-[#E80EB5]">
                        {{ article.title }}
                    </Link>
                    <p class="mt-2 text-sm text-zinc-600">{{ article.excerpt || 'Ringkasan artikel belum tersedia.' }}</p>
                    <p class="mt-3 text-xs text-zinc-500">Dipublikasikan {{ formatDate(article.published_at) }}</p>
                </article>
                <p v-if="articles.data.length === 0" class="text-sm text-zinc-500">Belum ada artikel dari penulis ini.</p>
            </div>

            <div class="mt-8 flex items-center justify-between rounded-xl border border-zinc-200 bg-white p-4">
                <Link
                    :href="articles.prev_page_url || '#'"
                    :class="articles.prev_page_url ? 'border-[#1BD6FF] text-[#0f5f74] hover:bg-[#e8faff]' : 'border-zinc-200 text-zinc-400 cursor-not-allowed'"
                    class="rounded-md border px-3 py-2 text-sm font-medium"
                >
                    Previous
                </Link>
                <p class="text-sm text-zinc-600">Halaman {{ articles.current_page }} dari {{ articles.last_page }}</p>
                <Link
                    :href="articles.next_page_url || '#'"
                    :class="articles.next_page_url ? 'border-[#1BD6FF] text-[#0f5f74] hover:bg-[#e8faff]' : 'border-zinc-200 text-zinc-400 cursor-not-allowed'"
                    class="rounded-md border px-3 py-2 text-sm font-medium"
                >
                    Next
                </Link>
            </div>
        </div>

        <PublicFooter />
    </div>
</template>
