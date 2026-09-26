<script setup>
import { computed, inject, ref } from 'vue';
import { ImagePlus, Trash2, RefreshCw } from 'lucide-vue-next';
import Btn from '../ui/Btn.vue';
import { useI18n } from '../../i18n';

// modelValue is either '' / a stored path (string) or { file, preview } for a freshly chosen file.
const props = defineProps({ modelValue: [String, Object], compact: Boolean });
const emit = defineEmits(['update:modelValue']);
const { t } = useI18n();

const uploadsUrl = inject('uploadsUrl', '');
const input = ref(null);
const problem = ref('');

const src = computed(() => {
    const v = props.modelValue;
    if (v && typeof v === 'object') return v.preview;
    return v ? `${uploadsUrl}/${v}` : '';
});

function choose(e) {
    const file = e.target.files?.[0];
    e.target.value = '';
    problem.value = '';
    if (!file) return;
    if (!/^image\/(jpeg|png|webp|gif)$/.test(file.type)) { problem.value = t('ui.image.invalidType'); return; }
    if (file.size > 4 * 1024 * 1024) { problem.value = t('ui.image.tooLarge'); return; }
    emit('update:modelValue', { file, preview: URL.createObjectURL(file) });
}
</script>

<template>
    <div>
        <div class="flex items-center gap-4">
            <div class="grid shrink-0 place-items-center overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200" :class="compact ? 'size-16' : 'size-24'">
                <img v-if="src" :src="src" alt="" class="size-full object-cover">
                <ImagePlus v-else class="size-6 text-slate-400" />
            </div>
            <div class="flex flex-wrap gap-2">
                <Btn variant="secondary" size="sm" @click="input.click()">
                    <RefreshCw v-if="src" class="size-3.5" /><ImagePlus v-else class="size-3.5" /> {{ src ? t('ui.image.replace') : t('ui.image.choose') }}
                </Btn>
                <Btn v-if="src" variant="danger" size="sm" @click="emit('update:modelValue', '')"><Trash2 class="size-3.5" /> {{ t('ui.image.remove') }}</Btn>
            </div>
            <input ref="input" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" @change="choose">
        </div>
        <p v-if="problem" class="mt-1.5 text-sm text-rose-600">{{ problem }}</p>
        <p v-else-if="!compact" class="mt-1.5 text-xs text-slate-500">{{ t('ui.image.hint') }}</p>
    </div>
</template>
