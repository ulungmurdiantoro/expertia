<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    metrics: Object,
    queues: Object,
    articleStatus: Object,
    editorialStatus: Object,
    roleDistribution: Array,
    topCategories: Array,
    topAuthors: Array,
    recentArticles: Array,
    recentReports: Array,
    recentActivity: Array,
});

const metricCards = computed(() => [
    { label: 'Total Users', value: props.metrics.total_users, tone: 'border-indigo-200 bg-indigo-50 text-indigo-800' },
    { label: 'Active Authors', value: props.metrics.active_authors, tone: 'border-sky-200 bg-sky-50 text-sky-800' },
    { label: 'Active Editors', value: props.metrics.active_editors, tone: 'border-violet-200 bg-violet-50 text-violet-800' },
    { label: 'Published Articles', value: props.metrics.published_articles, tone: 'border-emerald-200 bg-emerald-50 text-emerald-800' },
    { label: 'Scheduled', value: props.metrics.scheduled_articles, tone: 'border-cyan-200 bg-cyan-50 text-cyan-800' },
    { label: 'Draft', value: props.metrics.draft_articles, tone: 'border-gray-200 bg-white text-gray-800' },
    { label: 'Pending Reviews', value: props.metrics.pending_reviews, tone: 'border-amber-200 bg-amber-50 text-amber-800' },
    { label: 'Pending Reports', value: props.metrics.pending_reports, tone: 'border-rose-200 bg-rose-50 text-rose-800' },
    { label: 'Pending Comments', value: props.metrics.pending_comments, tone: 'border-orange-200 bg-orange-50 text-orange-800' },
    { label: 'Review 48j+', value: props.metrics.overdue_reviews, tone: 'border-red-200 bg-red-50 text-red-800' },
    { label: 'Report 24j+', value: props.metrics.overdue_reports, tone: 'border-fuchsia-200 bg-fuchsia-50 text-fuchsia-800' },
]);

const objectEntries = (value) => Object.entries(value || {});
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Admin Dashboard</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl space-y-5 sm:px-6 lg:px-8">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
                    <div
                        v-for="card in metricCards"
                        :key="card.label"
                        :class="card.tone"
                        class="rounded-lg border p-4 shadow-sm"
                    >
                        <p class="text-xs font-semibold uppercase tracking-wide">{{ card.label }}</p>
                        <p class="mt-2 text-2xl font-semibold">{{ card.value }}</p>
                    </div>
                </div>

                <div class="grid gap-5 xl:grid-cols-3">
                    <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold text-gray-900">Editorial Queue</h3>
                            <Link :href="route('editor.reviews.index')" class="text-sm font-medium text-indigo-600">Buka review</Link>
                        </div>
                        <div class="mt-4 space-y-3">
                            <div v-for="(value, label) in queues.reviews" :key="label" class="flex items-center justify-between rounded-md bg-gray-50 px-3 py-2">
                                <span class="text-sm text-gray-600">{{ label }}</span>
                                <strong class="text-gray-900">{{ value }}</strong>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold text-gray-900">Moderation Queue</h3>
                            <Link :href="route('editor.moderation.reports.index')" class="text-sm font-medium text-indigo-600">Buka laporan</Link>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div v-for="(value, label) in queues.moderation" :key="label" class="rounded-md bg-gray-50 px-3 py-2">
                                <p class="text-xs text-gray-500">{{ label }}</p>
                                <p class="mt-1 text-lg font-semibold text-gray-900">{{ value }}</p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-gray-900">Role Distribution</h3>
                        <div class="mt-4 space-y-3">
                            <div v-for="role in roleDistribution" :key="role.name" class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">{{ role.name }}</span>
                                <span class="rounded bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">{{ role.users_count }}</span>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="grid gap-5 xl:grid-cols-2">
                    <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-gray-900">Recent Articles</h3>
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                                    <tr>
                                        <th class="px-3 py-2">Judul</th>
                                        <th class="px-3 py-2">Author</th>
                                        <th class="px-3 py-2">Status</th>
                                        <th class="px-3 py-2">Update</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="article in recentArticles" :key="article.id" class="border-t border-gray-100">
                                        <td class="px-3 py-3">
                                            <Link :href="route('articles.show', article.slug)" class="font-medium text-gray-900 hover:text-indigo-600">
                                                {{ article.title }}
                                            </Link>
                                            <p class="text-xs text-gray-500">{{ article.category?.name || 'Tanpa kategori' }}</p>
                                        </td>
                                        <td class="px-3 py-3 text-gray-600">{{ article.author?.name || '-' }}</td>
                                        <td class="px-3 py-3">
                                            <span class="rounded bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">{{ article.status }} / {{ article.editorial_status }}</span>
                                        </td>
                                        <td class="px-3 py-3 text-gray-500">{{ article.updated_at }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-gray-900">Recent Reports</h3>
                        <div class="mt-4 space-y-3">
                            <div v-for="report in recentReports" :key="report.id" class="rounded-md border border-gray-100 p-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ report.subject_type }} - {{ report.reason }}</p>
                                        <p class="mt-1 text-xs text-gray-500">Reporter: {{ report.reporter?.name || '-' }} | {{ report.created_at }}</p>
                                    </div>
                                    <span class="rounded bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">{{ report.status }}</span>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="grid gap-5 xl:grid-cols-3">
                    <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-gray-900">Article Status</h3>
                        <div class="mt-4 space-y-2">
                            <div v-for="[label, value] in objectEntries(articleStatus)" :key="label" class="flex justify-between text-sm">
                                <span class="text-gray-600">{{ label }}</span>
                                <strong class="text-gray-900">{{ value }}</strong>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-gray-900">Editorial Status</h3>
                        <div class="mt-4 space-y-2">
                            <div v-for="[label, value] in objectEntries(editorialStatus)" :key="label" class="flex justify-between text-sm">
                                <span class="text-gray-600">{{ label }}</span>
                                <strong class="text-gray-900">{{ value }}</strong>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-gray-900">Top Authors</h3>
                        <div class="mt-4 space-y-3">
                            <div v-for="author in topAuthors" :key="author.id" class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-gray-900">{{ author.name }}</p>
                                    <p class="text-xs text-gray-500">{{ author.published_articles_count }} published / {{ author.articles_count }} total</p>
                                </div>
                                <Link :href="route('authors.show', author.profile_slug)" class="text-xs font-medium text-indigo-600">Profil</Link>
                            </div>
                        </div>
                    </section>
                </div>

                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">Recent Activity</h3>
                        <Link :href="route('admin.audit-logs.index')" class="text-sm font-medium text-indigo-600">Audit log</Link>
                    </div>
                    <div class="mt-4 grid gap-3 lg:grid-cols-2">
                        <div v-for="log in recentActivity" :key="log.id" class="rounded-md bg-gray-50 px-3 py-2">
                            <p class="text-sm font-medium text-gray-900">{{ log.action }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ log.user?.name || 'System' }} | {{ log.subject_type }}#{{ log.subject_id }} | {{ log.created_at }}</p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
