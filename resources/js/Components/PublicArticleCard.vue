<script setup>
import PublicAvatar from '@/Components/PublicAvatar.vue';
import PublicRubricMark from '@/Components/PublicRubricMark.vue';
import { authorRole, formatDate } from '@/site';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    article: {
        type: Object,
        required: true,
    },
    // image: sampul di atas · quote: ringkasan sebagai kutipan, untuk rubrik yang menonjolkan pakarnya
    variant: {
        type: String,
        default: 'image',
    },
    // false di halaman rubrik, karena nama rubrik sudah jelas dari header.
    showRubric: {
        type: Boolean,
        default: true,
    },
});

const kicker = computed(() => [
    props.showRubric ? props.article.category?.name : null,
    props.article.tags?.[0]?.name,
].filter(Boolean).join(' · '));

const hideImage = (event) => {
    event.target.style.display = 'none';
};
</script>

<template>
    <article class="a-card" :class="`is-${variant}`">
        <Link
            v-if="variant === 'image' || article.thumbnail_url"
            class="a-card-cover"
            :href="route('articles.show', article.slug)"
            tabindex="-1"
            aria-hidden="true"
        >
            <div class="cover light">
                <img v-if="article.thumbnail_url" :src="article.thumbnail_url" alt="" loading="lazy" @error="hideImage" />
            </div>
        </Link>
        <p v-if="variant === 'quote' && article.excerpt" class="a-card-quote">“{{ article.excerpt }}”</p>
        <p v-if="kicker" class="a-card-kicker">
            <PublicRubricMark v-if="showRubric" :slug="article.category?.slug" />
            {{ kicker }}
        </p>
        <h3>
            <Link :href="route('articles.show', article.slug)">{{ article.title }}</Link>
        </h3>
        <div v-if="variant === 'quote'" class="a-card-author">
            <PublicAvatar :author="article.author" :size="32" />
            <span>
                <strong>{{ article.author?.name || 'Redaksi Expertia' }}</strong>
                <template v-if="authorRole(article.author)"> · {{ authorRole(article.author) }}</template>
            </span>
        </div>
        <p v-else class="byline">
            <strong>{{ article.author?.name || 'Redaksi Expertia' }}</strong> · {{ formatDate(article.published_at) }}
        </p>
    </article>
</template>
