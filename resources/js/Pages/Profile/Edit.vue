<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
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
    canDeleteAccount: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-screen-2xl space-y-6 sm:px-6 lg:px-8">
                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8"
                >
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        :contributor-profile="contributorProfile"
                        class="max-w-xl"
                    />
                </div>

                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8"
                >
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8"
                >
                    <DeleteUserForm v-if="canDeleteAccount" class="max-w-xl" />
                    <section v-else class="max-w-xl">
                        <h2 class="text-lg font-medium text-gray-900">Hapus Akun</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Akun kontributor dan tim redaksi terhubung dengan artikel dan riwayat review,
                            jadi tidak bisa dihapus dari sini. Hubungi redaksi bila akun ini perlu ditutup.
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
