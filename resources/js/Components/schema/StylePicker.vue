<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Eye } from 'lucide-vue-next';
import { useI18n } from '../../i18n';

// A numbered picker (Style 1 … N) where every option is a LIVE thumbnail of the section itself, rendered by the
// server in the client's real theme — so a look is seen before it is applied, never guessed from a label.
const props = defineProps({
    modelValue: [String, Number],
    options: Array,            // [{ value: '1', label: 'Style 1' }, …]
    previewUrl: Function,      // (value) => url of the section rendered in that style
});
const emit = defineEmits(['update:modelValue']);
const { t } = useI18n();

const FRAME_W = 960; // the thumbnail renders a desktop-width page, scaled down to the tile
const boxes = ref([]);
const scale = ref(0.2);
let ro;
onMounted(() => {
    ro = new ResizeObserver(() => {
        const w = boxes.value[0]?.clientWidth;
        if (w) scale.value = w / FRAME_W;
    });
    if (boxes.value[0]) ro.observe(boxes.value[0]);
});
onBeforeUnmount(() => ro?.disconnect());
</script>

<template>
    <div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
            <label v-for="(o, i) in options" :key="o.value"
                   class="flex cursor-pointer flex-col gap-2 rounded-xl p-2.5 ring-1 transition"
                   :class="String(modelValue) === o.value ? 'bg-indigo-50 ring-2 ring-indigo-500' : 'ring-slate-200 hover:bg-slate-50'">
                <span class="flex items-center gap-2">
                    <input type="radio" class="text-indigo-600 focus:ring-indigo-500" :value="o.value" :checked="String(modelValue) === o.value" @change="emit('update:modelValue', o.value)">
                    <span class="grid size-6 place-items-center rounded-md bg-slate-900 text-xs font-bold text-white">{{ o.value }}</span>
                    <span class="flex-1 text-sm font-semibold text-slate-800">{{ o.label }}</span>
                    <a :href="previewUrl(o.value)" target="_blank" rel="noopener"
                       class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-indigo-600"
                       :title="t('manage.sections.form.previewStyle')" :aria-label="t('manage.sections.form.previewStyle')" @click.stop>
                        <Eye class="size-4" />
                    </a>
                </span>
                <span dir="ltr" :ref="(el) => { if (el) boxes[i] = el }" class="relative block aspect-[4/3] w-full overflow-hidden rounded-lg bg-slate-50 ring-1 ring-slate-200">
                    <iframe :src="previewUrl(o.value)" tabindex="-1" aria-hidden="true" loading="lazy"
                            class="pointer-events-none absolute left-0 top-0 origin-top-left border-0"
                            :style="{ width: FRAME_W + 'px', height: '720px', transform: `scale(${scale})` }" />
                </span>
            </label>
        </div>
        <p class="mt-3 text-xs text-slate-500">{{ t('manage.sections.form.styleHint') }}</p>
    </div>
</template>
