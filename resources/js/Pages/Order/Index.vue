<template>

    <section class="flex flex-wrap items-center gap-2 justify-between">

        <Link :href="route('logistic.home')" class="btn btn-ghost btn-sm">
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-lg"/>
            Torna a Dashboard
        </Link>

        <div class="flex min-w-0 flex-wrap items-center gap-2">
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
        <form class="mb-3 flex flex-wrap items-center gap-3 pb-2" role="search" @submit.prevent="applyFilter">
            <h2 class="text-xl font-semibold">Lista ordini</h2>
            <label class="min-w-0 flex-1 basis-48 max-w-sm"><span class="sr-only">Filtra ordini per nome cliente</span><input v-model="customerSearch" type="search" class="input input-bordered w-full" placeholder="Filtra per Cliente" autocomplete="off" maxlength="200" /></label>
            <span class="text-sm" aria-live="polite">{{ props.orders.total }} ordini</span>
        </form>

        <div v-if="!props.orders?.data?.length" class="alert">
            {{ props.filters.customer ? 'Nessun ordine trovato per questo cliente.' : 'Nessun ordine disponibile.' }}
        </div>
        
        <div class="grid grid-cols-12 gap-2">
    
        <Box v-for="order in props.orders.data" :key="order.id" class="col-span-12 min-w-0 md:col-span-6">
            <div class="flex flex-row justify-between mb-2">
                <div>
                    <div class="font-medium">
                        Codice ordine: <span class="badge badge-outline">{{ order.legacy_code ?? 'N/D' }}</span>
                    </div>
                    <div class="font-medium text-lg break-words">
                        {{ order.customer?.company_name || 'Cliente non disponibile' }}
                    </div>
                    <div class="text-gray-500">
                        Sede: {{ order.site?.address || 'non disponibile' }}
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
        <Pagination v-if="props.orders.links?.length && props.orders.last_page > 1" class="mt-4" :links="props.orders.links" />
    
    </section>
    
    
    
    </template>
    
<script setup>
import Box from '@/Components/UI/Box.vue';
import dayjs from 'dayjs';
import { Link, router } from '@inertiajs/vue3';
import {ref, watch, onBeforeUnmount} from 'vue';
import Pagination from '@/Components/UI/Pagination.vue';
import OrderSummaryItems from './Components/OrderSummaryItems.vue';
import OrderSummaryHolders from './Components/OrderSummaryHolders.vue';
    
const props = defineProps({
        orders : Object,
        holders: [Array,Object],
        filters: {type:Object, default:()=>({})},
    })

const customerSearch = ref(props.filters.customer ?? '')
let searchTimer
const applyFilter = () => {
    clearTimeout(searchTimer)
    router.get(route('order.index'), customerSearch.value.trim() ? {customer:customerSearch.value.trim()} : {}, {preserveState:true,preserveScroll:true,replace:true})
}
watch(customerSearch,()=>{clearTimeout(searchTimer);searchTimer=setTimeout(applyFilter,300)})
onBeforeUnmount(()=>clearTimeout(searchTimer))

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

