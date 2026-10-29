<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    paginator: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <nav v-if="paginator.last_page > 1" class="pager" aria-label="Halaman">
        <Link :href="paginator.prev_page_url || '#'" :class="{ off: !paginator.prev_page_url }">
            ← Sebelumnya
        </Link>
        <span class="muted">Halaman {{ paginator.current_page }} dari {{ paginator.last_page }}</span>
        <Link :href="paginator.next_page_url || '#'" :class="{ off: !paginator.next_page_url }">
            Berikutnya →
        </Link>
    </nav>
    <div v-else style="height: 72px"></div>
</template>
