<script setup>
import SchemaField from './SchemaField.vue';

// Renders a whole schema (a list of field definitions) bound to one object.
const props = defineProps({ fields: Array, modelValue: Object, errors: Object });
const emit = defineEmits(['update:modelValue']);

const set = (name, value) => emit('update:modelValue', { ...props.modelValue, [name]: value });
</script>

<template>
    <div class="space-y-6">
        <SchemaField v-for="f in fields" :key="f.name" :field="f" :model-value="modelValue[f.name]" @update:model-value="set(f.name, $event)" />
    </div>
</template>
