<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const showingNavigationDropdown = ref(false);
const mobileMenuToggleButton = ref(null);
const isSidebarCollapsed = ref(false);
const openGroups = ref({
    dashboard: true,
    apps: true,
    editorial: true,
    system: true,
});

const hasRole = (role) => (page.props.auth.roles || []).includes(role);
const hasAnyRole = (roles) => roles.some((role) => hasRole(role));
const moderation = page.props.moderation || {};
const unreadNotifications = computed(() => Number(page.props.notifications?.unread_count || 0));
const avatarUrl = computed(() => page.props.auth.user?.avatar_url || null);
const userInitials = computed(() => (page.props.auth.user?.name || 'U').substring(0, 2).toUpperCase());
const isActive = (pattern) => route().current(pattern);

const navItems = computed(() => [
    {
        key: 'dashboard',
        label: 'Dashboard',
        icon: 'fa-solid fa-house',
        visible: true,
        items: [
            {
                label: 'Overview',
                href: route('dashboard'),
                active: isActive('dashboard'),
                icon: 'fa-solid fa-chart-pie',
                badge: null,
            },
        ],
    },
    {
        key: 'apps',
        label: 'Apps',
        icon: 'fa-solid fa-grip',
        visible: true,
        items: [
            {
                label: 'Bookmark',
                href: route('me.bookmarks.index'),
                active: isActive('me.bookmarks.*'),
                icon: 'fa-solid fa-bookmark',
                badge: null,
            },
            {
                label: 'Notifikasi',
                href: route('me.notifications.index'),
                active: isActive('me.notifications.*'),
                icon: 'fa-solid fa-bell',
                badge: unreadNotifications.value > 0 ? unreadNotifications.value : null,
                badgeClass: 'bg-indigo-100 text-indigo-700',
            },
        ],
    },
    {
        key: 'editorial',
        label: 'Editorial',
        icon: 'fa-solid fa-pen-ruler',
        visible: hasAnyRole(['author', 'verified-expert', 'editor', 'moderator', 'admin', 'super-admin']),
        items: [
            {
                label: 'Author',
                href: route('author.articles.index'),
                active: isActive('author.articles.*'),
                icon: 'fa-solid fa-pen-nib',
                visible: hasAnyRole(['author', 'verified-expert', 'editor', 'admin', 'super-admin']),
                badge: null,
            },
            {
                label: 'Insights',
                href: route('author.insights.index'),
                active: isActive('author.insights.*'),
                icon: 'fa-solid fa-chart-column',
                visible: hasAnyRole(['author', 'verified-expert', 'editor', 'admin', 'super-admin']),
                badge: null,
            },
            {
                label: 'Review',
                href: route('editor.reviews.index'),
                active: isActive('editor.reviews.*'),
                icon: 'fa-solid fa-list-check',
                visible: hasAnyRole(['editor', 'admin', 'super-admin']),
                badge: moderation.pending_reviews > 0 ? moderation.pending_reviews : null,
                badgeClass: 'bg-amber-100 text-amber-700',
            },
            {
                label: 'Moderasi',
                href: route('editor.moderation.comments.index'),
                active: isActive('editor.moderation.*'),
                icon: 'fa-solid fa-shield-halved',
                visible: hasAnyRole(['editor', 'moderator', 'admin', 'super-admin']),
                badge:
                    moderation.pending_comments > 0 || moderation.pending_reports > 0
                        ? moderation.pending_comments + moderation.pending_reports
                        : null,
                badgeClass: 'bg-rose-100 text-rose-700',
            },
        ],
    },
    {
        key: 'system',
        label: 'System',
        icon: 'fa-solid fa-gears',
        visible: hasAnyRole(['admin', 'super-admin']),
        items: [
            {
                label: 'Admin',
                href: route('admin.dashboard'),
                active: isActive('admin.dashboard'),
                icon: 'fa-solid fa-chart-line',
                badge: null,
            },
            {
                label: 'Audit Log',
                href: route('admin.audit-logs.index'),
                active: isActive('admin.audit-logs.*'),
                icon: 'fa-solid fa-clipboard-list',
                badge: null,
            },
            {
                label: 'Role & Permission',
                href: route('admin.roles.index'),
                active: isActive('admin.roles.*'),
                icon: 'fa-solid fa-user-shield',
                badge: null,
            },
        ],
    },
]);

const visibleGroups = computed(() =>
    navItems.value
        .filter((group) => group.visible)
        .map((group) => ({
            ...group,
            items: (group.items || []).filter((item) => item.visible !== false),
        }))
        .filter((group) => group.items.length > 0),
);

const flatVisibleItems = computed(() => visibleGroups.value.flatMap((group) => group.items));

const isGroupActive = (group) => group.items.some((item) => item.active);

const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
};

const toggleGroup = (groupKey) => {
    openGroups.value[groupKey] = !openGroups.value[groupKey];
};

const handleGroupKeydown = (group, event) => {
    if (event.key === 'ArrowRight') {
        openGroups.value[group.key] = true;
        event.preventDefault();
        return;
    }

    if (event.key === 'ArrowLeft') {
        openGroups.value[group.key] = false;
        event.preventDefault();
        return;
    }

    if (event.key === 'ArrowDown') {
        if (!openGroups.value[group.key]) {
            openGroups.value[group.key] = true;
        }

        requestAnimationFrame(() => {
            const target = document.querySelector(`[data-group-key="${group.key}"] [data-group-link]`);
            target?.focus();
        });
        event.preventDefault();
        return;
    }

    if (event.key === 'Escape') {
        openGroups.value[group.key] = false;
        event.preventDefault();
    }
};

const getDesktopMenuFocusableElements = () =>
    Array.from(document.querySelectorAll('[data-desktop-sidebar] [data-menu-focusable="true"]')).filter(
        (element) => !element.hasAttribute('disabled'),
    );

const moveFocusInMenu = (currentElement, direction) => {
    const focusables = getDesktopMenuFocusableElements();
    if (!focusables.length) {
        return;
    }

    const currentIndex = focusables.indexOf(currentElement);
    if (currentIndex === -1) {
        focusables[0]?.focus();
        return;
    }

    const nextIndex = (currentIndex + direction + focusables.length) % focusables.length;
    focusables[nextIndex]?.focus();
};

const getMobileMenuFocusableElements = () =>
    Array.from(document.querySelectorAll('[data-mobile-menu] [data-mobile-focusable="true"]')).filter(
        (element) => !element.hasAttribute('disabled'),
    );

const moveFocusInMobileMenu = (currentElement, direction) => {
    const focusables = getMobileMenuFocusableElements();
    if (!focusables.length) {
        return;
    }

    const currentIndex = focusables.indexOf(currentElement);
    if (currentIndex === -1) {
        focusables[0]?.focus();
        return;
    }

    const nextIndex = (currentIndex + direction + focusables.length) % focusables.length;
    focusables[nextIndex]?.focus();
};

const closeMobileMenu = (restoreFocus = true) => {
    showingNavigationDropdown.value = false;
    requestAnimationFrame(() => {
        if (restoreFocus && mobileMenuToggleButton.value instanceof HTMLElement) {
            mobileMenuToggleButton.value.focus();
        }
    });
};

const handleMobileMenuContainerKeydown = (event) => {
    if (event.key === 'Escape') {
        closeMobileMenu();
        event.preventDefault();
        return;
    }

    if (event.key !== 'Tab' || !showingNavigationDropdown.value) {
        return;
    }

    const focusables = getMobileMenuFocusableElements();
    if (!focusables.length) {
        return;
    }

    const first = focusables[0];
    const last = focusables[focusables.length - 1];
    const activeElement = document.activeElement;

    if (event.shiftKey && activeElement === first) {
        event.preventDefault();
        last.focus();
        return;
    }

    if (!event.shiftKey && activeElement === last) {
        event.preventDefault();
        first.focus();
    }
};

const handleMobileMenuKeydown = (event) => {
    const target = event.currentTarget;
    if (!(target instanceof HTMLElement)) {
        return;
    }

    if (event.key === 'ArrowDown') {
        moveFocusInMobileMenu(target, 1);
        event.preventDefault();
        return;
    }

    if (event.key === 'ArrowUp') {
        moveFocusInMobileMenu(target, -1);
        event.preventDefault();
        return;
    }

    if (event.key === 'Home') {
        getMobileMenuFocusableElements()[0]?.focus();
        event.preventDefault();
        return;
    }

    if (event.key === 'End') {
        const items = getMobileMenuFocusableElements();
        items[items.length - 1]?.focus();
        event.preventDefault();
        return;
    }

    if (event.key === 'Escape') {
        closeMobileMenu();
        event.preventDefault();
    }
};

const handleMenuItemKeydown = (event) => {
    const target = event.currentTarget;
    if (!(target instanceof HTMLElement)) {
        return;
    }

    if (event.key === 'ArrowDown') {
        moveFocusInMenu(target, 1);
        event.preventDefault();
        return;
    }

    if (event.key === 'ArrowUp') {
        moveFocusInMenu(target, -1);
        event.preventDefault();
        return;
    }

    if (event.key === 'Home') {
        getDesktopMenuFocusableElements()[0]?.focus();
        event.preventDefault();
        return;
    }

    if (event.key === 'End') {
        const items = getDesktopMenuFocusableElements();
        items[items.length - 1]?.focus();
        event.preventDefault();
        return;
    }

    if (event.key === 'Escape') {
        const parentGroup = target.closest('[data-group-key]');
        const groupKey = parentGroup?.getAttribute('data-group-key');
        if (groupKey && openGroups.value[groupKey]) {
            openGroups.value[groupKey] = false;
            requestAnimationFrame(() => {
                const trigger = parentGroup.querySelector('[data-group-trigger]');
                if (trigger instanceof HTMLElement) {
                    trigger.focus();
                }
            });
        }
        event.preventDefault();
    }
};

const onSubmenuBeforeEnter = (el) => {
    el.style.height = '0px';
    el.style.opacity = '0';
    el.style.transform = 'translateY(-4px)';
};

const onSubmenuEnter = (el) => {
    el.style.transition = 'height 220ms ease, opacity 180ms ease, transform 220ms ease';
    el.style.height = `${el.scrollHeight}px`;
    el.style.opacity = '1';
    el.style.transform = 'translateY(0)';
};

const onSubmenuAfterEnter = (el) => {
    el.style.height = 'auto';
    el.style.transition = '';
};

const onSubmenuBeforeLeave = (el) => {
    el.style.height = `${el.scrollHeight}px`;
    el.style.opacity = '1';
    el.style.transform = 'translateY(0)';
};

const onSubmenuLeave = (el) => {
    void el.offsetHeight;
    el.style.transition = 'height 180ms ease, opacity 140ms ease, transform 180ms ease';
    el.style.height = '0px';
    el.style.opacity = '0';
    el.style.transform = 'translateY(-4px)';
};

const onSubmenuAfterLeave = (el) => {
    el.style.transition = '';
};

const baseLinkClass = (active, collapsed = false) => {
    const layout = collapsed
        ? 'group relative flex items-center justify-center rounded-xl px-2 py-2.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/70 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900'
        : 'group relative flex items-center justify-between rounded-xl px-3 py-2.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/70 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900';

    if (active) {
        return `${layout} bg-indigo-50 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-200`;
    }

    return `${layout} text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white`;
};

onMounted(() => {
    isSidebarCollapsed.value = localStorage.getItem('expertia.sidebar_collapsed') === '1';

    const savedGroups = localStorage.getItem('expertia.open_groups');
    if (savedGroups) {
        try {
            openGroups.value = { ...openGroups.value, ...JSON.parse(savedGroups) };
        } catch {
            // ignore broken JSON from localStorage
        }
    }
});

onBeforeUnmount(() => {
    document.body.style.overflow = '';
});

watch(isSidebarCollapsed, (value) => {
    localStorage.setItem('expertia.sidebar_collapsed', value ? '1' : '0');
});

watch(
    openGroups,
    (value) => {
        localStorage.setItem('expertia.open_groups', JSON.stringify(value));
    },
    { deep: true },
);

watch(showingNavigationDropdown, (isOpen) => {
    document.body.style.overflow = isOpen ? 'hidden' : '';

    if (!isOpen) {
        return;
    }

    requestAnimationFrame(() => {
        getMobileMenuFocusableElements()[0]?.focus();
    });
});

watch(
    () => page.url,
    () => {
        if (showingNavigationDropdown.value) {
            closeMobileMenu(false);
        }
    },
);
</script>

<template>
    <div class="min-h-screen bg-[#f6f7fb] text-gray-900 dark:bg-[#0f172a] dark:text-gray-100">
        <div class="flex min-h-screen">
            <aside
                :class="isSidebarCollapsed ? 'w-20' : 'w-72'"
                data-desktop-sidebar
                class="hidden shrink-0 border-r border-gray-200 bg-white transition-all duration-200 dark:border-gray-800 dark:bg-[#111827] lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col"
            >
                <div class="flex h-16 items-center justify-between border-b border-gray-100 px-5 dark:border-gray-800">
                    <Link :href="route('dashboard')" class="inline-flex items-center gap-2">
                        <ApplicationLogo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-100" />
                    </Link>
                    <button
                        @click="toggleSidebar"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                    >
                        <i class="fa-solid fa-angles-left text-xs transition-transform" :class="isSidebarCollapsed ? 'rotate-180' : ''"></i>
                    </button>
                </div>

                <div class="flex-1 space-y-4 overflow-y-auto px-3 py-4">
                    <template v-if="!isSidebarCollapsed">
                        <div v-for="group in visibleGroups" :key="group.key" class="space-y-1" :data-group-key="group.key">
                            <button
                                type="button"
                                @click="toggleGroup(group.key)"
                                @keydown="handleGroupKeydown(group, $event)"
                                :aria-expanded="openGroups[group.key] ? 'true' : 'false'"
                                data-group-trigger
                                data-menu-focusable="true"
                                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-400 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/70 focus-visible:ring-offset-2 dark:hover:bg-gray-800 dark:focus-visible:ring-offset-gray-900"
                            >
                                <span class="inline-flex items-center gap-2">
                                    <i :class="group.icon"></i>
                                    {{ group.label }}
                                </span>
                                <i class="fa-solid fa-chevron-down text-[10px] transition-transform" :class="openGroups[group.key] ? 'rotate-180' : ''"></i>
                            </button>

                            <Transition
                                @before-enter="onSubmenuBeforeEnter"
                                @enter="onSubmenuEnter"
                                @after-enter="onSubmenuAfterEnter"
                                @before-leave="onSubmenuBeforeLeave"
                                @leave="onSubmenuLeave"
                                @after-leave="onSubmenuAfterLeave"
                            >
                            <div v-if="openGroups[group.key]" class="space-y-1 overflow-hidden pb-1">
                                <Link
                                    v-for="item in group.items"
                                    :key="item.href"
                                    :href="item.href"
                                    data-group-link
                                    data-menu-focusable="true"
                                    @keydown="handleMenuItemKeydown"
                                    :class="baseLinkClass(item.active)"
                                >
                                    <span v-if="item.active" class="absolute left-0 top-1 bottom-1 w-1 rounded-r-full bg-gradient-to-b from-indigo-500 to-fuchsia-500"></span>
                                    <span class="inline-flex items-center gap-3">
                                        <i :class="[item.icon, item.active ? 'text-indigo-600 dark:text-indigo-300' : 'text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-200']"></i>
                                        <span class="text-sm font-medium">{{ item.label }}</span>
                                    </span>
                                    <span
                                        v-if="item.badge !== null"
                                        :class="item.badgeClass || 'bg-gray-100 text-gray-700'"
                                        class="rounded px-2 py-0.5 text-xs font-semibold"
                                    >
                                        {{ item.badge }}
                                    </span>
                                </Link>
                            </div>
                            </Transition>
                        </div>
                    </template>

                    <template v-else>
                        <Link
                            v-for="item in flatVisibleItems"
                            :key="item.href"
                            :href="item.href"
                            :class="baseLinkClass(item.active, true)"
                            data-menu-focusable="true"
                            @keydown="handleMenuItemKeydown"
                            :title="item.label"
                        >
                            <span v-if="item.active" class="absolute left-0 top-1 bottom-1 w-1 rounded-r-full bg-gradient-to-b from-indigo-500 to-fuchsia-500"></span>
                            <i :class="[item.icon, item.active ? 'text-indigo-600 dark:text-indigo-300' : 'text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-200']"></i>
                            <span class="pointer-events-none absolute left-full top-1/2 z-30 ml-2 -translate-y-1/2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs font-medium text-white opacity-0 shadow transition group-hover:opacity-100">
                                {{ item.label }}
                            </span>
                        </Link>
                    </template>
                </div>

            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="sticky top-0 z-20 border-b border-gray-200/90 bg-white/95 shadow-sm backdrop-blur-xl dark:border-gray-800 dark:bg-[#0b1220]/95">
                    <div class="flex h-16 items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                        <div class="flex items-center gap-3">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                ref="mobileMenuToggleButton"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white lg:hidden"
                                :aria-expanded="showingNavigationDropdown ? 'true' : 'false'"
                                aria-controls="mobile-nav-panel"
                            >
                                <i class="fa-solid fa-bars"></i>
                            </button>
                            <button
                                @click="toggleSidebar"
                                class="hidden h-9 w-9 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white lg:inline-flex"
                            >
                                <i class="fa-solid fa-bars-staggered text-sm"></i>
                            </button>
                        </div>

                        <div class="flex items-center">
                            <Dropdown align="right" width="56">
                                <template #trigger>
                                    <button
                                        type="button"
                                        class="inline-flex h-10 w-10 items-center justify-center overflow-hidden rounded-full border border-indigo-100 bg-indigo-50 text-xs font-semibold text-indigo-700 ring-2 ring-white transition hover:bg-indigo-100 focus:outline-none"
                                    >
                                        <img v-if="avatarUrl" :src="avatarUrl" alt="Profile avatar" class="h-full w-full object-cover">
                                        <span v-else>{{ userInitials }}</span>
                                    </button>
                                </template>

                                <template #content>
                                    <div class="border-b border-gray-100 px-4 py-3">
                                        <p class="text-sm font-semibold text-gray-900">{{ $page.props.auth.user.name }}</p>
                                        <p class="text-xs text-gray-500">{{ $page.props.auth.user.email }}</p>
                                    </div>
                                    <DropdownLink :href="route('profile.edit')">
                                        Profile
                                    </DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button">
                                        Logout
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </header>

                <transition
                    enter-active-class="transition-opacity duration-200"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition-opacity duration-150"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-if="showingNavigationDropdown"
                        class="fixed inset-0 z-30 bg-zinc-950/45 backdrop-blur-[1px] lg:hidden"
                        @click="closeMobileMenu(false)"
                    ></div>
                </transition>

                <transition
                    enter-active-class="transform transition duration-220 ease-out"
                    enter-from-class="-translate-y-2 scale-[0.99] opacity-0"
                    enter-to-class="translate-y-0 scale-100 opacity-100"
                    leave-active-class="transform transition duration-150 ease-in"
                    leave-from-class="translate-y-0 scale-100 opacity-100"
                    leave-to-class="-translate-y-2 scale-[0.99] opacity-0"
                >
                    <div
                        v-if="showingNavigationDropdown"
                        id="mobile-nav-panel"
                        data-mobile-menu
                        @keydown="handleMobileMenuContainerKeydown"
                        class="fixed inset-x-0 top-[4.25rem] z-40 mx-3 rounded-2xl border border-gray-200/90 bg-white/95 px-4 py-3 shadow-2xl backdrop-blur-xl dark:border-gray-800 dark:bg-[#111827]/95 lg:hidden"
                    >
                        <div class="space-y-1">
                            <Link
                                v-for="item in flatVisibleItems"
                                :key="`mobile-${item.href}`"
                                :href="item.href"
                                data-mobile-focusable="true"
                                @click="closeMobileMenu(false)"
                                @keydown="handleMobileMenuKeydown"
                                :class="baseLinkClass(item.active)"
                            >
                                <span v-if="item.active" class="absolute left-0 top-1 bottom-1 w-1 rounded-r-full bg-gradient-to-b from-indigo-500 to-fuchsia-500"></span>
                                <span class="inline-flex items-center gap-3">
                                    <i :class="[item.icon, item.active ? 'text-indigo-600 dark:text-indigo-300' : 'text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-200']"></i>
                                    {{ item.label }}
                                </span>
                            </Link>
                        </div>
                    </div>
                </transition>

                <header v-if="$slots.header" class="border-b border-gray-200/80 bg-white/95 shadow-sm backdrop-blur-xl dark:border-gray-800 dark:bg-[#111827]/95">
                    <div class="mx-auto max-w-screen-2xl px-4 py-4 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header>

                <main class="min-w-0 flex-1">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
