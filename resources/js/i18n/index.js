import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import en from './en';
import ar from './ar';

const dictionaries = { en, ar };

function get(obj, path) {
    return path.split('.').reduce((o, k) => (o == null ? undefined : o[k]), obj);
}

function interpolate(str, params) {
    if (!params) return str;
    return str.replace(/\{(\w+)\}/g, (_, k) => (params[k] !== undefined ? params[k] : `{${k}}`));
}

/**
 * Dashboard translations (English/Arabic). The active language comes from the shared Inertia props
 * `locale`/`direction` (server-set, session-based — see SetDashboardLocale). Keys are dot paths into
 * resources/js/i18n/{en,ar}.js; `{placeholder}` tokens are replaced from the `params` object.
 * A missing key falls back to English, then to the key itself (so the UI never shows blank text).
 */
export function useI18n() {
    const page = usePage();
    const locale = computed(() => (page.props.locale === 'ar' ? 'ar' : 'en'));
    const dir = computed(() => (page.props.direction === 'rtl' ? 'rtl' : 'ltr'));

    function t(key, params) {
        const value = get(dictionaries[locale.value], key) ?? get(dictionaries.en, key);
        if (value === undefined) {
            if (import.meta.env.DEV) console.warn(`[i18n] missing key: ${key}`);

            return key;
        }
        if (Array.isArray(value)) {
            return value.map((v) => interpolate(v, params));
        }

        return interpolate(String(value), params);
    }

    return { t, locale, dir };
}

export function applyDocumentLocale(page) {
    const locale = page?.props?.locale === 'ar' ? 'ar' : 'en';
    const dir = page?.props?.direction === 'rtl' ? 'rtl' : 'ltr';
    document.documentElement.lang = locale;
    document.documentElement.dir = dir;
    document.body.dir = dir;
}
