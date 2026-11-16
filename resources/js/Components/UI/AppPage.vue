<template>
    <section class="app-page min-w-0 space-y-4">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex min-w-0 w-full flex-col items-start gap-3 sm:w-auto sm:flex-1 sm:flex-row sm:items-center">
                <div v-if="$slots.back" class="max-w-full shrink-0"><slot name="back" /></div>
                <h1 class="min-w-0 break-words text-2xl font-bold">{{ title }}</h1>
            </div>
            <div v-if="$slots.actions" class="ml-auto flex flex-wrap items-center justify-end gap-2"><slot name="actions" /></div>
        </header>
        <slot />
    </section>
</template>
<script setup>
import {inject, onMounted, onBeforeUnmount, watch} from 'vue'
import {layoutContentWidthKey} from '@/Composables/layoutContentWidth'
const props = defineProps({title: {type: String, required: true}, fluid:Boolean})
const contentWidth = inject(layoutContentWidthKey, null)
const owner = Symbol('page')
const updateWidth = () => { if (contentWidth) {contentWidth.owner = owner; contentWidth.fluid = props.fluid} }
onMounted(updateWidth)
watch(() => props.fluid, updateWidth)
onBeforeUnmount(() => { if (contentWidth?.owner === owner) {contentWidth.fluid = false; contentWidth.owner = null} })
</script>
<style scoped>
.app-page :deep(.input), .app-page :deep(.select), .app-page :deep(.textarea) { width: 100%; min-width: 0; max-width: 100%; }
.app-page :deep(.input), .app-page :deep(.select) { min-height: 3rem; font-size: 1rem; }
.app-page :deep(.textarea) { min-height: 6rem; }
.app-page :deep(.label) { font-size: .875rem; font-weight: 600; white-space: normal; }
.app-page :deep(.input-error) { color: oklch(var(--er)); font-size: .875rem; }
.app-page :deep(.btn:not([data-action-size="sm"])) { min-height: 3rem; height: auto; white-space: normal; }
.app-page :deep(.btn-circle:not([data-action-size="sm"])), .app-page :deep(.btn-square:not([data-action-size="sm"])) { width: 3rem; height: 3rem; min-width: 3rem; }
.app-page :deep(.table) { font-size: .875rem; }
.app-page :deep(td), .app-page :deep(th) { white-space: normal; overflow-wrap: anywhere; }
.app-page :deep(.vs__dropdown-toggle) { min-height: 3rem; border-color: oklch(var(--bc) / .5); background: oklch(var(--b1)); }
.app-page :deep(.vs__dropdown-menu) { color: oklch(var(--bc)); background: oklch(var(--b1)); border-color: oklch(var(--bc) / .5); }
.app-page :deep(.vs__dropdown-option--highlight) { background: oklch(var(--p)); color: oklch(var(--pc)); }
.app-page :deep(.vs__selected), .app-page :deep(.vs__search) { color: oklch(var(--bc)); }
.app-page :deep(.vs__clear), .app-page :deep(.vs__open-indicator) { fill: oklch(var(--bc)); }
.app-page :deep(.dp__input) { min-height: 3rem; background: oklch(var(--b1)); color: oklch(var(--bc)); border-color: oklch(var(--bc) / .5); }
.app-page :deep(.dp__theme_light), .app-page :deep(.dp__theme_dark) {
    --dp-background-color: oklch(var(--b1)); --dp-text-color: oklch(var(--bc));
    --dp-border-color: oklch(var(--bc) / .5); --dp-hover-color: oklch(var(--b2));
    --dp-hover-text-color: oklch(var(--bc)); --dp-primary-color: oklch(var(--p));
    --dp-primary-text-color: oklch(var(--pc));
}
</style>
