<template>
    <span class="flex items-center gap-2">
        <font-awesome-icon :icon="['fas', 'user-tie']" class="text-2xl" />
        {{ props.customer.vat_number }}
    </span>
</template>
    
<script setup>
    const props = defineProps({
        customer: Object,
    })
</script>
