<template>
<Box>
    <div class="flex flex-col gap-0">
        <div class="flex flex-row justify-between items-center">
            <div class="flex flex-col gap-0">
                <div>
                    <font-awesome-icon :icon="['fas', 'scroll']" class="text-2xl"/>
                    {{ item.holder_quantity }} X {{ item.holder.name }} = {{ item.weight_declared }} Kg
                </div>
                <div>
                    <font-awesome-icon :icon="['fas', 'magnifying-glass']" class="text-2xl"/>
                    {{ item.cer_code.code }} - {{ item.cer_code.description }}
                </div>
            </div>
            <Link :href="route('relator.journeyCargo.create', {journey:  props.item.id})" class="btn btn-primary btn-outline font-medium text-center">
                Aggiungi a Trasbordo
                <font-awesome-icon :icon="['fas', 'cart-plus']" class="text-2xl"/>
            </Link>
        </div>
            <div>
                <font-awesome-icon :icon="['fas', 'user-tie']" class="text-2xl"/>
                {{ item.order.customer.ragione_sociale }} 
            </div>
            <div>
                <font-awesome-icon :icon="['fas', 'truck-ramp-box']" class="text-2xl"/>
                [sede prelievo] {{ item.order.site.indirizzo }}
            </div>
    </div>
</Box>
</template>

<script setup>
import JourneyHead from '@/Components/JourneyHead.vue';
import Box from '@/Components/UI/Box.vue';
import { Link, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';

const props = defineProps({
    item: Object,
})
</script>