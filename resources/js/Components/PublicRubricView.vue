<script setup>
import PublicArticleCard from '@/Components/PublicArticleCard.vue';
import PublicArticleRow from '@/Components/PublicArticleRow.vue';
import PublicAvatar from '@/Components/PublicAvatar.vue';
import PublicPager from '@/Components/PublicPager.vue';
import PublicPopularList from '@/Components/PublicPopularList.vue';
import { authorRole, formatDate } from '@/site';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    rubric: {
        type: Object,
        required: true,
    },
    articles: {
        type: Object,
        required: true,
    },
    // null di halaman 2 dst., hasil pencarian, atau urutan selain terbaru: tampil daftar biasa.
    showcase: {
        type: Object,
        default: null,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

// Rubrik yang menonjolkan orangnya: pakar tampil lebih dulu daripada sampul.
const personFirst = computed(() => ['perspektif-pakar', 'profil-pakar'].includes(props.rubric.slug));
const dark = computed(() => props.rubric.slug === 'riset-inovasi');

const items = computed(() => props.articles.data || []);
const featured = computed(() => (props.showcase ? items.value[0] : null));
const cards = computed(() => (props.showcase ? items.value.slice(1, 4) : []));
const rows = computed(() => (props.showcase ? items.value.slice(4) : items.value));
const rowsBeforeBreak = computed(() => rows.value.slice(0, 3));
const rowsAfterBreak = computed(() => rows.value.slice(3));

const q = ref(props.filters?.q ?? '');
const sort = ref(props.filters?.sort || 'latest');

const go = (extra = {}) => {
    router.get(route('articles.index'), {
        category: props.rubric.slug,
        q: q.value || undefined,
        sort: sort.value !== 'latest' ? sort.value : undefined,
        ...extra,
    }, { preserveScroll: true });
};

const hideImage = (event) => {
    event.target.style.display = 'none';
};
</script>

<template>
    <section class="rubric-head" :class="{ 'is-dark': dark }">
        <div class="wrap">
            <div class="rubric-head-main">
                <div class="rubric-head-copy">
                    <p class="eyebrow">Rubrik</p>
                    <h1>{{ rubric.name }}</h1>
                    <p class="sub">{{ rubric.description }}</p>
                    <p v-if="rubric.slug === 'perspektif-pakar'" class="disclaimer">Pandangan dalam rubrik ini adalah pandangan penulis.</p>
                </div>
                <div v-if="showcase?.contributors?.length" class="rubric-head-people">
                    <div class="avatar-stack">
                        <PublicAvatar v-for="person in showcase.contributors" :key="person.id" :author="person" :size="40" />
                    </div>
                    <p>
                        <strong>{{ showcase.article_count }} artikel</strong> dari
                        <strong>{{ showcase.contributor_count }} {{ personFirst ? 'pakar' : 'penulis' }}</strong>
                    </p>
                </div>
            </div>

            <div class="rubric-tools">
                <nav class="chips" aria-label="Topik">
                    <Link
                        class="chip"
                        :class="{ 'is-active': !filters.tag }"
                        :href="route('articles.index', { category: rubric.slug })"
                    >
                        Semua
                    </Link>
                    <Link
                        v-for="topic in showcase?.topics || []"
                        :key="topic.slug"
                        class="chip"
                        :class="{ 'is-active': filters.tag === topic.slug }"
                        :href="route('articles.index', { category: rubric.slug, tag: topic.slug })"
                    >
                        {{ topic.name }}
                    </Link>
                </nav>
                <form class="rubric-search" role="search" @submit.prevent="go()">
                    <label class="sr-only" :for="`cari-${rubric.slug}`">Cari di rubrik ini</label>
                    <input :id="`cari-${rubric.slug}`" v-model="q" class="field" type="search" placeholder="Cari di rubrik ini" />
                </form>
            </div>
        </div>
    </section>

    <div class="wrap">
        <article v-if="featured && personFirst" class="lead-person">
            <div class="lead-person-who">
                <PublicAvatar :author="featured.author" :size="160" />
                <div>
                    <p class="name">{{ featured.author?.name || 'Redaksi Expertia' }}</p>
                    <p v-if="authorRole(featured.author)" class="role">{{ authorRole(featured.author) }}</p>
                </div>
                <Link
                    v-if="featured.author?.profile_slug"
                    class="link-arrow"
                    :href="route('authors.show', featured.author.profile_slug)"
                >
                    Lihat profil →
                </Link>
            </div>
            <div class="lead-person-copy">
                <Link
                    v-if="featured.thumbnail_url"
                    class="lead-person-cover"
                    :href="route('articles.show', featured.slug)"
                    tabindex="-1"
                    aria-hidden="true"
                >
                    <div class="cover">
                        <img :src="featured.thumbnail_url" alt="" fetchpriority="high" @error="hideImage" />
                    </div>
                </Link>
                <p class="eyebrow">Terbaru<template v-if="featured.tags?.[0]"> · {{ featured.tags[0].name }}</template></p>
                <blockquote v-if="featured.excerpt">
                    <span aria-hidden="true" class="mark">“</span>{{ featured.excerpt }}
                </blockquote>
                <h2>
                    <Link :href="route('articles.show', featured.slug)">{{ featured.title }}</Link>
                </h2>
                <p class="byline">{{ formatDate(featured.published_at) }}</p>
            </div>
        </article>

        <article v-else-if="featured" class="lead-cover">
            <Link class="lead-cover-img" :href="route('articles.show', featured.slug)" tabindex="-1" aria-hidden="true">
                <div class="cover">
                    <img v-if="featured.thumbnail_url" :src="featured.thumbnail_url" alt="" fetchpriority="high" @error="hideImage" />
                </div>
            </Link>
            <div class="lead-cover-copy">
                <p v-if="featured.tags?.[0]" class="pill">{{ featured.tags[0].name }}</p>
                <h2>
                    <Link :href="route('articles.show', featured.slug)">{{ featured.title }}</Link>
                </h2>
                <p v-if="featured.excerpt" class="excerpt">{{ featured.excerpt }}</p>
                <div class="a-card-author">
                    <PublicAvatar :author="featured.author" :size="40" />
                    <span>
                        <strong>{{ featured.author?.name || 'Redaksi Expertia' }}</strong>
                        <template v-if="authorRole(featured.author)"> · {{ authorRole(featured.author) }}</template>
                        · {{ formatDate(featured.published_at) }}
                    </span>
                </div>
            </div>
        </article>

        <div v-if="cards.length" class="card-grid" :class="{ 'is-boxed': personFirst }">
            <PublicArticleCard
                v-for="article in cards"
                :key="article.id"
                :article="article"
                :variant="personFirst ? 'quote' : 'image'"
                :show-rubric="false"
            />
        </div>

        <section v-if="personFirst && showcase?.contributors?.length" class="people-band" aria-labelledby="pakar-h">
            <div class="section-head">
                <h2 id="pakar-h" class="h2">Pakar yang menulis di sini</h2>
                <Link class="link-arrow" :href="route('kontributor')">Jadi kontributor →</Link>
            </div>
            <ul>
                <li v-for="person in showcase.contributors" :key="person.id">
                    <PublicAvatar :author="person" :size="64" />
                    <Link v-if="person.profile_slug" :href="route('authors.show', person.profile_slug)">{{ person.name }}</Link>
                    <span v-else>{{ person.name }}</span>
                    <span class="meta">
                        <template v-if="authorRole(person)">{{ authorRole(person) }} · </template>{{ person.article_count }} tulisan
                    </span>
                </li>
            </ul>
        </section>

        <div v-if="rows.length" class="list-head" :class="{ 'is-first': !showcase }">
            <h2 class="h2">{{ showcase ? (personFirst ? 'Tulisan lainnya' : 'Terbaru di rubrik ini') : 'Artikel' }}</h2>
            <label class="sort">
                Urutkan
                <select v-model="sort" class="field" @change="go()">
                    <option value="latest">Terbaru</option>
                    <option value="hot">Populer</option>
                    <option value="trending">Trending</option>
                </select>
            </label>
        </div>

        <ul class="article-list">
            <PublicArticleRow v-for="article in rowsBeforeBreak" :key="article.id" :article="article" />
        </ul>

        <PublicPopularList v-if="showcase && (showcase.popular?.length ?? 0) > 1" :articles="showcase.popular" />

        <ul v-if="rowsAfterBreak.length" class="article-list">
            <PublicArticleRow v-for="article in rowsAfterBreak" :key="article.id" :article="article" />
        </ul>

        <p v-if="items.length === 0" class="empty">
            <template v-if="filters.q || filters.tag">
                Belum ada artikel yang cocok.
                <Link :href="route('articles.index', { category: rubric.slug })">Lihat semua artikel {{ rubric.name }}</Link>.
            </template>
            <template v-else>
                Rubrik ini segera hadir. Punya tulisan yang cocok?
                <Link :href="route('kontributor')">Jadi kontributor</Link>.
            </template>
        </p>

        <PublicPager :paginator="articles" />
    </div>
</template>
