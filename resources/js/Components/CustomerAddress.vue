<template>
    <Address :indirizzo = "customer.indirizzo_legale" />
</template>
    
<script setup>
    import Address from './Address.vue';

    const props = defineProps({
        customer: Object,
    })
</script>