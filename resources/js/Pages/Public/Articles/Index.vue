<script setup>
import PublicArticleRow from '@/Components/PublicArticleRow.vue';
import PublicArticlesHub from '@/Components/PublicArticlesHub.vue';
import PublicPager from '@/Components/PublicPager.vue';
import PublicRubricView from '@/Components/PublicRubricView.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { rubrics } from '@/site';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    articles: Object,
    // Sorotan halaman 1 (lihat ArticleShowcaseService); null untuk pencarian dan halaman berikutnya.
    showcase: {
        type: Object,
        default: null,
    },
    filters: {
        type: Object,
        default: () => ({
            q: '',
            category: '',
            tag: '',
            sort: 'latest',
        }),
    },
    categories: {
        type: Array,
        default: () => [],
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
    meta: {
        type: Object,
        default: () => ({}),
    },
});

const filterForm = useForm({
    q: props.filters?.q ?? '',
    category: props.filters?.category ?? '',
    tag: props.filters?.tag ?? '',
    sort: props.filters?.sort ?? 'latest',
});

const rubric = computed(() => rubrics.find((item) => item.slug === props.filters?.category));

// Halaman rubrik punya header sendiri di semua halamannya; trending dan feed tetap daftar biasa.
const rubricView = computed(() => (rubric.value && props.pageMeta?.mode === 'latest'
    ? { ...rubric.value, name: props.pageMeta?.title || rubric.value.name }
    : null));

// Deskripsi rubrik dari paket desain lebih tepat daripada deskripsi kategori di database.
const subtitle = computed(() => rubric.value?.description || props.pageMeta?.subtitle);

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
</script>

<template>
    <Head :title="meta.title || pageMeta.title || 'Artikel'" />

    <PublicLayout>
        <PublicArticlesHub
            v-if="pageMeta.mode === 'latest' && !filters.category"
            :articles="articles"
            :showcase="showcase?.kind === 'hub' ? showcase : null"
            :filters="filters"
        />

        <PublicRubricView
            v-else-if="rubricView"
            :rubric="rubricView"
            :articles="articles"
            :showcase="showcase"
            :filters="filters"
        />

        <template v-else>
            <div class="page-head">
                <div class="wrap">
                    <p class="eyebrow">{{ pageMeta.highlight || 'Jelajahi' }}</p>
                    <h1>{{ pageMeta.title || 'Artikel' }}</h1>
                    <p v-if="subtitle" class="sub">{{ subtitle }}</p>
                </div>
            </div>
    
            <div class="wrap">
                <form class="filters" role="search" @submit.prevent="applyFilters">
                    <label class="sr-only" for="filter-q">Cari artikel</label>
                    <input id="filter-q" v-model="filterForm.q" class="field" type="search" placeholder="Cari judul atau isi artikel" />
    
                    <label class="sr-only" for="filter-category">Rubrik</label>
                    <select id="filter-category" v-model="filterForm.category" class="field">
                        <option value="">Semua rubrik</option>
                        <option v-for="category in categories" :key="category.id" :value="category.slug">
                            {{ category.name }}
                        </option>
                    </select>
    
                    <label class="sr-only" for="filter-sort">Urutkan</label>
                    <select
                        id="filter-sort"
                        v-model="filterForm.sort"
                        class="field"
                        :disabled="pageMeta.mode === 'trending'"
                    >
                        <option value="latest">Terbaru</option>
                        <option value="trending">Trending</option>
                        <option value="hot">Populer</option>
                    </select>
    
                    <button type="submit" class="btn btn-navy" :disabled="filterForm.processing">Terapkan</button>
                </form>
    
                <ul class="article-list">
                    <PublicArticleRow v-for="article in articles.data" :key="article.id" :article="article" />
                </ul>
    
                <p v-if="articles.data.length === 0" class="empty">
                    Belum ada artikel yang cocok. Coba kata kunci lain atau
                    <Link :href="route('articles.index')">lihat semua artikel</Link>.
                </p>
    
                <PublicPager :paginator="articles" />
            </div>
        </template>
    </PublicLayout>
</template>
