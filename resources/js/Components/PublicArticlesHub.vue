<script setup>
import PublicArticleCard from '@/Components/PublicArticleCard.vue';
import PublicAvatar from '@/Components/PublicAvatar.vue';
import PublicPager from '@/Components/PublicPager.vue';
import PublicPopularList from '@/Components/PublicPopularList.vue';
import PublicRubricMark from '@/Components/PublicRubricMark.vue';
import { articleKicker, authorRole, formatDate, rubrics, SITE_TIME_ZONE } from '@/site';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    articles: {
        type: Object,
        required: true,
    },
    showcase: {
        type: Object,
        required: true,
    },
});

const items = computed(() => props.articles.data || []);
const lead = computed(() => items.value[0]);
const side = computed(() => items.value.slice(1, 3));
const rest = computed(() => items.value.slice(3));

const sections = computed(() => props.showcase.sections || []);
const filled = computed(() => sections.value.filter((section) => section.articles.length > 0));
const empty = computed(() => sections.value.filter((section) => section.article_count === 0));
const description = (slug) => rubrics.find((rubric) => rubric.slug === slug)?.description;
const personFirst = (slug) => ['perspektif-pakar', 'profil-pakar'].includes(slug);

// Kelompok waktu dihitung dalam WIB, sama dengan tanggal yang ditampilkan. Tanggal tak
// valid tidak boleh sampai ke Intl.DateTimeFormat: ia melempar error dan halaman kosong.
const dayKey = (date) => new Intl.DateTimeFormat('en-CA', { timeZone: SITE_TIME_ZONE }).format(date);
const groups = computed(() => {
    const today = new Date(`${dayKey(new Date())}T00:00:00`);
    const label = (article) => {
        const published = new Date(String(article.published_at || '').replace(' ', 'T'));
        if (Number.isNaN(published.getTime())) {
            return 'Sebelumnya';
        }
        const days = (today - new Date(`${dayKey(published)}T00:00:00`)) / 86400000;

        return days < 7 ? 'Minggu ini' : days < 31 ? 'Bulan ini' : 'Sebelumnya';
    };

    return rest.value.reduce((list, article) => {
        const name = label(article);
        const last = list[list.length - 1];
        if (last?.name === name) {
            last.articles.push(article);
        } else {
            list.push({ name, articles: [article] });
        }

        return list;
    }, []);
});

const q = ref('');
const search = () => {
    router.get(route('articles.index'), { q: q.value || undefined });
};

const hideImage = (event) => {
    event.target.style.display = 'none';
};
</script>

<template>
    <section class="hub-head">
        <div class="wrap">
            <div class="hub-head-main">
                <div class="hub-head-copy">
                    <p class="eyebrow">Jelajahi</p>
                    <h1>Semua tulisan pakar</h1>
                    <p class="sub">Riset, pandangan, dan profil dari para akademisi, peneliti, dan praktisi, dalam satu tempat.</p>
                </div>
                <form class="hub-search" role="search" @submit.prevent="search">
                    <label class="sr-only" for="hub-q">Cari artikel</label>
                    <input id="hub-q" v-model="q" class="field" type="search" placeholder="Cari judul atau isi artikel" />
                    <button type="submit" class="btn btn-navy">Cari</button>
                </form>
            </div>
            <nav class="rubric-tabs" aria-label="Rubrik">
                <Link :href="route('articles.index')" aria-current="page">Semua</Link>
                <Link v-for="rubric in rubrics" :key="rubric.slug" :href="route('articles.index', { category: rubric.slug })">
                    <PublicRubricMark :slug="rubric.slug" />{{ rubric.name }}
                </Link>
            </nav>
        </div>
    </section>

    <div class="wrap">
        <nav v-if="showcase.topics?.length" class="chips hub-topics" aria-label="Topik populer">
            <span class="muted">Topik populer:</span>
            <Link v-for="topic in showcase.topics" :key="topic.slug" class="chip" :href="route('articles.index', { tag: topic.slug })">
                {{ topic.name }}
            </Link>
        </nav>

        <section v-if="lead" class="spotlight" aria-label="Sorotan">
            <article class="spotlight-main">
                <img v-if="lead.thumbnail_url" :src="lead.thumbnail_url" alt="" fetchpriority="high" @error="hideImage" />
                <div class="spotlight-copy">
                    <p class="a-card-kicker">
                        <PublicRubricMark :slug="lead.category?.slug" />{{ articleKicker(lead) }}
                    </p>
                    <h2>
                        <Link :href="route('articles.show', lead.slug)">{{ lead.title }}</Link>
                    </h2>
                    <p class="byline">
                        <strong>{{ lead.author?.name || 'Redaksi Expertia' }}</strong> · {{ formatDate(lead.published_at) }}
                    </p>
                </div>
            </article>
            <div v-if="side.length" class="spotlight-side">
                <article v-for="article in side" :key="article.id" class="side-card">
                    <p class="a-card-kicker">
                        <PublicRubricMark :slug="article.category?.slug" />{{ article.category?.name }}
                    </p>
                    <h3>
                        <Link :href="route('articles.show', article.slug)">{{ article.title }}</Link>
                    </h3>
                    <div class="a-card-author">
                        <PublicAvatar :author="article.author" :size="32" />
                        <span>
                            <strong>{{ article.author?.name || 'Redaksi Expertia' }}</strong>
                            <template v-if="authorRole(article.author)"> · {{ authorRole(article.author) }}</template>
                        </span>
                    </div>
                </article>
            </div>
        </section>

        <section v-for="section in filled" :key="section.slug" class="hub-section" :aria-labelledby="`sec-${section.slug}`">
            <div class="list-head">
                <h2 :id="`sec-${section.slug}`" class="h2 with-mark">
                    <PublicRubricMark :slug="section.slug" :size="14" />{{ section.name }}
                </h2>
                <Link class="link-arrow" :href="route('articles.index', { category: section.slug })">
                    Lihat rubrik ({{ section.article_count }}) →
                </Link>
            </div>
            <div class="card-grid" :class="{ 'is-boxed': personFirst(section.slug) }">
                <PublicArticleCard
                    v-for="article in section.articles"
                    :key="article.id"
                    :article="article"
                    :variant="personFirst(section.slug) ? 'quote' : 'image'"
                    :show-rubric="false"
                />
            </div>
        </section>

        <div v-if="empty.length" class="soon-grid">
            <div v-for="section in empty" :key="section.slug" class="soon-card">
                <p class="a-card-kicker"><PublicRubricMark :slug="section.slug" />{{ section.name }}</p>
                <p class="title">Segera hadir</p>
                <p>{{ description(section.slug) }}</p>
                <Link class="btn btn-line" :href="route('kontributor')">Kirim tulisan</Link>
            </div>
        </div>

        <section v-if="rest.length" class="hub-latest" aria-labelledby="hub-latest-h">
            <div class="hub-latest-main">
                <div class="list-head">
                    <h2 id="hub-latest-h" class="h2">Terbaru dari semua rubrik</h2>
                </div>
                <template v-for="group in groups" :key="group.name">
                    <p class="group-label">{{ group.name }}</p>
                    <article v-for="article in group.articles" :key="article.id" class="tone-row">
                        <PublicRubricMark class="bar" :slug="article.category?.slug" />
                        <div>
                            <p class="a-card-kicker">{{ articleKicker(article) }}</p>
                            <h3>
                                <Link :href="route('articles.show', article.slug)">{{ article.title }}</Link>
                            </h3>
                            <p class="byline">
                                <strong>{{ article.author?.name || 'Redaksi Expertia' }}</strong> · {{ formatDate(article.published_at) }}
                            </p>
                        </div>
                    </article>
                </template>
                <PublicPager :paginator="articles" />
            </div>

            <aside class="hub-aside">
                <PublicPopularList :articles="showcase.popular || []" layout="stack" />
                <div v-if="showcase.new_contributors?.length" class="new-people">
                    <p class="eyebrow">Pakar baru bergabung</p>
                    <ul>
                        <li v-for="person in showcase.new_contributors" :key="person.id">
                            <PublicAvatar :author="person" :size="40" />
                            <div>
                                <Link v-if="person.profile_slug" :href="route('authors.show', person.profile_slug)">{{ person.name }}</Link>
                                <span v-else>{{ person.name }}</span>
                                <p v-if="authorRole(person)">{{ authorRole(person) }}</p>
                            </div>
                        </li>
                    </ul>
                    <Link class="link-arrow" :href="route('kontributor')">Jadi kontributor →</Link>
                </div>
            </aside>
        </section>
        <PublicPager v-else :paginator="articles" />

        <p v-if="items.length === 0" class="empty">Belum ada artikel terbit.</p>
    </div>
</template>
