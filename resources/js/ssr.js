// Render halaman publik di server (Inertia SSR) agar isi artikel ada di HTML awal:
// mesin pencari dan crawler AI umumnya tidak menjalankan JavaScript.
// Dijalankan oleh `php artisan inertia:start-ssr` (lihat README, bagian Deploy).
import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createSSRApp, h } from 'vue';
import { renderToString } from 'vue/server-renderer';
import { route as ziggyRoute, ZiggyVue } from '../../vendor/tightenco/ziggy';

createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        title: (title) => `${title}`,
        resolve: (name) =>
            resolvePageComponent(
                `./Pages/${name}.vue`,
                import.meta.glob('./Pages/**/*.vue'),
            ),
        setup({ App, props, plugin }) {
            const ziggy = {
                ...page.props.ziggy,
                location: new URL(page.props.ziggy.location),
            };

            // Komponen memanggil route() di <script setup>; di browser fungsi itu
            // disediakan @routes, di server disediakan di sini.
            globalThis.route = (name, params, absolute, config = ziggy) =>
                ziggyRoute(name, params, absolute, config);

            return createSSRApp({ render: () => h(App, props) })
                .use(plugin)
                .use(ZiggyVue, ziggy);
        },
    }),
);
