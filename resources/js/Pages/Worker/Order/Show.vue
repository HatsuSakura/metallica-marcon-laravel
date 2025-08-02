<template>
    <div>
        <!-- TESTATA -->
        <div class="mb-4">
            <Link class="btn btn-ghost" :href="route('warehouse-manager.orders.index')">
            <font-awesome-icon :icon="['fas','arrow-left']" class="text-xl"/>
            Torna a ordini scaricati
            </Link>
        </div>

            <div
                v-for="(groupedItems, warehouseName) in itemsByWarehouse"
                :key="warehouseName"
                class="mb-8"
                >
                <h2 class="text-lg font-bold mb-2">
                    Magazzino: {{ warehouseName === 'none' ? 'Non assegnato' : warehouseName }}
                </h2>

                <!-- qui puoi decidere se usare un component child o un semplice v-for -->
                <OrderItemRowSimple :items="groupedItems" />

            </div>
      

    </div>
</template>

<script setup>
import Box from '@/Components/UI/Box.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import OrderItemRowSimple from './Components/OrderItemRowSimple.vue';


const props = defineProps({
    order: Object,
})

const itemsByWarehouse = computed(() => {
  return props.order.items.reduce((acc, item) => {
    // estrai il download_id o usa “none”
    const downloadWarehouse = item.warehouse_download?.denominazione ?? 'none';

    if (!acc[downloadWarehouse]) {
      acc[downloadWarehouse] = []
    }
    acc[downloadWarehouse].push(item)
    return acc
  }, {})
})
</script>