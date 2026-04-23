<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    notifications: Object,
});

const markAllForm = useForm({});
const markOneForm = useForm({});

const unreadCount = computed(() =>
    (props.notifications?.data || []).filter((notification) => notification.read_at === null).length,
);

const markAllAsRead = () => {
    markAllForm.patch(route('me.notifications.read-all'), {
        preserveScroll: true,
    });
};

const markAsRead = (notificationId) => {
    markOneForm.patch(route('me.notifications.read', notificationId), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Notifikasi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Notifikasi</h2>
                <button
                    v-if="unreadCount > 0"
                    type="button"
                    @click="markAllAsRead"
                    class="rounded-md border border-indigo-300 px-3 py-1.5 text-sm font-medium text-indigo-700 hover:bg-indigo-50"
                >
                    Tandai semua dibaca
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl sm:px-6 lg:px-8">
                <div class="space-y-3">
                    <div
                        v-for="notification in notifications.data"
                        :key="notification.id"
                        class="rounded-lg border bg-white p-4 shadow-sm"
                        :class="notification.read_at ? 'border-gray-200' : 'border-indigo-300 ring-1 ring-indigo-100'"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-gray-900">{{ notification.title }}</p>
                                <p class="mt-1 text-sm text-gray-600">{{ notification.body }}</p>
                                <p class="mt-2 text-xs text-gray-400">{{ notification.created_at }}</p>
                            </div>
                            <button
                                v-if="!notification.read_at"
                                type="button"
                                @click="markAsRead(notification.id)"
                                class="shrink-0 rounded-md border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-700 hover:border-indigo-300 hover:text-indigo-700"
                            >
                                Sudah dibaca
                            </button>
                            <span
                                v-else
                                class="shrink-0 rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700"
                            >
                                Dibaca
                            </span>
                        </div>
                    </div>

                    <div v-if="notifications.data.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500">
                        Belum ada notifikasi.
                    </div>
                </div>

                <div v-if="notifications.links?.length > 3" class="mt-6 flex flex-wrap gap-2">
                    <button
                        v-for="link in notifications.links"
                        :key="`${link.label}-${link.url || 'null'}`"
                        type="button"
                        :disabled="link.url === null || link.active"
                        @click="router.visit(link.url, { preserveScroll: true, preserveState: true })"
                        class="rounded-md border px-3 py-1.5 text-sm"
                        :class="
                            link.active
                                ? 'border-indigo-400 bg-indigo-50 font-semibold text-indigo-700'
                                : 'border-gray-300 text-gray-700 hover:border-indigo-300 hover:text-indigo-700 disabled:cursor-not-allowed disabled:opacity-40'
                        "
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
