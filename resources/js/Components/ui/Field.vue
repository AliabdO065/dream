<script setup>
defineProps({ label: String, hint: String, error: String, required: Boolean, for: String });
</script>

<template>
    <div>
        <label v-if="label" :for="$props.for" class="mb-1.5 block text-sm font-medium text-slate-700">
            {{ label }}<span v-if="required" class="text-rose-500"> *</span>
        </label>
        <slot />
        <p v-if="error" class="mt-1.5 text-sm text-rose-600">{{ error }}</p>
        <p v-else-if="hint" class="mt-1.5 text-xs text-slate-500">{{ hint }}</p>
    </div>
</template>
