<template>
    <span class="flex items-center gap-2">
        <font-awesome-icon :icon="['fas', 'location-dot']" class="text-2xl"/>
        <span v-if="label" class="font-semibold mx-2">{{ label }}:</span> {{ indirizzo }}
    </span>
</template>
    
<script setup>
    const props = defineProps({
        indirizzo: String,
        label: String
    })
</script>