<script setup>
// A full-screen look at one image. v-model = the image URL (null = closed). Closes on backdrop click, ✕ or Escape.
import { onBeforeUnmount, watch } from 'vue';
import { X } from 'lucide-vue-next';
import { useI18n } from '../../i18n';

const src = defineModel({ type: String, default: null });
defineProps({ alt: { type: String, default: '' } });
const { t } = useI18n();

const onKey = (e) => { if (e.key === 'Escape') src.value = null; };
watch(src, (v) => (v ? window.addEventListener('keydown', onKey) : window.removeEventListener('keydown', onKey)));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <Teleport to="body">
        <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" enter-active-class="transition duration-150" leave-active-class="transition duration-150">
            <div v-if="src" class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-slate-950/80 p-6 backdrop-blur-sm" role="dialog" aria-modal="true" :aria-label="alt" @click="src = null">
                <img :src="src" :alt="alt" class="max-h-[80vh] max-w-[min(90vw,40rem)] rounded-2xl bg-white object-contain p-3 shadow-2xl" @click.stop>
                <p v-if="alt" class="mt-3 text-sm font-medium text-white">{{ alt }}</p>
                <button type="button" class="absolute end-4 top-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20" :aria-label="t('common.close')" @click="src = null"><X class="size-5" /></button>
            </div>
        </Transition>
    </Teleport>
</template>
