<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Mail, MailOpen, Phone, Trash2, User } from 'lucide-vue-next';
import PageHeader from '../../../Components/ui/PageHeader.vue';
import Card from '../../../Components/ui/Card.vue';
import Btn from '../../../Components/ui/Btn.vue';
import Badge from '../../../Components/ui/Badge.vue';
import { confirmDialog } from '../../../Composables/useConfirm';
import { useI18n } from '../../../i18n';

const props = defineProps({ lead: Object });
const { t } = useI18n();
const page = usePage();
const slug = computed(() => page.props.client.slug);
const can = computed(() => page.props.auth.membership?.can ?? {});
const tel = computed(() => (props.lead.phone || '').replace(/[^0-9+]/g, ''));

const markUnread = () => router.post(route('manage.leads.read', [slug.value, props.lead.id]), { read: 0 }, { onSuccess: () => router.visit(route('manage.leads.index', slug.value)) });
async function destroy() {
    if (await confirmDialog({ title: t('manage.leads.show.deleteConfirmTitle'), message: t('manage.leads.show.deleteConfirmMsg'), confirmLabel: t('manage.leads.show.deleteConfirmBtn') }))
        router.delete(route('manage.leads.destroy', [slug.value, props.lead.id]));
}
</script>

<template>
    <Head :title="`${t('nav.leads')} · ${lead.name}`" />
    <Link :href="route('manage.leads.index', slug)" class="mb-4 inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800"><ArrowLeft class="size-4 rtl:rotate-180" /> {{ t('manage.leads.show.allLeads') }}</Link>
    <PageHeader :title="lead.name" :description="t('manage.leads.show.receivedOn', { date: lead.created_at })">
        <Badge tone="green" dot>{{ t('manage.leads.show.read') }}</Badge>
    </PageHeader>

    <div class="grid max-w-5xl items-start gap-6 lg:grid-cols-[1fr_20rem]">
        <Card :title="t('manage.leads.show.message')">
            <p v-if="lead.message" class="text-[15px] leading-7 whitespace-pre-line text-slate-700">{{ lead.message }}</p>
            <p v-else class="text-sm text-slate-400">{{ t('manage.leads.show.noMessage') }}</p>
        </Card>

        <div class="space-y-6">
            <Card :title="t('manage.leads.show.contact')">
                <ul class="space-y-4 text-sm">
                    <li class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-lg bg-slate-100 text-slate-500"><User class="size-4" /></span><span class="font-medium text-slate-800">{{ lead.name }}</span></li>
                    <li v-if="lead.phone" class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-lg bg-emerald-50 text-emerald-600"><Phone class="size-4" /></span><a :href="`tel:${tel}`" class="font-medium text-indigo-600 hover:underline" dir="ltr">{{ lead.phone }}</a></li>
                    <li v-if="lead.email" class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-lg bg-indigo-50 text-indigo-600"><Mail class="size-4" /></span><a :href="`mailto:${lead.email}`" class="font-medium break-all text-indigo-600 hover:underline" dir="ltr">{{ lead.email }}</a></li>
                    <li class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-lg bg-slate-100 text-slate-500"><Calendar class="size-4" /></span><span class="text-slate-600">{{ lead.created_at }}</span></li>
                </ul>
                <div class="mt-5 flex flex-wrap gap-2 border-t border-slate-100 pt-5">
                    <Btn v-if="lead.phone" :href="`tel:${tel}`" external size="sm"><Phone class="size-3.5" /> {{ t('manage.leads.show.call') }}</Btn>
                    <Btn v-if="lead.email" :href="`mailto:${lead.email}`" external size="sm" variant="secondary"><Mail class="size-3.5" /> {{ t('manage.leads.show.reply') }}</Btn>
                </div>
            </Card>

            <Card>
                <div class="flex flex-wrap gap-2">
                    <Btn variant="secondary" size="sm" @click="markUnread"><MailOpen class="size-3.5" /> {{ t('manage.leads.show.markAsUnread') }}</Btn>
                    <Btn v-if="can['leads.delete']" variant="danger" size="sm" @click="destroy"><Trash2 class="size-3.5" /> {{ t('manage.leads.show.deleteBtn') }}</Btn>
                </div>
            </Card>
        </div>
    </div>
</template>
