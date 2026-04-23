<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    categories: Array,
});

const form = useForm({
    title: '',
    excerpt: '',
    content: '',
    writing_source: '',
    category_id: '',
    thumbnail: null,
    thumbnail_alt: '',
    thumbnail_source: '',
    meta_title: '',
    meta_description: '',
    scheduled_at: '',
    scheduled_timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
});

const contentWordCount = computed(() => {
    const plainText = (form.content || '').replace(/<[^>]*>/g, ' ');

    return plainText
        .trim()
        .split(/\s+/)
        .filter(Boolean).length;
});

const onThumbnailChange = (event) => {
    form.thumbnail = event.target.files[0] ?? null;
};

const submit = () => {
    form.post(route('author.articles.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Buat Artikel" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Draft Baru</h2>
                <button
                    @click="submit"
                    type="button"
                    :disabled="form.processing"
                    class="rounded-full bg-gray-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
                >
                    Simpan Draft
                </button>
            </div>
        </template>

        <div class="bg-[#f7f7f5] py-8">
            <form @submit.prevent="submit" class="mx-auto grid max-w-screen-2xl gap-6 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
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
                                <option v-for="category in props.categories" :key="category.id" :value="category.id">{{ category.name }}</option>
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
                            <input
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
                        <p class="mt-1">Status: <span class="font-semibold text-zinc-900">Draft</span></p>
                    </div>
                </aside>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
