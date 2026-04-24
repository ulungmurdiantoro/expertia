<script setup>
import PublicFooter from '@/Components/PublicFooter.vue';
import PublicHeader from '@/Components/PublicHeader.vue';

defineProps({
    variant: {
        type: String,
        default: 'card',
    },
});
</script>

<template>
    <div class="min-h-screen bg-[#f8fafc] text-zinc-900">
        <PublicHeader />

        <main class="mx-auto w-full max-w-screen-2xl px-4 py-10 sm:px-6 lg:px-8">
            <div
                v-if="variant === 'card'"
                class="mx-auto w-full overflow-hidden bg-white px-6 py-6 shadow-md sm:max-w-md sm:rounded-lg"
            >
                <slot />
            </div>
            <div v-else class="mx-auto w-full">
                <slot />
            </div>
        </main>

        <PublicFooter />
    </div>
</template>
