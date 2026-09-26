<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Check, Copy, KeyRound, ShieldCheck, UserPlus, Users, X } from 'lucide-vue-next';
import PageHeader from '../../../Components/ui/PageHeader.vue';
import Card from '../../../Components/ui/Card.vue';
import Btn from '../../../Components/ui/Btn.vue';
import Badge from '../../../Components/ui/Badge.vue';
import Field from '../../../Components/ui/Field.vue';
import Pagination from '../../../Components/ui/Pagination.vue';
import { confirmDialog } from '../../../Composables/useConfirm';
import { useI18n } from '../../../i18n';

const { t } = useI18n();
const props = defineProps({ users: Object, tab: String, sites: Array, counts: Object });

// side list: everyone, the platform team, then one entry per site; `site` in the query string picks it ('all' = no filter)
const tabHref = (site) => route('admin.admins.index', site === 'all' ? {} : { site });
const navItems = computed(() => [
    { key: 'all', name: t('admin.admins.tabAll'), count: props.counts.all, icon: Users },
    { key: 'staff', name: t('admin.admins.tabStaff'), count: props.counts.staff, icon: ShieldCheck },
    ...props.sites.map((s) => ({ key: s.slug, name: s.name, count: s.count, logo: s.logo, initial: s.name[0].toUpperCase() })),
]);

const form = useForm({ email: '', name: '', role: 'assistant' });
const add = () => form.post(route('admin.admins.store'), { preserveScroll: true, onSuccess: () => form.reset() });

// '' in the <select> = no platform role
const roleLabel = (r) => ({ admin: t('admin.admins.superAdmin'), assistant: t('admin.admins.assistant') }[r] ?? t('admin.admins.clientUser'));
const roleTone = (r) => ({ admin: 'indigo', assistant: 'amber' }[r] ?? 'slate');

async function change(u, e) {
    const role = e.target.value || null;
    const ok = await confirmDialog({
        title: t('admin.admins.changeConfirmTitle', { email: u.email, role: roleLabel(role) }),
        message: t(`admin.admins.changeConfirmMsg.${role ?? 'none'}`),
        confirmLabel: t('admin.admins.change'), tone: role === 'admin' ? 'primary' : 'danger',
    });
    if (ok) router.put(route('admin.admins.role', u.id), { role }, { preserveScroll: true });
    else e.target.value = u.role ?? ''; // put the select back
}

// New password: the server replaces it and flashes it back ONCE (flash.password) — it is never stored readable.
const page = usePage();
const fresh = ref(null);
watch(() => page.props.flash?.password, (p) => { if (p) { fresh.value = p; copied.value = false; } }, { immediate: true });
const copied = ref(false);
async function copy() {
    await navigator.clipboard.writeText(fresh.value.password);
    copied.value = true;
}
async function resetPassword(u) {
    const ok = await confirmDialog({
        title: t('admin.admins.resetConfirmTitle', { email: u.email }),
        message: t('admin.admins.resetConfirmMsg'),
        confirmLabel: t('admin.admins.resetPassword'), tone: 'danger',
    });
    if (ok) router.post(route('admin.admins.password.reset', u.id), {}, { preserveScroll: true, preserveState: true });
}
</script>

<template>
    <Head :title="t('admin.admins.title')" />
    <PageHeader :title="t('admin.admins.title')" :description="t('admin.admins.description')" />

    <div class="space-y-6">
        <div v-if="fresh" class="flex flex-wrap items-center gap-3 rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-200">
            <KeyRound class="size-5 shrink-0 text-amber-600" />
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-slate-900">{{ t('admin.admins.newPasswordFor', { email: fresh.email }) }}</p>
                <p class="text-xs text-amber-700">{{ t('admin.admins.shownOnce') }}</p>
            </div>
            <code class="rounded-lg bg-white px-3 py-1.5 font-mono text-base tracking-wider text-slate-900 ring-1 ring-amber-200 select-all" dir="ltr">{{ fresh.password }}</code>
            <Btn size="sm" variant="secondary" @click="copy"><component :is="copied ? Check : Copy" class="size-4" /> {{ copied ? t('admin.admins.copied') : t('admin.admins.copy') }}</Btn>
            <button type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-amber-100 hover:text-slate-700" :aria-label="t('admin.admins.close')" @click="fresh = null"><X class="size-4" /></button>
        </div>

        <Card :title="t('admin.admins.rolesTitle')">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl p-4 ring-1 ring-slate-200">
                    <Badge tone="indigo">{{ t('admin.admins.superAdmin') }}</Badge>
                    <p class="mt-2 text-sm text-slate-600">{{ t('admin.admins.adminCan') }}</p>
                </div>
                <div class="rounded-xl p-4 ring-1 ring-slate-200">
                    <Badge tone="amber">{{ t('admin.admins.assistant') }}</Badge>
                    <p class="mt-2 text-sm text-slate-600">{{ t('admin.admins.assistantCan') }}</p>
                </div>
            </div>
        </Card>

        <Card :title="t('admin.admins.addTitle')" :subtitle="t('admin.admins.addHint')">
            <form class="flex flex-wrap items-end gap-3" @submit.prevent="add">
                <Field :label="t('admin.admins.email')" :error="form.errors.email" class="min-w-64 flex-1"><input v-model="form.email" type="email" class="input" dir="ltr" placeholder="person@example.com" required></Field>
                <Field :label="t('admin.admins.nameIfNew')" class="w-52"><input v-model="form.name" class="input"></Field>
                <Field :label="t('admin.admins.colRole')" class="w-44">
                    <select v-model="form.role" class="input"><option value="assistant">{{ t('admin.admins.assistant') }}</option><option value="admin">{{ t('admin.admins.superAdmin') }}</option></select>
                </Field>
                <Btn type="submit" :loading="form.processing"><UserPlus class="size-4" /> {{ t('admin.admins.promoteCreate') }}</Btn>
            </form>
        </Card>

        <div class="grid items-start gap-6 lg:grid-cols-[15rem_minmax(0,1fr)]">
        <Card flush class="lg:sticky lg:top-6">
            <p class="hidden px-4 pt-4 pb-2 text-xs font-semibold tracking-wide text-slate-400 uppercase lg:block">{{ t('admin.admins.bySite') }}</p>
            <nav class="flex gap-1 overflow-x-auto p-2 lg:flex-col lg:overflow-visible lg:pt-0" :aria-label="t('admin.admins.bySite')">
                <template v-for="(tb, i) in navItems" :key="tb.key">
                    <hr v-if="i === 2" class="my-1 hidden border-slate-100 lg:block">
                    <Link :href="tabHref(tb.key)" preserve-scroll
                        class="flex shrink-0 items-center gap-2.5 rounded-lg px-3 py-2 text-sm whitespace-nowrap"
                        :class="tab === tb.key ? 'bg-indigo-50 font-semibold text-indigo-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'">
                        <img v-if="tb.logo" :src="tb.logo" alt="" class="size-6 shrink-0 rounded-md bg-white object-contain ring-1" :class="tab === tb.key ? 'ring-indigo-400' : 'ring-slate-200'" loading="lazy">
                        <span v-else class="grid size-6 shrink-0 place-items-center rounded-md text-xs font-semibold" :class="tab === tb.key ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-500'"><component :is="tb.icon" v-if="tb.icon" class="size-3.5" /><template v-else>{{ tb.initial }}</template></span>
                        <span class="min-w-0 flex-1 truncate">{{ tb.name }}</span>
                        <span class="text-xs tabular-nums" :class="tab === tb.key ? 'text-indigo-600' : 'text-slate-400'">{{ tb.count }}</span>
                    </Link>
                </template>
            </nav>
        </Card>
        <Card flush>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50/70 text-start text-xs font-semibold tracking-wide text-slate-500 uppercase">
                        <tr><th class="px-5 py-3">{{ t('admin.admins.colUser') }}</th><th class="px-5 py-3">{{ t('admin.admins.colRole') }}</th><th class="px-5 py-3">{{ t('admin.admins.colClients') }}</th><th class="px-5 py-3" /></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="u in users.data" :key="u.id" class="hover:bg-slate-50/60">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="grid size-9 place-items-center rounded-full bg-indigo-50 text-xs font-semibold text-indigo-600">{{ (u.name || u.email)[0].toUpperCase() }}</span>
                                    <div><p class="font-medium text-slate-900">{{ u.name }} <span v-if="u.is_me" class="text-xs font-normal text-slate-400">{{ t('admin.admins.you') }}</span></p><p class="text-xs text-slate-500">{{ u.email }}</p></div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5"><Badge :tone="roleTone(u.role)" dot>{{ roleLabel(u.role) }}</Badge></td>
                            <td class="px-5 py-3.5">
                                <div v-if="u.memberships.length" class="flex max-w-md flex-wrap gap-1.5">
                                    <Link v-for="m in u.memberships" :key="m.id" :href="route('admin.clients.edit', m.slug)" class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-xs ring-1 ring-slate-200 hover:bg-slate-50">
                                        <span class="font-medium text-slate-700">{{ m.name }}</span>
                                        <Badge :tone="m.role === 'owner' ? 'indigo' : 'slate'">{{ m.role === 'owner' ? t('admin.clients.edit.roleOwner') : t('admin.clients.edit.roleEditor') }}</Badge>
                                    </Link>
                                </div>
                                <span v-else class="text-slate-400">—</span>
                            </td>
                            <td class="px-5 py-3.5 text-end">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" class="rounded-lg p-1.5 text-slate-400 ring-1 ring-slate-200 hover:bg-amber-50 hover:text-amber-700"
                                        :title="t('admin.admins.resetPassword')" :aria-label="t('admin.admins.resetPassword')" @click="resetPassword(u)"><KeyRound class="size-4" /></button>
                                    <select class="input w-auto py-1 text-xs" :value="u.role ?? ''" :aria-label="t('admin.admins.change')" @change="change(u, $event)">
                                        <option value="admin">{{ t('admin.admins.superAdmin') }}</option>
                                        <option value="assistant">{{ t('admin.admins.assistant') }}</option>
                                        <option value="">{{ t('admin.admins.clientUser') }}</option>
                                    </select>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="users" />
        </Card>
        </div>
    </div>
</template>
