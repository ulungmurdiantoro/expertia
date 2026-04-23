<script setup>
import PublicFooter from '@/Components/PublicFooter.vue';
import PublicHeader from '@/Components/PublicHeader.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    article: Object,
    comments: Array,
    viewer: Object,
});

const commentForm = useForm({
    content: '',
    parent_id: null,
});

const replyForm = useForm({
    content: '',
    parent_id: null,
});

const reportArticleForm = useForm({
    reason: 'Konten tidak pantas',
    note: '',
});

const reportCommentForm = useForm({
    reason: 'Komentar tidak pantas',
    note: '',
});

const reactionForm = useForm({
    type: 'like',
});

const activeReplyId = ref(null);

const escapeHtml = (value) => {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
};

const renderedArticleContent = computed(() => {
    const rawContent = props.article?.content || props.article?.excerpt || '';

    // If content already contains HTML tags from rich text editor, render as-is.
    if (/<[a-z][\s\S]*>/i.test(rawContent)) {
        return rawContent;
    }

    // Legacy plain text content fallback.
    return escapeHtml(rawContent).replace(/\n/g, '<br>');
});

const formatDate = (value) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const submitComment = () => {
    commentForm.post(route('articles.comments.store', props.article.slug), {
        preserveScroll: true,
        onSuccess: () => {
            commentForm.reset('content', 'parent_id');
        },
    });
};

const openReply = (commentId) => {
    activeReplyId.value = commentId;
    replyForm.parent_id = commentId;
    replyForm.content = '';
};

const submitReply = () => {
    replyForm.post(route('articles.comments.store', props.article.slug), {
        preserveScroll: true,
        onSuccess: () => {
            replyForm.reset('content', 'parent_id');
            activeReplyId.value = null;
        },
    });
};

const deleteComment = (commentId) => {
    commentForm.delete(route('articles.comments.destroy', [props.article.slug, commentId]), {
        preserveScroll: true,
    });
};

const reportArticle = () => {
    reportArticleForm.post(route('articles.reports.store', props.article.slug), {
        preserveScroll: true,
    });
};

const reportComment = (commentId) => {
    reportCommentForm.post(route('articles.comments.reports.store', [props.article.slug, commentId]), {
        preserveScroll: true,
    });
};

const react = (type) => {
    reactionForm.type = type;
    reactionForm.post(route('articles.reactions.store', props.article.slug), {
        preserveScroll: true,
    });
};

const clearReaction = () => {
    reactionForm.delete(route('articles.reactions.destroy', props.article.slug), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="article.title" />

    <div class="min-h-screen bg-[#f8fafc] text-zinc-900">
        <PublicHeader />
        <div class="mx-auto max-w-screen-2xl px-4 py-8 sm:px-6 lg:px-8">
            <Link :href="route('articles.index')" class="text-sm font-medium text-zinc-500 hover:text-[#1BD6FF]">&lt;- Kembali ke artikel</Link>

            <div class="mt-4 grid gap-6 lg:grid-cols-12">
                <article class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8 lg:col-span-8">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="rounded-full bg-[#e8faff] px-2.5 py-1 font-medium text-[#0f5f74]">{{ article.category?.name ?? 'Umum' }}</span>
                        <span class="rounded-full bg-[#ffe9f9] px-2.5 py-1 font-medium text-[#E80EB5]" v-for="tag in article.tags || []" :key="tag.id">#{{ tag.name }}</span>
                    </div>

                    <h1 class="mt-4 font-serif text-3xl font-bold leading-tight text-zinc-900 sm:text-4xl">{{ article.title }}</h1>

                    <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-zinc-500">
                        <span>{{ formatDate(article.published_at) }}</span>
                        <span>|</span>
                        <span>{{ article.view_count || 0 }} views</span>
                        <span>|</span>
                        <span>{{ article.comment_count || 0 }} komentar</span>
                    </div>

                    <img
                        v-if="article.thumbnail_url"
                        :src="article.thumbnail_url"
                        :alt="article.thumbnail_alt || article.title"
                        class="mt-6 h-auto w-full rounded-xl object-cover"
                    />

                    <div class="article-content prose prose-zinc mt-8 max-w-none leading-relaxed text-zinc-700" v-html="renderedArticleContent"></div>
                </article>

                <aside class="space-y-4 lg:col-span-4">
                    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">Penulis</p>
                        <div class="mt-2">
                            <Link
                                v-if="article.author?.profile_slug"
                                :href="route('authors.show', article.author.profile_slug)"
                                class="font-serif text-2xl font-semibold text-zinc-900 hover:text-[#E80EB5]"
                            >
                                {{ article.author.name }}
                            </Link>
                            <p v-else class="font-serif text-2xl font-semibold text-zinc-900">{{ article.author?.name || 'Redaksi' }}</p>
                        </div>
                        <p class="mt-3 text-sm text-zinc-600">
                            Baca profil penulis untuk melihat artikel lainnya dan ikuti pembaruan terbaru.
                        </p>
                    </section>

                    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">Ringkasan</p>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-600">
                            {{ article.excerpt || 'Ringkasan belum tersedia untuk artikel ini.' }}
                        </p>
                    </section>

                    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">Aksi</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <template v-if="viewer.is_authenticated">
                                <button
                                    @click="react('like')"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:border-[#1BD6FF]"
                                >
                                    <i class="fa-solid fa-thumbs-up text-[11px]"></i>
                                    Like ({{ article.reactions?.like || 0 }})
                                </button>
                                <button
                                    @click="react('love')"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:border-[#E80EB5]"
                                >
                                    <i class="fa-solid fa-heart text-[11px]"></i>
                                    Love ({{ article.reactions?.love || 0 }})
                                </button>
                                <button
                                    @click="react('insightful')"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:border-[#1BD6FF]"
                                >
                                    <i class="fa-solid fa-lightbulb text-[11px]"></i>
                                    Insightful ({{ article.reactions?.insightful || 0 }})
                                </button>
                                <button
                                    @click="react('celebrate')"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:border-[#FF7950]"
                                >
                                    <i class="fa-solid fa-champagne-glasses text-[11px]"></i>
                                    Celebrate ({{ article.reactions?.celebrate || 0 }})
                                </button>
                                <button
                                    v-if="viewer.reaction"
                                    @click="clearReaction"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50"
                                >
                                    <i class="fa-solid fa-trash text-[11px]"></i>
                                    Hapus Reaksi
                                </button>
                            </template>
                            <button
                                v-if="viewer.is_authenticated && article.can_report"
                                @click="reportArticle"
                                class="inline-flex items-center gap-1.5 rounded-md border border-[#FF7950] px-3 py-1.5 text-xs font-medium text-[#c9532f] hover:bg-[#fff1ec]"
                            >
                                <i class="fa-solid fa-flag text-[11px]"></i>
                                Laporkan Artikel
                            </button>
                            <Link
                                :href="route('articles.index')"
                                class="inline-flex items-center gap-1.5 rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:border-[#1BD6FF] hover:text-[#0f5f74]"
                            >
                                <i class="fa-solid fa-newspaper text-[11px]"></i>
                                Artikel Lainnya
                            </Link>
                        </div>
                    </section>
                </aside>
            </div>

            <section class="mt-10 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="font-serif text-2xl font-semibold text-zinc-900">Komentar</h2>

                <form v-if="viewer.is_authenticated" @submit.prevent="submitComment" class="mt-4 space-y-3">
                    <textarea
                        v-model="commentForm.content"
                        rows="3"
                        placeholder="Tulis komentar..."
                        class="w-full rounded-lg border-zinc-300 text-sm focus:border-[#1BD6FF] focus:ring-[#1BD6FF]"
                    />
                    <button
                        type="submit"
                        :disabled="commentForm.processing"
                        class="rounded-md bg-[#1BD6FF] px-4 py-2 text-sm font-medium text-white hover:bg-[#15bfdc] disabled:opacity-50"
                    >
                        Kirim Komentar
                    </button>
                </form>
                <p v-else class="mt-4 text-sm text-zinc-600">Login untuk menulis komentar.</p>

                <div class="mt-6 space-y-4">
                    <article
                        v-for="comment in comments"
                        :key="comment.id"
                        class="rounded-xl border border-zinc-200 bg-zinc-50 p-4"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-zinc-900">{{ comment.author?.name }}</p>
                                <p class="mt-1 text-xs text-zinc-500">{{ formatDate(comment.created_at) }}</p>
                                <p class="mt-2 text-sm leading-relaxed text-zinc-700">{{ comment.content }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <button
                                    v-if="comment.can_reply"
                                    @click="openReply(comment.id)"
                                    class="text-xs font-medium text-[#0f5f74] hover:text-[#1BD6FF]"
                                >
                                    Balas
                                </button>
                                <button
                                    v-if="comment.can_report"
                                    @click="reportComment(comment.id)"
                                    class="text-xs font-medium text-[#c9532f] hover:text-[#FF7950]"
                                >
                                    Laporkan
                                </button>
                                <button
                                    v-if="comment.can_delete"
                                    @click="deleteComment(comment.id)"
                                    class="text-xs font-medium text-rose-600"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>

                        <form
                            v-if="activeReplyId === comment.id"
                            @submit.prevent="submitReply"
                            class="mt-3 space-y-2"
                        >
                            <textarea
                                v-model="replyForm.content"
                                rows="2"
                                placeholder="Tulis balasan..."
                                class="w-full rounded-lg border-zinc-300 text-sm focus:border-[#E80EB5] focus:ring-[#E80EB5]"
                            />
                            <button
                                type="submit"
                                :disabled="replyForm.processing"
                                class="rounded-md bg-[#E80EB5] px-3 py-1.5 text-xs font-medium text-white hover:bg-[#ca0a9d]"
                            >
                                Kirim Balasan
                            </button>
                        </form>

                        <div v-if="comment.replies?.length" class="mt-3 space-y-2 border-l border-zinc-200 pl-4">
                            <div v-for="reply in comment.replies" :key="reply.id" class="rounded-lg bg-white p-3 ring-1 ring-zinc-200">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold text-zinc-800">{{ reply.author?.name }}</p>
                                        <p class="mt-1 text-xs text-zinc-500">{{ formatDate(reply.created_at) }}</p>
                                        <p class="mt-1 text-sm text-zinc-700">{{ reply.content }}</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <button
                                            v-if="reply.can_report"
                                            @click="reportComment(reply.id)"
                                            class="text-xs font-medium text-[#c9532f] hover:text-[#FF7950]"
                                        >
                                            Laporkan
                                        </button>
                                        <button
                                            v-if="reply.can_delete"
                                            @click="deleteComment(reply.id)"
                                            class="text-xs font-medium text-rose-600"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                    <p v-if="comments.length === 0" class="text-sm text-zinc-500">Belum ada komentar.</p>
                </div>
            </section>
        </div>

        <PublicFooter />
    </div>
</template>

<style scoped>
:deep(.article-content ul) {
    list-style-type: disc;
    padding-left: 1.3rem;
}

:deep(.article-content ol) {
    list-style-type: decimal;
    padding-left: 1.3rem;
}

:deep(.article-content li::marker) {
    color: #3f3f46;
}

:deep(.article-content strong) {
    font-weight: 700;
    color: #18181b;
}

:deep(.article-content em) {
    font-style: italic;
}

:deep(.article-content p) {
    margin: 0.75rem 0;
}

:deep(.article-content h2) {
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
    font-size: 1.75rem;
    line-height: 1.25;
    font-weight: 700;
    color: #18181b;
}

:deep(.article-content h3) {
    margin-top: 1.25rem;
    margin-bottom: 0.6rem;
    font-size: 1.35rem;
    line-height: 1.3;
    font-weight: 700;
    color: #27272a;
}
</style>
