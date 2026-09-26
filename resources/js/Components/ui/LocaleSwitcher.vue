<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Check, Globe } from 'lucide-vue-next';
import { useI18n } from '../../i18n';

const { t, locale } = useI18n();

// Data-driven so a 3rd dashboard language later is just one more entry here.
const options = [
    { code: 'en', label: 'English' },
    { code: 'ar', label: 'العربية' },
];

const open = ref(false);
const root = ref(null);

function set(code) {
    open.value = false;
    if (code === locale.value) return;
    router.post(route('dashboard-locale.update', code), {}, { preserveScroll: true });
}
function onDocClick(e) { if (root.value && !root.value.contains(e.target)) open.value = false; }
onMounted(() => document.addEventListener('click', onDocClick));
onBeforeUnmount(() => document.removeEventListener('click', onDocClick));
</script>

<template>
    <div ref="root" class="relative">
        <button type="button" class="grid size-9 place-items-center rounded-full text-slate-500 hover:bg-slate-100" :aria-label="t('nav.language')" :aria-expanded="open" @click="open = !open">
            <Globe class="size-[18px]" />
        </button>
        <transition name="pop">
            <div v-if="open" class="absolute end-0 mt-2 w-44 rounded-xl bg-white p-1.5 shadow-pop ring-1 ring-slate-200 ltr:origin-top-right rtl:origin-top-left">
                <button v-for="o in options" :key="o.code" type="button" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm hover:bg-slate-50"
                        :class="o.code === locale ? 'font-semibold text-indigo-600' : 'text-slate-600'" @click="set(o.code)">
                    {{ o.label }}
                    <Check v-if="o.code === locale" class="size-4" />
                </button>
            </div>
        </transition>
    </div>
</template>
