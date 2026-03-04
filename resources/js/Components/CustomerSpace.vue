<template>
        &nbsp; <span class="text-gray-500">|</span>
        &nbsp; Frequenza: &nbsp; 
            <span v-if="props.customer.is_occasional_customer" class="font-bold">Occasionale</span>
            <span v-else class="font-bold">Continuativo</span>
            &nbsp; <span class="text-gray-500">|</span>
        &nbsp; SDI: &nbsp;<span class="font-bold">{{ props.customer.sdi_code }}</span> <span class="text-gray-500">|</span>
        &nbsp; Tipo: &nbsp; 
            <span v-if="props.customer.business_type" class="font-bold">{{ props.customer.business_type }}</span>
            <span v-else class="font-bold">undef.</span>
        &nbsp;<span class="text-gray-500">|</span>
</template>

<script setup>

const props = defineProps({
    customer: Object,
})

</script>
