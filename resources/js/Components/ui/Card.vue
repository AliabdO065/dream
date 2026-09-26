<script setup>
defineProps({
    title: String,
    subtitle: String,
    flush: Boolean, // no inner padding (tables)
    tone: { type: String, default: 'default' }, // default | danger
});
</script>

<template>
    <section class="rounded-2xl bg-white shadow-card ring-1" :class="tone === 'danger' ? 'ring-rose-200' : 'ring-slate-200/70'">
        <header v-if="title || $slots.actions" class="flex items-start justify-between gap-4 px-5 pt-5" :class="flush ? 'pb-4' : ''">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">{{ title }}</h2>
                <p v-if="subtitle" class="mt-0.5 text-sm text-slate-500">{{ subtitle }}</p>
            </div>
            <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2"><slot name="actions" /></div>
        </header>
        <div :class="flush ? '' : 'p-5'" :style="title && !flush ? 'padding-top:1rem' : ''"><slot /></div>
    </section>
</template>
