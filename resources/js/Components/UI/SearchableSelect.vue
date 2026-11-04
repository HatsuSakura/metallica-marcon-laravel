<template>
    <VSelect v-model="model" :options="options" label="label" :reduce="option => option.value" :input-id="inputId" :placeholder="placeholder" :disabled="disabled" class="searchable-select min-w-0 w-full">
        <template #search="{attributes, events}">
            <input v-bind="attributes" class="vs__search" :required="required && (model === '' || model == null)" v-on="events" />
        </template>
        <template #no-options>Nessun risultato.</template>
    </VSelect>
</template>
<script setup>
import VSelect from 'vue-select'
const model = defineModel()
defineProps({options:{type:Array,default:()=>[]},inputId:String,placeholder:{type:String,default:'Cerca e seleziona'},required:Boolean,disabled:Boolean})
</script>
<style>
@import 'vue-select/dist/vue-select.css';
</style>
<style scoped>
.searchable-select {
    --vs-controls-color: oklch(var(--bc));
    --vs-border-color: oklch(var(--bc) / 0.2);
    --vs-dropdown-bg: oklch(var(--b1));
    --vs-dropdown-color: oklch(var(--bc));
    --vs-dropdown-option-color: oklch(var(--bc));
    --vs-selected-color: oklch(var(--bc));
    --vs-search-input-color: oklch(var(--bc));
    --vs-dropdown-option--active-bg: oklch(var(--p));
    --vs-dropdown-option--active-color: oklch(var(--pc));
}
.searchable-select :deep(.vs__dropdown-toggle) { min-height: 3rem; border-radius: var(--rounded-btn, 0.5rem); }
.searchable-select :deep(.vs__selected-options) { min-width: 0; }
.searchable-select :deep(.vs__selected),
.searchable-select :deep(.vs__dropdown-option) { white-space: normal; overflow-wrap: anywhere; }
.searchable-select :deep(.vs__dropdown-option) { min-height: 3rem; display: flex; align-items: center; padding: 0.5rem; }
.searchable-select :deep(.vs__search) { min-width: 2rem; }
.searchable-select :deep(.vs__clear) { min-height: 2.75rem; min-width: 2.75rem; display: inline-flex; align-items: center; justify-content: center; }
</style>
