<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    report: Object,
});

const statusForm = useForm({
    status: props.report.status ?? 'pending',
    resolution_note: props.report.resolution_note ?? '',
});

const actionForm = useForm({
    action: props.report.subject_key === 'article' ? 'hide_article' : 'hide_comment',
    resolution_note: '',
});

const updateStatus = () => {
    statusForm.patch(route('editor.moderation.reports.update', props.report.id));
};

const applyAction = () => {
    actionForm.patch(route('editor.moderation.reports.subject-action', props.report.id));
};
</script>

<template>
    <Head title="Detail Laporan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Detail Laporan</h2>
                <Link :href="route('editor.moderation.reports.index')" class="text-sm text-indigo-600">Kembali</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto grid max-w-screen-2xl gap-4 sm:px-6 lg:px-8 md:grid-cols-2">
                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-gray-900">Informasi Laporan</h3>
                    <p class="mt-2 text-sm text-gray-700">Reason: {{ report.reason }}</p>
                    <p class="mt-1 text-sm text-gray-700">Note: {{ report.note || '-' }}</p>
                    <p class="mt-1 text-sm text-gray-700">Status: {{ report.status }}</p>
                    <p class="mt-1 text-sm text-gray-700">Reporter: {{ report.reporter?.name }}</p>
                    <p class="mt-1 text-sm text-gray-700">Subject: {{ report.subject_type }}</p>
                    <p class="mt-1 text-sm text-gray-700">{{ report.subject_summary }}</p>

                    <form @submit.prevent="updateStatus" class="mt-4 space-y-2">
                        <select v-model="statusForm.status" class="w-full rounded-md border-gray-300">
                            <option value="pending">pending</option>
                            <option value="resolved">resolved</option>
                            <option value="dismissed">dismissed</option>
                        </select>
                        <textarea
                            v-model="statusForm.resolution_note"
                            rows="3"
                            placeholder="Resolution note"
                            class="w-full rounded-md border-gray-300"
                        />
                        <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-sm text-white">Update status</button>
                    </form>
                </section>

                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-gray-900">Konten Terkait</h3>
                    <div v-if="report.subject" class="mt-3 text-sm text-gray-700">
                        <p v-if="report.subject_key === 'article'">Judul: {{ report.subject.title }}</p>
                        <p v-if="report.subject_key === 'article'">Status: {{ report.subject.status }} | Visibility: {{ report.subject.visibility }}</p>
                        <p v-if="report.subject_key === 'article'">Excerpt: {{ report.subject.excerpt || '-' }}</p>

                        <p v-if="report.subject_key === 'comment'">Komentar: {{ report.subject.content }}</p>
                        <p v-if="report.subject_key === 'comment'">Status: {{ report.subject.status }}</p>
                    </div>
                    <p v-else class="mt-3 text-sm text-gray-500">Konten sudah tidak tersedia.</p>

                    <form v-if="report.subject" @submit.prevent="applyAction" class="mt-4 space-y-2">
                        <select v-model="actionForm.action" class="w-full rounded-md border-gray-300">
                            <option v-if="report.subject_key === 'comment'" value="hide_comment">hide_comment</option>
                            <option v-if="report.subject_key === 'comment'" value="approve_comment">approve_comment</option>
                            <option v-if="report.subject_key === 'comment'" value="spam_comment">spam_comment</option>
                            <option v-if="report.subject_key === 'article'" value="hide_article">hide_article</option>
                            <option v-if="report.subject_key === 'article'" value="archive_article">archive_article</option>
                        </select>
                        <textarea
                            v-model="actionForm.resolution_note"
                            rows="3"
                            placeholder="Catatan aksi"
                            class="w-full rounded-md border-gray-300"
                        />
                        <button type="submit" class="rounded bg-rose-600 px-4 py-2 text-sm text-white">Apply action</button>
                    </form>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

