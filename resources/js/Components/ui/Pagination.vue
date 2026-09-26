<script setup>
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { useI18n } from '../../i18n';

// Takes a Laravel paginator (as serialised by Inertia): { links, from, to, total }
defineProps({ paginator: Object });
const { t } = useI18n();
</script>

<template>
    <nav v-if="paginator && paginator.links && paginator.links.length > 3" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-5 py-3">
        <p class="text-sm text-slate-500">{{ t('ui.pagination.showing') }} <b class="text-slate-700">{{ paginator.from }}</b>{{ t('ui.pagination.to') }}<b class="text-slate-700">{{ paginator.to }}</b> {{ t('ui.pagination.ofTotal') }} <b class="text-slate-700">{{ paginator.total }}</b></p>
        <div class="flex items-center gap-1">
            <template v-for="(l, i) in paginator.links" :key="i">
                <!-- Laravel always orders links [Previous, 1, 2, …, Next]; in RTL "previous" points right, so the icons flip. -->
                <span v-if="!l.url" class="grid h-8 min-w-8 place-items-center rounded-md px-2 text-sm text-slate-300">
                    <ChevronLeft v-if="i === 0" class="size-4 rtl:rotate-180" /><ChevronRight v-else-if="i === paginator.links.length - 1" class="size-4 rtl:rotate-180" /><span v-else v-html="l.label" />
                </span>
                <Link v-else :href="l.url" preserve-scroll class="grid h-8 min-w-8 place-items-center rounded-md px-2 text-sm font-medium transition"
                      :class="l.active ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100'">
                    <ChevronLeft v-if="i === 0" class="size-4 rtl:rotate-180" /><ChevronRight v-else-if="i === paginator.links.length - 1" class="size-4 rtl:rotate-180" /><span v-else v-html="l.label" />
                </Link>
            </template>
        </div>
    </nav>
</template>
