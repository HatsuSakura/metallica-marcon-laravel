<template>
        &nbsp; <span class="text-gray-500">|</span>
        &nbsp; Frequenza: &nbsp; 
            <span v-if="props.customer.customerOccasionale" class="font-bold">Occasionale</span>
            <span v-else class="font-bold">Continuativo</span>
            &nbsp; <span class="text-gray-500">|</span>
        &nbsp; SDI: &nbsp;<span class="font-bold">{{customer.codice_sdi}}</span> <span class="text-gray-500">|</span>
        &nbsp; Tipo: &nbsp; 
            <span v-if="customer.jobType" class="font-bold">{{customer.job_type}}</span>
            <span v-else class="font-bold">undef.</span>
        &nbsp;<span class="text-gray-500">|</span>
</template>

<script setup>

const props = defineProps({
    customer: Object,
})

</script>
