<template>

    <section>
        Per creare un nuovo Ordine: 
    <Link @click="closeDrawer" :href="route('relator.customer.index')" class="btn btn-primary mb-4">
        Inizia da un cliente
    </Link>
    </section>
    
    <section>
        
        <div class="grid grid-cols-12 gap-2">
    
        <Box v-for="order in props.orders.data" class="col-span-6">
            <div class="flex flex-row justify-between mb-2">
                <div>
                    <div class="font-medium text-lg">
                        {{ order.customer.ragione_sociale }}
                    </div>
                    <div class="text-gray-500">
                        Sede: {{ order.site.indirizzo }}
                    </div>
                    <div>
                        <font-awesome-icon :icon="['fas', 'gauge-simple-high']" class="text-2xl"/>
                        Stato dell'ordine
                            <span class="font-medium">{{ order.state }} </span>
                    </div>
                    <div>
                        <font-awesome-icon :icon="['fas', 'calendar-check']" class="text-2xl"/>
                        Data inserimento ordine:
                            <span class="font-medium">{{ dayjs(order.created_at).format('YYYY-MM-DD HH:mm:ss') }} </span>
                    </div>
                    <div>
                        <font-awesome-icon :icon="['fas', 'calendar-days']" class="text-2xl"/>
                        Data ipotesi ritiro:
                            <span class="font-medium">{{ order.expected_withdraw_dt? dayjs(order.expected_withdraw_dt).format('YYYY-MM-DD'): 'NON impostata' }} </span>
                    </div>
                    <OrderSummaryItems :items="order.items" :holders="props.holders" />
                    <OrderSummaryHolders :items="order.holders" :holders="props.holders" />
                
                </div>
            </div>
    
            <div class="flex flex-row justify-end gap-2">
                <Link
                :href="route('relator.order.edit', {order: order.id} )"
                method="get"
                as="button"
                class="btn btn-primary btn-circle btn-sm"
                >
                    <font-awesome-icon :icon="['fas', 'pen']" />
                </Link>
    
                <Link
                    :href="route('relator.order.destroy', {order: order.id} )"
                    method="delete"
                    as="button"
                    class="btn btn-error btn-circle btn-sm"
                >
                    <font-awesome-icon :icon="['fas', 'trash-can']" />
                </Link>
            </div>
        </Box>
    
        </div>
    
    </section>
    
    
    
    </template>
    
    <script setup>
    import Box from '@/Components/UI/Box.vue';
    import dayjs from 'dayjs';
    import { Link } from '@inertiajs/vue3';
import OrderSummaryItems from './Components/OrderSummaryItems.vue';
import OrderSummaryHolders from './Components/OrderSummaryHolders.vue';
    
    const props = defineProps({
        orders : Object,
        holders: Object,
    })
    
    </script>