<script setup>
import PublicFooter from '@/Components/PublicFooter.vue';
import PublicHeader from '@/Components/PublicHeader.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    featured: {
        type: Object,
        default: null,
    },
    latest: {
        type: Array,
        default: () => [],
    },
    opinion: {
        type: Array,
        default: () => [],
    },
    popularCategories: {
        type: Array,
        default: () => [],
    },
});

const formatDate = (value) => {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
};

const leadSide = props.latest.slice(0, 3);
const latestList = props.latest.slice(3, 9);
const editorPicks = props.latest.slice(0, 6);
</script>

<template>
    <Head title="Expertia | Perspektif dan Analisis" />

    <div class="min-h-screen bg-white text-zinc-950">
        <PublicHeader :can-login="canLogin" :can-register="canRegister" />

        <main class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            <section class="grid gap-8 border-b border-zinc-200 pb-8 lg:grid-cols-12">
                <article class="lg:col-span-7">
                    <p class="section-kicker">Headline</p>
                    <template v-if="featured">
                        <Link :href="route('articles.show', featured.slug)" class="group block">
                            <img
                                v-if="featured.thumbnail_url"
                                :src="featured.thumbnail_url"
                                :alt="featured.thumbnail_alt || featured.title"
                                class="mb-5 aspect-[16/9] w-full object-cover"
                            />
                            <h1 class="font-serif text-4xl font-bold leading-[1.05] tracking-tight text-zinc-950 transition-colors duration-200 hover:text-[#1BD6FF] sm:text-5xl group-hover:text-[#1BD6FF]">
                                {{ featured.title }}
                            </h1>
                            <p class="mt-4 max-w-3xl text-lg leading-7 text-zinc-700">
                                {{ featured.excerpt || 'Analisis mendalam dari penulis dan pakar kami.' }}
                            </p>
                            <p class="mt-4 text-sm text-zinc-500">
                                {{ featured.author?.name || 'Redaksi' }} | {{ featured.category?.name || 'Umum' }} | {{ formatDate(featured.published_at) }}
                            </p>
                        </Link>
                    </template>
                    <div v-else class="border-y border-zinc-200 py-10">
                        <h1 class="font-serif text-4xl font-bold leading-tight tracking-tight text-zinc-950 sm:text-5xl">
                            Perspektif kritis untuk pembaca Indonesia
                        </h1>
                        <p class="mt-4 max-w-3xl text-lg leading-7 text-zinc-700">
                            Konten unggulan akan tampil di sini setelah artikel pertama dipublikasikan.
                        </p>
                    </div>
                </article>

                <aside class="space-y-5 lg:col-span-5">
                    <p class="section-kicker">Sorotan Hari Ini</p>
                    <article
                        v-for="story in leadSide"
                        :key="story.id"
                        class="border-b border-zinc-200 pb-5"
                    >
                        <Link :href="route('articles.show', story.slug)" class="group grid gap-4 sm:grid-cols-5">
                            <img
                                v-if="story.thumbnail_url"
                                :src="story.thumbnail_url"
                                :alt="story.thumbnail_alt || story.title"
                                class="aspect-[4/3] w-full object-cover sm:col-span-2"
                            />
                            <div :class="story.thumbnail_url ? 'sm:col-span-3' : 'sm:col-span-5'">
                                <h2 class="font-serif text-2xl font-semibold leading-tight transition-colors duration-200 hover:text-[#FF7950] group-hover:text-[#FF7950]">
                                    {{ story.title }}
                                </h2>
                                <p class="mt-2 text-xs uppercase tracking-[0.08em] text-zinc-500">
                                    {{ story.category?.name || 'Umum' }} | {{ formatDate(story.published_at) }}
                                </p>
                            </div>
                        </Link>
                    </article>
                    <p v-if="leadSide.length === 0" class="text-sm text-zinc-500">Belum ada artikel sorotan.</p>
                </aside>
            </section>

            <section class="grid gap-10 py-9 lg:grid-cols-12">
                <div class="lg:col-span-8">
                    <div class="section-heading">
                        <h2>Artikel Terbaru</h2>
                        <Link :href="route('articles.index')" class="text-zinc-600 transition-colors duration-200 hover:text-[#1BD6FF]">Lihat Semua</Link>
                    </div>

                    <div class="divide-y divide-zinc-200">
                        <article v-for="story in latestList" :key="story.id" class="py-5">
                            <Link :href="route('articles.show', story.slug)" class="group grid gap-4 sm:grid-cols-12">
                                <div class="text-xs font-semibold uppercase tracking-[0.08em] text-zinc-500 sm:col-span-2">
                                    {{ story.category?.name || 'Umum' }}
                                </div>
                                <div class="sm:col-span-7">
                                    <h3 class="font-serif text-2xl font-semibold leading-tight transition-colors duration-200 hover:text-[#1BD6FF] group-hover:text-[#1BD6FF]">
                                        {{ story.title }}
                                    </h3>
                                    <p class="mt-2 line-clamp-2 text-sm leading-6 text-zinc-600">
                                        {{ story.excerpt || 'Baca artikel untuk memahami isu ini lebih mendalam.' }}
                                    </p>
                                    <p class="mt-2 text-xs text-zinc-500">
                                        {{ story.author?.name || 'Redaksi' }} | {{ formatDate(story.published_at) }}
                                    </p>
                                </div>
                                <img
                                    v-if="story.thumbnail_url"
                                    :src="story.thumbnail_url"
                                    :alt="story.thumbnail_alt || story.title"
                                    class="aspect-[4/3] w-full object-cover sm:col-span-3"
                                />
                            </Link>
                        </article>
                        <p v-if="latestList.length === 0" class="py-5 text-sm text-zinc-500">Artikel terbaru akan muncul setelah ada publikasi baru.</p>
                    </div>
                </div>

                <aside class="space-y-8 lg:col-span-4">
                    <section>
                        <div class="section-heading">
                            <h2>Opini</h2>
                        </div>
                        <div class="divide-y divide-zinc-200">
                            <article v-for="story in opinion" :key="story.id" class="py-4">
                                <Link :href="route('articles.show', story.slug)" class="font-serif text-xl font-semibold leading-snug transition-colors duration-200 hover:text-[#E80EB5]">
                                    {{ story.title }}
                                </Link>
                                <p class="mt-2 text-xs text-zinc-500">{{ story.author?.name || 'Kontributor' }} | {{ formatDate(story.published_at) }}</p>
                            </article>
                            <p v-if="opinion.length === 0" class="py-4 text-sm text-zinc-500">Belum ada artikel opini.</p>
                        </div>
                    </section>

                    <section>
                        <div class="section-heading">
                            <h2>Kategori Populer</h2>
                        </div>
                        <div class="divide-y divide-zinc-200">
                            <div v-for="category in popularCategories" :key="category.id" class="flex items-center justify-between py-3 text-sm">
                                <span class="font-medium text-zinc-900">{{ category.name }}</span>
                                <span class="text-zinc-500">{{ category.published_articles_count }} artikel</span>
                            </div>
                            <p v-if="popularCategories.length === 0" class="py-4 text-sm text-zinc-500">Kategori populer belum tersedia.</p>
                        </div>
                    </section>
                </aside>
            </section>

            <section class="border-t border-zinc-200 py-9">
                <div class="section-heading">
                    <h2>Pilihan Redaksi</h2>
                    <Link :href="route('articles.index')" class="text-zinc-600 transition-colors duration-200 hover:text-[#0f5f74]">Semua Topik</Link>
                </div>
                <div class="mt-5 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <article v-for="story in editorPicks" :key="`pick-${story.id}`" class="border-b border-zinc-200 pb-5">
                        <img
                            v-if="story.thumbnail_url"
                            :src="story.thumbnail_url"
                            :alt="story.thumbnail_alt || story.title"
                            class="mb-3 aspect-[16/10] w-full object-cover"
                        />
                        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-zinc-500">{{ story.category?.name || 'Umum' }}</p>
                        <Link :href="route('articles.show', story.slug)" class="mt-2 block font-serif text-xl font-semibold leading-snug transition-colors duration-200 hover:text-[#0f5f74]">
                            {{ story.title }}
                        </Link>
                        <p class="mt-2 line-clamp-2 text-sm leading-6 text-zinc-600">{{ story.excerpt || 'Analisis lengkap tersedia pada artikel ini.' }}</p>
                    </article>
                    <p v-if="editorPicks.length === 0" class="text-sm text-zinc-500 sm:col-span-2 lg:col-span-3">
                        Artikel pilihan redaksi akan tampil otomatis setelah lebih banyak konten dipublikasikan.
                    </p>
                </div>
            </section>
        </main>

        <PublicFooter />
    </div>
</template>
