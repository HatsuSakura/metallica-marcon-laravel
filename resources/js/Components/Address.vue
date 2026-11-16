<template>
    <span class="flex min-w-0 items-start gap-2">
        <font-awesome-icon :icon="['fas', 'location-dot']" class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
        <span class="min-w-0 break-words"><span v-if="label" class="font-semibold">{{ label }}: </span>{{ address }}</span>
    </span>
</template>

<script setup>
    const props = defineProps({
        address: String,
        label: String
    })
</script>
