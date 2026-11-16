<template>
    <h2 class="text-center mb-4 text-xl">Ordini in Corso</h2>
    <ul v-if="props.orders.length" class="">
        <li v-for="order in props.orders" :key="order.id" class="flex flex-wrap gap-2 items-center mb-2">
            Ord. #{{String( order.id ).padStart(6, '0')}} del {{ dayjs(order.requested_at).format('DD-MM-YYYY HH:mm') }}
            <Link
                :href="route('order.edit', {order: order.id} )"
                method="get"
                as="button"
                class="btn btn-primary btn-outline btn-circle" :aria-label="`Modifica ordine ${order.id}`"
            >
                <font-awesome-icon :icon="['fas', 'pencil']" />
            </Link>

<RegistryDeleteAction :label="`ordine ${order.id}`" :busy="deleting === route('order.destroy', {order:order.id})" @confirm="removeRecord(route('order.destroy', {order:order.id}))" />
        </li>
    </ul>
    <EmptyState v-else>Nessun ordine attivo per questo Cliente</EmptyState>
</template>

<script setup>
import RegistryDeleteAction from '@/Components/Registry/RegistryDeleteAction.vue'
import {useRegistryDelete} from '@/Composables/useRegistryDelete'
const {deleting, removeRecord} = useRegistryDelete()

import dayjs from 'dayjs';
import EmptyState from '@/Components/UI/EmptyState.vue';
import { Link, router } from '@inertiajs/vue3';


const props = defineProps({
    orders: Array
})

function destroyOrder(orderId) {
  router.delete(route('order.destroy', { order: orderId }), {
    preserveScroll: true,
    onSuccess: () => {
      router.reload({ only: ['orders'], preserveScroll: true })
    },
  })
}

</script>
