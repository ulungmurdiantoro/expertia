<script setup>
import PublicArticleRow from '@/Components/PublicArticleRow.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    author: Object,
    articles: Object,
    viewer: Object,
    meta: {
        type: Object,
        default: () => ({}),
    },
});

const followForm = useForm({});

const affiliation = computed(() => [props.author.position, props.author.institution].filter(Boolean).join(', '));

const follow = () => {
    followForm.post(route('authors.follow', props.author.profile_slug), { preserveScroll: true });
};

const unfollow = () => {
    followForm.delete(route('authors.unfollow', props.author.profile_slug), { preserveScroll: true });
};

const hideImage = (event) => {
    event.target.style.display = 'none';
};
</script>

<template>
    <Head :title="meta.title || author.name" />

    <PublicLayout>
        <div class="page-head">
            <div class="wrap">
                <div class="profile-head">
                    <div v-if="author.avatar_url" class="avatar">
                        <img :src="author.avatar_url" :alt="`Foto ${author.name}`" @error="hideImage" />
                    </div>
                    <div class="who">
                        <p class="eyebrow">Profil Kontributor</p>
                        <h1>{{ author.name }}</h1>
                        <p v-if="affiliation" class="affiliation">{{ affiliation }}</p>
                        <p v-if="author.expertise_areas?.length" class="expertise">{{ author.expertise_areas.join(' · ') }}</p>
                        <p v-if="author.bio" class="sub">{{ author.bio }}</p>
                        <div v-if="viewer.is_authenticated && !viewer.is_own_profile" class="actions" style="margin-top: 28px">
                            <button
                                v-if="!viewer.is_following"
                                type="button"
                                class="btn btn-navy"
                                :disabled="followForm.processing"
                                @click="follow"
                            >
                                Ikuti
                            </button>
                            <button
                                v-else
                                type="button"
                                class="btn btn-line"
                                :disabled="followForm.processing"
                                @click="unfollow"
                            >
                                Berhenti mengikuti
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="wrap">
            <div class="section-head" style="padding-bottom: 8px; border-bottom: 1px solid var(--rule)">
                <h2 class="h2">Karya di Expertia</h2>
                <Link class="link-arrow" :href="route('articles.index')">Semua artikel →</Link>
            </div>

            <ul class="article-list">
                <PublicArticleRow
                    v-for="article in articles.data"
                    :key="article.id"
                    :article="article"
                    :show-author="false"
                />
            </ul>

            <p v-if="articles.data.length === 0" class="empty">Belum ada karya yang terbit dari kontributor ini.</p>

            <nav v-if="articles.last_page > 1" class="pager" aria-label="Halaman">
                <Link :href="articles.prev_page_url || '#'" :class="{ off: !articles.prev_page_url }">← Sebelumnya</Link>
                <span class="muted">Halaman {{ articles.current_page }} dari {{ articles.last_page }}</span>
                <Link :href="articles.next_page_url || '#'" :class="{ off: !articles.next_page_url }">Berikutnya →</Link>
            </nav>
            <div v-else style="height: 72px"></div>
        </div>
    </PublicLayout>
</template>
