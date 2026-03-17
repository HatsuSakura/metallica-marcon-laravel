<template>

    <section>
        <div class="mb-2">Per creare un nuovo ordine:</div>
        <Link :href="route('customer.index')" class="btn btn-primary mb-4">
            Inizia da un cliente
        </Link>
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
                        <span class="badge ml-2" :class="documentsStateBadgeClass(order.documents_state)">
                            {{ documentsStateLabel(order.documents_state) }}
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
                    <OrderSummaryItems :items="order.items" :holders="props.holders" />
                    <OrderSummaryHolders :items="order.holders" :holders="props.holders" />
                
                </div>
            </div>
    
            <div class="flex flex-row justify-end gap-2">
                <button
                    type="button"
                    class="btn btn-outline btn-sm"
                    :disabled="generatingOrderId === order.id"
                    @click="generateDocuments(order.id)"
                >
                    <font-awesome-icon :icon="['fas', 'file-export']" />
                    {{ generatingOrderId === order.id ? 'Avvio...' : 'Genera documenti' }}
                </button>

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
import axios from 'axios';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useStore } from 'vuex';
import OrderSummaryItems from './Components/OrderSummaryItems.vue';
import OrderSummaryHolders from './Components/OrderSummaryHolders.vue';
    
const props = defineProps({
        orders : Object,
        holders: Object,
    })

const store = useStore();
const generatingOrderId = ref(null);

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

const generateDocuments = async (orderId) => {
    if (!orderId || generatingOrderId.value) {
        return;
    }

    generatingOrderId.value = orderId;

    try {
        const response = await axios.post(`/api/orders/${orderId}/generate-documents`);
        store.dispatch('flash/queueMessage', {
            type: response?.data?.type ?? 'success',
            text: response?.data?.message ?? 'Generazione documenti avviata.',
        });
        router.reload({ only: ['orders'] });
    } catch (error) {
        const message = error?.response?.data?.message ?? 'Errore durante l’avvio della generazione documenti.';
        store.dispatch('flash/queueMessage', { type: 'error', text: message });
    } finally {
        generatingOrderId.value = null;
    }
};

</script>
