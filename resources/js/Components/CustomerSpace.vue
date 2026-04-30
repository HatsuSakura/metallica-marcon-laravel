<template>
        &nbsp; <span class="text-gray-500">|</span>
        <span v-if="props.vat">&nbsp; P.IVA: &nbsp;<span class="font-bold">{{ props.customer.vat_number }}</span> <span class="text-gray-500">|</span></span>
        &nbsp; Frequenza: &nbsp; 
            <span v-if="props.customer.is_occasional_customer" class="font-bold">Occasionale</span>
            <span v-else class="font-bold">Continuativo</span>
            &nbsp; <span class="text-gray-500">|</span>
        &nbsp; SDI: &nbsp;<span class="font-bold">{{ props.customer.sdi_code }}</span> <span class="text-gray-500">|</span>
        &nbsp; Tipo: &nbsp;
            <span v-if="businessTypeName" class="font-bold">{{ businessTypeName }}</span>
            <span v-else class="font-bold">undef.</span>
        &nbsp;<span class="text-gray-500">|</span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    customer: Object,
    vat: {
        type: Boolean,
        default: false,
    },
})

const businessTypeName = computed(() =>
    props.customer?.business_type?.name
    ?? props.customer?.businessType?.name
    ?? null
)

</script>
