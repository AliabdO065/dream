<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: String,
    value: [Number, String],
    hint: String,
    icon: Object, // a lucide component
    tone: { type: String, default: 'indigo' }, // indigo | green | amber | rose | slate
    href: String,
});

const tones = {
    indigo: 'bg-indigo-50 text-indigo-600',
    green: 'bg-emerald-50 text-emerald-600',
    amber: 'bg-amber-50 text-amber-600',
    rose: 'bg-rose-50 text-rose-600',
    slate: 'bg-slate-100 text-slate-600',
};
const iconCls = computed(() => tones[props.tone] ?? tones.indigo);
</script>

<template>
    <component :is="href ? 'a' : 'div'" :href="href" class="group rounded-2xl bg-white p-5 shadow-card ring-1 ring-slate-200/70 transition" :class="href ? 'hover:ring-indigo-300 hover:shadow-md' : ''">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-slate-500">{{ label }}</p>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900 tabular-nums">{{ value }}</p>
            </div>
            <span v-if="icon" class="grid size-10 shrink-0 place-items-center rounded-xl" :class="iconCls"><component :is="icon" class="size-5" /></span>
        </div>
        <p v-if="hint" class="mt-3 text-xs text-slate-500">{{ hint }}</p>
    </component>
</template>
