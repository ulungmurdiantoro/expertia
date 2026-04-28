<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
        default: true,
    },
    canRegister: {
        type: Boolean,
        default: true,
    },
});

const headerCategories = [
    { label: 'Pendidikan', href: route('articles.index', { category: 'pendidikan' }) },
    { label: 'Sains', href: route('articles.index', { category: 'sains' }) },
    { label: 'Teknologi', href: route('articles.index', { category: 'teknologi' }) },
    { label: 'Ekonomi', href: route('articles.index', { category: 'ekonomi' }) },
    { label: 'Kebijakan', href: route('articles.index', { category: 'kebijakan' }) },
];
</script>

<template>
    <header class="border-b border-zinc-200 bg-white">
        <div class="h-1 bg-gradient-to-r from-[#1BD6FF] via-[#E80EB5] to-[#FF7950]"></div>

        <div class="mx-auto flex w-full max-w-screen-2xl flex-col gap-4 px-4 py-5 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between gap-4">
                <Link href="/" class="group inline-flex flex-col leading-none">
                    <img
                        src="/assets/logo/LOGO-EXPERTIA-005.png"
                        alt="Expertia"
                        class="hidden h-10 w-auto sm:block"
                    />
                    <img
                        src="/assets/logo/LOGO-EXPERTIA-003.png"
                        alt="Expertia"
                        class="h-9 w-auto sm:hidden"
                    />
                </Link>

                <nav v-if="canLogin" class="flex flex-wrap items-center justify-end gap-x-4 gap-y-2 text-sm">
                    <Link :href="route('articles.trending')" class="font-medium text-zinc-700 hover:text-[#0f5f74]">
                        Trending
                    </Link>
                    <Link v-if="$page.props.auth.user" :href="route('me.feed')" class="font-medium text-zinc-700 hover:text-[#0f5f74]">
                        Feed
                    </Link>
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="border-l border-zinc-300 pl-4 font-medium text-zinc-950 hover:text-[#0f5f74]">
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link :href="route('login')" class="font-medium text-zinc-700 hover:text-[#0f5f74]">
                            Masuk
                        </Link>
                        <Link v-if="canRegister" :href="route('register')" class="font-medium text-zinc-700 hover:text-[#0f5f74]">
                            Daftar
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register.author')"
                            class="border-l border-zinc-300 pl-4 font-semibold text-[#E80EB5] hover:text-zinc-950"
                        >
                            Menulis
                        </Link>
                    </template>
                </nav>
            </div>

            <nav class="flex items-center gap-5 overflow-x-auto border-t border-zinc-200 pt-3 text-[13px] font-semibold uppercase tracking-[0.08em] text-zinc-600">
                <Link :href="route('articles.index')" class="shrink-0 text-zinc-950 hover:text-[#0f5f74]">
                    Semua
                </Link>
                <Link
                    v-for="item in headerCategories"
                    :key="item.label"
                    :href="item.href"
                    class="shrink-0 hover:text-[#0f5f74]"
                >
                    {{ item.label }}
                </Link>
            </nav>
        </div>
    </header>
</template>
