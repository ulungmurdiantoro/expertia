<script setup>
import { initials } from '@/site';
import { ref } from 'vue';

defineProps({
    author: {
        type: Object,
        default: null,
    },
    size: {
        type: Number,
        default: 40,
    },
});

const failed = ref(false);
</script>

<template>
    <span class="pa" :style="{ width: `${size}px`, height: `${size}px`, fontSize: `${Math.round(size * 0.34)}px` }" aria-hidden="true">
        <img v-if="author?.avatar_url && !failed" :src="author.avatar_url" alt="" loading="lazy" @error="failed = true" />
        <template v-else>{{ initials(author?.name || 'Redaksi Expertia') }}</template>
    </span>
</template>
