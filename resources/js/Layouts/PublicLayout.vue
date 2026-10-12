<script setup>
import PublicFooter from '@/Components/PublicFooter.vue';
import PublicHeader from '@/Components/PublicHeader.vue';

defineProps({
    // false: tanpa header/footer (mis. preview editorial).
    chrome: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <div class="xp">
        <PublicHeader v-if="chrome" />
        <main>
            <slot />
        </main>
        <PublicFooter v-if="chrome" />
    </div>
</template>
