<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import PageHeader from '../../../Components/ui/PageHeader.vue';
import { sectionIcon } from '../../../Composables/icons';
import { useI18n } from '../../../i18n';

defineProps({ types: Array });
const slug = computed(() => usePage().props.client.slug);
const { t } = useI18n();
</script>

<template>
    <Head :title="t('manage.sections.pick.title')" />
    <Link :href="route('manage.sections.index', slug)" class="mb-4 inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800"><ArrowLeft class="size-4 rtl:rotate-180" /> {{ t('manage.sections.pick.backToSections') }}</Link>
    <PageHeader :title="t('manage.sections.pick.title')" :description="t('manage.sections.pick.description')" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <Link v-for="t2 in types" :key="t2.key" :href="route('manage.sections.create', { client: slug, type: t2.key })"
              class="group flex gap-4 rounded-2xl bg-white p-5 shadow-card ring-1 ring-slate-200/70 transition hover:-translate-y-0.5 hover:ring-2 hover:ring-indigo-400">
            <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-indigo-50 text-indigo-600 transition group-hover:bg-indigo-600 group-hover:text-white"><component :is="sectionIcon(t2.key)" class="size-6" /></span>
            <div>
                <p class="font-semibold text-slate-900">{{ t2.label }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ t2.description }}</p>
            </div>
        </Link>
    </div>
</template>
