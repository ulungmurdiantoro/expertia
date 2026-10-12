<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { contact, formatDate, readingMinutes } from '@/site';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    article: Object,
    comments: Array,
    viewer: Object,
    meta: {
        type: Object,
        default: () => ({}),
    },
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

const approvalForm = useForm({});
const reviewForm = useForm({
    note: '',
});

const reactionTypes = [
    { type: 'like', label: 'Suka', icon: 'fa-solid fa-thumbs-up' },
    { type: 'love', label: 'Menyentuh', icon: 'fa-solid fa-heart' },
    { type: 'insightful', label: 'Mencerahkan', icon: 'fa-solid fa-lightbulb' },
    { type: 'celebrate', label: 'Apresiasi', icon: 'fa-solid fa-hands-clapping' },
];

const activeReplyId = ref(null);
const isSharing = ref(false);
const openReviewAction = ref(null);
// Pesan hasil aksi (flash dari server), ditampilkan dekat tombol yang ditekan.
const toolsNotice = ref('');
const commentNotice = ref('');

const flashFrom = (page, fallback) => page?.props?.flash?.success || fallback;
const errorFrom = (errors) => errors?.throttle || Object.values(errors || {})[0] || 'Aksi gagal. Coba lagi.';

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

// Sumber tulisan berupa teks biasa; tautan di dalamnya dijadikan link tanpa v-html.
const sourceLines = computed(() =>
    String(props.article?.writing_source || '')
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter(Boolean)
        .map((line) =>
            line
                .split(/(https?:\/\/\S+)/g)
                .filter(Boolean)
                .map((text) => ({ text, url: /^https?:\/\//.test(text) ? text : null })),
        ),
);

const formatDateTime = (value) => {
    if (!value) return '';

    const date = new Date(String(value).replace(' ', 'T'));

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};

const hideImage = (event) => {
    event.target.style.display = 'none';
};

const submitComment = () => {
    commentForm.post(route('articles.comments.store', props.article.slug), {
        preserveScroll: true,
        onSuccess: (page) => {
            commentForm.reset('content', 'parent_id');
            commentNotice.value = flashFrom(page, 'Komentar terkirim.');
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
        onSuccess: (page) => {
            replyForm.reset('content', 'parent_id');
            activeReplyId.value = null;
            commentNotice.value = flashFrom(page, 'Balasan terkirim.');
        },
    });
};

const deleteComment = (commentId) => {
    if (!window.confirm('Hapus komentar ini?')) {
        return;
    }

    commentForm.delete(route('articles.comments.destroy', [props.article.slug, commentId]), {
        preserveScroll: true,
        onSuccess: (page) => {
            commentNotice.value = flashFrom(page, 'Komentar dihapus.');
        },
    });
};

const reportArticle = () => {
    if (!window.confirm('Laporkan artikel ini ke redaksi?')) {
        return;
    }

    reportArticleForm.post(route('articles.reports.store', props.article.slug), {
        preserveScroll: true,
        onSuccess: (page) => {
            toolsNotice.value = flashFrom(page, 'Laporan terkirim.');
        },
        onError: (errors) => {
            toolsNotice.value = errorFrom(errors);
        },
    });
};

const reportComment = (commentId) => {
    if (!window.confirm('Laporkan komentar ini ke redaksi?')) {
        return;
    }

    reportCommentForm.post(route('articles.comments.reports.store', [props.article.slug, commentId]), {
        preserveScroll: true,
        onSuccess: (page) => {
            commentNotice.value = flashFrom(page, 'Laporan terkirim.');
        },
        onError: (errors) => {
            commentNotice.value = errorFrom(errors);
        },
    });
};

const react = (type) => {
    reactionForm.type = type;
    reactionForm.post(route('articles.reactions.store', props.article.slug), {
        preserveScroll: true,
        onSuccess: (page) => {
            toolsNotice.value = flashFrom(page, 'Reaksi disimpan.');
        },
        onError: (errors) => {
            toolsNotice.value = errorFrom(errors);
        },
    });
};

const clearReaction = () => {
    reactionForm.delete(route('articles.reactions.destroy', props.article.slug), {
        preserveScroll: true,
        onSuccess: (page) => {
            toolsNotice.value = flashFrom(page, 'Reaksi dihapus.');
        },
    });
};

const toggleReaction = (type) => {
    if (props.viewer?.reaction === type) {
        clearReaction();
        return;
    }

    react(type);
};

// Endpoint share mengembalikan JSON (bukan halaman Inertia) dan terbuka untuk tamu,
// jadi dipanggil lewat axios. Kegagalan mencatat tidak perlu mengganggu pembaca.
const recordShare = (channel = 'web') => {
    window.axios.post(route('articles.shares.store', props.article.slug), { channel }).catch(() => {});
};

const shareArticle = async () => {
    if (props.article.is_preview || isSharing.value) {
        return;
    }

    isSharing.value = true;

    const shareUrl = window.location.href;
    const sharePayload = {
        title: props.article.title,
        text: props.article.excerpt || props.article.title,
        url: shareUrl,
    };

    try {
        if (navigator.share) {
            await navigator.share(sharePayload);
            recordShare('native');
            toolsNotice.value = 'Artikel dibagikan.';
            return;
        }

        await navigator.clipboard.writeText(shareUrl);
        recordShare('copy_link');
        toolsNotice.value = 'Tautan artikel disalin.';
    } catch (error) {
        if (error?.name === 'AbortError') {
            return;
        }

        recordShare('attempt');
        toolsNotice.value = 'Tautan siap dibagikan.';
    } finally {
        isSharing.value = false;
    }
};

const approveArticle = () => {
    approvalForm.patch(route('editor.reviews.approve', props.article.id), {
        preserveScroll: true,
    });
};

const rejectArticle = () => {
    reviewForm.patch(route('editor.reviews.reject', props.article.id), {
        preserveScroll: true,
        onSuccess: () => {
            reviewForm.reset();
            openReviewAction.value = null;
        },
    });
};

const requestRevision = () => {
    reviewForm.patch(route('editor.reviews.request-revision', props.article.id), {
        preserveScroll: true,
        onSuccess: () => {
            reviewForm.reset();
            openReviewAction.value = null;
        },
    });
};
</script>

<template>
    <Head :title="meta.title || article.title" />

    <PublicLayout :chrome="!article.is_preview">
        <div v-if="article.is_preview" class="preview-bar">
            <div class="wrap">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Preview editorial</p>
                        <p class="mt-1 text-sm text-gray-600">Artikel ini belum dipublikasikan. Interaksi publik dinonaktifkan pada mode preview.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Link :href="route('editor.reviews.index')" class="rounded border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Kembali
                        </Link>
                        <button
                            @click="approveArticle"
                            :disabled="approvalForm.processing"
                            class="rounded bg-emerald-600 px-3 py-2 text-sm font-medium text-white disabled:opacity-50"
                        >
                            Approve
                        </button>
                        <button
                            @click="openReviewAction = openReviewAction === 'reject' ? null : 'reject'"
                            class="rounded bg-rose-600 px-3 py-2 text-sm font-medium text-white"
                        >
                            Reject
                        </button>
                        <button
                            @click="openReviewAction = openReviewAction === 'revision' ? null : 'revision'"
                            class="rounded bg-amber-500 px-3 py-2 text-sm font-medium text-white"
                        >
                            Request Revision
                        </button>
                    </div>
                </div>

                <div v-if="openReviewAction" class="mt-4 rounded-md bg-gray-50 p-4">
                    <label class="text-sm font-medium text-gray-700">
                        Catatan {{ openReviewAction === 'reject' ? 'penolakan' : 'revisi' }}
                    </label>
                    <textarea
                        v-model="reviewForm.note"
                        rows="3"
                        class="mt-2 w-full rounded-md border-gray-300 text-sm"
                        placeholder="Tulis catatan untuk author"
                    ></textarea>
                    <p v-if="reviewForm.errors.note" class="mt-1 text-sm text-rose-600">{{ reviewForm.errors.note }}</p>
                    <div class="mt-3 flex gap-2">
                        <button
                            v-if="openReviewAction === 'reject'"
                            @click="rejectArticle"
                            :disabled="reviewForm.processing"
                            class="rounded bg-rose-600 px-3 py-2 text-sm font-medium text-white disabled:opacity-50"
                        >
                            Kirim Reject
                        </button>
                        <button
                            v-else
                            @click="requestRevision"
                            :disabled="reviewForm.processing"
                            class="rounded bg-amber-500 px-3 py-2 text-sm font-medium text-white disabled:opacity-50"
                        >
                            Kirim Revisi
                        </button>
                        <button @click="openReviewAction = null" class="rounded bg-gray-700 px-3 py-2 text-sm font-medium text-white">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <article>
            <header class="article-head">
                <div class="wrap narrow">
                    <p class="eyebrow">
                        <template v-if="article.category">
                            <Link
                                v-if="!article.is_preview"
                                :href="route('articles.index', { category: article.category.slug })"
                                style="color: inherit"
                            >
                                {{ article.category.name }}
                            </Link>
                            <span v-else>{{ article.category.name }}</span>
                        </template>
                        <template v-if="article.category && article.tags?.length"> · </template>
                        <template v-if="article.tags?.length">{{ article.tags[0].name }}</template>
                    </p>
                    <h1>{{ article.title }}</h1>
                    <p v-if="article.excerpt" class="dek">{{ article.excerpt }}</p>
                    <div class="article-by">
                        <span>
                            Oleh
                            <Link
                                v-if="article.author?.profile_slug && !article.is_preview"
                                :href="route('authors.show', article.author.profile_slug)"
                            >
                                <strong>{{ article.author.name }}</strong>
                            </Link>
                            <strong v-else>{{ article.author?.name || 'Redaksi Expertia' }}</strong>
                        </span>
                        <span v-if="article.is_preview">{{ article.status }} / {{ article.editorial_status }}</span>
                        <span v-else>{{ formatDate(article.published_at) }}</span>
                        <span>Bacaan {{ readingMinutes(article.content) }} menit</span>
                    </div>
                </div>
            </header>

            <figure v-if="article.thumbnail_url" class="article-cover wrap" style="max-width: 1000px">
                <div class="cover">
                    <img :src="article.thumbnail_url" :alt="article.thumbnail_alt || ''" @error="hideImage" />
                </div>
                <figcaption v-if="article.thumbnail_source">{{ article.thumbnail_source }}</figcaption>
            </figure>

            <div class="article-body wrap narrow">
                <div class="prose">
                    <div class="article-content" v-html="renderedArticleContent"></div>

                    <aside v-if="sourceLines.length" class="source-box">
                        <h2>Sumber tulisan</h2>
                        <p v-for="(line, index) in sourceLines" :key="index">
                            <template v-for="(part, partIndex) in line" :key="partIndex">
                                <a v-if="part.url" :href="part.url" target="_blank" rel="noopener nofollow">{{ part.text }}</a>
                                <template v-else>{{ part.text }}</template>
                            </template>
                        </p>
                    </aside>

                    <div v-if="!article.is_preview" class="article-tools">
                        <button type="button" class="chip" :disabled="isSharing" @click="shareArticle">
                            <i class="fa-solid fa-share-nodes" aria-hidden="true"></i>
                            Bagikan
                        </button>
                        <template v-if="viewer.is_authenticated">
                            <button
                                v-for="reaction in reactionTypes"
                                :key="reaction.type"
                                type="button"
                                class="chip"
                                :class="{ 'is-active': viewer.reaction === reaction.type }"
                                :aria-pressed="viewer.reaction === reaction.type"
                                :disabled="reactionForm.processing"
                                @click="toggleReaction(reaction.type)"
                            >
                                <i :class="reaction.icon" aria-hidden="true"></i>
                                {{ reaction.label }}
                                <span v-if="article.reactions?.[reaction.type]">{{ article.reactions[reaction.type] }}</span>
                            </button>
                        </template>
                        <button
                            v-if="viewer.is_authenticated && article.can_report"
                            type="button"
                            class="text-btn"
                            style="margin-left: auto"
                            @click="reportArticle"
                        >
                            Laporkan artikel
                        </button>
                        <p v-if="toolsNotice" class="muted" role="status" style="flex-basis: 100%; margin: 4px 0 0">{{ toolsNotice }}</p>
                    </div>

                    <aside class="end-cta">
                        <p>
                            <strong>Anda juga meneliti atau berkarya di bidang ini?</strong>
                            Kirimkan materi Anda, dan tim editor kami membantu mengolahnya menjadi tulisan populer. Gratis untuk
                            kontributor perdana yang lolos kurasi.
                        </p>
                        <a class="btn btn-navy" :href="contact.registrationUrl" target="_blank" rel="noopener">Daftar sebagai kontributor</a>
                    </aside>
                </div>
            </div>
        </article>

        <section v-if="!article.is_preview" class="comments" aria-labelledby="komentar-h">
            <div class="wrap narrow">
                <hr class="rule" style="margin-bottom: 40px" />
                <h2 id="komentar-h" class="h2">Komentar</h2>

                <form v-if="viewer.is_authenticated" class="comment-form" @submit.prevent="submitComment">
                    <label class="sr-only" for="comment-content">Tulis komentar</label>
                    <textarea
                        id="comment-content"
                        v-model="commentForm.content"
                        class="field"
                        rows="4"
                        maxlength="2000"
                        placeholder="Tulis tanggapan Anda…"
                    ></textarea>
                    <p v-if="commentForm.errors.content" class="field-error">{{ commentForm.errors.content }}</p>
                    <button type="submit" class="btn btn-navy" :disabled="commentForm.processing || !commentForm.content.trim()">
                        Kirim komentar
                    </button>
                </form>
                <p v-else class="muted" style="margin: 0 0 28px">
                    <Link :href="route('login')">Masuk</Link> untuk ikut berdiskusi.
                </p>

                <p v-if="commentNotice" class="muted" role="status" style="margin: -12px 0 24px">{{ commentNotice }}</p>

                <article v-for="comment in comments" :key="comment.id" class="comment">
                    <div class="comment-head">
                        <strong>{{ comment.author?.name }}</strong>
                        <time :datetime="comment.created_at">{{ formatDateTime(comment.created_at) }}</time>
                    </div>
                    <p class="comment-body">{{ comment.content }}</p>
                    <div v-if="comment.can_reply || comment.can_report || comment.can_delete" class="comment-actions">
                        <button v-if="comment.can_reply" type="button" class="text-btn" @click="openReply(comment.id)">Balas</button>
                        <button v-if="comment.can_report" type="button" class="text-btn" @click="reportComment(comment.id)">Laporkan</button>
                        <button v-if="comment.can_delete" type="button" class="text-btn danger" @click="deleteComment(comment.id)">Hapus</button>
                    </div>

                    <form
                        v-if="activeReplyId === comment.id"
                        class="comment-form"
                        style="margin: 14px 0 0"
                        @submit.prevent="submitReply"
                    >
                        <label class="sr-only" :for="`reply-${comment.id}`">Tulis balasan</label>
                        <textarea
                            :id="`reply-${comment.id}`"
                            v-model="replyForm.content"
                            class="field"
                            rows="3"
                            maxlength="2000"
                            placeholder="Tulis balasan…"
                        ></textarea>
                        <p v-if="replyForm.errors.content" class="field-error">{{ replyForm.errors.content }}</p>
                        <div style="display: flex; gap: 16px; align-items: center">
                            <button type="submit" class="btn btn-navy" :disabled="replyForm.processing || !replyForm.content.trim()">
                                Kirim balasan
                            </button>
                            <button type="button" class="text-btn" @click="activeReplyId = null">Batal</button>
                        </div>
                    </form>

                    <div v-if="comment.replies?.length" class="replies">
                        <article v-for="reply in comment.replies" :key="reply.id" class="comment">
                            <div class="comment-head">
                                <strong>{{ reply.author?.name }}</strong>
                                <time :datetime="reply.created_at">{{ formatDateTime(reply.created_at) }}</time>
                            </div>
                            <p class="comment-body">{{ reply.content }}</p>
                            <div v-if="reply.can_report || reply.can_delete" class="comment-actions">
                                <button v-if="reply.can_report" type="button" class="text-btn" @click="reportComment(reply.id)">Laporkan</button>
                                <button v-if="reply.can_delete" type="button" class="text-btn danger" @click="deleteComment(reply.id)">Hapus</button>
                            </div>
                        </article>
                    </div>
                </article>

                <p v-if="comments.length === 0" class="muted">Belum ada komentar.</p>
            </div>
        </section>
        <div v-else style="height: 56px"></div>
    </PublicLayout>
</template>
