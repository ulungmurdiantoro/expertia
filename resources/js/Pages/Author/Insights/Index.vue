<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    stats: Object,
    top_articles: Array,
    daily_views: Array,
    daily_interactions: Array,
    interaction_breakdown: Object,
    kpi_comparison: Object,
    categories: Array,
    filters: Object,
});

const activeDays = computed(() => Number(props.filters?.days || 30));
const activeCategoryId = computed(() => Number(props.filters?.category_id || 0));

const switchDays = (days) => {
    router.get(route('author.insights.index'), {
        days,
        category_id: activeCategoryId.value || undefined,
    }, {
        preserveScroll: true,
        replace: true,
    });
};

const switchCategory = (categoryId) => {
    const nextCategoryId = Number(categoryId || 0);

    router.get(route('author.insights.index'), {
        days: activeDays.value,
        category_id: nextCategoryId || undefined,
    }, {
        preserveScroll: true,
        replace: true,
    });
};

const chartViewBox = '0 0 760 260';

const maxViews = computed(() => Math.max(...(props.daily_views || []).map((row) => Number(row.total || 0)), 1));
const maxInteractions = computed(() => Math.max(...(props.daily_interactions || []).map((row) => Number(row.total || 0)), 1));

const toPoints = (rows, maxValue) => {
    if (!rows || rows.length === 0) {
        return '';
    }

    const width = 760;
    const height = 260;
    const leftPad = 44;
    const rightPad = 44;
    const topPad = 16;
    const bottomPad = 24;
    const innerWidth = width - leftPad - rightPad;
    const innerHeight = height - topPad - bottomPad;
    const stepX = rows.length > 1 ? innerWidth / (rows.length - 1) : innerWidth;

    return rows
        .map((row, index) => {
            const x = leftPad + (stepX * index);
            const ratio = Number(row.total || 0) / Math.max(maxValue, 1);
            const y = topPad + (innerHeight * (1 - ratio));
            return `${x.toFixed(2)},${y.toFixed(2)}`;
        })
        .join(' ');
};

const viewPoints = computed(() => toPoints(props.daily_views || [], maxViews.value));
const interactionPoints = computed(() => toPoints(props.daily_interactions || [], maxInteractions.value));

const xTickLabels = computed(() => {
    const rows = props.daily_views || [];
    if (!rows.length) {
        return [];
    }

    const indices = [0, Math.floor((rows.length - 1) / 2), rows.length - 1];

    return [...new Set(indices)]
        .map((idx) => ({
            idx,
            label: rows[idx]?.day || '',
        }));
});

const openTooltipKey = ref(null);
const tooltipWrappers = ref({});
const tooltipTriggerButtons = ref({});
const tooltipPlacement = ref({
    articles: 'left',
    views: 'left',
    comments: 'left',
    engagement: 'left',
});

const setTooltipWrapper = (metricKey, element) => {
    if (!element) return;
    tooltipWrappers.value[metricKey] = element;
};

const setTooltipTrigger = (metricKey, element) => {
    if (!element) return;
    tooltipTriggerButtons.value[metricKey] = element;
};

const closeTooltip = (returnFocus = false) => {
    const previousKey = openTooltipKey.value;
    openTooltipKey.value = null;

    if (returnFocus && previousKey) {
        nextTick(() => {
            const trigger = tooltipTriggerButtons.value[previousKey];
            if (trigger instanceof HTMLElement) {
                trigger.focus();
            }
        });
    }
};

const toggleTooltip = (metricKey) => {
    if (openTooltipKey.value === metricKey) {
        openTooltipKey.value = null;
        return;
    }

    openTooltipKey.value = metricKey;
    nextTick(() => {
        updateTooltipPlacement(metricKey);
    });
};

const isTooltipOpen = (metricKey) => openTooltipKey.value === metricKey;
const tooltipPanelId = (metricKey) => `kpi-tooltip-${metricKey}`;

const tooltipPlacementClass = (metricKey) => {
    return tooltipPlacement.value[metricKey] === 'right' ? 'right-0' : 'left-0';
};

const updateTooltipPlacement = (metricKey) => {
    const wrapper = tooltipWrappers.value[metricKey];
    if (!(wrapper instanceof HTMLElement)) {
        return;
    }

    const panel = wrapper.querySelector('[data-tooltip-panel]');
    if (!(panel instanceof HTMLElement)) {
        return;
    }

    tooltipPlacement.value[metricKey] = 'left';

    const panelRect = panel.getBoundingClientRect();
    const viewportWidth = window.innerWidth;

    if (panelRect.right > viewportWidth - 8) {
        tooltipPlacement.value[metricKey] = 'right';
        return;
    }

    if (panelRect.left < 8) {
        tooltipPlacement.value[metricKey] = 'left';
    }
};

const handleDocumentClick = (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) {
        openTooltipKey.value = null;
        return;
    }

    if (!target.closest('[data-kpi-tooltip]')) {
        openTooltipKey.value = null;
    }
};

const handleWindowResize = () => {
    if (openTooltipKey.value) {
        updateTooltipPlacement(openTooltipKey.value);
    }
};

const handleTooltipKeydown = (event, metricKey) => {
    if (event.key === 'Escape') {
        closeTooltip(true);
        event.preventDefault();
        event.stopPropagation();
        return;
    }

    if (event.key === 'Enter' || event.key === ' ') {
        toggleTooltip(metricKey);
        event.preventDefault();
        event.stopPropagation();
    }
};

const handleDocumentKeydown = (event) => {
    if (event.key === 'Escape') {
        closeTooltip(true);
    }
};

onMounted(() => {
    document.addEventListener('click', handleDocumentClick);
    document.addEventListener('keydown', handleDocumentKeydown);
    window.addEventListener('resize', handleWindowResize);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleDocumentClick);
    document.removeEventListener('keydown', handleDocumentKeydown);
    window.removeEventListener('resize', handleWindowResize);
});

const comparisonText = (metricKey) => {
    const metric = props.kpi_comparison?.[metricKey];
    if (!metric) return 'Belum ada pembanding';

    const sign = metric.direction === 'up' ? '+' : metric.direction === 'down' ? '-' : '';
    return `${sign}${Math.abs(metric.growth_pct || 0)}% vs periode sebelumnya`;
};

const comparisonClass = (metricKey) => {
    const direction = props.kpi_comparison?.[metricKey]?.direction;
    if (direction === 'up') return 'text-emerald-600';
    if (direction === 'down') return 'text-rose-600';
    return 'text-gray-500';
};

const comparisonIcon = (metricKey) => {
    const direction = props.kpi_comparison?.[metricKey]?.direction;
    if (direction === 'up') return 'fa-solid fa-arrow-trend-up';
    if (direction === 'down') return 'fa-solid fa-arrow-trend-down';
    return 'fa-solid fa-minus';
};

const tooltipLines = (metricKey) => {
    const metric = props.kpi_comparison?.[metricKey];
    if (!metric) {
        return {
            current: 'Periode ini: -',
            previous: 'Periode sebelumnya: -',
        };
    }

    return {
        current: `Periode ini: ${metric.current}`,
        previous: `Periode sebelumnya: ${metric.previous}`,
    };
};
</script>

<template>
    <Head title="Creator Insights" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Creator Insights</h2>
                <div class="flex items-center gap-2">
                    <a
                        :href="route('author.insights.export', { days: activeDays, category_id: activeCategoryId || undefined })"
                        class="rounded-md border border-emerald-300 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50"
                    >
                        Export Summary CSV
                    </a>
                    <a
                        :href="route('author.insights.export-articles', { days: activeDays, category_id: activeCategoryId || undefined })"
                        class="rounded-md border border-indigo-300 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50"
                    >
                        Export Detail Artikel
                    </a>
                    <Link
                        :href="route('author.articles.index')"
                        class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:border-indigo-400 hover:text-indigo-700"
                    >
                        Kelola Artikel
                    </Link>
                </div>
            </div>
        </template>

        <div class="bg-[#f7f8fb] py-8">
            <div class="mx-auto max-w-screen-2xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.08em] text-gray-500">Rentang Data</p>
                            <div class="mt-3 flex items-center gap-2">
                                <button
                                    v-for="days in [7, 30, 90]"
                                    :key="days"
                                    type="button"
                                    @click="switchDays(days)"
                                    :class="activeDays === days ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                    class="rounded-md px-3 py-1.5 text-sm font-medium"
                                >
                                    {{ days }} hari
                                </button>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.08em] text-gray-500">Kategori</p>
                            <select
                                :value="activeCategoryId"
                                @change="switchCategory($event.target.value)"
                                class="mt-3 w-full rounded-md border-gray-300 text-sm"
                            >
                                <option :value="0">Semua kategori</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                            </select>
                        </div>
                    </div>
                </section>

                <section class="grid gap-4 md:grid-cols-4">
                    <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-gray-500">Artikel</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.total_articles || 0 }}</p>
                        <p class="mt-1 text-xs text-gray-500">Published: {{ stats.published_articles || 0 }}</p>
                        <div :ref="(el) => setTooltipWrapper('articles', el)" class="group relative mt-1 inline-flex" data-kpi-tooltip>
                            <button
                                :ref="(el) => setTooltipTrigger('articles', el)"
                                type="button"
                                @click.stop="toggleTooltip('articles')"
                                @keydown="handleTooltipKeydown($event, 'articles')"
                                class="inline-flex items-center gap-1 rounded-md px-1 py-0.5 text-xs font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white"
                                :class="comparisonClass('articles')"
                                :aria-expanded="isTooltipOpen('articles') ? 'true' : 'false'"
                                :aria-controls="tooltipPanelId('articles')"
                                aria-label="Lihat detail perbandingan KPI artikel"
                            >
                                <i :class="comparisonIcon('articles')" />
                                <span>{{ comparisonText('articles') }}</span>
                            </button>
                            <div
                                :id="tooltipPanelId('articles')"
                                data-tooltip-panel
                                class="pointer-events-none absolute top-full z-20 mt-1 w-52 rounded-md bg-gray-900 px-2 py-1.5 text-[11px] text-white shadow-lg transition-all duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0 group-hover:scale-100"
                                :class="[tooltipPlacementClass('articles'), isTooltipOpen('articles') ? 'opacity-100 translate-y-0 scale-100' : 'opacity-0 -translate-y-1 scale-95']"
                                role="tooltip"
                                :aria-hidden="isTooltipOpen('articles') ? 'false' : 'true'"
                            >
                                <p>{{ tooltipLines('articles').current }}</p>
                                <p class="mt-0.5 text-gray-200">{{ tooltipLines('articles').previous }}</p>
                            </div>
                        </div>
                    </article>
                    <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-gray-500">Views</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.total_views || 0 }}</p>
                        <p class="mt-1 text-xs text-gray-500">Total performa konten</p>
                        <div :ref="(el) => setTooltipWrapper('views', el)" class="group relative mt-1 inline-flex" data-kpi-tooltip>
                            <button
                                :ref="(el) => setTooltipTrigger('views', el)"
                                type="button"
                                @click.stop="toggleTooltip('views')"
                                @keydown="handleTooltipKeydown($event, 'views')"
                                class="inline-flex items-center gap-1 rounded-md px-1 py-0.5 text-xs font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white"
                                :class="comparisonClass('views')"
                                :aria-expanded="isTooltipOpen('views') ? 'true' : 'false'"
                                :aria-controls="tooltipPanelId('views')"
                                aria-label="Lihat detail perbandingan KPI views"
                            >
                                <i :class="comparisonIcon('views')" />
                                <span>{{ comparisonText('views') }}</span>
                            </button>
                            <div
                                :id="tooltipPanelId('views')"
                                data-tooltip-panel
                                class="pointer-events-none absolute top-full z-20 mt-1 w-52 rounded-md bg-gray-900 px-2 py-1.5 text-[11px] text-white shadow-lg transition-all duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0 group-hover:scale-100"
                                :class="[tooltipPlacementClass('views'), isTooltipOpen('views') ? 'opacity-100 translate-y-0 scale-100' : 'opacity-0 -translate-y-1 scale-95']"
                                role="tooltip"
                                :aria-hidden="isTooltipOpen('views') ? 'false' : 'true'"
                            >
                                <p>{{ tooltipLines('views').current }}</p>
                                <p class="mt-0.5 text-gray-200">{{ tooltipLines('views').previous }}</p>
                            </div>
                        </div>
                    </article>
                    <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-gray-500">Komentar</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.total_comments || 0 }}</p>
                        <p class="mt-1 text-xs text-gray-500">Interaksi diskusi</p>
                        <div :ref="(el) => setTooltipWrapper('comments', el)" class="group relative mt-1 inline-flex" data-kpi-tooltip>
                            <button
                                :ref="(el) => setTooltipTrigger('comments', el)"
                                type="button"
                                @click.stop="toggleTooltip('comments')"
                                @keydown="handleTooltipKeydown($event, 'comments')"
                                class="inline-flex items-center gap-1 rounded-md px-1 py-0.5 text-xs font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white"
                                :class="comparisonClass('comments')"
                                :aria-expanded="isTooltipOpen('comments') ? 'true' : 'false'"
                                :aria-controls="tooltipPanelId('comments')"
                                aria-label="Lihat detail perbandingan KPI komentar"
                            >
                                <i :class="comparisonIcon('comments')" />
                                <span>{{ comparisonText('comments') }}</span>
                            </button>
                            <div
                                :id="tooltipPanelId('comments')"
                                data-tooltip-panel
                                class="pointer-events-none absolute top-full z-20 mt-1 w-52 rounded-md bg-gray-900 px-2 py-1.5 text-[11px] text-white shadow-lg transition-all duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0 group-hover:scale-100"
                                :class="[tooltipPlacementClass('comments'), isTooltipOpen('comments') ? 'opacity-100 translate-y-0 scale-100' : 'opacity-0 -translate-y-1 scale-95']"
                                role="tooltip"
                                :aria-hidden="isTooltipOpen('comments') ? 'false' : 'true'"
                            >
                                <p>{{ tooltipLines('comments').current }}</p>
                                <p class="mt-0.5 text-gray-200">{{ tooltipLines('comments').previous }}</p>
                            </div>
                        </div>
                    </article>
                    <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-gray-500">Bookmark + Reaksi</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ (stats.total_bookmarks || 0) + (stats.total_reactions || 0) }}</p>
                        <p class="mt-1 text-xs text-gray-500">Signal ketertarikan pembaca</p>
                        <div :ref="(el) => setTooltipWrapper('engagement', el)" class="group relative mt-1 inline-flex" data-kpi-tooltip>
                            <button
                                :ref="(el) => setTooltipTrigger('engagement', el)"
                                type="button"
                                @click.stop="toggleTooltip('engagement')"
                                @keydown="handleTooltipKeydown($event, 'engagement')"
                                class="inline-flex items-center gap-1 rounded-md px-1 py-0.5 text-xs font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white"
                                :class="comparisonClass('engagement')"
                                :aria-expanded="isTooltipOpen('engagement') ? 'true' : 'false'"
                                :aria-controls="tooltipPanelId('engagement')"
                                aria-label="Lihat detail perbandingan KPI engagement"
                            >
                                <i :class="comparisonIcon('engagement')" />
                                <span>{{ comparisonText('engagement') }}</span>
                            </button>
                            <div
                                :id="tooltipPanelId('engagement')"
                                data-tooltip-panel
                                class="pointer-events-none absolute top-full z-20 mt-1 w-52 rounded-md bg-gray-900 px-2 py-1.5 text-[11px] text-white shadow-lg transition-all duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0 group-hover:scale-100"
                                :class="[tooltipPlacementClass('engagement'), isTooltipOpen('engagement') ? 'opacity-100 translate-y-0 scale-100' : 'opacity-0 -translate-y-1 scale-95']"
                                role="tooltip"
                                :aria-hidden="isTooltipOpen('engagement') ? 'false' : 'true'"
                            >
                                <p>{{ tooltipLines('engagement').current }}</p>
                                <p class="mt-0.5 text-gray-200">{{ tooltipLines('engagement').previous }}</p>
                            </div>
                        </div>
                    </article>
                </section>

                <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.08em] text-gray-500">Trend Views vs Interaksi (Dual Axis)</h3>
                        <div class="flex items-center gap-4 text-xs">
                            <span class="inline-flex items-center gap-1 text-indigo-600">
                                <span class="inline-block h-2 w-2 rounded-full bg-indigo-600"></span>
                                Views (max {{ maxViews }})
                            </span>
                            <span class="inline-flex items-center gap-1 text-emerald-600">
                                <span class="inline-block h-2 w-2 rounded-full bg-emerald-600"></span>
                                Interaksi (max {{ maxInteractions }})
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 rounded-lg border border-gray-100 bg-gray-50 p-3">
                        <svg :viewBox="chartViewBox" class="h-56 w-full">
                            <line x1="44" y1="16" x2="44" y2="236" stroke="#d1d5db" stroke-width="1" />
                            <line x1="716" y1="16" x2="716" y2="236" stroke="#d1d5db" stroke-width="1" />
                            <line x1="44" y1="236" x2="716" y2="236" stroke="#d1d5db" stroke-width="1" />

                            <polyline
                                v-if="viewPoints"
                                :points="viewPoints"
                                fill="none"
                                stroke="#4f46e5"
                                stroke-width="3"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                            <polyline
                                v-if="interactionPoints"
                                :points="interactionPoints"
                                fill="none"
                                stroke="#10b981"
                                stroke-width="3"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />

                            <g v-for="tick in xTickLabels" :key="tick.idx">
                                <text
                                    :x="44 + ((716 - 44) * (tick.idx / Math.max((daily_views.length - 1), 1)))"
                                    y="252"
                                    text-anchor="middle"
                                    font-size="10"
                                    fill="#6b7280"
                                >
                                    {{ tick.label }}
                                </text>
                            </g>
                        </svg>
                        <p v-if="daily_views.length === 0" class="text-sm text-gray-500">Belum ada data pada range ini.</p>
                    </div>
                </section>

                <section class="grid gap-4 lg:grid-cols-3">
                    <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-1">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.08em] text-gray-500">Breakdown Interaksi</h3>
                        <ul class="mt-4 space-y-2 text-sm text-gray-700">
                            <li class="flex items-center justify-between">
                                <span>Bookmarks</span>
                                <span class="font-semibold text-gray-900">{{ interaction_breakdown.bookmarks || 0 }}</span>
                            </li>
                            <li class="flex items-center justify-between">
                                <span>Komentar Approved</span>
                                <span class="font-semibold text-gray-900">{{ interaction_breakdown.comments || 0 }}</span>
                            </li>
                            <li class="flex items-center justify-between">
                                <span>Reaksi</span>
                                <span class="font-semibold text-gray-900">{{ interaction_breakdown.reactions || 0 }}</span>
                            </li>
                        </ul>
                    </article>

                    <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.08em] text-gray-500">Top Artikel (Trending Score)</h3>
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-500">
                                        <th class="px-2 py-2">Judul</th>
                                        <th class="px-2 py-2">Kategori</th>
                                        <th class="px-2 py-2">Status</th>
                                        <th class="px-2 py-2">Views</th>
                                        <th class="px-2 py-2">Komentar</th>
                                        <th class="px-2 py-2">Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="article in top_articles" :key="article.id" class="border-b border-gray-100">
                                        <td class="px-2 py-2 font-medium text-gray-900">
                                            <Link :href="route('articles.show', article.slug)" class="hover:text-indigo-600">
                                                {{ article.title }}
                                            </Link>
                                        </td>
                                        <td class="px-2 py-2 text-gray-600">{{ article.category?.name || '-' }}</td>
                                        <td class="px-2 py-2 text-gray-600">{{ article.status }}</td>
                                        <td class="px-2 py-2 text-gray-600">{{ article.view_count }}</td>
                                        <td class="px-2 py-2 text-gray-600">{{ article.comment_count }}</td>
                                        <td class="px-2 py-2 text-gray-900">{{ article.score_trending }}</td>
                                    </tr>
                                    <tr v-if="top_articles.length === 0">
                                        <td colspan="6" class="px-2 py-4 text-center text-gray-500">Belum ada artikel untuk dianalisis.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </article>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
