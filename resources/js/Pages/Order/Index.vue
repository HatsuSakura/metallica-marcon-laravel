<template>

    <section class="flex items-center gap-2 justify-between">

        <Link :href="route('logistic.home')" class="btn btn-ghost btn-sm">
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-lg"/>
            Torna a Dashboard
        </Link>

        <div>
            <span>
                Per creare un nuovo ordine 
                <font-awesome-icon :icon="['fas', 'arrow-right']" class="text-lg"/>
                &nbsp;
            </span>
            <Link :href="route('customer.index')" class="btn btn-primary btn-sm">
                <font-awesome-icon :icon="['fas', 'user-tie']" class="text-lg"/>
                Inizia da un cliente
            </Link>
        </div>
        
    </section>
    
    <section>
        <h2 class="text-xl font-semibold mb-3">Lista ordini</h2>

        <div v-if="!props.orders?.data?.length" class="alert">
            Nessun ordine disponibile.
        </div>
        
        <div class="grid grid-cols-12 gap-2">
    
        <Box v-for="order in props.orders.data" :key="order.id" class="col-span-6">
            <div class="flex flex-row justify-between mb-2">
                <div>
                    <div class="font-medium">
                        Codice ordine: <span class="badge badge-outline">{{ order.legacy_code ?? 'N/D' }}</span>
                    </div>
                    <div class="font-medium text-lg">
                        {{ order.customer.company_name }}
                    </div>
                    <div class="text-gray-500">
                        Sede: {{ order.site.address }}
                    </div>
                    <div>
                        <font-awesome-icon :icon="['fas', 'gauge-simple-high']" class="text-2xl"/>
                        Stato dell'ordine
                            <span class="font-medium">{{ order.status }} </span>
                    </div>
                    <div>
                        <font-awesome-icon :icon="['fas', 'file-lines']" class="text-2xl"/>
                        Stato documenti
                        <span class="badge ml-2" :class="documentsStateBadgeClass(order.documents_status)">
                            {{ documentsStateLabel(order.documents_status) }}
                        </span>
                    </div>
                    <div>
                        <font-awesome-icon :icon="['fas', 'calendar-check']" class="text-2xl"/>
                        Data inserimento ordine:
                            <span class="font-medium">{{ dayjs(order.created_at).format('YYYY-MM-DD HH:mm:ss') }} </span>
                    </div>
                    <div>
                        <font-awesome-icon :icon="['fas', 'calendar-days']" class="text-2xl"/>
                        Data ipotesi ritiro:
                            <span class="font-medium">{{ order.expected_withdraw_at? dayjs(order.expected_withdraw_at).format('YYYY-MM-DD'): 'NON impostata' }} </span>
                    </div>
                    <div>
                        <font-awesome-icon :icon="['fas', 'clock']" class="text-2xl"/>
                        Data fissa:
                            <span class="font-medium">{{ order.fixed_withdraw_at ? dayjs(order.fixed_withdraw_at).format('YYYY-MM-DD HH:mm') : 'NON impostata' }} </span>
                    </div>
                    <OrderSummaryItems :items="order.items" :holders="props.holders" />
                    <OrderSummaryHolders :items="order.holders" :holders="props.holders" />
                
                </div>
            </div>
    
            <div class="flex flex-row justify-end gap-2">
                <Link
                :href="route('order.edit', {order: order.id} )"
                method="get"
                as="button"
                class="btn btn-primary btn-circle btn-sm"
                >
                    <font-awesome-icon :icon="['fas', 'pen']" />
                </Link>
    
                <Link
                    :href="route('order.destroy', {order: order.id} )"
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

const documentsStateLabel = (state) => {
    switch (state) {
        case 'generated':
            return 'Generati';
        case 'generating':
            return 'In generazione';
        case 'failed':
            return 'Errore';
        case 'not_generated':
        default:
            return 'Non generati';
    }
};

const documentsStateBadgeClass = (state) => {
    switch (state) {
        case 'generated':
            return 'badge-success';
        case 'generating':
            return 'badge-info';
        case 'failed':
            return 'badge-error';
        case 'not_generated':
        default:
            return 'badge-warning';
    }
};


</script>

