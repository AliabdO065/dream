<script setup>
import { computed, provide, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PageHeader from '../../Components/ui/PageHeader.vue';
import Badge from '../../Components/ui/Badge.vue';
import Card from '../../Components/ui/Card.vue';
import Btn from '../../Components/ui/Btn.vue';
import Field from '../../Components/ui/Field.vue';
import ImageField from '../../Components/schema/ImageField.vue';
import SchemaField from '../../Components/schema/SchemaField.vue';
import { hydrate, serialize } from '../../Components/schema/schema';
import { useI18n } from '../../i18n';

const props = defineProps({ profile: Object, translation: [Object, Array], extraFields: Array, languages: Array, timezones: Array, fonts: Array, suggestions: Array });
const page = usePage();
const { t } = useI18n();
const slug = computed(() => page.props.client.slug);
const languageName = (code) => props.languages.find((l) => l.value === code)?.label ?? code.toUpperCase();

provide('locales', ['en']); // the custom-fields repeater has no translatable inputs
provide('languages', {});
provide('uploadsUrl', '');

const extraField = { name: 'extra', type: 'list', label: t('manage.profile.customFields'), max: 30, fields: props.extraFields };
const p = props.profile;

const form = useForm({
    name: p.name ?? '', legal_name: p.legal_name ?? '', business_type: p.business_type ?? '', tagline: p.tagline ?? '',
    email: p.email ?? '', phone: p.phone ?? '', website: p.website ?? '',
    address_line1: p.address_line1 ?? '', address_line2: p.address_line2 ?? '', postal_code: p.postal_code ?? '', city: p.city ?? '', region: p.region ?? '', country: p.country ?? '',
    registration_no: p.registration_no ?? '', tax_id: p.tax_id ?? '',
    timezone: p.timezone ?? 'UTC', currency: p.currency ?? '', default_locale: p.default_locale, locales: [...p.locales],
    theme_brand: p.theme_brand, theme_accent: p.theme_accent, theme_font: p.theme_font,
    extra: hydrate([extraField], { extra: p.extra }, ['en']).extra,
});

// Logo: keep the stored one, replace it with a newly chosen file, or remove it.
const hasLogo = !!p.logo;
const logoFile = ref(''); // ImageField model: '' or { file, preview }
const removeLogo = ref(false);
const keepingLogo = computed(() => hasLogo && !removeLogo.value && !logoFile.value);

const has = (c) => form.locales.includes(c);
const toggleLocale = (c, on) => { form.locales = on ? [...form.locales, c] : form.locales.filter((x) => x !== c); };

function submit() {
    form.transform((d) => ({
        ...d,
        _method: 'put',
        extra: serialize([extraField], { extra: d.extra }).content.extra,
        logo: logoFile.value?.file ?? undefined,
        remove_logo: removeLogo.value ? 1 : 0,
        locales: d.locales.includes(d.default_locale) ? d.locales : [d.default_locale, ...d.locales],
    })).post(route('manage.profile.update', slug.value), { forceFormData: true, preserveScroll: true });
}
</script>

<template>
    <Head :title="t('manage.profile.title')" />
    <PageHeader :title="t('manage.profile.title')" :description="t('manage.profile.description')" />

    <form class="max-w-4xl space-y-6" @submit.prevent="submit">
        <Card :title="t('manage.profile.business')">
            <div class="grid gap-5 sm:grid-cols-2">
                <Field :label="t('manage.profile.name')" :error="form.errors.name" required><input v-model="form.name" class="input" maxlength="150" required></Field>
                <Field :label="t('manage.profile.legalName')" :error="form.errors.legal_name"><input v-model="form.legal_name" class="input" maxlength="190"></Field>
                <Field :label="t('manage.profile.typeLabel')" :error="form.errors.business_type">
                    <input v-model="form.business_type" class="input" list="btypes" maxlength="100"><datalist id="btypes"><option v-for="s in suggestions" :key="s" :value="s" /></datalist>
                </Field>
                <Field :label="t('manage.profile.tagline')" :hint="t('manage.profile.taglineHint')" :error="form.errors.tagline"><input v-model="form.tagline" class="input" maxlength="255"></Field>
                <Field :label="t('manage.profile.registrationNo')" :error="form.errors.registration_no"><input v-model="form.registration_no" class="input" maxlength="120"></Field>
                <Field :label="t('manage.profile.taxId')" :error="form.errors.tax_id"><input v-model="form.tax_id" class="input" maxlength="120"></Field>
            </div>
        </Card>

        <Card :title="t('manage.profile.contactAddress')">
            <div class="grid gap-5 sm:grid-cols-2">
                <Field :label="t('manage.profile.email')" :error="form.errors.email"><input v-model="form.email" type="email" class="input" dir="ltr" maxlength="190"></Field>
                <Field :label="t('manage.profile.phone')" :error="form.errors.phone"><input v-model="form.phone" class="input" maxlength="50" dir="auto"></Field>
                <Field :label="t('manage.profile.website')" :error="form.errors.website" class="sm:col-span-2"><input v-model="form.website" type="url" class="input" dir="ltr" maxlength="255" placeholder="https://"></Field>
                <Field :label="t('manage.profile.address1')" :error="form.errors.address_line1"><input v-model="form.address_line1" class="input" maxlength="190"></Field>
                <Field :label="t('manage.profile.address2')" :error="form.errors.address_line2"><input v-model="form.address_line2" class="input" maxlength="190"></Field>
                <Field :label="t('manage.profile.postalCode')" :error="form.errors.postal_code"><input v-model="form.postal_code" class="input" maxlength="20"></Field>
                <Field :label="t('manage.profile.city')" :error="form.errors.city"><input v-model="form.city" class="input" maxlength="120"></Field>
                <Field :label="t('manage.profile.region')" :error="form.errors.region"><input v-model="form.region" class="input" maxlength="120"></Field>
                <Field :label="t('manage.profile.country')" :error="form.errors.country"><input v-model="form.country" class="input" maxlength="100"></Field>
            </div>
        </Card>

        <Card :title="t('manage.profile.langRegion')">
            <div class="grid gap-5 sm:grid-cols-3">
                <Field :label="t('manage.profile.mainLanguage')" :error="form.errors.default_locale"><select v-model="form.default_locale" class="input"><option v-for="l in languages" :key="l.value" :value="l.value">{{ l.label }}</option></select></Field>
                <Field :label="t('manage.profile.timezone')" :error="form.errors.timezone"><select v-model="form.timezone" class="input" dir="ltr"><option v-for="tz in timezones" :key="tz" :value="tz">{{ tz }}</option></select></Field>
                <Field :label="t('manage.profile.currency')" :hint="t('manage.profile.currencyHint')" :error="form.errors.currency"><input v-model="form.currency" class="input uppercase" dir="ltr" maxlength="3"></Field>
            </div>
            <p class="mt-6 mb-2 text-sm font-medium text-slate-700">{{ t('manage.profile.alsoOffer') }}</p>
            <div class="grid gap-2 sm:grid-cols-3 lg:grid-cols-5">
                <label v-for="l in languages" :key="l.value" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm ring-1 ring-slate-200" :class="has(l.value) ? 'bg-indigo-50 ring-indigo-300' : 'hover:bg-slate-50'">
                    <input type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" :checked="has(l.value)" :disabled="l.value === form.default_locale" @change="toggleLocale(l.value, $event.target.checked)"> {{ l.label }}
                </label>
            </div>
            <p class="mt-2 text-xs text-slate-500">{{ t('manage.profile.alsoOfferHint') }}</p>

            <!-- how far each extra language is: visitors only get a language once every text on the page exists in it -->
            <div v-if="Object.keys(translation).length" class="mt-6 space-y-3 border-t border-slate-100 pt-5">
                <p class="text-sm font-medium text-slate-700">{{ t('manage.profile.translationTitle') }}</p>
                <div v-for="(s, code) in translation" :key="code" class="rounded-xl p-3 ring-1" :class="s.complete ? 'ring-emerald-200 bg-emerald-50/50' : 'ring-amber-200 bg-amber-50/50'">
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="font-medium text-slate-800">{{ languageName(code) }}</span>
                        <Badge :tone="s.complete ? 'green' : 'amber'" dot>{{ s.complete ? t('manage.profile.langLive') : t('manage.profile.langHidden') }}</Badge>
                        <span class="ms-auto text-xs text-slate-500 tabular-nums">{{ t('manage.profile.langProgress', { done: s.sections - s.missing.length, total: s.sections }) }}</span>
                    </div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white ring-1 ring-slate-200">
                        <div class="h-full rounded-full" :class="s.complete ? 'bg-emerald-500' : 'bg-amber-500'" :style="{ width: (s.sections ? ((s.sections - s.missing.length) / s.sections) * 100 : 100) + '%' }" />
                    </div>
                    <p v-if="!s.complete" class="mt-2 flex flex-wrap items-center gap-1.5 text-xs text-slate-600">
                        {{ t('manage.profile.langMissing') }}
                        <Link v-for="m in s.missing" :key="m.id" :href="route('manage.sections.edit', [slug, m.id])" class="rounded-md bg-white px-2 py-0.5 font-medium text-indigo-700 ring-1 ring-slate-200 hover:ring-indigo-300">{{ m.name }}</Link>
                    </p>
                </div>
            </div>
        </Card>

        <Card :title="t('manage.profile.look')" :subtitle="t('manage.profile.lookHint')">
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="space-y-5">
                    <Field :label="t('manage.profile.brandColor')" :error="form.errors.theme_brand">
                        <div class="flex items-center gap-3">
                            <input v-model="form.theme_brand" type="color" class="h-10 w-14 cursor-pointer rounded-lg border-slate-300 p-1">
                            <input v-model="form.theme_brand" class="input w-32 font-mono" dir="ltr" maxlength="7" pattern="#[0-9a-fA-F]{6}">
                        </div>
                    </Field>
                    <Field :label="t('manage.profile.accentColor')" :error="form.errors.theme_accent">
                        <div class="flex items-center gap-3">
                            <input v-model="form.theme_accent" type="color" class="h-10 w-14 cursor-pointer rounded-lg border-slate-300 p-1">
                            <input v-model="form.theme_accent" class="input w-32 font-mono" dir="ltr" maxlength="7" pattern="#[0-9a-fA-F]{6}">
                        </div>
                    </Field>
                    <Field :label="t('manage.profile.fontStyle')" :error="form.errors.theme_font"><select v-model="form.theme_font" class="input"><option v-for="f in fonts" :key="f" :value="f">{{ t('ui.fonts.' + f) }}</option></select></Field>
                </div>
                <Field :label="t('manage.profile.logo')" :error="form.errors.logo">
                    <div v-if="keepingLogo" class="flex items-center gap-4">
                        <img :src="p.logo" :alt="t('manage.profile.currentLogo')" class="size-24 rounded-xl bg-slate-100 object-cover ring-1 ring-slate-200">
                        <div class="space-y-2">
                            <p class="text-xs text-slate-500">{{ t('manage.profile.currentLogo') }}</p>
                            <Btn size="sm" variant="danger" @click="removeLogo = true">{{ t('manage.profile.removeOrReplace') }}</Btn>
                        </div>
                    </div>
                    <div v-else>
                        <p v-if="removeLogo" class="mb-3 text-xs text-amber-700">{{ t('manage.profile.removeLogoNote') }}
                            <button type="button" class="font-semibold underline" @click="removeLogo = false; logoFile = ''">{{ t('common.undo') }}</button></p>
                        <ImageField v-model="logoFile" />
                    </div>
                </Field>
            </div>
            <div class="mt-6 flex items-center gap-4 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                <span class="text-xs font-medium text-slate-500 uppercase">{{ t('manage.profile.preview') }}</span>
                <span class="rounded-full px-5 py-2 text-sm font-semibold text-white shadow" :style="{ background: form.theme_brand }">{{ t('manage.profile.callNow') }}</span>
                <span class="rounded-full px-5 py-2 text-sm font-semibold ring-2" :style="{ color: form.theme_brand, '--tw-ring-color': form.theme_brand }">{{ t('manage.profile.learnMore') }}</span>
                <span class="rounded-full px-5 py-2 text-sm font-semibold text-white shadow" :style="{ background: form.theme_accent }">{{ t('manage.profile.accentColor') }}</span>
            </div>
        </Card>

        <Card :title="t('manage.profile.customFields')" :subtitle="t('manage.profile.customFieldsHint')">
            <SchemaField :field="extraField" v-model="form.extra" />
        </Card>

        <div class="sticky bottom-0 z-20 -mx-4 flex justify-end gap-3 border-t border-slate-200 bg-white/90 px-4 py-3.5 backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <Btn type="submit" :loading="form.processing">{{ t('manage.profile.saveProfile') }}</Btn>
        </div>
    </form>
</template>
