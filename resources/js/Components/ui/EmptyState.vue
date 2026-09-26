<script setup>
defineProps({ title: String, text: String, icon: Object });
</script>

<template>
    <div class="flex flex-col items-center px-6 py-14 text-center">
        <span v-if="icon" class="mb-4 grid size-12 place-items-center rounded-2xl bg-slate-100 text-slate-400"><component :is="icon" class="size-6" /></span>
        <h3 class="text-sm font-semibold text-slate-900">{{ title }}</h3>
        <p v-if="text" class="mt-1 max-w-sm text-sm text-slate-500">{{ text }}</p>
        <div v-if="$slots.default" class="mt-5"><slot /></div>
    </div>
</template>
