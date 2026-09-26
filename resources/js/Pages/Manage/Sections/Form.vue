<script setup>
import { computed, provide, reactive, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft } from 'lucide-vue-next';
import PageHeader from '../../../Components/ui/PageHeader.vue';
import Card from '../../../Components/ui/Card.vue';
import Btn from '../../../Components/ui/Btn.vue';
import Badge from '../../../Components/ui/Badge.vue';
import Field from '../../../Components/ui/Field.vue';
import SchemaForm from '../../../Components/schema/SchemaForm.vue';
import { hydrate, serialize } from '../../../Components/schema/schema';
import { sectionIcon } from '../../../Composables/icons';
import { useI18n } from '../../../i18n';

const props = defineProps({
    mode: String, section: Object, fields: Array, configFields: Array, content: Object, config: Object,
    locales: Array, defaultLocale: String, languages: Object, uploadsUrl: String,
});
const page = usePage();
const { t } = useI18n();
const slug = computed(() => page.props.client.slug);
const errors = computed(() => page.props.errors ?? {});

// context for every field component below
provide('locales', props.locales);
provide('languages', props.languages);
provide('uploadsUrl', props.uploadsUrl);

const state = reactive({
    name: props.section.name ?? '',
    anchor: props.section.anchor ?? '',
    content: hydrate(props.fields, props.content, props.locales),
    config: hydrate(props.configFields, props.config, props.locales),
});

const processing = ref(false);
const editing = computed(() => props.mode === 'edit');

function submit() {
    const c = serialize(props.fields, state.content);
    const cfg = serialize(props.configFields, state.config);
    const payload = { name: state.name, anchor: state.anchor, content: c.content, config: cfg.content, uploads: c.uploads };

    const url = editing.value ? route('manage.sections.update', [slug.value, props.section.id]) : route('manage.sections.store', slug.value);
    if (editing.value) payload._method = 'put'; else payload.type = props.section.type;

    router.post(url, payload, {
        forceFormData: true, preserveScroll: true,
        onStart: () => (processing.value = true), onFinish: () => (processing.value = false),
    });
}
const back = computed(() => route('manage.sections.index', slug.value));
</script>

<template>
    <Head :title="editing ? section.name : t('manage.sections.form.newOf', { type: section.type_label })" />
    <Link :href="back" class="mb-4 inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800"><ArrowLeft class="size-4 rtl:rotate-180" /> {{ t('manage.sections.form.backToSections') }}</Link>

    <PageHeader :title="editing ? section.name : t('manage.sections.form.newOf', { type: section.type_label.toLowerCase() })" :description="section.description">
        <span class="inline-flex items-center gap-2 rounded-lg bg-indigo-50 px-3 py-1.5 text-sm font-medium text-indigo-700"><component :is="sectionIcon(section.type)" class="size-4" /> {{ section.type_label }}</span>
    </PageHeader>

    <div v-if="Object.keys(errors).length" class="mb-6 flex gap-3 rounded-xl bg-rose-50 p-4 text-sm text-rose-800 ring-1 ring-rose-200">
        <AlertCircle class="mt-0.5 size-5 shrink-0" />
        <ul class="space-y-0.5"><li v-for="(m, k) in errors" :key="k">{{ m }}</li></ul>
    </div>

    <form @submit.prevent="submit">
        <div class="grid items-start gap-6 xl:grid-cols-[1fr_22rem]">
            <div class="space-y-6">
                <Card :title="t('manage.sections.form.basics')">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <Field :label="t('manage.sections.form.name')" :hint="t('manage.sections.form.nameHint')" :error="errors.name" required><input v-model="state.name" class="input" maxlength="120" required></Field>
                        <Field :label="t('manage.sections.form.linkId')" :error="errors.anchor" :hint="t('manage.sections.form.linkIdHint')"><input v-model="state.anchor" class="input font-mono" dir="ltr" maxlength="80" placeholder="auto"></Field>
                    </div>
                </Card>

                <Card :title="t('manage.sections.form.content')" :subtitle="locales.length > 1 ? t('manage.sections.form.contentHint', { list: locales.map((l) => l.toUpperCase()).join(', '), fallback: defaultLocale.toUpperCase() }) : null">
                    <SchemaForm v-model="state.content" :fields="fields" />
                </Card>
            </div>

            <Card :title="t('manage.sections.form.appearance')" class="xl:sticky xl:top-24">
                <SchemaForm v-model="state.config" :fields="configFields" />
            </Card>
        </div>

        <div class="sticky bottom-0 z-20 -mx-4 mt-8 flex items-center justify-between gap-3 border-t border-slate-200 bg-white/90 px-4 py-3.5 backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <p class="hidden text-sm text-slate-500 sm:block">{{ editing ? t('manage.sections.form.footerEdit') : t('manage.sections.form.footerNew') }}</p>
            <div class="ms-auto flex gap-3">
                <Btn variant="secondary" :href="back">{{ t('manage.sections.form.cancel') }}</Btn>
                <Btn type="submit" :loading="processing">{{ editing ? t('manage.sections.form.saveChanges') : t('manage.sections.form.addSection') }}</Btn>
            </div>
        </div>
    </form>
</template>
