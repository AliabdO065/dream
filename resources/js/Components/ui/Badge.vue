<script setup>
import { computed } from 'vue';

const props = defineProps({ tone: { type: String, default: 'slate' }, dot: Boolean });
const tones = {
    slate: 'bg-slate-100 text-slate-700 ring-slate-200',
    green: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    amber: 'bg-amber-50 text-amber-800 ring-amber-200',
    red: 'bg-rose-50 text-rose-700 ring-rose-200',
    indigo: 'bg-indigo-50 text-indigo-700 ring-indigo-200',
};
const dots = { slate: 'bg-slate-400', green: 'bg-emerald-500', amber: 'bg-amber-500', red: 'bg-rose-500', indigo: 'bg-indigo-500' };
const cls = computed(() => tones[props.tone] ?? tones.slate);
</script>

<template>
    <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset" :class="cls">
        <span v-if="dot" class="size-1.5 rounded-full" :class="dots[tone]" />
        <slot />
    </span>
</template>
