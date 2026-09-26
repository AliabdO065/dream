<script setup>
defineProps({ modelValue: Boolean, label: String, disabled: Boolean });
defineEmits(['update:modelValue']);
</script>

<template>
    <button type="button" role="switch" :aria-checked="modelValue" :aria-label="label" :disabled="disabled"
            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors disabled:cursor-not-allowed disabled:opacity-50"
            :class="modelValue ? 'bg-indigo-600' : 'bg-slate-300'" @click="$emit('update:modelValue', !modelValue)">
        <span class="pointer-events-none absolute top-0.5 start-0.5 size-5 rounded-full bg-white shadow transition-transform" :class="modelValue ? 'translate-x-5 rtl:-translate-x-5' : ''" />
    </button>
</template>
