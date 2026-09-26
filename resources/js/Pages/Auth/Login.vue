<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Globe2, Inbox, Layers, LayoutTemplate, Loader2 } from 'lucide-vue-next';
import Btn from '../../Components/ui/Btn.vue';
import Field from '../../Components/ui/Field.vue';
import LocaleSwitcher from '../../Components/ui/LocaleSwitcher.vue';
import { useI18n } from '../../i18n';

const { t } = useI18n();
const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post(route('login'), { onFinish: () => form.reset('password') });

const points = [
    { icon: LayoutTemplate, title: t('auth.login.f1t'), text: t('auth.login.f1d') },
    { icon: Inbox, title: t('auth.login.f2t'), text: t('auth.login.f2d') },
    { icon: Globe2, title: t('auth.login.f3t'), text: t('auth.login.f3d') },
];
</script>

<template>
    <Head :title="t('auth.login.logIn')" />
    <div class="grid min-h-screen lg:grid-cols-[1.05fr_1fr]">
        <!-- brand panel -->
        <aside class="relative hidden overflow-hidden bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-800 p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="pointer-events-none absolute -top-32 -end-24 size-[28rem] rounded-full bg-white/10 blur-3xl" />
            <div class="pointer-events-none absolute -bottom-40 -start-24 size-[30rem] rounded-full bg-fuchsia-400/20 blur-3xl" />
            <a :href="$page.props.app.homeUrl" class="relative flex items-center gap-2.5 text-lg font-semibold">
                <span class="grid size-10 place-items-center rounded-xl bg-white/15 ring-1 ring-white/25"><Layers class="size-5" /></span>
                {{ $page.props.app.name }}
            </a>
            <div class="relative max-w-md">
                <h1 class="text-4xl font-semibold tracking-tight">{{ t('auth.login.title') }}</h1>
                <p class="mt-4 text-lg text-indigo-100">{{ t('auth.login.subtitle') }}</p>
                <ul class="mt-10 space-y-6">
                    <li v-for="p in points" :key="p.title" class="flex gap-4">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-white/12 ring-1 ring-white/20"><component :is="p.icon" class="size-5" /></span>
                        <div><p class="font-semibold">{{ p.title }}</p><p class="text-sm text-indigo-100">{{ p.text }}</p></div>
                    </li>
                </ul>
            </div>
            <p class="relative text-sm text-indigo-200">© {{ new Date().getFullYear() }} {{ $page.props.app.name }}</p>
        </aside>

        <!-- form -->
        <main class="flex items-center justify-center bg-slate-50 px-6 py-12">
            <div class="w-full max-w-sm">
                <div class="mb-8 flex items-center justify-between">
                    <a :href="$page.props.app.homeUrl" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800"><ArrowLeft class="size-4 rtl:rotate-180" /> {{ t('auth.login.backToWebsite') }}</a>
                    <LocaleSwitcher />
                </div>
                <h2 class="text-2xl font-semibold tracking-tight text-slate-900">{{ t('auth.login.welcome') }}</h2>
                <p class="mt-1.5 text-sm text-slate-500">{{ t('auth.login.loginToManage') }}</p>

                <form class="mt-8 space-y-5" @submit.prevent="submit">
                    <Field :label="t('auth.login.email')" :error="form.errors.email" for="email">
                        <input id="email" v-model="form.email" type="email" class="input" :class="form.errors.email && 'input-error'" autocomplete="username" autofocus required dir="ltr">
                    </Field>
                    <Field :label="t('auth.login.password')" :error="form.errors.password" for="password">
                        <input id="password" v-model="form.password" type="password" class="input" autocomplete="current-password" required dir="ltr">
                    </Field>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> {{ t('auth.login.rememberMe') }}
                    </label>
                    <Btn type="submit" size="lg" class="w-full" :loading="form.processing">{{ t('auth.login.logIn') }}</Btn>
                </form>
            </div>
        </main>
    </div>
</template>
