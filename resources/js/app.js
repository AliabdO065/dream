import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import AppLayout from './Layouts/AppLayout.vue';
import { applyDocumentLocale } from './i18n';

// Pages live in resources/js/Pages and are named by the controllers (Inertia::render('Manage/Sections/Index')).
// route('name', params) is Ziggy's global helper, defined by the @routes directive in app.blade.php.
const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });

// <html lang/dir> is set correctly on first paint by the server (app.blade.php); Inertia visits after
// that never reload <html>, so keep it in sync with every navigation (locale switch, back/forward, …).
// 'success' fires with the new page on every completed visit; 'navigate' also covers bfcache restores.
router.on('success', (event) => applyDocumentLocale(event.detail.page));
router.on('navigate', (event) => applyDocumentLocale(event.detail.page));

createInertiaApp({
    title: (title) => (title ? `${title} · ERP Sites` : 'ERP Sites'),
    resolve: (name) => {
        const page = pages[`./Pages/${name}.vue`];
        if (!page) throw new Error(`Inertia page not found: ${name}`);
        // every page gets the dashboard layout, except the login screen (it brings its own)
        page.default.layout ??= name.startsWith('Auth/') ? undefined : AppLayout;
        return page;
    },
    setup({ el, App, props, plugin }) {
        applyDocumentLocale(props.initialPage);
        const app = createApp({ render: () => h(App, props) }).use(plugin);
        // Ziggy defines window.route(); templates can only see it if it is registered on the app
        app.config.globalProperties.route = (...args) => window.route(...args);
        app.mount(el);
    },
    progress: { color: '#6366f1', showSpinner: false },
});
