<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});
const email = computed(() => usePage().props.auth.user?.email);

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
const verificationLinkFailed = computed(
    () => props.status === 'verification-link-failed',
);
</script>

<template>
    <GuestLayout>
        <Head title="Verifikasi Email" />

        <div class="mb-4 space-y-2 text-sm text-gray-600">
            <p>
                Satu langkah lagi: buka email dari Expertia yang kami kirim ke
                <strong class="text-gray-900">{{ email }}</strong>, lalu klik tautan verifikasinya.
            </p>
            <p>
                Setelah email terverifikasi, Anda bisa berkomentar, menyimpan artikel, mengikuti penulis,
                dan memakai dashboard. Belum menerima email? Periksa folder spam, atau kirim ulang di bawah.
            </p>
            <p>
                Alamat email salah?
                <Link :href="route('profile.edit')" class="font-medium text-indigo-600 underline hover:text-indigo-800">Perbaiki di halaman Profil</Link>,
                lalu kirim ulang tautannya.
            </p>
        </div>

        <div
            class="mb-4 text-sm font-medium text-green-600"
            v-if="verificationLinkSent"
        >
            Tautan verifikasi baru sudah dikirim ke email Anda.
        </div>

        <div
            class="mb-4 text-sm font-medium text-rose-600"
            v-if="verificationLinkFailed"
        >
            Email verifikasi gagal dikirim. Coba lagi beberapa saat lagi, atau hubungi redaksi bila masalah berlanjut.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Kirim ulang email verifikasi
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >Keluar</Link
                >
            </div>
        </form>
    </GuestLayout>
</template>
