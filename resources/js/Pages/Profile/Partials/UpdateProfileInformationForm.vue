<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    contributorProfile: {
        type: Object,
        default: null,
    },
});

const page = usePage();
// Reaktif: setelah Save, props.auth.user berisi foto dan data terbaru.
const user = computed(() => page.props.auth.user);
const avatarInput = ref(null);
let objectUrl = null;

const form = useForm({
    name: user.value.name,
    email: user.value.email,
    ...(props.contributorProfile
        ? {
              position: props.contributorProfile.position ?? '',
              bio: props.contributorProfile.bio ?? '',
          }
        : {}),
    avatar: null,
    remove_avatar: false,
});

const avatarPreview = computed(() => {
    if (form.avatar instanceof File) {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
        }

        objectUrl = URL.createObjectURL(form.avatar);
        return objectUrl;
    }

    if (form.remove_avatar) {
        return null;
    }

    return user.value.avatar_url || null;
});

const setAvatar = (event) => {
    const file = event.target.files?.[0] ?? null;
    form.avatar = file;
    form.remove_avatar = false;
};

const removeAvatar = () => {
    form.avatar = null;
    form.remove_avatar = true;
    if (avatarInput.value) {
        avatarInput.value.value = '';
    }
};

// Form berisi file (foto), jadi dikirim multipart. PHP hanya membaca body multipart
// pada POST, sehingga PATCH dikirim sebagai POST + _method (method spoofing Laravel).
const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'patch',
    })).post(route('profile.update'), {
        forceFormData: true,
        preserveScroll: true,
        // Foto yang sudah tersimpan tidak ikut terunggah ulang saat Save ditekan lagi.
        onSuccess: () => {
            form.avatar = null;
            form.remove_avatar = false;
            if (avatarInput.value) {
                avatarInput.value.value = '';
            }
        },
        onFinish: () => form.transform((data) => data),
    });
};

onBeforeUnmount(() => {
    if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
        objectUrl = null;
    }
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Update your account's profile information and email address.
            </p>
        </header>

        <form
            @submit.prevent="submit"
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel for="avatar" value="Photo Profile" />
                <div class="mt-2 flex items-center gap-4">
                    <img
                        v-if="avatarPreview"
                        :src="avatarPreview"
                        alt="Avatar preview"
                        class="h-14 w-14 rounded-full border border-gray-200 object-cover"
                    >
                    <div
                        v-else
                        class="inline-flex h-14 w-14 items-center justify-center rounded-full border border-gray-200 bg-gray-100 text-sm font-semibold text-gray-600"
                    >
                        {{ (user.name || 'U').substring(0, 2).toUpperCase() }}
                    </div>

                    <div class="space-y-2">
                        <input
                            id="avatar"
                            ref="avatarInput"
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp"
                            @change="setAvatar"
                            class="block text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100"
                        >
                        <button
                            v-if="avatarPreview"
                            type="button"
                            @click="removeAvatar"
                            class="rounded-md border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-700 hover:border-rose-300 hover:text-rose-700"
                        >
                            Hapus avatar
                        </button>
                    </div>
                </div>
                <InputError class="mt-2" :message="form.errors.avatar" />
            </div>

            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="contributorProfile" class="space-y-6 border-t border-gray-200 pt-6">
                <div>
                    <h3 class="text-base font-medium text-gray-900">Profil publik kontributor</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Jabatan dan bio tampil di halaman profil Anda yang dilihat pembaca.
                        <a
                            v-if="contributorProfile.public_url"
                            :href="contributorProfile.public_url"
                            target="_blank"
                            rel="noopener"
                            class="text-indigo-600 underline hover:text-indigo-800"
                        >Lihat profil publik</a>
                    </p>
                </div>

                <div>
                    <InputLabel for="position" value="Jabatan" />
                    <TextInput
                        id="position"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.position"
                        maxlength="255"
                        placeholder="mis. Dosen Ekonomi Syariah"
                        autocomplete="organization-title"
                    />
                    <InputError class="mt-2" :message="form.errors.position" />
                </div>

                <div>
                    <InputLabel for="bio" value="Bio" />
                    <textarea
                        id="bio"
                        v-model="form.bio"
                        rows="5"
                        :maxlength="contributorProfile.bio_max_length"
                        placeholder="Dua sampai empat kalimat tentang bidang, riset, dan pengalaman Anda."
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <p class="mt-1 text-xs text-gray-500">
                        {{ (form.bio || '').length }}/{{ contributorProfile.bio_max_length }} karakter
                    </p>
                    <InputError class="mt-2" :message="form.errors.bio" />
                </div>

                <p class="text-sm text-gray-600">
                    Institusi dan bidang keahlian diatur redaksi. Hubungi redaksi bila perlu diubah.
                </p>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Email Anda belum diverifikasi, jadi komentar, bookmark, follow, dan dashboard belum bisa dipakai.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Kirim ulang email verifikasi.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    Tautan verifikasi baru sudah dikirim ke email Anda.
                </div>
                <div
                    v-show="status === 'verification-link-failed'"
                    class="mt-2 text-sm font-medium text-rose-600"
                >
                    Email verifikasi gagal dikirim. Coba lagi beberapa saat lagi.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
