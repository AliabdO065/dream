<script setup>
import { computed, inject } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import { GripVertical, Plus, Trash2 } from 'lucide-vue-next';
import Btn from '../ui/Btn.vue';
import SchemaField from './SchemaField.vue';
import { blankRow } from './schema';
import { useI18n } from '../../i18n';

const props = defineProps({ field: Object, modelValue: Array });
const emit = defineEmits(['update:modelValue']);
const { t } = useI18n();

const locales = inject('locales', ['en']);
const rows = computed({ get: () => props.modelValue ?? [], set: (v) => emit('update:modelValue', v) });
const atMax = computed(() => rows.value.length >= (props.field.max ?? 50));

function setRow(i, name, value) {
    const next = rows.value.slice();
    next[i] = { ...next[i], [name]: value };
    emit('update:modelValue', next);
}
const add = () => emit('update:modelValue', [...rows.value, blankRow(props.field.fields, locales)]);
const remove = (i) => emit('update:modelValue', rows.value.filter((_, idx) => idx !== i));

// a short label for the row header: the first text the person typed
function summary(row) {
    for (const f of props.field.fields) {
        const v = row[f.name];
        if (f.type === 'text' || f.type === 'textarea') {
            const s = typeof v === 'string' ? v : Object.values(v || {}).find((x) => x);
            if (s) return s;
        }
    }
    return '';
}
</script>

<template>
    <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200">
        <VueDraggable v-model="rows" handle=".drag-handle" :animation="160" :force-fallback="true" ghost-class="opacity-40" class="space-y-3">
            <div v-for="(row, i) in rows" :key="row._k" class="rounded-lg bg-white shadow-xs ring-1 ring-slate-200">
                <div class="flex items-center gap-2 border-b border-slate-100 px-3 py-2">
                    <span class="drag-handle cursor-grab text-slate-400 hover:text-slate-600 active:cursor-grabbing" :title="t('ui.list.dragToReorder')"><GripVertical class="size-4" /></span>
                    <span class="w-5 text-xs font-semibold text-slate-400 tabular-nums">{{ i + 1 }}</span>
                    <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-700">{{ summary(row) || t('ui.list.newItem') }}</span>
                    <button type="button" class="rounded-md p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600" :title="t('ui.list.removeItem')" :aria-label="t('ui.list.removeItem')" @click="remove(i)"><Trash2 class="size-4" /></button>
                </div>
                <div class="space-y-4 p-4">
                    <SchemaField v-for="sf in field.fields" :key="sf.name" :field="sf" :model-value="row[sf.name]" in-row @update:model-value="setRow(i, sf.name, $event)" />
                </div>
            </div>
        </VueDraggable>

        <p v-if="!rows.length" class="py-3 text-center text-sm text-slate-500">{{ t('ui.list.empty') }}</p>
        <div class="mt-3 flex items-center justify-between">
            <Btn variant="secondary" size="sm" :disabled="atMax" @click="add"><Plus class="size-4" /> {{ t('ui.list.addItem') }}</Btn>
            <span class="text-xs text-slate-400">{{ rows.length }} / {{ field.max ?? 50 }}</span>
        </div>
    </div>
</template>
