<script setup>
import { computed } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { UserMinus, UserPlus } from 'lucide-vue-next';
import PageHeader from '../../Components/ui/PageHeader.vue';
import Card from '../../Components/ui/Card.vue';
import Btn from '../../Components/ui/Btn.vue';
import Badge from '../../Components/ui/Badge.vue';
import Field from '../../Components/ui/Field.vue';
import { confirmDialog } from '../../Composables/useConfirm';
import { useI18n } from '../../i18n';

defineProps({ members: Array });
const page = usePage();
const { t } = useI18n();
const slug = computed(() => page.props.client.slug);

const form = useForm({ email: '', name: '' });
const add = () => form.post(route('manage.team.store', slug.value), { preserveScroll: true, onSuccess: () => form.reset() });

async function remove(m) {
    if (await confirmDialog({ title: t('manage.team.removeConfirmTitle', { email: m.email }), message: t('manage.team.removeConfirmMsg'), confirmLabel: t('manage.team.remove') }))
        router.delete(route('manage.team.destroy', [slug.value, m.id]), { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('manage.team.title')" />
    <PageHeader :title="t('manage.team.title')" :description="t('manage.team.description')" />

    <div class="max-w-4xl space-y-6">
        <Card :title="t('manage.team.rolesTitle')">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl p-4 ring-1 ring-slate-200">
                    <Badge tone="indigo">{{ t('manage.team.owner') }}</Badge>
                    <p class="mt-2 text-sm text-slate-600">{{ t('manage.team.ownerCan') }}</p>
                </div>
                <div class="rounded-xl p-4 ring-1 ring-slate-200">
                    <Badge tone="slate">{{ t('manage.team.editor') }}</Badge>
                    <p class="mt-2 text-sm text-slate-600">{{ t('manage.team.editorCan') }}</p>
                </div>
            </div>
        </Card>

        <Card :title="t('manage.team.people')" flush>
            <ul class="divide-y divide-slate-100">
                <li v-for="m in members" :key="m.id" class="flex items-center gap-3 px-5 py-3">
                    <span class="grid size-9 place-items-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600">{{ (m.name || m.email)[0].toUpperCase() }}</span>
                    <div class="min-w-0 flex-1"><p class="truncate text-sm font-medium text-slate-800">{{ m.name }}</p><p class="truncate text-xs text-slate-500">{{ m.email }}</p></div>
                    <Badge :tone="m.role === 'owner' ? 'indigo' : 'slate'">{{ m.role === 'owner' ? t('manage.team.owner') : t('manage.team.editor') }}</Badge>
                    <Btn v-if="m.role === 'editor'" size="sm" variant="danger" @click="remove(m)"><UserMinus class="size-3.5" /> {{ t('manage.team.remove') }}</Btn>
                </li>
            </ul>
            <form class="flex flex-wrap items-end gap-3 border-t border-slate-200 bg-slate-50/60 p-5" @submit.prevent="add">
                <Field :label="t('manage.team.email')" :error="form.errors.email" class="min-w-56 flex-1"><input v-model="form.email" type="email" class="input" dir="ltr" placeholder="person@example.com" required></Field>
                <Field :label="t('manage.team.nameIfNew')" class="w-44"><input v-model="form.name" class="input"></Field>
                <Btn type="submit" variant="secondary" :loading="form.processing"><UserPlus class="size-4" /> {{ t('manage.team.addEditor') }}</Btn>
            </form>
            <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">{{ t('manage.team.ownersHint') }}</p>
        </Card>
    </div>
</template>
