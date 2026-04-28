<script setup>
import PublicFooter from '@/Components/PublicFooter.vue';
import PublicHeader from '@/Components/PublicHeader.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    articles: Object,
    filters: {
        type: Object,
        default: () => ({
            q: '',
            category: '',
            tag: '',
            sort: 'latest',
        }),
    },
    pageMeta: {
        type: Object,
        default: () => ({
            title: 'Artikel',
            subtitle: 'Kumpulan artikel yang sudah dipublikasikan di Expertia.',
            highlight: 'Jelajahi',
            mode: 'latest',
        }),
    },
});

const filterForm = useForm({
    q: props.filters?.q ?? '',
    category: props.filters?.category ?? '',
    tag: props.filters?.tag ?? '',
    sort: props.filters?.sort ?? 'latest',
});

const applyFilters = () => {
    const routeName = props.pageMeta?.mode === 'trending'
        ? 'articles.trending'
        : props.pageMeta?.mode === 'feed'
            ? 'me.feed'
            : 'articles.index';
    router.get(route(routeName), filterForm.data(), {
        preserveScroll: true,
        replace: true,
    });
};

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
    <Head :title="pageMeta.title || 'Artikel'" />

    <div class="min-h-screen bg-[#f8fafc] text-zinc-900">
        <PublicHeader />

        <main class="mx-auto grid w-full max-w-screen-2xl gap-10 px-4 py-8 sm:px-6 lg:px-8">
            <header class="border-b border-zinc-200 pb-6">
                <p class="section-kicker">{{ pageMeta.highlight || 'Jelajahi' }}</p>
                <div class="mt-2 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h1 class="font-serif text-5xl font-bold tracking-tight text-zinc-950">{{ pageMeta.title || 'Artikel' }}</h1>
                        <p class="mt-3 max-w-3xl text-base leading-7 text-zinc-600">{{ pageMeta.subtitle || 'Kumpulan artikel yang sudah dipublikasikan di Expertia.' }}</p>
                    </div>
                    <Link href="/" class="text-sm font-semibold text-zinc-600 hover:text-[#1BD6FF]">
                        Kembali ke Beranda
                    </Link>
                </div>
            </header>

            <form @submit.prevent="applyFilters" class="grid gap-3 border-b border-zinc-200 py-5 md:grid-cols-4">
                <input
                    v-model="filterForm.q"
                    type="text"
                    placeholder="Cari judul atau konten"
                    class="border-zinc-300 text-sm focus:border-[#1BD6FF] focus:ring-[#1BD6FF]"
                />
                <input
                    v-model="filterForm.category"
                    type="text"
                    placeholder="Kategori slug"
                    class="border-zinc-300 text-sm focus:border-[#1BD6FF] focus:ring-[#1BD6FF]"
                />
                <select
                    v-model="filterForm.sort"
                    class="border-zinc-300 text-sm focus:border-[#1BD6FF] focus:ring-[#1BD6FF]"
                >
                    <option value="latest">Terbaru</option>
                    <option value="trending">Trending</option>
                    <option value="hot">Hotness</option>
                </select>
                <button type="submit" class="bg-zinc-950 px-3 py-2 text-sm font-semibold text-white hover:bg-[#1BD6FF]">
                    Terapkan
                </button>
            </form>

            <section class="divide-y divide-zinc-200">
                <article v-for="article in articles.data" :key="article.id" class="py-6">
                    <Link :href="route('articles.show', article.slug)" class="group grid gap-5 sm:grid-cols-12">
                        <div class="sm:col-span-8">
                            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-[0.08em] text-zinc-500">
                                <span class="text-[#1BD6FF]">{{ article.category?.name ?? 'Tanpa kategori' }}</span>
                                <span>|</span>
                                <span>{{ formatDate(article.published_at) }}</span>
                            </div>
                            <h2 class="mt-2 font-serif text-3xl font-semibold leading-tight tracking-tight group-hover:text-[#1BD6FF]">
                                {{ article.title }}
                            </h2>
                            <p class="mt-3 line-clamp-3 text-base leading-7 text-zinc-600">
                                {{ article.excerpt || 'Ringkasan artikel belum tersedia.' }}
                            </p>
                            <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-zinc-500">
                                <span>oleh {{ article.author?.name || 'Redaksi' }}</span>
                                <span>|</span>
                                <span>{{ article.view_count || 0 }} views</span>
                                <span>|</span>
                                <span>{{ article.comment_count || 0 }} komentar</span>
                            </div>
                        </div>
                        <div class="sm:col-span-4">
                            <img
                                v-if="article.thumbnail_url"
                                :src="article.thumbnail_url"
                                :alt="article.title"
                                class="aspect-[4/3] w-full object-cover"
                            />
                            <div v-else class="flex aspect-[4/3] w-full items-center justify-center border border-zinc-200 text-xs font-semibold uppercase tracking-[0.14em] text-zinc-400">
                                Expertia
                            </div>
                        </div>
                    </Link>
                </article>

                <p v-if="articles.data.length === 0" class="py-8 text-sm text-zinc-500">Belum ada artikel terbit.</p>
            </section>

            <div class="mt-8 flex items-center justify-between border-t border-zinc-200 pt-5">
                <Link
                    :href="articles.prev_page_url || '#'"
                    :class="articles.prev_page_url ? 'text-zinc-950 hover:text-[#1BD6FF]' : 'text-zinc-400 pointer-events-none'"
                    class="text-sm font-semibold"
                >
                    Previous
                </Link>
                <p class="text-sm text-zinc-500">Halaman {{ articles.current_page }} dari {{ articles.last_page }}</p>
                <Link
                    :href="articles.next_page_url || '#'"
                    :class="articles.next_page_url ? 'text-zinc-950 hover:text-[#1BD6FF]' : 'text-zinc-400 pointer-events-none'"
                    class="text-sm font-semibold"
                >
                    Next
                </Link>
            </div>
        </main>

        <PublicFooter />
    </div>
</template>
