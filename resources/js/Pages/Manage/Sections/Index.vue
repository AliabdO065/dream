<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { AlertTriangle, GripVertical, Languages, LayoutTemplate, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { VueDraggable } from 'vue-draggable-plus';
import PageHeader from '../../../Components/ui/PageHeader.vue';
import Card from '../../../Components/ui/Card.vue';
import Btn from '../../../Components/ui/Btn.vue';
import Badge from '../../../Components/ui/Badge.vue';
import Toggle from '../../../Components/ui/Toggle.vue';
import EmptyState from '../../../Components/ui/EmptyState.vue';
import { sectionIcon } from '../../../Composables/icons';
import { confirmDialog } from '../../../Composables/useConfirm';
import { useI18n } from '../../../i18n';

const props = defineProps({ sections: Array, hiddenLanguages: Array });
const page = usePage();
const { t } = useI18n();
const slug = computed(() => page.props.client.slug);
const can = computed(() => page.props.auth.membership?.can ?? {});

// a local copy so dragging feels instant; it re-syncs whenever the server sends a fresh list
const list = ref(props.sections.map((s) => ({ ...s })));
watch(() => props.sections, (s) => { list.value = s.map((x) => ({ ...x })); });

function saveOrder() {
    router.post(route('manage.sections.reorder', slug.value), { ids: list.value.map((s) => s.id) }, { preserveScroll: true, preserveState: true });
}
function toggle(s) {
    s.is_enabled = !s.is_enabled; // optimistic
    router.post(route('manage.sections.toggle', [slug.value, s.id]), {}, { preserveScroll: true, preserveState: true });
}
async function remove(s) {
    if (await confirmDialog({ title: t('manage.sections.deleteConfirmTitle', { name: s.name }), message: t('manage.sections.deleteConfirmMsg'), confirmLabel: t('manage.sections.deleteConfirmBtn') }))
        router.delete(route('manage.sections.destroy', [slug.value, s.id]), { preserveScroll: true });
}
const live = computed(() => list.value.filter((s) => s.is_enabled && s.available).length);
</script>

<template>
    <Head :title="t('manage.sections.title')" />
    <PageHeader :title="t('manage.sections.title')" :description="t('manage.sections.description')">
        <Btn :href="route('manage.sections.create', slug)"><Plus class="size-4" /> {{ t('manage.sections.addSection') }}</Btn>
    </PageHeader>

    <div v-if="hiddenLanguages.length" class="mb-4 flex items-start gap-3 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">
        <Languages class="mt-0.5 size-5 shrink-0 text-amber-600" />
        <p>{{ t('manage.sections.languagesHidden', { list: hiddenLanguages.map((l) => l.toUpperCase()).join(' · ') }) }}</p>
    </div>

    <Card v-if="list.length" :title="t('manage.sections.countSections', { n: list.length })" :subtitle="t('manage.sections.liveOnSite', { n: live })" flush>
        <VueDraggable v-model="list" handle=".drag-handle" :animation="180" :force-fallback="true" fallback-class="shadow-pop" ghost-class="bg-indigo-50" tag="ul" class="divide-y divide-slate-100" @end="saveOrder">
            <li v-for="s in list" :key="s.id" class="group flex items-center gap-3 px-4 py-3.5 transition sm:gap-4 sm:px-5" :class="!s.is_enabled || !s.available ? 'bg-slate-50/60' : 'bg-white'">
                <span class="drag-handle cursor-grab touch-none text-slate-300 group-hover:text-slate-500 active:cursor-grabbing" :title="t('ui.list.dragToReorder')"><GripVertical class="size-5" /></span>
                <span class="grid size-10 shrink-0 place-items-center rounded-xl" :class="s.is_enabled && s.available ? 'bg-indigo-50 text-indigo-600' : 'bg-slate-100 text-slate-400'"><component :is="sectionIcon(s.type)" class="size-5" /></span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold" :class="s.is_enabled && s.available ? 'text-slate-900' : 'text-slate-500'">{{ s.name }}</p>
                    <p class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
                        <span>{{ s.type_label }}</span><span class="text-slate-300">·</span><span class="font-mono" dir="ltr">#{{ s.anchor }}</span>
                        <Badge v-if="s.in_nav" tone="indigo">{{ t('manage.sections.inMenu') }}</Badge>
                        <Badge v-if="!s.available" tone="amber"><AlertTriangle class="size-3" /> {{ t('manage.sections.unavailable') }}</Badge>
                        <Badge v-if="s.missing_languages.length" tone="amber" :title="t('manage.sections.untranslatedHint')"><Languages class="size-3" /> {{ t('manage.sections.untranslated', { list: s.missing_languages.map((l) => l.toUpperCase()).join(' · ') }) }}</Badge>
                    </p>
                </div>
                <span class="hidden text-xs text-slate-400 md:block">{{ s.updated }}</span>
                <Toggle :model-value="s.is_enabled" :label="s.is_enabled ? t('manage.sections.visible') : t('manage.sections.hidden')" @update:model-value="toggle(s)" />
                <div class="flex shrink-0 items-center gap-1.5">
                    <Btn v-if="s.available" size="sm" variant="secondary" :href="route('manage.sections.edit', [slug, s.id])"><Pencil class="size-3.5" /><span class="hidden sm:inline"> {{ t('common.edit') }}</span></Btn>
                    <button v-if="can['sections.delete']" type="button" class="rounded-lg p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600" :title="t('common.delete')" :aria-label="`${t('common.delete')} ${s.name}`" @click="remove(s)"><Trash2 class="size-4" /></button>
                </div>
            </li>
        </VueDraggable>
    </Card>

    <Card v-else>
        <EmptyState :icon="LayoutTemplate" :title="t('manage.sections.noSectionsTitle')" :text="t('manage.sections.noSectionsText')">
            <Btn :href="route('manage.sections.create', slug)"><Plus class="size-4" /> {{ t('manage.sections.addSection') }}</Btn>
        </EmptyState>
    </Card>
</template>
