<script setup>
import { rubricTone } from '@/site';

defineProps({
    slug: {
        type: String,
        default: '',
    },
    size: {
        type: Number,
        default: 10,
    },
});
</script>

<template>
    <span class="rubric-mark" :style="{ width: `${size}px`, height: `${size}px`, background: rubricTone(slug) }" aria-hidden="true"></span>
</template>
