<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    articles: Object,
});

const approvalForm = useForm({});
const rejectForm = useForm({ note: '' });
const revisionForm = useForm({ note: '' });

const approve = (id) => approvalForm.patch(route('editor.reviews.approve', id));
const reject = (id) => rejectForm.patch(route('editor.reviews.reject', id));
const requestRevision = (id) => revisionForm.patch(route('editor.reviews.request-revision', id));
</script>

<template>
    <Head title="Review Artikel" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Review Artikel</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl sm:px-6 lg:px-8">
                <div class="space-y-4">
                    <article
                        v-for="article in articles.data"
                        :key="article.id"
                        class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
                    >
                        <h3 class="text-lg font-semibold text-gray-900">{{ article.title }}</h3>
                        <p class="mt-1 text-sm text-gray-600">Oleh {{ article.author?.name }}</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <button @click="approve(article.id)" class="rounded bg-emerald-600 px-3 py-1 text-sm text-white">Approve</button>
                            <button @click="reject(article.id)" class="rounded bg-rose-600 px-3 py-1 text-sm text-white">Reject</button>
                            <button @click="requestRevision(article.id)" class="rounded bg-amber-500 px-3 py-1 text-sm text-white">Request Revision</button>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

