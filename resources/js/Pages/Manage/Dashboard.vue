<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, CheckCircle2, Circle, Inbox, LayoutTemplate, MailOpen, Plus, TrendingUp } from 'lucide-vue-next';
import PageHeader from '../../Components/ui/PageHeader.vue';
import StatCard from '../../Components/ui/StatCard.vue';
import Card from '../../Components/ui/Card.vue';
import Btn from '../../Components/ui/Btn.vue';
import Badge from '../../Components/ui/Badge.vue';
import LineChart from '../../Components/ui/LineChart.vue';
import EmptyState from '../../Components/ui/EmptyState.vue';
import { useI18n } from '../../i18n';

const props = defineProps({ kpis: Object, series: Array, recentLeads: Array, languages: Array, checklist: Array });
const page = usePage();
const { t } = useI18n();
const client = computed(() => page.props.client);
const slug = computed(() => client.value.slug);

const labels = computed(() => props.series.map((p) => p.label));
const values = computed(() => props.series.map((p) => p.count));
// The label text is translated client-side by position; done/route come from the server.
const checklist = computed(() => props.checklist.map((c, i) => ({ ...c, label: t('manage.dashboard.checklist')[i] ?? c.label })));
const done = computed(() => props.checklist.filter((c) => c.done).length);
const percent = computed(() => Math.round((done.value / props.checklist.length) * 100));
// an editor still sees the profile steps (they're part of the progress) but can't open the profile screen
const can = computed(() => page.props.auth.membership?.can ?? {});
const linkable = (c) => c.route !== 'manage.profile.edit' || can.value['profile.edit'];
</script>

<template>
    <Head :title="t('nav.overview')" />
    <PageHeader :title="client.name" :description="t('manage.dashboard.description')">
        <Badge :tone="client.status === 'active' ? 'green' : 'amber'" dot>{{ client.status === 'active' ? t('manage.dashboard.live') : client.status }}</Badge>
        <Btn :href="route('manage.sections.create', slug)"><Plus class="size-4" /> {{ t('manage.dashboard.addSection') }}</Btn>
    </PageHeader>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard :label="t('manage.dashboard.newLeads')" :value="kpis.unread" :hint="t('manage.dashboard.unreadRequests')" :icon="MailOpen" tone="rose" :href="route('manage.leads.index', { client: slug, filter: 'unread' })" />
        <StatCard :label="t('manage.dashboard.leads7')" :value="kpis.leads7" :hint="`${kpis.leads} ${t('manage.dashboard.inTotal')}`" :icon="TrendingUp" tone="green" :href="route('manage.leads.index', slug)" />
        <StatCard :label="t('manage.dashboard.leads30')" :value="kpis.leads30" :hint="t('manage.dashboard.leads30Hint')" :icon="Inbox" />
        <StatCard :label="t('manage.dashboard.liveSections')" :value="`${kpis.sections_on}/${kpis.sections}`" :hint="t('manage.dashboard.languagesLabel', { list: languages.join(', ') })" :icon="LayoutTemplate" tone="amber" :href="route('manage.sections.index', slug)" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <Card class="xl:col-span-2" :title="t('manage.dashboard.leadsReceived')" :subtitle="t('manage.dashboard.last30')">
            <LineChart :labels="labels" :values="values" :series-label="t('nav.leads')" />
        </Card>

        <Card :title="t('manage.dashboard.gettingStarted')" :subtitle="t('manage.dashboard.doneOf', { done, total: checklist.length })">
            <div class="mb-4 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-violet-500 transition-all" :style="{ width: percent + '%' }" /></div>
            <ul class="space-y-1">
                <li v-for="c in checklist" :key="c.label">
                    <component :is="linkable(c) ? Link : 'div'" :href="linkable(c) ? route(c.route, slug) : undefined" class="group flex items-center gap-3 rounded-lg px-2 py-2 text-sm" :class="linkable(c) && 'hover:bg-slate-50'">
                        <CheckCircle2 v-if="c.done" class="size-5 shrink-0 text-emerald-500" />
                        <Circle v-else class="size-5 shrink-0 text-slate-300" />
                        <span class="flex-1" :class="c.done ? 'text-slate-400 line-through' : 'text-slate-700'">{{ c.label }}</span>
                        <ArrowRight v-if="!c.done && linkable(c)" class="size-4 text-slate-300 group-hover:text-indigo-500 rtl:rotate-180" />
                    </component>
                </li>
            </ul>
        </Card>
    </div>

    <Card class="mt-6" :title="t('manage.dashboard.latestLeads')" flush>
        <template #actions><Btn variant="ghost" size="sm" :href="route('manage.leads.index', slug)">{{ t('manage.dashboard.viewAll') }}</Btn></template>
        <ul v-if="recentLeads.length" class="divide-y divide-slate-100">
            <li v-for="l in recentLeads" :key="l.id">
                <Link :href="route('manage.leads.show', [slug, l.id])" class="flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50/70">
                    <span class="size-2 shrink-0 rounded-full" :class="l.read ? 'bg-slate-200' : 'bg-indigo-500'" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm" :class="l.read ? 'text-slate-700' : 'font-semibold text-slate-900'">{{ l.name }} <span class="font-normal text-slate-400">· {{ l.phone || l.email }}</span></p>
                        <p v-if="l.message" class="truncate text-sm text-slate-500">{{ l.message }}</p>
                    </div>
                    <span class="shrink-0 text-xs text-slate-400">{{ l.created }}</span>
                </Link>
            </li>
        </ul>
        <EmptyState v-else :icon="Inbox" :title="t('manage.dashboard.noLeadsTitle')" :text="t('manage.dashboard.noLeadsText')" />
    </Card>
</template>
