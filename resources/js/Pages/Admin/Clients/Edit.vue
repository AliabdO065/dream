<script setup>
import { computed } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ExternalLink, Trash2, UserMinus, UserPlus } from 'lucide-vue-next';
import PageHeader from '../../../Components/ui/PageHeader.vue';
import Card from '../../../Components/ui/Card.vue';
import Btn from '../../../Components/ui/Btn.vue';
import Badge from '../../../Components/ui/Badge.vue';
import Field from '../../../Components/ui/Field.vue';
import Toggle from '../../../Components/ui/Toggle.vue';
import { confirmDialog } from '../../../Composables/useConfirm';
import { useI18n } from '../../../i18n';

const props = defineProps({ target: Object, types: Array, allowed: Array, modules: Array, members: Array });
const { t } = useI18n();
// assistants can't delete a client, suspend it or lift a suspension (the server enforces the same)
const isSuper = computed(() => !!usePage().props.auth.user.is_super_admin);
const statusLocked = computed(() => !isSuper.value && props.target.status === 'suspended');

const form = useForm({
    name: props.target.name, slug: props.target.slug, business_type: props.target.business_type ?? '', status: props.target.status,
    types: props.allowed ?? props.types.map((t) => t.key),
    modules: Object.fromEntries(props.modules.map((m) => [m.key, m.enabled])),
});
const has = (k) => form.types.includes(k);
const toggleType = (k, on) => { form.types = on ? [...form.types, k] : form.types.filter((x) => x !== k); };
const save = () => form.put(route('admin.clients.update', props.target.slug), { preserveScroll: true });

const member = useForm({ email: '', name: '', role: 'owner' });
const addMember = () => member.post(route('admin.clients.members.add', props.target.slug), { preserveScroll: true, onSuccess: () => member.reset() });

async function removeMember(m) {
    if (await confirmDialog({ title: t('admin.clients.edit.removeConfirmTitle', { email: m.email }), message: t('admin.clients.edit.removeConfirmMsg'), confirmLabel: t('admin.clients.edit.remove') }))
        router.delete(route('admin.clients.members.remove', [props.target.slug, m.id]), { preserveScroll: true });
}
async function destroy() {
    if (await confirmDialog({ title: t('admin.clients.edit.deleteConfirmTitle', { name: props.target.name }), message: t('admin.clients.edit.deleteConfirmMsg'), confirmLabel: t('admin.clients.edit.deleteClient') }))
        router.delete(route('admin.clients.destroy', props.target.slug));
}
const tone = computed(() => ({ active: 'green', draft: 'indigo', suspended: 'red' }[props.target.status] ?? 'slate'));
const statusLabel = computed(() => ({ active: t('admin.clients.statusActive'), draft: t('admin.clients.statusDraft'), suspended: t('admin.clients.statusSuspended') }[props.target.status] ?? props.target.status));
</script>

<template>
    <Head :title="`${target.name} · ${t('admin.clients.settings')}`" />
    <a :href="route('admin.clients.index')" class="mb-4 inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800"><ArrowLeft class="size-4 rtl:rotate-180" /> {{ t('admin.clients.edit.allClients') }}</a>
    <PageHeader :title="target.name" :description="t('admin.clients.edit.description')">
        <Badge :tone="tone" dot>{{ statusLabel }}</Badge>
        <Btn variant="secondary" :href="target.url" external><ExternalLink class="size-4" /> {{ t('admin.clients.edit.viewSite') }}</Btn>
        <Btn :href="route('manage.dashboard', target.slug)">{{ t('admin.clients.edit.openDashboard') }}</Btn>
    </PageHeader>

    <div class="max-w-4xl space-y-6">
        <form class="space-y-6" @submit.prevent="save">
            <Card :title="t('admin.clients.edit.basics')">
                <div class="grid gap-5 sm:grid-cols-2">
                    <Field :label="t('admin.clients.edit.name')" :error="form.errors.name" required><input v-model="form.name" class="input" required></Field>
                    <Field :label="t('admin.clients.edit.address')" :error="form.errors.slug" :hint="t('admin.clients.edit.addressHint')" required><input v-model="form.slug" class="input font-mono" dir="ltr" required pattern="[a-z0-9]+(-[a-z0-9]+)*" :readonly="target.is_home"></Field>
                    <Field :label="t('admin.clients.edit.typeLabel')" :error="form.errors.business_type"><input v-model="form.business_type" class="input"></Field>
                    <Field :label="t('admin.clients.edit.status')" :error="form.errors.status">
                        <select v-model="form.status" class="input" :disabled="statusLocked"><option value="active">{{ t('admin.clients.edit.statusActive') }}</option><option value="draft">{{ t('admin.clients.edit.statusDraft') }}</option><option value="suspended" :disabled="!isSuper">{{ t('admin.clients.edit.statusSuspended') }}</option></select>
                    </Field>
                </div>
            </Card>

            <Card :title="t('admin.clients.edit.sectionTypes')" :subtitle="t('admin.clients.edit.sectionTypesHint')">
                <div class="grid gap-2 sm:grid-cols-2">
                    <label v-for="ty in types" :key="ty.key" class="flex items-start gap-3 rounded-xl p-3 ring-1 ring-slate-200 hover:bg-slate-50">
                        <Toggle :model-value="has(ty.key)" :label="ty.label" @update:model-value="toggleType(ty.key, $event)" />
                        <span class="min-w-0"><span class="block text-sm font-medium text-slate-800">{{ ty.label }}</span><span class="block text-xs text-slate-500">{{ ty.description }}</span></span>
                    </label>
                </div>
                <div v-if="modules.length" class="mt-4 space-y-2 border-t border-slate-100 pt-4">
                    <label v-for="m in modules" :key="m.key" class="flex items-center gap-3">
                        <Toggle v-model="form.modules[m.key]" :label="m.key" /> <span class="text-sm text-slate-700">{{ t('admin.clients.edit.module') }}: <b>{{ m.key }}</b></span>
                    </label>
                </div>
            </Card>

            <div class="flex gap-3"><Btn type="submit" size="lg" :loading="form.processing">{{ t('admin.clients.edit.saveChanges') }}</Btn></div>
        </form>

        <Card :title="t('admin.clients.edit.people')" :subtitle="t('admin.clients.edit.peopleHint')" flush>
            <ul class="divide-y divide-slate-100">
                <li v-for="m in members" :key="m.id" class="px-5 py-3">
                    <div class="flex items-center gap-3">
                        <span class="grid size-9 place-items-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600">{{ (m.name || m.email)[0].toUpperCase() }}</span>
                        <div class="min-w-0 flex-1"><p class="truncate text-sm font-medium text-slate-800">{{ m.name }}</p><p class="truncate text-xs text-slate-500">{{ m.email }}</p></div>
                        <Badge :tone="m.role === 'owner' ? 'indigo' : 'slate'">{{ m.role === 'owner' ? t('admin.clients.edit.roleOwner') : t('admin.clients.edit.roleEditor') }}</Badge>
                        <Btn size="sm" variant="danger" @click="removeMember(m)"><UserMinus class="size-3.5" /> {{ t('admin.clients.edit.remove') }}</Btn>
                    </div>
                </li>
                <li v-if="!members.length" class="px-5 py-6 text-center text-sm text-slate-500">{{ t('admin.clients.edit.nobodyYet') }}</li>
            </ul>
            <form class="flex flex-wrap items-end gap-3 border-t border-slate-200 bg-slate-50/60 p-5" @submit.prevent="addMember">
                <Field :label="t('admin.clients.edit.emailLabel')" :error="member.errors.email" class="min-w-56 flex-1"><input v-model="member.email" type="email" class="input" dir="ltr" placeholder="person@example.com" required></Field>
                <Field :label="t('admin.clients.edit.nameIfNew')" class="w-44"><input v-model="member.name" class="input"></Field>
                <Field :label="t('admin.clients.edit.role')" class="w-32"><select v-model="member.role" class="input"><option value="owner">{{ t('admin.clients.edit.roleOwner') }}</option><option value="editor">{{ t('admin.clients.edit.roleEditor') }}</option></select></Field>
                <Btn type="submit" variant="secondary" :loading="member.processing"><UserPlus class="size-4" /> {{ t('admin.clients.edit.addPerson') }}</Btn>
            </form>
        </Card>

        <Card v-if="isSuper && !target.is_home" :title="t('admin.clients.edit.dangerZone')" tone="danger">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <p class="max-w-md text-sm text-slate-500">{{ t('admin.clients.edit.dangerText') }}</p>
                <Btn variant="danger" @click="destroy"><Trash2 class="size-4" /> {{ t('admin.clients.edit.deleteClient') }}</Btn>
            </div>
        </Card>
    </div>
</template>
