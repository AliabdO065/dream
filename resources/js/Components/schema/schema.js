// Helpers for the schema-driven section editor. A "field" is what Schema::forClient() sends:
// { name, type: text|textarea|url|select|checkbox|image|list, label, translatable?, options?, fields?, max? }

let counter = 0;
const rowKey = () => 'r' + ++counter;

export function blankValue(f, locales) {
    switch (f.type) {
        case 'checkbox': return false;
        case 'select': return f.options?.[0]?.value ?? '';
        case 'image': return '';
        case 'list': return [];
        default: return f.translatable ? Object.fromEntries(locales.map((l) => [l, ''])) : '';
    }
}

export function blankRow(fields, locales) {
    const row = { _k: rowKey() };
    for (const f of fields) row[f.name] = blankValue(f, locales);
    return row;
}

/** Make server data safe to edit: every field present, every language present, every list row keyed. */
export function hydrate(fields, data, locales) {
    const out = {};
    const src = data && !Array.isArray(data) ? data : {};
    for (const f of fields) {
        const v = src[f.name];
        switch (f.type) {
            case 'checkbox': out[f.name] = !!v && v !== '0' && v !== 0; break;
            case 'select': out[f.name] = f.options.some((o) => o.value === String(v)) ? String(v) : f.options[0]?.value ?? ''; break;
            case 'image': out[f.name] = typeof v === 'string' ? v : ''; break;
            case 'list': out[f.name] = (Array.isArray(v) ? v : []).map((row) => ({ ...hydrate(f.fields, row, locales), _k: rowKey() })); break;
            default:
                if (f.translatable) {
                    const obj = v && typeof v === 'object' ? v : {};
                    out[f.name] = Object.fromEntries(locales.map((l) => [l, typeof obj[l] === 'string' ? obj[l] : '']));
                } else {
                    out[f.name] = typeof v === 'string' ? v : '';
                }
        }
    }
    return out;
}

/**
 * Split editor state into what the server expects:
 *  - content: everything as plain data (images as their stored path)
 *  - uploads: the same shape, but only newly chosen image files
 * Both are built from the same row order, so list indexes line up.
 */
export function serialize(fields, data) {
    const content = {};
    const uploads = {};
    for (const f of fields) {
        const v = data?.[f.name];
        switch (f.type) {
            case 'checkbox': content[f.name] = v ? 1 : 0; break;
            case 'image':
                if (v && typeof v === 'object' && v.file) { content[f.name] = ''; uploads[f.name] = v.file; } else content[f.name] = v || '';
                break;
            case 'list': {
                const rows = (v || []).map((r) => serialize(f.fields, r));
                content[f.name] = rows.map((r) => r.content);
                if (rows.some((r) => Object.keys(r.uploads).length)) uploads[f.name] = rows.map((r) => r.uploads);
                break;
            }
            default: content[f.name] = v ?? (f.translatable ? {} : '');
        }
    }
    return { content, uploads };
}

export const isRtl = (locale) => ['ar', 'he', 'fa', 'ur'].includes(locale);
