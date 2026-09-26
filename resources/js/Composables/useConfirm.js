import { reactive } from 'vue';

// One global confirm dialog (rendered by ConfirmDialog.vue in the layout) instead of the browser's confirm().
export const confirmState = reactive({ open: false, title: '', message: '', confirmLabel: 'Confirm', tone: 'danger', resolve: null });

/** const ok = await confirmDialog({ title, message, confirmLabel, tone }) */
export function confirmDialog({ title = 'Are you sure?', message = '', confirmLabel = 'Confirm', tone = 'danger' } = {}) {
    return new Promise((resolve) => {
        Object.assign(confirmState, { open: true, title, message, confirmLabel, tone, resolve });
    });
}

export function answerConfirm(value) {
    confirmState.open = false;
    confirmState.resolve?.(value);
    confirmState.resolve = null;
}
