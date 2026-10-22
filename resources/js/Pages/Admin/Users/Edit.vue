<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
    roleOptions: Array,
    canChangeRole: Boolean,
    expertiseOptions: Array,
    bioMaxLength: Number,
});

const form = useForm({
    name: props.user.name ?? '',
    role: props.user.role ?? 'user',
    gender: props.user.gender ?? '',
    whatsapp_number: props.user.whatsapp_number ?? '',
    position: props.user.position ?? '',
    institution: props.user.institution ?? '',
    institution_unit: props.user.institution_unit ?? '',
    linkedin_url: props.user.linkedin_url ?? '',
    bio: props.user.bio ?? '',
    expertise_topics: props.user.expertise_topics ?? [],
    expertise_other: props.user.expertise_other ?? '',
});

const submit = () => {
    form.put(route('admin.users.update', props.user.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Edit: ${user.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit Pengguna</h2>
                <Link :href="route('admin.users.index')" class="rounded bg-gray-700 px-3 py-2 text-sm text-white hover:bg-gray-600">
                    Kembali
                </Link>
            </div>
        </template>

        <div class="py-8">
            <form @submit.prevent="submit" class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <section class="space-y-5 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <header>
                        <h3 class="text-base font-semibold text-gray-900">Akun</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Email: <strong>{{ user.email }}</strong>. Email dan password hanya bisa diubah pemilik akun di halaman Profile.
                        </p>
                    </header>

                    <div>
                        <InputLabel for="name" value="Nama" />
                        <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required maxlength="255" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="role" value="Role" />
                        <select
                            id="role"
                            v-model="form.role"
                            :disabled="!canChangeRole"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
                        >
                            <option v-for="role in roleOptions" :key="role.value" :value="role.value">{{ role.label }}</option>
                        </select>
                        <p v-if="!canChangeRole" class="mt-1 text-xs text-gray-500">Role akun Anda sendiri tidak bisa diubah di sini.</p>
                        <InputError class="mt-2" :message="form.errors.role" />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <InputLabel for="gender" value="Gender" />
                            <select
                                id="gender"
                                v-model="form.gender"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">-</option>
                                <option value="Pria">Pria</option>
                                <option value="Wanita">Wanita</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.gender" />
                        </div>
                        <div>
                            <InputLabel for="whatsapp_number" value="Nomor WhatsApp (tidak tampil di situs)" />
                            <TextInput id="whatsapp_number" type="text" class="mt-1 block w-full" v-model="form.whatsapp_number" maxlength="30" />
                            <InputError class="mt-2" :message="form.errors.whatsapp_number" />
                        </div>
                    </div>
                </section>

                <section class="space-y-5 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <header>
                        <h3 class="text-base font-semibold text-gray-900">Profil publik</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Tampil di halaman profil kontributor.
                            <a
                                v-if="user.profile_url"
                                :href="user.profile_url"
                                target="_blank"
                                rel="noopener"
                                class="text-indigo-600 underline hover:text-indigo-800"
                            >Lihat profil publik</a>
                        </p>
                    </header>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <InputLabel for="position" value="Jabatan" />
                            <TextInput id="position" type="text" class="mt-1 block w-full" v-model="form.position" maxlength="255" />
                            <InputError class="mt-2" :message="form.errors.position" />
                        </div>
                        <div>
                            <InputLabel for="institution" value="Asal Instansi" />
                            <TextInput id="institution" type="text" class="mt-1 block w-full" v-model="form.institution" maxlength="255" />
                            <InputError class="mt-2" :message="form.errors.institution" />
                        </div>
                        <div>
                            <InputLabel for="institution_unit" value="Unit Kerja" />
                            <TextInput id="institution_unit" type="text" class="mt-1 block w-full" v-model="form.institution_unit" maxlength="255" />
                            <InputError class="mt-2" :message="form.errors.institution_unit" />
                        </div>
                        <div>
                            <InputLabel for="linkedin_url" value="LinkedIn" />
                            <TextInput id="linkedin_url" type="url" class="mt-1 block w-full" v-model="form.linkedin_url" placeholder="https://www.linkedin.com/in/username" />
                            <InputError class="mt-2" :message="form.errors.linkedin_url" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="bio" value="Bio" />
                        <textarea
                            id="bio"
                            v-model="form.bio"
                            rows="4"
                            :maxlength="bioMaxLength"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p class="mt-1 text-xs text-gray-500">{{ (form.bio || '').length }}/{{ bioMaxLength }} karakter</p>
                        <InputError class="mt-2" :message="form.errors.bio" />
                    </div>

                    <div>
                        <InputLabel value="Bidang keahlian" />
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            <label
                                v-for="option in expertiseOptions"
                                :key="option"
                                class="inline-flex items-center gap-2 text-sm text-gray-700"
                            >
                                <input
                                    v-model="form.expertise_topics"
                                    type="checkbox"
                                    :value="option"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                />
                                <span>{{ option }}</span>
                            </label>
                        </div>
                        <div class="mt-3">
                            <InputLabel for="expertise_other" value="Lainnya" />
                            <TextInput id="expertise_other" type="text" class="mt-1 block w-full" v-model="form.expertise_other" maxlength="255" />
                        </div>
                        <InputError class="mt-2" :message="form.errors.expertise_topics || form.errors.expertise_other" />
                    </div>
                </section>

                <div class="flex justify-end">
                    <PrimaryButton :disabled="form.processing">Simpan</PrimaryButton>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
