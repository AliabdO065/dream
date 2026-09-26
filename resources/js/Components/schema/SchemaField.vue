<script setup>
import { defineAsyncComponent, inject } from 'vue';
import Toggle from '../ui/Toggle.vue';
import ImageField from './ImageField.vue';
import { isRtl } from './schema';

// ListField renders SchemaField for its sub-fields, so it is loaded lazily to break the import cycle.
const ListField = defineAsyncComponent(() => import('./ListField.vue'));

const props = defineProps({ field: Object, modelValue: null, inRow: Boolean });
const emit = defineEmits(['update:modelValue']);

const locales = inject('locales', ['en']);
const languages = inject('languages', {});

const setLocale = (loc, v) => emit('update:modelValue', { ...props.modelValue, [loc]: v });
</script>

<template>
    <!-- switch -->
    <label v-if="field.type === 'checkbox'" class="flex items-center gap-3">
        <Toggle :model-value="!!modelValue" :label="field.label" @update:model-value="emit('update:modelValue', $event)" />
        <span class="text-sm text-slate-700">{{ field.label }}</span>
    </label>

    <div v-else>
        <div class="mb-1.5 flex items-baseline justify-between gap-2">
            <label class="block text-sm font-medium text-slate-700">{{ field.label }}</label>
        </div>

        <select v-if="field.type === 'select'" class="input" :value="modelValue" @change="emit('update:modelValue', $event.target.value)">
            <option v-for="o in field.options" :key="o.value" :value="o.value">{{ o.label }}</option>
        </select>

        <ImageField v-else-if="field.type === 'image'" :model-value="modelValue" :compact="inRow" @update:model-value="emit('update:modelValue', $event)" />

        <ListField v-else-if="field.type === 'list'" :field="field" :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)" />

        <!-- text / textarea / url, one input per language when translatable -->
        <div v-else-if="field.translatable" class="space-y-2">
            <div v-for="loc in locales" :key="loc" class="flex items-start gap-2">
                <span v-if="locales.length > 1" class="mt-1.5 grid h-7 w-9 shrink-0 place-items-center rounded-md bg-slate-100 text-[11px] font-semibold text-slate-600 uppercase" :title="languages[loc] || loc">{{ loc }}</span>
                <textarea v-if="field.type === 'textarea'" class="input" rows="3" :maxlength="field.max || 5000" :dir="isRtl(loc) ? 'rtl' : 'ltr'" :value="modelValue?.[loc] ?? ''" @input="setLocale(loc, $event.target.value)" />
                <input v-else class="input" type="text" :maxlength="field.max || 255" :dir="isRtl(loc) ? 'rtl' : 'ltr'" :value="modelValue?.[loc] ?? ''" @input="setLocale(loc, $event.target.value)">
            </div>
        </div>
        <textarea v-else-if="field.type === 'textarea'" class="input" rows="3" :maxlength="field.max || 5000" :value="modelValue" @input="emit('update:modelValue', $event.target.value)" />
        <input v-else class="input" :type="'text'" :maxlength="field.max || 255" :placeholder="field.type === 'url' ? 'https://… , #anchor , tel: , mailto:' : ''" :value="modelValue" @input="emit('update:modelValue', $event.target.value)">
    </div>
</template>
