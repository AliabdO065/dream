<script setup>
import { nextTick, ref, watch } from 'vue';
import { AlertTriangle } from 'lucide-vue-next';
import { answerConfirm, confirmState } from '../../Composables/useConfirm';
import Btn from './Btn.vue';
import { useI18n } from '../../i18n';

const { t } = useI18n();
const confirmBtn = ref(null);
watch(() => confirmState.open, async (open) => { if (open) { await nextTick(); confirmBtn.value?.$el?.focus?.(); } });
</script>

<template>
    <Teleport to="body">
        <transition name="fade">
            <div v-if="confirmState.open" class="fixed inset-0 z-[100] grid place-items-center bg-slate-900/50 p-4 backdrop-blur-[2px]" @keydown.esc="answerConfirm(false)" @click.self="answerConfirm(false)">
                <transition name="pop" appear>
                    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-pop" role="dialog" aria-modal="true">
                        <div class="flex gap-4">
                            <span class="grid size-11 shrink-0 place-items-center rounded-full" :class="confirmState.tone === 'danger' ? 'bg-rose-100 text-rose-600' : 'bg-indigo-100 text-indigo-600'"><AlertTriangle class="size-5" /></span>
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">{{ confirmState.title }}</h3>
                                <p v-if="confirmState.message" class="mt-1.5 text-sm text-slate-500">{{ confirmState.message }}</p>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end gap-2">
                            <Btn variant="secondary" @click="answerConfirm(false)">{{ t('common.cancel') }}</Btn>
                            <Btn ref="confirmBtn" :variant="confirmState.tone === 'danger' ? 'danger' : 'primary'" @click="answerConfirm(true)">{{ confirmState.confirmLabel || t('confirm.confirm') }}</Btn>
                        </div>
                    </div>
                </transition>
            </div>
        </transition>
    </Teleport>
</template>
