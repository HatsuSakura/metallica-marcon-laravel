<template>
    <span class="flex items-center gap-2">
        <font-awesome-icon :icon="['fas', 'user-tie']" class="text-2xl" />
        {{ ragioneSociale }}
    </span>
</template>
    
<script setup>
    const props = defineProps({
        ragioneSociale: String,
    })
</script>