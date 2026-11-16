<template>
    <span class="flex min-w-0 items-start gap-2 text-sm leading-5">
        <font-awesome-icon :icon="['fas', 'list-ul']" class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
        <span class="flex min-w-0 flex-wrap gap-x-3 gap-y-1">
        <span v-if="vat">P. IVA: <span class="font-medium">{{ customer.vat_number || '—' }}</span></span>
        <span>Frequenza: <span class="font-medium">{{ customer.is_occasional_customer ? 'Occasionale' : 'Continuativo' }}</span></span>
        <span>SDI: <span class="font-medium">{{ customer.sdi_code || '—' }}</span></span>
        <span>Tipologia: <span class="font-medium">{{ businessTypeName || 'Non indicata' }}</span></span>
        </span>
    </span>
</template>
<script setup>
import {computed} from 'vue'
const props = defineProps({customer:Object, vat:Boolean})
const businessTypeName = computed(() => props.customer?.business_type?.name ?? props.customer?.businessType?.name ?? null)
</script>
