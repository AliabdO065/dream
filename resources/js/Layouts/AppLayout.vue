<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell, Building2, ChevronsUpDown, ExternalLink, Layers, LayoutDashboard, LogOut, Menu, Shield, UsersRound, X } from 'lucide-vue-next';
import { menuIcon } from '../Composables/icons';
import { useI18n } from '../i18n';
import Toasts from '../Components/ui/Toasts.vue';
import ConfirmDialog from '../Components/ui/ConfirmDialog.vue';
import LocaleSwitcher from '../Components/ui/LocaleSwitcher.vue';

const { t } = useI18n();
const page = usePage();
const user = computed(() => page.props.auth?.user);
const client = computed(() => page.props.client);
const menu = computed(() => page.props.menu ?? []);
const myClients = computed(() => page.props.auth?.clients ?? []);
const isSuper = computed(() => !!user.value?.is_super_admin);
const isStaff = computed(() => !!user.value?.is_staff); // admin or assistant
const staffLabel = computed(() => (isSuper.value ? t('nav.superAdmin') : t('nav.assistant')));

const sidebarOpen = ref(false);
const userMenuOpen = ref(false);
const userMenuEl = ref(null);

// Labels are translated client-side by route name (the server sends stable route names + icons; a module
// route this dictionary does not know about falls back to the server-provided label).
const menuLabel = { 'manage.dashboard': 'nav.overview', 'manage.leads.index': 'nav.leads', 'manage.sections.index': 'nav.sections', 'manage.profile.edit': 'nav.profile', 'manage.team.index': 'nav.team' };

const platformNav = computed(() => [
    { label: t('nav.overview'), route: 'admin.dashboard', match: 'admin.dashboard', icon: LayoutDashboard },
    // three different things, three different pictures: the numbers, the businesses, the people
    { label: t('nav.clients'), route: 'admin.clients.index', match: 'admin.clients.*', icon: Building2 },
    ...(isSuper.value ? [{ label: t('nav.admins'), route: 'admin.admins.index', match: 'admin.admins.*', icon: UsersRound }] : []),
]);

const workspaceNav = computed(() => menu.value.map((i) => ({
    label: menuLabel[i.route] ? t(menuLabel[i.route]) : i.label, icon: menuIcon(i.icon), badge: i.badge,
    href: route(i.route, client.value.slug),
    active: i.route === 'manage.dashboard' ? route().current('manage.dashboard') : route().current(i.route.replace(/\.[^.]+$/, '') + '.*'),
})));

const unreadTotal = computed(() => menu.value.reduce((n, i) => n + (i.route === 'manage.leads.index' ? i.badge : 0), 0));
const leadsUrl = computed(() => (client.value ? route('manage.leads.index', client.value.slug) : null));
const initials = computed(() => (user.value?.name || '?').split(/\s+/).map((w) => w[0]).slice(0, 2).join('').toUpperCase());
const clientInitial = computed(() => (client.value?.name || '?')[0].toUpperCase());

function logout() { router.post(route('logout')); }
function switchClient(e) { if (e.target.value) router.visit(route('manage.dashboard', e.target.value)); }
function onDocClick(e) { if (userMenuEl.value && !userMenuEl.value.contains(e.target)) userMenuOpen.value = false; }
onMounted(() => document.addEventListener('click', onDocClick));
onBeforeUnmount(() => document.removeEventListener('click', onDocClick));
router.on('navigate', () => { sidebarOpen.value = false; userMenuOpen.value = false; });
</script>

<template>
    <div class="min-h-screen">
        <!-- mobile backdrop -->
        <transition name="fade"><div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden" @click="sidebarOpen = false" /></transition>

        <!-- Sidebar -->
        <!-- the hide/show slide only applies below lg (lg:translate-x-0 always wins on desktop); it is scoped with
             max-lg: so that rtl: (which otherwise wins the cascade over lg: regardless of viewport) cannot hide
             the sidebar off-screen on a wide RTL layout -->
        <aside class="fixed inset-y-0 start-0 z-50 flex w-64 flex-col bg-slate-900 text-slate-300 transition-transform duration-200 lg:translate-x-0"
               :class="sidebarOpen ? 'translate-x-0' : 'max-lg:-translate-x-full max-lg:rtl:translate-x-full'">
            <div class="flex h-16 shrink-0 items-center justify-between px-5">
                <Link :href="isStaff ? route('admin.dashboard') : route('home')" class="flex items-center gap-2.5">
                    <span class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white shadow-lg shadow-indigo-900/40"><Layers class="size-5" /></span>
                    <span class="text-base font-semibold tracking-tight text-white">{{ page.props.app.name }}</span>
                </Link>
                <button type="button" class="text-slate-400 hover:text-white lg:hidden" :aria-label="t('nav.closeMenu')" @click="sidebarOpen = false"><X class="size-5" /></button>
            </div>

            <div class="thin-scroll flex-1 overflow-y-auto px-3 pb-4">
                <!-- who am I managing -->
                <div v-if="client" class="mb-5 rounded-xl bg-white/5 p-3 ring-1 ring-white/10">
                    <div class="flex items-center gap-3">
                        <img v-if="client.logo" :src="client.logo" alt="" class="size-10 shrink-0 rounded-lg bg-white object-cover">
                        <span v-else class="grid size-10 shrink-0 place-items-center rounded-lg bg-indigo-500/20 text-base font-semibold text-indigo-200">{{ clientInitial }}</span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-white">{{ client.name }}</p>
                            <p class="flex items-center gap-1.5 truncate text-xs text-slate-400">
                                <span class="size-1.5 shrink-0 rounded-full" :class="client.status === 'active' ? 'bg-emerald-400' : 'bg-amber-400'" />
                                {{ client.is_home ? t('nav.platformWebsite') : '/' + client.slug }}
                            </p>
                        </div>
                    </div>
                    <div v-if="myClients.length > 1" class="relative mt-3">
                        <select class="w-full appearance-none rounded-lg border-0 bg-white/10 py-1.5 pe-8 ps-3 text-xs text-slate-200 focus:ring-2 focus:ring-indigo-400" :aria-label="t('nav.switchClient')" @change="switchClient">
                            <option v-for="c in myClients" :key="c.slug" :value="c.slug" :selected="c.slug === client.slug" class="text-slate-900">{{ c.name }}</option>
                        </select>
                        <ChevronsUpDown class="pointer-events-none absolute top-1/2 end-2 size-3.5 -translate-y-1/2 text-slate-400" />
                    </div>
                </div>

                <template v-if="client">
                    <p class="mb-2 px-3 text-[11px] font-semibold tracking-wider text-slate-500 uppercase">{{ t('nav.workspace') }}</p>
                    <nav class="space-y-0.5">
                        <Link v-for="i in workspaceNav" :key="i.label" :href="i.href" class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition"
                              :class="i.active ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white'">
                            <component :is="i.icon" class="size-[18px] shrink-0" :class="i.active ? 'text-indigo-300' : 'text-slate-500 group-hover:text-slate-300'" />
                            <span class="flex-1">{{ i.label }}</span>
                            <span v-if="i.badge > 0" class="rounded-full bg-rose-500 px-1.5 py-px text-[11px] font-semibold text-white tabular-nums">{{ i.badge }}</span>
                        </Link>
                    </nav>
                </template>

                <template v-if="isStaff">
                    <p class="mt-6 mb-2 px-3 text-[11px] font-semibold tracking-wider text-slate-500 uppercase">{{ t('nav.platform') }}</p>
                    <nav class="space-y-0.5">
                        <Link v-for="i in platformNav" :key="i.label" :href="route(i.route)" class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition"
                              :class="route().current(i.match) ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white'">
                            <component :is="i.icon" class="size-[18px] shrink-0" :class="route().current(i.match) ? 'text-indigo-300' : 'text-slate-500 group-hover:text-slate-300'" />
                            {{ i.label }}
                        </Link>
                    </nav>
                </template>

            </div>

            <!-- signed-in user -->
            <div class="shrink-0 border-t border-white/10 p-3">
                <div class="flex items-center gap-3 rounded-lg px-2 py-1.5">
                    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-indigo-500/25 text-xs font-semibold text-indigo-100">{{ initials }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-white">{{ user?.name }}</p>
                        <p class="truncate text-xs text-slate-400">{{ isStaff ? staffLabel : user?.email }}</p>
                    </div>
                    <button type="button" class="rounded-md p-1.5 text-slate-400 hover:bg-white/10 hover:text-white" :title="t('nav.logOut')" :aria-label="t('nav.logOut')" @click="logout"><LogOut class="size-4" /></button>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <div class="lg:ps-64">
            <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/85 px-4 backdrop-blur sm:px-6">
                <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" :aria-label="t('nav.openMenu')" @click="sidebarOpen = true"><Menu class="size-5" /></button>

                <div v-if="isStaff && client" class="hidden items-center gap-2 rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-800 ring-1 ring-amber-200 sm:flex">
                    <Shield class="size-3.5" /> {{ isSuper ? t('nav.viewingAsSuperAdmin') : t('nav.viewingAsAssistant') }}
                </div>

                <div class="ms-auto flex items-center gap-2">
                    <LocaleSwitcher />
                    <a v-if="client" :href="client.url" target="_blank" rel="noopener" class="hidden items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50 sm:inline-flex">
                        {{ t('nav.viewSite') }} <ExternalLink class="size-3.5" />
                    </a>
                    <Link v-if="leadsUrl" :href="leadsUrl" class="relative rounded-lg p-2 text-slate-500 hover:bg-slate-100" :aria-label="`${unreadTotal} ${t('nav.unreadLeads')}`">
                        <Bell class="size-5" />
                        <span v-if="unreadTotal > 0" class="absolute top-1 end-1 grid min-w-4 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-semibold text-white">{{ unreadTotal }}</span>
                    </Link>

                    <div ref="userMenuEl" class="relative">
                        <button type="button" class="flex items-center gap-2 rounded-full p-0.5 pe-2 hover:bg-slate-100" :aria-label="t('nav.accountMenu')" @click="userMenuOpen = !userMenuOpen">
                            <span class="grid size-8 place-items-center rounded-full bg-indigo-600 text-xs font-semibold text-white">{{ initials }}</span>
                            <ChevronsUpDown class="size-3.5 text-slate-400" />
                        </button>
                        <transition name="pop">
                            <div v-if="userMenuOpen" class="absolute end-0 mt-2 w-60 origin-top-end rounded-xl bg-white p-1.5 shadow-pop ring-1 ring-slate-200 ltr:origin-top-right rtl:origin-top-left">
                                <div class="px-3 py-2">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ user?.name }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ user?.email }}</p>
                                </div>
                                <div class="my-1 border-t border-slate-100" />
                                <a :href="page.props.app.homeUrl" target="_blank" rel="noopener" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50"><ExternalLink class="size-4" /> {{ t('nav.platformWebsite') }}</a>
                                <button type="button" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50" @click="logout"><LogOut class="size-4" /> {{ t('nav.logOut') }}</button>
                            </div>
                        </transition>
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <slot />
            </main>
        </div>

        <Toasts />
        <ConfirmDialog />
    </div>
</template>
