<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';

const props = defineProps({
    variant: { type: String, default: 'primary' }, // primary | secondary | soft | danger | ghost
    size: { type: String, default: 'md' }, // sm | md | lg
    href: String,
    external: Boolean, // plain <a> (opens elsewhere) instead of an Inertia visit
    type: { type: String, default: 'button' },
    loading: Boolean,
    disabled: Boolean,
});

const classes = computed(() => [
    'inline-flex items-center justify-center gap-2 rounded-lg font-semibold whitespace-nowrap transition select-none',
    'focus-visible:outline-2 focus-visible:outline-offset-2 disabled:opacity-50 disabled:cursor-not-allowed',
    {
        primary: 'bg-indigo-600 text-white shadow-sm hover:bg-indigo-500 active:bg-indigo-700',
        secondary: 'bg-white text-slate-700 ring-1 ring-slate-300 shadow-xs hover:bg-slate-50',
        soft: 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100',
        danger: 'bg-white text-rose-600 ring-1 ring-rose-200 hover:bg-rose-50',
        ghost: 'text-slate-600 hover:bg-slate-100',
    }[props.variant],
    { sm: 'px-3 py-1.5 text-xs', md: 'px-4 py-2 text-sm', lg: 'px-5 py-2.5 text-sm' }[props.size],
]);
</script>

<template>
    <a v-if="href && external" :href="href" target="_blank" rel="noopener" :class="classes"><slot /></a>
    <Link v-else-if="href" :href="href" :class="classes"><slot /></Link>
    <button v-else :type="type" :disabled="disabled || loading" :class="classes">
        <Loader2 v-if="loading" class="size-4 animate-spin" />
        <slot />
    </button>
</template>
