<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Ban, Building2, CheckCircle2, Clock, EyeOff, Inbox, MailOpen, PhoneOff, Plus, TrendingUp, UserX, UsersRound } from 'lucide-vue-next';
import PageHeader from '../../Components/ui/PageHeader.vue';
import StatCard from '../../Components/ui/StatCard.vue';
import Card from '../../Components/ui/Card.vue';
import Btn from '../../Components/ui/Btn.vue';
import LineChart from '../../Components/ui/LineChart.vue';
import EmptyState from '../../Components/ui/EmptyState.vue';
import { useI18n } from '../../i18n';

const props = defineProps({ kpis: Object, series: Array, attention: Array, recentLeads: Array });
const { t } = useI18n();
const labels = computed(() => props.series.map((p) => p.label));
const values = computed(() => props.series.map((p) => p.count));

// each problem: its picture and the screen that fixes it
const reasons = {
    waiting: { icon: Clock, bg: 'bg-rose-50 text-rose-600', route: 'manage.leads.index' },
    no_owner: { icon: UserX, bg: 'bg-amber-50 text-amber-600', route: 'admin.clients.edit' },
    suspended: { icon: Ban, bg: 'bg-rose-50 text-rose-600', route: 'admin.clients.edit' },
    draft: { icon: EyeOff, bg: 'bg-indigo-50 text-indigo-600', route: 'manage.dashboard' },
    no_contact: { icon: PhoneOff, bg: 'bg-slate-100 text-slate-500', route: 'manage.profile.edit' },
};
</script>

<template>
    <Head :title="t('admin.dashboard.title')" />
    <PageHeader :title="t('admin.dashboard.title')" :description="t('admin.dashboard.description')">
        <Btn :href="route('admin.clients.create')"><Plus class="size-4" /> {{ t('admin.dashboard.newClient') }}</Btn>
    </PageHeader>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard :label="t('admin.dashboard.clients')" :value="kpis.clients" :hint="`${kpis.active} ${t('admin.dashboard.active')} · ${kpis.inactive} ${t('admin.dashboard.hidden')}`" :icon="Building2" :href="route('admin.clients.index')" />
        <StatCard :label="t('admin.dashboard.users')" :value="kpis.users" :hint="t('admin.dashboard.usersHint')" :icon="UsersRound" tone="slate" :href="$page.props.auth.user.is_super_admin ? route('admin.admins.index') : undefined" />
        <StatCard :label="t('admin.dashboard.leads30')" :value="kpis.leads30" :hint="`${kpis.leads} ${t('admin.dashboard.inTotal')}`" :icon="TrendingUp" tone="green" />
        <StatCard :label="t('admin.dashboard.unreadLeads')" :value="kpis.unread" :hint="t('admin.dashboard.unreadHint')" :icon="MailOpen" tone="rose" />
    </div>

    <!-- what needs someone: the overview's own job (the full list of clients lives on the Clients page) -->
    <Card class="mt-6" :title="t('admin.dashboard.attention')" :subtitle="t('admin.dashboard.attentionHint')" flush>
        <ul v-if="attention.length" class="divide-y divide-slate-100">
            <li v-for="(a, i) in attention" :key="i" class="flex flex-wrap items-center gap-3 px-5 py-3">
                <span class="grid size-9 shrink-0 place-items-center rounded-lg" :class="reasons[a.reason].bg"><component :is="reasons[a.reason].icon" class="size-4" /></span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-slate-900">{{ a.name }}</p>
                    <p class="text-xs text-slate-500">{{ t(`admin.dashboard.reason.${a.reason}`, { n: a.count }) }}</p>
                </div>
                <Btn size="sm" variant="secondary" :href="route(reasons[a.reason].route, a.slug)">{{ t(`admin.dashboard.fix.${a.reason}`) }}</Btn>
            </li>
        </ul>
        <EmptyState v-else :icon="CheckCircle2" :title="t('admin.dashboard.allGood')" :text="t('admin.dashboard.allGoodHint')" />
    </Card>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <Card class="xl:col-span-2" :title="t('admin.dashboard.leadsReceived')" :subtitle="t('admin.dashboard.last30')">
            <LineChart :labels="labels" :values="values" :series-label="t('admin.dashboard.leads30')" />
        </Card>

        <Card :title="t('admin.dashboard.recentLeads')" flush>
            <ul v-if="recentLeads.length" class="divide-y divide-slate-100">
                <li v-for="l in recentLeads" :key="l.id" class="flex items-center gap-3 px-5 py-3">
                    <span class="size-2 shrink-0 rounded-full" :class="l.read ? 'bg-slate-200' : 'bg-indigo-500'" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-slate-800">{{ l.name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ l.client }} · {{ l.contact }}</p>
                    </div>
                    <span class="shrink-0 text-xs text-slate-400">{{ l.created }}</span>
                </li>
            </ul>
            <EmptyState v-else :icon="Inbox" :title="t('admin.dashboard.noLeadsYet')" :text="t('admin.dashboard.noLeadsHint')" />
        </Card>
    </div>
</template>
