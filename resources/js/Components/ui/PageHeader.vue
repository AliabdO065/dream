<script setup>
defineProps({ title: String, description: String });
</script>

<template>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ title }}</h1>
            <p v-if="description" class="mt-1 max-w-2xl text-sm text-slate-500">{{ description }}</p>
        </div>
        <div v-if="$slots.default" class="flex flex-wrap items-center gap-2"><slot /></div>
    </div>
</template>
