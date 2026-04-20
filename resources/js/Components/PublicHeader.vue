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
        <div class="mx-auto flex w-full max-w-screen-2xl items-center justify-between gap-4 px-4 py-5 sm:px-6 lg:px-8">
            <div class="flex items-center gap-6">
                <Link href="/" class="inline-flex">
                    <img
                        src="/assets/logo/LOGO-EXPERTIA-005.png"
                        alt="Expertia"
                        class="h-8 w-auto sm:h-9"
                    />
                </Link>

            </div>

            <nav v-if="canLogin" class="ml-2 flex items-center gap-2 text-sm">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="rounded-md border border-[#1BD6FF] px-3 py-2 font-medium text-[#0f5f74] hover:bg-[#1BD6FF] hover:text-white"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="rounded-md border border-zinc-300 px-3 py-2 font-medium text-zinc-700 hover:border-[#1BD6FF] hover:text-[#0f5f74]"
                    >
                        Masuk
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="rounded-md bg-[#E80EB5] px-3 py-2 font-medium text-white hover:bg-[#ca0a9d]"
                    >
                        Gabung Sebagai Penulis
                    </Link>
                </template>
            </nav>
        </div>

        <div class="border-t border-zinc-100">
            <div class="mx-auto flex w-full max-w-screen-2xl items-center gap-2 overflow-x-auto px-4 py-3 sm:px-6 lg:px-8">
                <Link
                    :href="route('articles.index')"
                    class="whitespace-nowrap rounded-full bg-[#1BD6FF]/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-[#0f5f74] hover:bg-[#1BD6FF]/20"
                >
                    Semua
                </Link>
                <Link
                    v-for="item in headerCategories"
                    :key="item.label"
                    :href="item.href"
                    class="whitespace-nowrap rounded-full bg-zinc-100 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-zinc-700 hover:bg-zinc-200"
                >
                    {{ item.label }}
                </Link>
            </div>
        </div>
    </header>
</template>
