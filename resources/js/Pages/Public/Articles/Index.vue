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

        <div class="mx-auto max-w-screen-2xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between gap-4 border-b border-zinc-200 pb-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#1BD6FF]">{{ pageMeta.highlight || 'Jelajahi' }}</p>
                    <h1 class="mt-2 font-serif text-4xl font-bold text-zinc-900">{{ pageMeta.title || 'Artikel' }}</h1>
                    <p class="mt-2 text-sm text-zinc-600">{{ pageMeta.subtitle || 'Kumpulan artikel yang sudah dipublikasikan di Expertia.' }}</p>
                </div>
                <Link href="/" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:border-[#1BD6FF] hover:text-[#0f5f74]">
                    Kembali ke Beranda
                </Link>
            </div>

            <form @submit.prevent="applyFilters" class="mt-4 grid gap-2 rounded-xl border border-zinc-200 bg-white p-4 md:grid-cols-4">
                <input
                    v-model="filterForm.q"
                    type="text"
                    placeholder="Cari judul/konten..."
                    class="rounded-md border-zinc-300 text-sm focus:border-[#1BD6FF] focus:ring-[#1BD6FF]"
                />
                <input
                    v-model="filterForm.category"
                    type="text"
                    placeholder="Kategori slug"
                    class="rounded-md border-zinc-300 text-sm focus:border-[#1BD6FF] focus:ring-[#1BD6FF]"
                />
                <select
                    v-model="filterForm.sort"
                    class="rounded-md border-zinc-300 text-sm focus:border-[#1BD6FF] focus:ring-[#1BD6FF]"
                >
                    <option value="latest">Terbaru</option>
                    <option value="trending">Trending</option>
                    <option value="hot">Hotness</option>
                </select>
                <button type="submit" class="rounded-md bg-[#1BD6FF] px-3 py-2 text-sm font-medium text-white hover:bg-[#14bad9]">
                    Terapkan
                </button>
            </form>

            <section class="mt-8 rounded-2xl border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-200 px-5 py-4">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.18em] text-[#FF7950]">Artikel Terbaru</h2>
                </div>

                <div class="divide-y divide-zinc-200">
                    <article
                        v-for="article in articles.data"
                        :key="article.id"
                        class="px-5 py-5"
                    >
                        <Link :href="route('articles.show', article.slug)" class="group grid gap-4 sm:grid-cols-12 sm:items-start">
                            <div class="sm:col-span-4 lg:col-span-3">
                                <img
                                    v-if="article.thumbnail_url"
                                    :src="article.thumbnail_url"
                                    :alt="article.title"
                                    class="h-40 w-full rounded-lg object-cover sm:h-28 lg:h-24"
                                />
                                <div
                                    v-else
                                    class="flex h-40 items-center justify-center rounded-lg bg-zinc-100 text-xs font-medium uppercase tracking-[0.12em] text-zinc-400 sm:h-28 lg:h-24"
                                >
                                    No image
                                </div>
                            </div>

                            <div class="sm:col-span-8 lg:col-span-9">
                                <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                                    <span class="rounded-full bg-[#e8faff] px-2.5 py-1 font-medium text-[#0f5f74]">
                                        {{ article.category?.name ?? 'Tanpa kategori' }}
                                    </span>
                                    <span>{{ formatDate(article.published_at) }}</span>
                                </div>

                                <h3 class="mt-2 font-serif text-2xl font-semibold leading-tight text-zinc-900 transition group-hover:text-[#E80EB5]">
                                    {{ article.title }}
                                </h3>

                                <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-zinc-600">
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
                        </Link>
                    </article>

                    <p v-if="articles.data.length === 0" class="px-5 py-6 text-sm text-zinc-500">Belum ada artikel terbit.</p>
                </div>
            </section>

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
