<script setup>
import { ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Building2, Check, Copy, ExternalLink, MessageCircle, Pin, Plus, Search, X } from 'lucide-vue-next';
import PageHeader from '../../../Components/ui/PageHeader.vue';
import Card from '../../../Components/ui/Card.vue';
import Btn from '../../../Components/ui/Btn.vue';
import Badge from '../../../Components/ui/Badge.vue';
import ImagePreview from '../../../Components/ui/ImagePreview.vue';
import EmptyState from '../../../Components/ui/EmptyState.vue';
import Pagination from '../../../Components/ui/Pagination.vue';
import { useI18n } from '../../../i18n';

const props = defineProps({ clients: Object, filters: Object });
const page = usePage();
const { t } = useI18n();

const q = ref(props.filters.q);
const status = ref(props.filters.status);
let timer;
function reload() {
    router.get(route('admin.clients.index'), { q: q.value || undefined, status: status.value || undefined }, { preserveState: true, replace: true });
}
watch(q, () => { clearTimeout(timer); timer = setTimeout(reload, 300); });
watch(status, reload);

const tone = (s) => ({ active: 'green', draft: 'indigo', suspended: 'red' }[s] ?? 'slate');
const statusLabel = (s) => ({ active: t('admin.clients.statusActive'), draft: t('admin.clients.statusDraft'), suspended: t('admin.clients.statusSuspended') }[s] ?? s);

// credentials of a client that was just created (shown once)
const created = ref(page.props.flash?.created ?? null);
watch(() => page.props.flash?.created, (v) => { if (v) created.value = v; });
const copied = ref('');
const logoPreview = ref(null); // { src, name } of the logo being viewed full size
async function copy(text, what) { await navigator.clipboard?.writeText(text); copied.value = what; setTimeout(() => (copied.value = ''), 1800); }

// a ready-to-send message for the owner (WhatsApp, email…) with everything they need to sign in
const welcome = (c) => [
    t('admin.clients.welcome.hello', { name: c.name }),
    `${t('admin.clients.welcome.site')}: ${c.url}`,
    `${t('admin.clients.welcome.login')}: ${c.login_url}`,
    `${t('admin.clients.welcome.email')}: ${c.email}`,
    c.password ? `${t('admin.clients.welcome.password')}: ${c.password}` : t('admin.clients.welcome.samePassword'),
].join('\n');
</script>

<template>
    <Head :title="t('admin.clients.title')" />
    <PageHeader :title="t('admin.clients.title')" :description="t('admin.clients.businessesOnPlatform', { n: clients.total })">
        <Btn :href="route('admin.clients.create')"><Plus class="size-4" /> {{ t('admin.clients.newClient') }}</Btn>
    </PageHeader>

    <transition name="fade">
        <div v-if="created" class="mb-6 rounded-2xl bg-emerald-50 p-5 ring-1 ring-emerald-200">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="flex items-center gap-2 font-semibold text-emerald-900"><Check class="size-5" /> {{ t('admin.clients.wasCreated', { name: created.name }) }}</p>
                    <dl class="mt-3 grid gap-x-8 gap-y-1.5 text-sm text-emerald-900 sm:grid-cols-[auto_1fr]">
                        <dt class="text-emerald-700">{{ t('admin.clients.publicSite') }}</dt><dd><a :href="created.url" target="_blank" rel="noopener" class="font-medium underline">{{ created.url }}</a></dd>
                        <dt class="text-emerald-700">{{ t('admin.clients.ownerLogin') }}</dt><dd class="font-mono" dir="ltr">{{ created.email }}</dd>
                        <dt class="text-emerald-700">{{ t('admin.clients.password') }}</dt>
                        <dd v-if="created.password" class="flex items-center gap-2"><code class="rounded bg-white/70 px-2 py-0.5 font-mono" dir="ltr">{{ created.password }}</code>
                            <button type="button" class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700 hover:text-emerald-900" @click="copy(created.password, 'password')"><Copy class="size-3.5" /> {{ copied === 'password' ? t('admin.clients.copied') : t('admin.clients.copy') }}</button>
                            <span class="text-xs text-emerald-700">— {{ t('admin.clients.shownOnce') }}</span></dd>
                        <dd v-else class="text-emerald-700">{{ t('admin.clients.existingUserKeeps') }}</dd>
                    </dl>
                    <p v-if="created.published === false" class="mt-3 text-sm text-emerald-800">{{ t('admin.clients.hiddenUntilReady') }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <Btn v-if="created.slug" size="sm" :href="route('manage.dashboard', created.slug)">{{ t('admin.clients.startEditing') }}</Btn>
                        <Btn size="sm" variant="secondary" @click="copy(welcome(created), 'message')"><MessageCircle class="size-3.5" /> {{ copied === 'message' ? t('admin.clients.copied') : t('admin.clients.copyWelcome') }}</Btn>
                    </div>
                </div>
                <button type="button" class="text-emerald-600 hover:text-emerald-900" :aria-label="t('common.close')" @click="created = null"><X class="size-5" /></button>
            </div>
        </div>
    </transition>

    <Card flush>
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-200 p-4">
            <div class="relative min-w-56 flex-1 sm:max-w-sm">
                <Search class="pointer-events-none absolute top-1/2 start-3 size-4 -translate-y-1/2 text-slate-400" />
                <input v-model="q" type="search" class="input ps-9" :placeholder="t('admin.clients.searchPlaceholder')" :aria-label="t('admin.clients.title')">
            </div>
            <select v-model="status" class="input w-auto" :aria-label="t('admin.clients.allStatuses')">
                <option value="">{{ t('admin.clients.allStatuses') }}</option>
                <option value="active">{{ t('admin.clients.statusActive') }}</option><option value="draft">{{ t('admin.clients.statusDraft') }}</option><option value="suspended">{{ t('admin.clients.statusSuspended') }}</option>
            </select>
        </div>

        <div v-if="clients.data.length" class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/70 text-start text-xs font-semibold tracking-wide text-slate-500 uppercase">
                    <tr><th class="px-5 py-3">{{ t('admin.clients.colClient') }}</th><th class="px-5 py-3">{{ t('admin.clients.colType') }}</th><th class="px-5 py-3">{{ t('admin.clients.colStatus') }}</th><th class="px-5 py-3">{{ t('admin.clients.colOwner') }}</th><th class="px-5 py-3">{{ t('admin.clients.colSections') }}</th><th class="px-5 py-3">{{ t('admin.clients.colCreated') }}</th><th class="px-5 py-3" /></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="c in clients.data" :key="c.id" :class="c.is_home ? 'bg-indigo-50/50 hover:bg-indigo-50' : 'hover:bg-slate-50/60'">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <button v-if="c.logo" type="button" class="shrink-0 cursor-zoom-in rounded-lg transition hover:scale-110 hover:shadow-md" :aria-label="c.name" @click="logoPreview = { src: c.logo, name: c.name }">
                                    <img :src="c.logo" alt="" class="size-9 rounded-lg bg-white object-contain ring-1 ring-slate-200" loading="lazy">
                                </button>
                                <span v-else class="grid size-9 shrink-0 place-items-center rounded-lg text-sm font-semibold" :class="c.is_home ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-600'">{{ c.name[0].toUpperCase() }}</span>
                                <div class="min-w-0">
                                    <p class="flex items-center gap-1.5 truncate font-medium text-slate-900">{{ c.name }}<Pin v-if="c.is_home" class="size-3.5 shrink-0 rotate-45 fill-indigo-600 text-indigo-600" :aria-label="t('admin.clients.pinned')" /></p>
                                    <a :href="c.url" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-indigo-600">{{ c.is_home ? t('admin.clients.platformSite') : '/' + c.slug }} <ExternalLink class="size-3" /></a>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-slate-600">{{ c.business_type || '—' }}</td>
                        <td class="px-5 py-3.5"><Badge :tone="tone(c.status)" dot>{{ statusLabel(c.status) }}</Badge></td>
                        <td class="max-w-48 truncate px-5 py-3.5 text-slate-600">{{ c.owners.join(', ') || '—' }}</td>
                        <td class="px-5 py-3.5 text-slate-600 tabular-nums">{{ c.sections }}</td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-slate-500">{{ c.created }}</td>
                        <td class="px-5 py-3.5">
                            <div class="flex justify-end gap-2">
                                <Btn size="sm" :href="route('manage.dashboard', c.slug)">{{ t('admin.clients.manage') }}</Btn>
                                <Btn size="sm" variant="secondary" :href="route('admin.clients.edit', c.slug)">{{ t('admin.clients.settings') }}</Btn>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <EmptyState v-else :icon="Building2" :title="t('admin.clients.notFoundTitle')" :text="t('admin.clients.notFoundText')">
            <Btn :href="route('admin.clients.create')"><Plus class="size-4" /> {{ t('admin.clients.newClient') }}</Btn>
        </EmptyState>
        <Pagination :paginator="clients" />
        <ImagePreview :model-value="logoPreview?.src ?? null" :alt="logoPreview?.name ?? ''" @update:model-value="logoPreview = null" />
    </Card>
</template>
