<template>
    <div class="flex flex-wrap items-start gap-x-4 gap-y-3 text-sm">
        <section class="min-w-0 space-y-1"><h5 class="font-medium">{{ pickup.label }}</h5><JourneyContainerQuantities :groups="pickup.groups" :completed="completed" /></section>
        <section class="min-w-0 space-y-1"><h5 class="font-medium">{{ completed ? 'Contenitori consegnati' : 'Contenitori da consegnare' }}</h5><JourneyContainerQuantities :groups="delivery.groups" :completed="completed" /></section>
        <section v-if="balance" class="min-w-0 space-y-1"><h5 class="font-medium">Contenitori residui disponibili</h5><JourneyContainerQuantities :groups="balance.groups" completed empty-label="Nessun contenitore residuo" /><p v-if="balance.has_shortage" class="text-xs text-warning">Le consegne superano la dotazione iniziale. Verifica le quantità.</p></section>
    </div>
</template>
<script setup>
import {computed} from 'vue'
import JourneyContainerQuantities from './JourneyContainerQuantities.vue'
import {describeContainers,describeDeliveryContainers,deliveryHoldersForOrders,stopContainerItems,uniqueContainerOrders} from '@/utils/journeyContainers'
const props = defineProps({orders:{type:Array,default:()=>[]},census:{type:Array,default:()=>[]},completed:Boolean,balance:{type:Object,default:null}})
const orders = computed(()=>uniqueContainerOrders(props.orders))
const pickup = computed(()=>describeContainers(stopContainerItems({orders:orders.value}),props.census,props.completed))
const delivery = computed(()=>describeDeliveryContainers(deliveryHoldersForOrders(orders.value),props.completed))
</script>
