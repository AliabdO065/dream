<script setup>
import { computed, ref, watch } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Check, CircleCheck, CircleX, EyeOff, Globe, LoaderCircle, Pencil, Rocket } from 'lucide-vue-next';
import PageHeader from '../../../Components/ui/PageHeader.vue';
import Btn from '../../../Components/ui/Btn.vue';
import Field from '../../../Components/ui/Field.vue';
import Toggle from '../../../Components/ui/Toggle.vue';
import { kindIcon, sectionIcon } from '../../../Composables/icons';
import { useI18n } from '../../../i18n';

const props = defineProps({ kinds: Array, presets: Object, languages: Array, colors: Array, baseUrl: String });
const { t } = useI18n();
const page = usePage();

const form = useForm({
    kind: '', name: '', slug: '', business_type: '',
    default_locale: page.props.locale === 'ar' ? 'ar' : 'en', // most people build sites in the language they work in
    brand: props.colors[0], publish: false, owner_email: '', owner_name: '',
});

// ---- 1. kind of business: picks the starting layout
const kind = computed(() => props.kinds.find((k) => k.value === form.kind));
const sections = computed(() => props.presets[kind.value?.preset ?? 'blank'] ?? []);

// ---- 2. the address: made from the name by the server (it transliterates Arabic etc.), until someone edits it
const slugState = ref('idle'); // idle | checking | ok | taken
const slugMessage = ref('');
const editingSlug = ref(false);
const slugTouched = ref(false);
let timer;
let seq = 0;

async function ask(params) {
    const mine = ++seq;
    slugState.value = 'checking';
    try {
        const res = await fetch(route('admin.clients.slug', params), { headers: { Accept: 'application/json' } });
        const data = await res.json();
        if (mine !== seq) return; // a newer question was asked meanwhile
        if (params.name !== undefined && !slugTouched.value) form.slug = data.slug; // never overwrite what's being typed
        slugState.value = data.available ? 'ok' : 'taken';
        slugMessage.value = data.message ?? '';
    } catch {
        if (mine === seq) slugState.value = 'idle'; // the server checks again on save anyway
    }
}
watch(() => [form.name, form.default_locale], () => {
    if (slugTouched.value) return;
    clearTimeout(timer);
    if (!form.name.trim()) { form.slug = ''; slugState.value = 'idle'; return; }
    timer = setTimeout(() => ask({ name: form.name, locale: form.default_locale }), 350);
});
function onSlugInput() {
    slugTouched.value = true;
    form.slug = form.slug.toLowerCase().replace(/[^a-z0-9-]+/g, '-').replace(/-{2,}/g, '-');
    clearTimeout(timer);
    if (!form.slug) { // cleared: go back to the address made from the name
        slugTouched.value = false;
        if (form.name.trim()) timer = setTimeout(() => ask({ name: form.name, locale: form.default_locale }), 350);
        return;
    }
    timer = setTimeout(() => ask({ slug: form.slug.replace(/^-+|-+$/g, '') }), 350);
}
const address = computed(() => `${props.baseUrl}/${form.slug || '…'}`);

// ---- languages: the two most used as chips, the rest in a list
const quickLangs = computed(() => props.languages.filter((l) => ['ar', 'en'].includes(l.value)));
const otherLangs = computed(() => props.languages.filter((l) => !['ar', 'en'].includes(l.value)));

const canSubmit = computed(() => form.name.trim() && form.owner_email.trim() && slugState.value !== 'taken' && slugState.value !== 'checking');
const submit = () => form
    .transform((d) => ({ ...d, slug: d.slug || null, status: d.publish ? 'active' : 'draft', business_type: d.kind === 'other' ? d.business_type : null }))
    .post(route('admin.clients.store'));
</script>

<template>
    <Head :title="t('admin.clients.create.title')" />
    <a :href="route('admin.clients.index')" class="mb-4 inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800"><ArrowLeft class="size-4 rtl:rotate-180" /> {{ t('admin.clients.create.allClients') }}</a>
    <PageHeader :title="t('admin.clients.create.title')" :description="t('admin.clients.create.description')" />

    <form class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]" @submit.prevent="submit">
        <div class="space-y-6">
            <!-- 1. kind -->
            <section class="rounded-2xl bg-white p-5 shadow-card ring-1 ring-slate-200/70 sm:p-6">
                <h2 class="flex items-center gap-3 text-base font-semibold text-slate-900"><span class="step">1</span> {{ t('admin.clients.create.kindTitle') }}</h2>
                <p class="mt-1 ms-10 text-sm text-slate-500">{{ t('admin.clients.create.kindHint') }}</p>
                <div class="mt-4 grid grid-cols-2 gap-2.5 sm:grid-cols-3 xl:grid-cols-4">
                    <button v-for="k in kinds" :key="k.value" type="button" :aria-pressed="form.kind === k.value"
                            class="group flex items-center gap-3 rounded-xl p-3 text-start ring-1 transition"
                            :class="form.kind === k.value ? 'bg-indigo-50 ring-2 ring-indigo-500' : 'ring-slate-200 hover:bg-slate-50 hover:ring-slate-300'"
                            @click="form.kind = k.value">
                        <span class="grid size-10 shrink-0 place-items-center rounded-lg transition" :class="form.kind === k.value ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-500 group-hover:text-indigo-600'">
                            <component :is="kindIcon(k.icon)" class="size-5" />
                        </span>
                        <span class="text-sm leading-snug font-medium text-slate-800">{{ k.label }}</span>
                    </button>
                </div>
                <div v-if="form.kind === 'other'" class="mt-4 max-w-sm">
                    <Field :label="t('admin.clients.create.otherKind')" :error="form.errors.business_type">
                        <input v-model="form.business_type" class="input" maxlength="100" :placeholder="t('admin.clients.create.otherKindPlaceholder')">
                    </Field>
                </div>
            </section>

            <!-- 2. the business -->
            <section class="rounded-2xl bg-white p-5 shadow-card ring-1 ring-slate-200/70 sm:p-6">
                <h2 class="flex items-center gap-3 text-base font-semibold text-slate-900"><span class="step">2</span> {{ t('admin.clients.create.businessTitle') }}</h2>
                <div class="mt-4 space-y-5">
                    <Field :label="t('admin.clients.create.name')" :error="form.errors.name" required>
                        <input v-model="form.name" class="input py-3 text-lg" maxlength="150" required autofocus :placeholder="t('admin.clients.create.namePlaceholder')">
                    </Field>

                    <div>
                        <p class="mb-1.5 text-sm font-medium text-slate-700">{{ t('admin.clients.create.address') }}</p>
                        <div v-if="!editingSlug" class="flex flex-wrap items-center gap-2 rounded-lg bg-slate-50 px-3 py-2.5 text-sm ring-1 ring-slate-200">
                            <Globe class="size-4 shrink-0 text-slate-400" />
                            <span class="min-w-0 flex-1 truncate font-mono text-slate-700" dir="ltr">{{ address }}</span>
                            <LoaderCircle v-if="slugState === 'checking'" class="size-4 animate-spin text-slate-400" />
                            <CircleCheck v-else-if="slugState === 'ok'" class="size-4 text-emerald-500" />
                            <CircleX v-else-if="slugState === 'taken'" class="size-4 text-rose-500" />
                            <button type="button" class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-800" @click="editingSlug = true"><Pencil class="size-3.5" /> {{ t('admin.clients.create.changeAddress') }}</button>
                        </div>
                        <div v-else class="flex items-center gap-2" dir="ltr">
                            <span class="shrink-0 font-mono text-sm text-slate-400">{{ baseUrl }}/</span>
                            <input v-model="form.slug" class="input font-mono" maxlength="60" autofocus @input="onSlugInput">
                            <LoaderCircle v-if="slugState === 'checking'" class="size-4 shrink-0 animate-spin text-slate-400" />
                            <CircleCheck v-else-if="slugState === 'ok'" class="size-4 shrink-0 text-emerald-500" />
                            <CircleX v-else-if="slugState === 'taken'" class="size-4 shrink-0 text-rose-500" />
                        </div>
                        <p v-if="form.errors.slug || (slugState === 'taken' && slugMessage)" class="mt-1.5 text-sm text-rose-600">{{ form.errors.slug || slugMessage }}</p>
                        <p v-else class="mt-1.5 text-xs text-slate-500">{{ t('admin.clients.create.addressHint') }}</p>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <p class="mb-1.5 text-sm font-medium text-slate-700">{{ t('admin.clients.create.language') }}</p>
                            <div class="flex flex-wrap items-center gap-2">
                                <button v-for="l in quickLangs" :key="l.value" type="button" class="chip" :class="form.default_locale === l.value && 'chip-on'" @click="form.default_locale = l.value">{{ l.label }}</button>
                                <select class="input w-auto py-1.5 text-sm" :class="!['ar', 'en'].includes(form.default_locale) && 'ring-2 ring-indigo-500'" :value="['ar', 'en'].includes(form.default_locale) ? '' : form.default_locale"
                                        :aria-label="t('admin.clients.create.otherLanguage')" @change="$event.target.value && (form.default_locale = $event.target.value)">
                                    <option value="">{{ t('admin.clients.create.otherLanguage') }}</option>
                                    <option v-for="l in otherLangs" :key="l.value" :value="l.value">{{ l.label }}</option>
                                </select>
                            </div>
                            <p v-if="form.errors.default_locale" class="mt-1.5 text-sm text-rose-600">{{ form.errors.default_locale }}</p>
                        </div>
                        <div>
                            <p class="mb-1.5 text-sm font-medium text-slate-700">{{ t('admin.clients.create.color') }}</p>
                            <div class="flex flex-wrap items-center gap-2">
                                <button v-for="c in colors" :key="c" type="button" class="grid size-8 place-items-center rounded-full ring-offset-2 transition" :class="form.brand === c && 'ring-2 ring-slate-900'"
                                        :style="{ background: c }" :aria-label="c" :aria-pressed="form.brand === c" @click="form.brand = c">
                                    <Check v-if="form.brand === c" class="size-4 text-white" />
                                </button>
                                <label class="relative size-8 cursor-pointer overflow-hidden rounded-full ring-1 ring-slate-300" :title="t('admin.clients.create.customColor')"
                                       :style="{ background: colors.includes(form.brand) ? 'conic-gradient(red, yellow, lime, aqua, blue, magenta, red)' : form.brand }">
                                    <input v-model="form.brand" type="color" class="absolute inset-0 cursor-pointer opacity-0" :aria-label="t('admin.clients.create.customColor')">
                                </label>
                            </div>
                            <p v-if="form.errors.brand" class="mt-1.5 text-sm text-rose-600">{{ form.errors.brand }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. the owner -->
            <section class="rounded-2xl bg-white p-5 shadow-card ring-1 ring-slate-200/70 sm:p-6">
                <h2 class="flex items-center gap-3 text-base font-semibold text-slate-900"><span class="step">3</span> {{ t('admin.clients.create.ownerTitle') }}</h2>
                <p class="mt-1 ms-10 text-sm text-slate-500">{{ t('admin.clients.create.ownerHint') }}</p>
                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    <Field :label="t('admin.clients.create.ownerEmail')" :error="form.errors.owner_email" required><input v-model="form.owner_email" type="email" class="input" dir="ltr" required placeholder="owner@example.com"></Field>
                    <Field :label="t('admin.clients.create.ownerName')" :error="form.errors.owner_name"><input v-model="form.owner_name" class="input" maxlength="120" :placeholder="t('admin.clients.create.ownerNamePlaceholder')"></Field>
                </div>
            </section>
        </div>

        <!-- live preview + the one button -->
        <aside class="space-y-4 lg:sticky lg:top-24">
            <div class="overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-slate-200/70">
                <div class="flex items-center gap-1.5 border-b border-slate-100 bg-slate-50 px-3 py-2">
                    <span class="size-2.5 rounded-full bg-rose-300" /><span class="size-2.5 rounded-full bg-amber-300" /><span class="size-2.5 rounded-full bg-emerald-300" />
                    <span class="ms-2 min-w-0 flex-1 truncate rounded bg-white px-2 py-0.5 font-mono text-[11px] text-slate-500 ring-1 ring-slate-200" dir="ltr">{{ address }}</span>
                </div>
                <div class="px-5 py-7 text-white transition-colors" :style="{ background: form.brand }" :dir="form.default_locale === 'ar' ? 'rtl' : 'ltr'">
                    <p class="text-xs font-medium tracking-wide uppercase opacity-80">{{ kind && kind.value !== 'other' ? kind.label : (form.business_type || t('admin.clients.create.previewKind')) }}</p>
                    <p class="mt-1 text-xl font-bold wrap-break-word">{{ form.name || t('admin.clients.create.previewName') }}</p>
                </div>
                <ul class="space-y-1.5 p-4">
                    <li v-for="(s, i) in sections" :key="i" class="flex items-center gap-2.5 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600">
                        <component :is="sectionIcon(s.type)" class="size-4 shrink-0 text-slate-400" /> {{ s.label }}
                    </li>
                </ul>
                <p class="border-t border-slate-100 px-4 py-2.5 text-xs text-slate-500">{{ t('admin.clients.create.previewHint') }}</p>
            </div>

            <div class="rounded-2xl bg-white p-4 shadow-card ring-1 ring-slate-200/70">
                <div class="flex items-start gap-3">
                    <Toggle v-model="form.publish" :label="t('admin.clients.create.publishNow')" />
                    <div class="min-w-0">
                        <p class="flex items-center gap-1.5 text-sm font-medium text-slate-800">
                            <Rocket v-if="form.publish" class="size-4 text-emerald-600" /><EyeOff v-else class="size-4 text-slate-400" />
                            {{ form.publish ? t('admin.clients.create.publishNow') : t('admin.clients.create.keepHidden') }}
                        </p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ form.publish ? t('admin.clients.create.publishNowHint') : t('admin.clients.create.keepHiddenHint') }}</p>
                    </div>
                </div>
                <Btn type="submit" size="lg" class="mt-4 w-full justify-center" :loading="form.processing" :disabled="!canSubmit">{{ t('admin.clients.create.createClient') }}</Btn>
            </div>
        </aside>
    </form>
</template>

<style scoped>
.step { display: grid; place-items: center; width: 1.75rem; height: 1.75rem; flex-shrink: 0; border-radius: 9999px; background: var(--color-indigo-600); color: white; font-size: 0.8rem; }
.chip { border-radius: 9999px; padding: 0.375rem 0.875rem; font-size: 0.875rem; color: var(--color-slate-700); box-shadow: inset 0 0 0 1px var(--color-slate-300); }
.chip:hover { background: var(--color-slate-50); }
.chip-on { background: var(--color-indigo-600); color: white; box-shadow: none; }
.chip-on:hover { background: var(--color-indigo-700); }
</style>
