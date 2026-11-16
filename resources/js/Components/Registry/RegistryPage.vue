<template>
    <section class="registry-page min-w-0 space-y-4">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="min-w-0 break-words text-2xl font-bold">{{ title }}</h1>
            <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2"><slot name="actions" /></div>
        </header>
        <slot />
    </section>
</template>
<script setup>
defineProps({title: {type: String, required: true}})
</script>
<style scoped>
.registry-page :deep(.input), .registry-page :deep(.select), .registry-page :deep(.textarea) { width: 100%; min-width: 0; max-width: 100%; }
.registry-page :deep(.input), .registry-page :deep(.select) { min-height: 3rem; }
.registry-page :deep(.textarea) { min-height: 6rem; }
.registry-page :deep(.label) { font-size: .875rem; font-weight: 600; white-space: normal; }
.registry-page :deep(.input-error) { color: oklch(var(--er)); font-size: .875rem; }
.registry-page :deep(.btn) { min-height: 3rem; height: auto; white-space: normal; }
.registry-page :deep(.btn-circle), .registry-page :deep(.btn-square) { width: 3rem; height: 3rem; min-width: 3rem; }
.registry-page :deep(.table) { font-size: .875rem; }
.registry-page :deep(td), .registry-page :deep(th) { white-space: normal; overflow-wrap: anywhere; }
</style>
