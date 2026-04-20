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

const topStories = props.latest.slice(0, 3);
const compactStories = props.latest.slice(3, 8);
const featureGrid = props.latest.slice(0, 6);
</script>

<template>
    <Head title="Expertia | Perspektif dan Analisis" />

    <div class="min-h-screen bg-[#f8fafc] text-zinc-900">
        <PublicHeader :can-login="canLogin" :can-register="canRegister" />

        <main class="mx-auto grid w-full max-w-screen-2xl gap-10 px-4 py-8 sm:px-6 lg:px-8">
            <section class="border-b border-zinc-200 pb-8">
                <article>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#E80EB5]">Headline</p>
                    <template v-if="featured">
                        <Link :href="route('articles.show', featured.slug)" class="group mt-4 block overflow-hidden rounded-xl">
                            <div v-if="featured.thumbnail_url" class="relative">
                                <img
                                    :src="featured.thumbnail_url"
                                    :alt="featured.thumbnail_alt || featured.title"
                                    class="h-[28rem] w-full object-cover sm:h-[34rem]"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/25 to-transparent"></div>
                                <div class="absolute inset-x-0 bottom-0 p-6 sm:p-10">
                                    <h3 class="max-w-5xl font-serif text-3xl font-bold leading-tight text-white transition group-hover:text-[#1BD6FF] sm:text-5xl">
                                        {{ featured.title }}
                                    </h3>
                                    <p class="mt-4 max-w-4xl text-base leading-relaxed text-zinc-100">
                                        {{ featured.excerpt || 'Baca analisis mendalam dari para penulis kami.' }}
                                    </p>
                                    <p class="mt-4 text-sm text-zinc-200">
                                        {{ featured.author?.name || 'Redaksi' }} | {{ featured.category?.name || 'Umum' }} | {{ formatDate(featured.published_at) }}
                                    </p>
                                </div>
                            </div>
                            <div v-else class="rounded-xl bg-white p-6 ring-1 ring-zinc-200 sm:p-10">
                                <h2 class="font-serif text-3xl font-bold leading-tight text-zinc-900 transition group-hover:text-[#E80EB5] sm:text-5xl">
                                    {{ featured.title }}
                                </h2>
                                <p class="mt-4 max-w-4xl text-base leading-relaxed text-zinc-600">
                                    {{ featured.excerpt || 'Baca analisis mendalam dari para penulis kami.' }}
                                </p>
                                <p class="mt-4 text-sm text-zinc-500">
                                    {{ featured.author?.name || 'Redaksi' }} | {{ featured.category?.name || 'Umum' }} | {{ formatDate(featured.published_at) }}
                                </p>
                            </div>
                        </Link>
                    </template>
                    <template v-else>
                        <div class="mt-4 rounded-xl bg-white p-8 ring-1 ring-zinc-200 sm:p-10">
                            <h2 class="font-serif text-3xl font-bold leading-tight text-zinc-900 sm:text-5xl">
                                Perspektif kritis untuk pembaca Indonesia
                            </h2>
                            <p class="mt-4 max-w-3xl text-base leading-relaxed text-zinc-600">
                                Konten unggulan akan tampil di sini setelah artikel pertama dipublikasikan.
                            </p>
                        </div>
                    </template>
                </article>
            </section>

            <section>
                <h2 class="border-b border-zinc-200 pb-2 text-sm font-semibold uppercase tracking-[0.2em] text-[#FF7950]">Sorotan Hari Ini</h2>
                <div class="mt-4 space-y-4">
                    <article
                        v-for="story in topStories"
                        :key="story.id"
                        class="grid items-start gap-4 border-b border-zinc-100 pb-4 sm:grid-cols-12"
                    >
                        <div class="sm:col-span-3">
                            <img
                                v-if="story.thumbnail_url"
                                :src="story.thumbnail_url"
                                :alt="story.thumbnail_alt || story.title"
                                class="h-28 w-full rounded-lg object-cover sm:h-24"
                            />
                        </div>
                        <div :class="story.thumbnail_url ? 'sm:col-span-9' : 'sm:col-span-12'">
                            <Link :href="route('articles.show', story.slug)" class="font-serif text-2xl font-semibold leading-tight text-zinc-900 hover:text-[#FF7950]">
                                {{ story.title }}
                            </Link>
                            <p class="mt-2 text-sm text-zinc-500">
                                {{ story.author?.name || 'Redaksi' }} | {{ story.category?.name || 'Umum' }} | {{ formatDate(story.published_at) }}
                            </p>
                        </div>
                    </article>
                    <p v-if="topStories.length === 0" class="text-sm text-zinc-500">Belum ada artikel sorotan.</p>
                </div>
            </section>

            <section class="grid gap-8 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <div class="flex items-center justify-between border-b border-zinc-200 pb-2">
                        <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-[#1BD6FF]">Artikel Terbaru</h2>
                        <Link :href="route('articles.index')" class="text-xs font-semibold uppercase tracking-[0.12em] text-[#0f5f74] hover:text-[#1BD6FF]">Lihat Semua</Link>
                    </div>
                    <div class="mt-4 divide-y divide-zinc-100">
                        <article v-for="story in compactStories" :key="story.id" class="grid gap-2 py-4 sm:grid-cols-4 sm:gap-6">
                            <div class="text-xs uppercase tracking-[0.08em] text-zinc-500">{{ story.category?.name || 'Umum' }}</div>
                            <div class="sm:col-span-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                <img
                                    v-if="story.thumbnail_url"
                                    :src="story.thumbnail_url"
                                    :alt="story.thumbnail_alt || story.title"
                                    class="mb-2 h-24 w-full rounded-lg object-cover sm:mb-0 sm:h-28"
                                />
                                <div :class="story.thumbnail_url ? 'sm:col-span-2' : 'sm:col-span-3'">
                                <Link :href="route('articles.show', story.slug)" class="font-serif text-2xl font-semibold leading-tight text-zinc-900 hover:text-[#1BD6FF]">
                                    {{ story.title }}
                                </Link>
                                <p class="mt-1 text-sm text-zinc-600">{{ story.excerpt || 'Analisis lengkap tersedia pada artikel ini.' }}</p>
                                <p class="mt-2 text-xs text-zinc-500">{{ story.author?.name || 'Redaksi' }} | {{ formatDate(story.published_at) }}</p>
                                </div>
                            </div>
                        </article>
                        <p v-if="compactStories.length === 0" class="py-4 text-sm text-zinc-500">Artikel terbaru akan muncul setelah ada publikasi baru.</p>
                    </div>
                </div>

                <div class="space-y-8">
                    <section>
                        <h2 class="border-b border-zinc-200 pb-2 text-sm font-semibold uppercase tracking-[0.2em] text-[#E80EB5]">Opini</h2>
                        <div class="mt-4 space-y-4">
                            <article v-for="story in opinion" :key="story.id" class="rounded-md bg-white p-4 ring-1 ring-[#f2c9ea]">
                                <Link :href="route('articles.show', story.slug)" class="font-serif text-xl font-semibold leading-snug text-zinc-900 hover:text-[#E80EB5]">
                                    {{ story.title }}
                                </Link>
                                <p class="mt-2 text-xs text-zinc-500">{{ story.author?.name || 'Kontributor' }} | {{ formatDate(story.published_at) }}</p>
                            </article>
                            <p v-if="opinion.length === 0" class="text-sm text-zinc-500">Belum ada artikel opini.</p>
                        </div>
                    </section>

                    <section>
                        <h2 class="border-b border-zinc-200 pb-2 text-sm font-semibold uppercase tracking-[0.2em] text-[#FF7950]">Kategori Populer</h2>
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div
                                v-for="category in popularCategories"
                                :key="category.id"
                                class="rounded-md bg-white px-3 py-2 text-sm ring-1 ring-zinc-200"
                            >
                                <p class="font-medium text-zinc-900">{{ category.name }}</p>
                                <p class="text-xs text-zinc-500">{{ category.published_articles_count }} artikel</p>
                            </div>
                            <p v-if="popularCategories.length === 0" class="col-span-2 text-sm text-zinc-500">Kategori populer belum tersedia.</p>
                        </div>
                    </section>
                </div>
            </section>

            <section>
                <div class="flex items-center justify-between border-b border-zinc-200 pb-2">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-[#0f5f74]">Pilihan Redaksi</h2>
                    <Link :href="route('articles.index')" class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-600 hover:text-[#0f5f74]">
                        Lihat Semua Topik
                    </Link>
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="story in featureGrid"
                        :key="`feature-${story.id}`"
                        class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-zinc-200 transition hover:-translate-y-0.5 hover:ring-[#b6f0ff]"
                    >
                        <img
                            v-if="story.thumbnail_url"
                            :src="story.thumbnail_url"
                            :alt="story.thumbnail_alt || story.title"
                            class="mb-3 h-40 w-full rounded-lg object-cover"
                        />
                        <p class="text-xs uppercase tracking-[0.08em] text-zinc-500">{{ story.category?.name || 'Umum' }}</p>
                        <Link :href="route('articles.show', story.slug)" class="mt-2 block font-serif text-xl font-semibold leading-snug text-zinc-900 hover:text-[#0f5f74]">
                            {{ story.title }}
                        </Link>
                        <p class="mt-2 line-clamp-3 text-sm text-zinc-600">{{ story.excerpt || 'Baca artikel untuk memahami isu ini lebih mendalam.' }}</p>
                        <p class="mt-3 text-xs text-zinc-500">{{ story.author?.name || 'Redaksi' }} | {{ formatDate(story.published_at) }}</p>
                    </article>
                    <article
                        v-if="featureGrid.length === 0"
                        class="rounded-xl bg-white p-5 text-sm text-zinc-500 shadow-sm ring-1 ring-zinc-200 sm:col-span-2 lg:col-span-3"
                    >
                        Artikel pilihan redaksi akan tampil otomatis setelah lebih banyak konten dipublikasikan.
                    </article>
                </div>
            </section>

            <section class="rounded-2xl bg-[#0b2530] px-6 py-8 text-white sm:px-8">
                <div class="grid gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#1BD6FF]">Kontribusi</p>
                        <h2 class="mt-2 font-serif text-3xl font-bold leading-tight">Punya gagasan berbasis riset atau pengalaman lapangan?</h2>
                        <p class="mt-3 max-w-3xl text-sm leading-relaxed text-zinc-200">
                            Kirimkan tulisan Anda untuk dibaca publik luas. Tim editor Expertia membantu proses kurasi agar artikel lebih kuat, jelas, dan relevan.
                        </p>
                    </div>
                    <div class="flex items-center lg:justify-end">
                        <Link
                            :href="canRegister ? route('register') : route('login')"
                            class="inline-flex rounded-md bg-[#FF7950] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#eb6239]"
                        >
                            Mulai Menulis
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <PublicFooter />
    </div>
</template>
