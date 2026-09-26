<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CheckCheck, Inbox, Mail, MailOpen, Phone, Search } from 'lucide-vue-next';
import PageHeader from '../../../Components/ui/PageHeader.vue';
import Card from '../../../Components/ui/Card.vue';
import Btn from '../../../Components/ui/Btn.vue';
import EmptyState from '../../../Components/ui/EmptyState.vue';
import Pagination from '../../../Components/ui/Pagination.vue';
import { useI18n } from '../../../i18n';

const props = defineProps({ leads: Object, unread: Number, total: Number, filters: Object });
const page = usePage();
const { t } = useI18n();
const slug = computed(() => page.props.client.slug);

const q = ref(props.filters.q);
let timer;
const go = (params) => router.get(route('manage.leads.index', slug.value), params, { preserveState: true, replace: true });
watch(q, (v) => { clearTimeout(timer); timer = setTimeout(() => go({ filter: props.filters.filter === 'unread' ? 'unread' : undefined, q: v || undefined }), 300); });

const tabs = computed(() => [
    { key: 'all', label: t('manage.leads.all'), count: props.total, href: route('manage.leads.index', { client: slug.value, q: q.value || undefined }) },
    { key: 'unread', label: t('manage.leads.unread'), count: props.unread, href: route('manage.leads.index', { client: slug.value, filter: 'unread', q: q.value || undefined }) },
]);

const mark = (l, read) => router.post(route('manage.leads.read', [slug.value, l.id]), { read: read ? 1 : 0 }, { preserveScroll: true });
const markAll = () => router.post(route('manage.leads.read-all', slug.value), {}, { preserveScroll: true });
</script>

<template>
    <Head :title="t('manage.leads.title')" />
    <PageHeader :title="t('manage.leads.title')" :description="t('manage.leads.description')">
        <Btn v-if="unread > 0" variant="secondary" @click="markAll"><CheckCheck class="size-4" /> {{ t('manage.leads.markAllRead', { n: unread }) }}</Btn>
    </PageHeader>

    <Card flush>
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4">
            <div class="inline-flex rounded-lg bg-slate-100 p-1">
                <Link v-for="tb in tabs" :key="tb.key" :href="tb.href" preserve-state class="flex items-center gap-2 rounded-md px-3.5 py-1.5 text-sm font-medium transition" :class="filters.filter === tb.key ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'">
                    {{ tb.label }} <span class="rounded-full px-1.5 text-xs tabular-nums" :class="tb.key === 'unread' && tb.count ? 'bg-rose-500 text-white' : 'bg-slate-200 text-slate-600'">{{ tb.count }}</span>
                </Link>
            </div>
            <div class="relative w-full sm:w-72">
                <Search class="pointer-events-none absolute top-1/2 start-3 size-4 -translate-y-1/2 text-slate-400" />
                <input v-model="q" type="search" class="input ps-9" :placeholder="t('manage.leads.searchPlaceholder')" :aria-label="t('common.search')">
            </div>
        </div>

        <ul v-if="leads.data.length" class="divide-y divide-slate-100">
            <li v-for="l in leads.data" :key="l.id" class="group flex items-center gap-4 px-5 py-4 hover:bg-slate-50/70" :class="!l.read && 'bg-indigo-50/40'">
                <span class="size-2.5 shrink-0 rounded-full" :class="l.read ? 'bg-slate-200' : 'bg-indigo-500'" :title="l.read ? t('manage.leads.show.read') : t('manage.leads.unread')" />
                <Link :href="route('manage.leads.show', [slug, l.id])" class="min-w-0 flex-1">
                    <p class="truncate text-sm" :class="l.read ? 'text-slate-700' : 'font-semibold text-slate-900'">{{ l.name }}</p>
                    <p class="mt-0.5 flex flex-wrap items-center gap-x-4 gap-y-0.5 text-xs text-slate-500">
                        <span v-if="l.phone" class="inline-flex items-center gap-1" dir="ltr"><Phone class="size-3" /> {{ l.phone }}</span>
                        <span v-if="l.email" class="inline-flex items-center gap-1" dir="ltr"><Mail class="size-3" /> {{ l.email }}</span>
                    </p>
                    <p v-if="l.message" class="mt-1 truncate text-sm text-slate-500">{{ l.message }}</p>
                </Link>
                <span class="hidden shrink-0 text-xs text-slate-400 sm:block">{{ l.created }}</span>
                <div class="flex shrink-0 gap-2">
                    <Btn size="sm" variant="secondary" @click="mark(l, !l.read)">
                        <MailOpen v-if="!l.read" class="size-3.5" /><Mail v-else class="size-3.5" /><span class="hidden lg:inline"> {{ l.read ? t('manage.leads.markUnread') : t('manage.leads.markRead') }}</span>
                    </Btn>
                    <Btn size="sm" :href="route('manage.leads.show', [slug, l.id])">{{ t('manage.leads.openBtn') }}</Btn>
                </div>
            </li>
        </ul>
        <EmptyState v-else :icon="Inbox" :title="filters.filter === 'unread' ? t('manage.leads.noneUnread') : (filters.q ? t('manage.leads.noMatches') : t('manage.leads.noneYetTitle'))"
                    :text="filters.filter === 'unread' ? t('manage.leads.noneUnreadText') : t('manage.leads.noneYetText')" />
        <Pagination :paginator="leads" />
    </Card>
</template>
