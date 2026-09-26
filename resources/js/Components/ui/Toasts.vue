<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2, XCircle, X } from 'lucide-vue-next';

const page = usePage();
const toasts = ref([]);
let id = 0;

function push(tone, text) {
    const t = { id: ++id, tone, text };
    toasts.value.push(t);
    setTimeout(() => dismiss(t.id), tone === 'error' ? 8000 : 5000);
}
const dismiss = (tid) => { toasts.value = toasts.value.filter((t) => t.id !== tid); };

// The flash prop is a new object on every visit, so this fires once per response.
watch(() => page.props.flash, (f) => { if (f?.status) push('success', f.status); }, { immediate: true });
// A general (non-field) error, e.g. "there must always be one super admin", is shown as a toast too.
watch(() => page.props.errors, (e) => { if (e?.admin) push('error', e.admin); }, { immediate: true });
</script>

<template>
    <div class="pointer-events-none fixed top-4 end-4 z-[90] flex w-full max-w-sm flex-col gap-2">
        <transition-group name="fade">
            <div v-for="t in toasts" :key="t.id" class="pointer-events-auto flex items-start gap-3 rounded-xl bg-white p-4 shadow-pop ring-1 ring-slate-200">
                <CheckCircle2 v-if="t.tone === 'success'" class="mt-0.5 size-5 shrink-0 text-emerald-500" />
                <XCircle v-else class="mt-0.5 size-5 shrink-0 text-rose-500" />
                <p class="min-w-0 flex-1 text-sm break-words text-slate-700">{{ t.text }}</p>
                <button type="button" class="text-slate-400 hover:text-slate-600" aria-label="Dismiss" @click="dismiss(t.id)"><X class="size-4" /></button>
            </div>
        </transition-group>
    </div>
</template>
