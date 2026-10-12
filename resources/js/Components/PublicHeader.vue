<script setup>
import { contact } from '@/site';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const page = usePage();
const logoFailed = ref(false);
const menu = ref(null);

const navItems = computed(() => [
    { label: 'Artikel', href: route('articles.index'), key: 'articles' },
    { label: 'Riset & Inovasi', href: route('articles.index', { category: 'riset-inovasi' }), key: 'riset-inovasi' },
    { label: 'Perspektif Pakar', href: route('articles.index', { category: 'perspektif-pakar' }), key: 'perspektif-pakar' },
    { label: 'Tentang', href: route('tentang'), key: 'tentang' },
]);

const currentKey = computed(() => {
    const url = new URL(page.url, 'http://x');

    if (url.pathname === '/articles') {
        return url.searchParams.get('category') || 'articles';
    }

    return url.pathname.replace(/^\//, '');
});

let stopListening = null;

onMounted(() => {
    stopListening = router.on('navigate', () => {
        if (menu.value) {
            menu.value.open = false;
        }
    });
});

onUnmounted(() => stopListening?.());
</script>

<template>
    <div class="announce">
        <div class="wrap">
            <span class="dot" aria-hidden="true"></span>
            <span>Program Kontributor Perdana dibuka. Riset, paten, atau kepakaran Anda kami bantu publikasikan, gratis.</span>
            <a :href="contact.registrationUrl" target="_blank" rel="noopener">Daftar →</a>
        </div>
    </div>

    <header class="site-header">
        <div class="wrap">
            <Link class="logo" :class="{ noimg: logoFailed }" href="/" aria-label="Expertia, ke beranda">
                <img
                    src="/assets/logo/LOGO-EXPERTIA-005.png"
                    alt="Expertia"
                    width="197"
                    height="30"
                    @error="logoFailed = true"
                />
                <span class="wordmark">expertia</span>
            </Link>

            <nav class="nav" aria-label="Navigasi utama">
                <Link
                    v-for="item in navItems"
                    :key="item.key"
                    :href="item.href"
                    :aria-current="currentKey === item.key ? 'page' : undefined"
                >
                    {{ item.label }}
                </Link>
                <Link v-if="page.props.auth.user" :href="route('dashboard')">Dashboard</Link>
                <Link class="btn" :href="route('kontributor')">Jadi Kontributor</Link>
            </nav>

            <details ref="menu" class="menu">
                <summary aria-label="Buka menu">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                        <path d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                </summary>
                <div class="menu-panel">
                    <Link
                        v-for="item in navItems"
                        :key="item.key"
                        :href="item.href"
                        :aria-current="currentKey === item.key ? 'page' : undefined"
                    >
                        {{ item.label }}
                    </Link>
                    <Link :href="route('kontak')">Kontak</Link>
                    <Link v-if="page.props.auth.user" :href="route('dashboard')">Dashboard</Link>
                    <Link class="btn" :href="route('kontributor')">Jadi Kontributor</Link>
                </div>
            </details>
        </div>
    </header>
</template>
