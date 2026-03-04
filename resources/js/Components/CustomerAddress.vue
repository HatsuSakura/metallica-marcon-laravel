<template>
    <Address :address="customer.legal_address" />
</template>
    
<script setup>
    import Address from './Address.vue';

    const props = defineProps({
        customer: Object,
    })
</script>

