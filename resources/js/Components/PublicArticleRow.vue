<script setup>
import { articleKicker, formatDate } from '@/site';
import { Link } from '@inertiajs/vue3';

defineProps({
    article: {
        type: Object,
        required: true,
    },
    // false di halaman profil penulis, karena nama penulis sudah jelas.
    showAuthor: {
        type: Boolean,
        default: true,
    },
});

const hideImage = (event) => {
    event.target.style.display = 'none';
};
</script>

<template>
    <li class="article-row">
        <div class="row-copy">
            <p v-if="articleKicker(article)" class="eyebrow" style="font-size: 12px">{{ articleKicker(article) }}</p>
            <h2>
                <Link :href="route('articles.show', article.slug)">{{ article.title }}</Link>
            </h2>
            <p v-if="article.excerpt" class="excerpt">{{ article.excerpt }}</p>
            <p class="byline">
                <template v-if="showAuthor">
                    <strong>{{ article.author?.name || 'Redaksi Expertia' }}</strong> ·
                </template>
                {{ formatDate(article.published_at) }}
            </p>
        </div>
        <Link
            v-if="article.thumbnail_url"
            class="row-cover"
            :href="route('articles.show', article.slug)"
            tabindex="-1"
            aria-hidden="true"
        >
            <div class="cover light">
                <img :src="article.thumbnail_url" alt="" loading="lazy" @error="hideImage" />
            </div>
        </Link>
    </li>
</template>
