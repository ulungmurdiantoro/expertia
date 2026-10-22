<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    article: Object,
    categories: Array,
});

// Server mengirim jadwal sebagai ISO 8601; input datetime-local butuh waktu lokal browser.
const toLocalDateTimeInput = (iso) => {
    if (!iso) return '';

    const date = new Date(iso);
    const pad = (value) => String(value).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

const form = useForm({
    title: props.article.title ?? '',
    excerpt: props.article.excerpt ?? '',
    content: props.article.content ?? '',
    writing_source: props.article.writing_source ?? '',
    category_id: props.article.category_id ?? '',
    thumbnail: null,
    thumbnail_alt: props.article.thumbnail_alt ?? '',
    thumbnail_source: props.article.thumbnail_source ?? '',
    meta_title: props.article.meta_title ?? '',
    meta_description: props.article.meta_description ?? '',
    scheduled_at: toLocalDateTimeInput(props.article.scheduled_at),
    scheduled_timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
});

const contentWordCount = computed(() => {
    const plainText = (form.content || '').replace(/<[^>]*>/g, ' ');

    return plainText
        .trim()
        .split(/\s+/)
        .filter(Boolean).length;
});

// Dari ArticlePolicy: author tidak bisa mengubah naskah yang sedang direview atau sudah tayang.
const canUpdate = computed(() => props.article.can_update !== false);
const canSubmit = computed(() => props.article.can_submit === true);

const thumbnailInput = ref(null);

const onThumbnailChange = (event) => {
    form.thumbnail = event.target.files[0] ?? null;
};

// Setelah tersimpan: isi form menjadi titik awal baru (isDirty kembali false) dan
// thumbnail tidak ikut terunggah ulang pada simpan berikutnya.
const markSaved = () => {
    form.thumbnail = null;
    if (thumbnailInput.value) {
        thumbnailInput.value.value = '';
    }
    form.defaults();
};

const update = () => {
    form.transform((data) => ({
        ...data,
        _method: 'put',
    })).post(route('author.articles.update', props.article.id), {
        forceFormData: true,
        onSuccess: markSaved,
        onFinish: () => form.transform((data) => data),
    });
};

const sendToReview = () => {
    router.patch(route('author.articles.submit', props.article.id), {}, {
        preserveScroll: true,
    });
};

const submitForReview = () => {
    if (form.processing) return;

    if (form.isDirty || form.thumbnail) {
        form.transform((data) => ({
            ...data,
            _method: 'put',
        })).post(route('author.articles.update', props.article.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                markSaved();
                sendToReview();
            },
            onFinish: () => form.transform((data) => data),
        });
        return;
    }

    sendToReview();
};
</script>

<template>
    <Head :title="`Edit: ${article.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ canUpdate ? 'Edit Draft' : 'Lihat Artikel' }}</h2>
                <div class="flex items-center gap-2">
                    <button
                        v-if="canUpdate"
                        @click="update"
                        type="button"
                        :disabled="form.processing"
                        class="rounded-full bg-gray-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
                    >
                        Simpan
                    </button>
                    <button
                        v-if="canSubmit"
                        @click="submitForReview"
                        type="button"
                        :disabled="form.processing"
                        class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
                    >
                        Submit Review
                    </button>
                </div>
            </div>
        </template>

        <div v-if="!canUpdate" class="mx-auto mt-6 max-w-screen-2xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Artikel ini sedang direview atau sudah tayang, jadi tidak bisa diubah dari sini.
                Untuk koreksi, hubungi redaksi agar perubahan ikut melalui pemeriksaan editor.
            </div>
        </div>

        <div class="bg-[#f7f7f5] py-8">
            <form @submit.prevent="update" class="mx-auto grid max-w-screen-2xl gap-6 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
                <fieldset :disabled="!canUpdate" class="contents">
                <section class="lg:col-span-2">
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
                        <input
                            v-model="form.title"
                            type="text"
                            placeholder="Judul artikel"
                            class="w-full border-0 px-0 text-4xl font-bold tracking-tight text-zinc-900 placeholder:text-zinc-300 focus:ring-0 sm:text-5xl"
                        />
                        <textarea
                            v-model="form.excerpt"
                            rows="3"
                            placeholder="Tulis dek singkat artikel..."
                            class="mt-4 w-full resize-none border-0 px-0 text-lg text-zinc-600 placeholder:text-zinc-300 focus:ring-0"
                        />
                        <textarea
                            v-model="form.writing_source"
                            rows="2"
                            placeholder="Sumber penulisan (opsional): referensi, jurnal, tautan, atau catatan riset"
                            class="mt-4 w-full resize-none rounded-lg border-zinc-300 text-sm text-zinc-700 placeholder:text-zinc-400"
                        />
                        <div class="mt-8">
                            <RichTextEditor
                                v-model="form.content"
                                :editable="canUpdate"
                                placeholder="Mulai menulis ceritamu di sini..."
                            />
                        </div>
                    </div>
                </section>

                <aside class="space-y-4">
                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.1em] text-zinc-500">Pengaturan</h3>
                        <div class="mt-4 space-y-3">
                            <select v-model="form.category_id" class="w-full rounded-lg border-zinc-300 text-sm">
                                <option value="">Pilih kategori</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                            </select>
                            <input v-model="form.meta_title" type="text" placeholder="Meta title (opsional)" class="w-full rounded-lg border-zinc-300 text-sm" />
                            <textarea v-model="form.meta_description" rows="3" placeholder="Meta description (opsional)" class="w-full rounded-lg border-zinc-300 text-sm" />
                            <input v-model="form.scheduled_at" type="datetime-local" class="w-full rounded-lg border-zinc-300 text-sm" />
                            <p class="text-xs text-zinc-500">Timezone jadwal: {{ form.scheduled_timezone }}</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.1em] text-zinc-500">Thumbnail</h3>
                        <div class="mt-4 space-y-3">
                            <img
                                v-if="article.thumbnail_url"
                                :src="article.thumbnail_url"
                                :alt="article.thumbnail_alt || article.title"
                                class="h-28 w-full rounded-lg border border-zinc-200 object-cover"
                            />
                            <input
                                ref="thumbnailInput"
                                type="file"
                                accept="image/*"
                                @input="onThumbnailChange"
                                class="w-full rounded-lg border-zinc-300 text-sm"
                            />
                            <input
                                v-model="form.thumbnail_alt"
                                type="text"
                                placeholder="Alt text thumbnail"
                                class="w-full rounded-lg border-zinc-300 text-sm"
                            />
                            <input
                                v-model="form.thumbnail_source"
                                type="text"
                                placeholder="Sumber thumbnail (opsional): URL/kredit foto"
                                class="w-full rounded-lg border-zinc-300 text-sm"
                            />
                            <p class="text-xs text-zinc-500">Batas upload dokumen/file apapun: maksimal 2MB per file.</p>
                            <p class="text-xs text-zinc-500">Untuk thumbnail, format harus gambar. Rekomendasi rasio 16:9.</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 text-sm text-zinc-600 shadow-sm">
                        <p>Kata: <span class="font-semibold text-zinc-900">{{ contentWordCount }}</span></p>
                        <p class="mt-1">Status: <span class="font-semibold text-zinc-900">{{ article.status }}</span></p>
                        <p class="mt-1">Editorial: <span class="font-semibold text-zinc-900">{{ article.editorial_status }}</span></p>
                    </div>
                </aside>
                </fieldset>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
