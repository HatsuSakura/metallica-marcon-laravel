<template>
    <div class="flex flex-col gap-2">

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

        <div class="flex flex-col md:flex-row gap-2 md:items-center justify-between">
            <div>
                <span class="flex items-center gap-2">
                    <font-awesome-icon :icon="['fas', 'id-card']" class="text-2xl"/>
                    {{ order.journey.driver.name }} {{ order.journey.driver.surname }}
                </span>
            </div>
            <div>
                <span class="flex items-center gap-2">
                    <font-awesome-icon :icon="['fas', 'download']" class="text-2xl"/>
                    {{ dayjs(order.journey.updated_at).format('DD-MM-YYYY HH:MM')  }}
                </span>             
            </div>
            <div>
                <span class="flex items-center gap-2">
                    <font-awesome-icon :icon="['fas', 'truck']" class="text-2xl"/>
                {{ cargoVehicle.carrier.carrier_data.plate }} | {{ cargoVehicle.cargo.description }}
                </span>
                
            </div>
            <div v-if="cargoTrailer != [] && cargoTrailer != undefined">
                <span class="flex items-center gap-2">
                    <font-awesome-icon :icon="['fas', 'trailer']" class="text-2xl"/>
                {{ cargoTrailer.carrier.carrier_data.plate }} | {{ cargoTrailer.cargo.description }}
                </span>
            </div>
        </div>

    </div>

</template>

<script setup>
import { computed } from 'vue';
import Address from './Address.vue';
import dayjs from 'dayjs';
import CustomerRagioneSociale from './CustomerRagioneSociale.vue';
const props = defineProps({
    customer: Object,
    order: Object,
    site: Object,
})

const cargoVehicle = computed(() =>  props.order.journey.journey_cargos.find(c => c.carrier.is_vehicle === true));
const cargoTrailer = computed(() =>  props.order.journey.journey_cargos.find(c => c.carrier.is_vehicle === false));

</script>
