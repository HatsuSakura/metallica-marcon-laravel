<template>
    <h2 class="text-center mb-4 text-xl">Ordini in Corso</h2>
    <ul v-if="props.orders.length" class="">
        <li v-for="order in props.orders" :key="order.id" class="flex flex-row gap-2 items-center mb-2">
            Ord. #{{String( order.id ).padStart(6, '0')}} del {{ dayjs(order.requested_at).format('DD-MM-YYYY HH:mm') }}
            <Link
                :href="route('relator.order.edit', {order: order.id} )"
                method="get"
                as="button"
                class="btn btn-primary btn-circle btn-sm"
            >
                <font-awesome-icon :icon="['fas', 'pencil']" />
            </Link>
            
            <Link
                :href="route('relator.order.destroy', {order: order.id} )"
                method="delete"
                as="button"
                class="btn btn-error btn-circle btn-sm"
            >
                <font-awesome-icon :icon="['fas', 'trash-can']" />
            </Link>
        </li>
    </ul>
    <EmptyState v-else>Nessun ordine attivo per questo Cliente</EmptyState>
</template>

<script setup>
import dayjs from 'dayjs';
import EmptyState from '@/Components/UI/EmptyState.vue';
import { Link } from '@inertiajs/vue3';


const props = defineProps({
    orders: Array
})

</script>