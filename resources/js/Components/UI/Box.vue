<template>
    <div class="min-w-0 rounded-xl border border-base-300 bg-base-100" :class="padding">
        <div v-if="$slots.header" class="mb-2 font-semibold text-base-content"><slot name="header" /></div>
        <slot />
    </div>
</template>
<script setup>
defineProps({padding: {type: String, default: 'p-4'}})
</script>
