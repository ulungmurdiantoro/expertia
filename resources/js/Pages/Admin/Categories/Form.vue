<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    // null saat menambah kategori baru.
    category: {
        type: Object,
        default: null,
    },
});

const isEditing = computed(() => props.category !== null);
const slugLocked = computed(() => Boolean(props.category?.slug_lock_reason));

const form = useForm({
    name: props.category?.name ?? '',
    slug: props.category?.slug ?? '',
    description: props.category?.description ?? '',
    is_active: props.category?.is_active ?? true,
});

// Perkiraan slug yang akan dibuat server (Str::slug) bila kolom slug dikosongkan.
const suggestedSlug = computed(() =>
    form.name
        .normalize('NFKD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/&/g, ' ')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, ''),
);
const previewSlug = computed(() => form.slug || suggestedSlug.value || 'slug-kategori');

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.categories.update', props.category.id), { preserveScroll: true });
        return;
    }

    form.post(route('admin.categories.store'), { preserveScroll: true });
};
</script>

<template>
    <Head :title="isEditing ? `Edit Kategori: ${category.name}` : 'Tambah Kategori'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ isEditing ? 'Edit Kategori' : 'Tambah Kategori' }}</h2>
                <Link :href="route('admin.categories.index')" class="rounded bg-gray-700 px-3 py-2 text-sm text-white hover:bg-gray-600">
                    Kembali
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-3xl py-8 sm:px-6 lg:px-8">
            <form @submit.prevent="submit" class="space-y-5 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div>
                    <InputLabel for="name" value="Nama" />
                    <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required maxlength="100" autofocus />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="slug" value="Slug" />
                    <TextInput
                        id="slug"
                        type="text"
                        class="mt-1 block w-full font-mono text-sm disabled:bg-gray-100 disabled:text-gray-500"
                        v-model="form.slug"
                        :disabled="slugLocked"
                        :placeholder="isEditing ? '' : 'Kosongkan untuk dibuat otomatis dari nama'"
                        maxlength="100"
                    />
                    <p class="mt-1 text-xs text-gray-500">
                        Alamat kategori: <span class="font-mono">/articles?category={{ previewSlug }}</span>
                    </p>
                    <p v-if="slugLocked" class="mt-1 text-xs text-gray-500">Slug tidak bisa diubah: {{ category.slug_lock_reason }}.</p>
                    <InputError class="mt-2" :message="form.errors.slug" />
                </div>

                <div>
                    <InputLabel for="description" value="Deskripsi" />
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        maxlength="500"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Satu-dua kalimat; tampil di halaman kategori dan untuk mesin pencari."
                    />
                    <InputError class="mt-2" :message="form.errors.description" />
                </div>

                <div>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            :disabled="category?.is_menu_rubric"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 disabled:opacity-50"
                        />
                        Aktif (bisa dipilih penulis)
                    </label>
                    <p v-if="category?.is_menu_rubric" class="mt-1 text-xs text-gray-500">Rubrik yang ditautkan dari menu dan beranda selalu aktif.</p>
                    <InputError class="mt-2" :message="form.errors.is_active" />
                </div>

                <div class="flex justify-end">
                    <PrimaryButton :disabled="form.processing">{{ isEditing ? 'Simpan' : 'Tambah Kategori' }}</PrimaryButton>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
