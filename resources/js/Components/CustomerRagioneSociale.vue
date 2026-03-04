<template>
    <span class="flex items-center gap-2">
        <font-awesome-icon :icon="['fas', 'user-tie']" class="text-2xl" />
        {{ company_name }}
    </span>
</template>
    
<script setup>
    const props = defineProps({
        company_name: String,
    })
</script>
