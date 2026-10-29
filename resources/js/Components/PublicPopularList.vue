<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    articles: {
        type: Array,
        default: () => [],
    },
    // grid: melebar di dalam daftar · stack: satu kolom di sidebar
    layout: {
        type: String,
        default: 'grid',
    },
});
</script>

<template>
    <section v-if="articles.length" class="popular" :class="`is-${layout}`" aria-labelledby="popular-h">
        <p id="popular-h" class="eyebrow">Paling banyak dibaca</p>
        <ol>
            <li v-for="(article, index) in articles" :key="article.id">
                <span class="n" aria-hidden="true">{{ index + 1 }}</span>
                <Link :href="route('articles.show', article.slug)">{{ article.title }}</Link>
            </li>
        </ol>
    </section>
</template>
