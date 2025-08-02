<template>
    <div class="flex flex-col md:flex-row gap-2 md:items-center justify-between">
            <div>
                <span class="flex items-center gap-2">
                    <font-awesome-icon :icon="['fas', 'database']" class="text-2xl"/>
                    {{ order.id }}
                </span>
            </div>
            <div>
                <span class="flex items-center gap-2">
                    <font-awesome-icon :icon="['fas', 'file-contract']" class="text-2xl"/>
                    {{ order.legacy_code }}
                </span>
            </div>
            <div>
                <span class="flex items-center gap-2">
                    <font-awesome-icon :icon="['fas', 'mobile-screen']" class="text-2xl"/>
                    {{ customer.seller.name }} {{ customer.seller.surname }}
                </span>
            </div>
            <div><CustomerRagioneSociale :ragioneSociale="customer.ragione_sociale"/></div>
            <div><Address :indirizzo="site.indirizzo" label="Sede"/></div>        
    </div>
</template>

<script setup>
import { computed } from 'vue';
import Address from './Address.vue';
import CustomerRagioneSociale from './CustomerRagioneSociale.vue';
const props = defineProps({
    customer: Object,
    order: Object,
    site: Object,
})


</script>
