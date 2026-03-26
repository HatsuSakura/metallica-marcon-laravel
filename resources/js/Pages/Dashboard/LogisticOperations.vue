<template>
    <DashboardHeader>
        Dashboard Appoggi e Trasbordi
    </DashboardHeader>

    <div class="mb-4">
        <Link :href="route('logistic.home')" class="btn btn-ghost">
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl" />
            Torna a dashboard logistica
        </Link>
    </div>

    <div class="space-y-4">
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="font-semibold mb-2">Appoggi attivi</div>
                <div v-if="groundings.length === 0" class="text-sm opacity-70">Nessun appoggio attivo.</div>
                <div v-else class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Journey</th>
                                <th>Cargo</th>
                                <th>Magazzino</th>
                                <th>Stato dispatch</th>
                                <th>Aggiornato</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in groundings" :key="`ground-${row.id}`">
                                <td>
                                    <Link :href="route('logistic-dispatch.show', row.journey_id)" class="link link-hover">
                                        #{{ row.journey_id }}
                                    </Link>
                                </td>
                                <td>{{ row.cargo_location === 'vehicle' ? 'Motrice' : 'Rimorchio' }}</td>
                                <td>{{ row.warehouse?.name ?? 'Parcheggio' }}</td>
                                <td>{{ row.journey?.dispatch_status ?? '-' }}</td>
                                <td>{{ formatDateTime(row.updated_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between gap-3 mb-2">
                    <div class="font-semibold">Trasbordi</div>
                    <div class="join">
                        <Link
                            :href="route('dashboard.logistic.operations', { transshipments: 'active' })"
                            class="btn btn-sm join-item"
                            :class="isActiveView ? 'btn-primary' : 'btn-outline'"
                        >
                            Attivi ({{ activeCount }})
                        </Link>
                        <Link
                            :href="route('dashboard.logistic.operations', { transshipments: 'cancelled' })"
                            class="btn btn-sm join-item"
                            :class="!isActiveView ? 'btn-primary' : 'btn-outline'"
                        >
                            Annullati ({{ cancelledCount }})
                        </Link>
                    </div>
                </div>
                <div v-if="transshipments.length === 0" class="text-sm opacity-70">
                    {{ isActiveView ? 'Nessun trasbordo attivo.' : 'Nessun trasbordo annullato.' }}
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Viaggio</th>
                                <th>Elemento</th>
                                <th>Da</th>
                                <th>A</th>
                                <th>Quantità</th>
                                <th>Stato</th>
                                <th>Aggiornato</th>
                                <th class="text-right">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in transshipments" :key="`ts-${row.id}`">
                                <td>
                                    <Link :href="route('logistic-dispatch.show', row.journey_id)" class="link link-hover">
                                        #{{ row.journey_id }}
                                    </Link>
                                </td>
                                <td>{{ row.order_item?.description ?? `Item #${row.order_item_id}` }}</td>
                                <td>{{ row.from_warehouse?.name ?? '-' }}</td>
                                <td>{{ row.to_warehouse?.name ?? '-' }}</td>
                                <td>{{ row.quantity_containers }} {{ row.order_item?.holder?.name ?? 'unità' }}</td>
                                <td>
                                    <span class="badge" :class="statusBadgeClass(row.status)">
                                        {{ statusLabel(row.status) }}
                                    </span>
                                </td>
                                <td>{{ formatDateTime(row.updated_at) }}</td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            class="btn btn-sm btn-success"
                                            :disabled="isActionDisabled(row, 'approve')"
                                            @click="approveRow(row)"
                                        >
                                            Approva
                                        </button>
                                        <button
                                            class="btn btn-sm btn-error"
                                            :disabled="isActionDisabled(row, 'cancel')"
                                            @click="cancelRow(row)"
                                        >
                                            Annulla
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import DashboardHeader from '@/Components/UI/HeaderForDashboard.vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';
import {
    isTransshipmentCancelled,
    isTransshipmentProposed,
    normalizeTransshipmentStatus,
    transshipmentBadgeClass,
    transshipmentStatusLabel,
} from '@/Constants/transshipmentStatus';

const props = defineProps({
    groundings: {
        type: Array,
        default: () => [],
    },
    transshipments: {
        type: Array,
        default: () => [],
    },
    transshipmentView: {
        type: String,
        default: 'active',
    },
    transshipmentCounts: {
        type: Object,
        default: () => ({ active: 0, cancelled: 0 }),
    },
});
const rowActionLoading = ref({});
const isActiveView = computed(() => props.transshipmentView !== 'cancelled');
const activeCount = computed(() => Number(props.transshipmentCounts?.active ?? 0));
const cancelledCount = computed(() => Number(props.transshipmentCounts?.cancelled ?? 0));

function formatDateTime(value) {
    if (!value) return '-';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '-';
    return date.toLocaleString('it-IT');
}

function statusLabel(status) {
    return transshipmentStatusLabel(status);
}

function statusBadgeClass(status) {
    return transshipmentBadgeClass(status);
}

function isActionDisabled(row, action) {
    const status = normalizeTransshipmentStatus(row?.status);
    const rowId = Number(row?.id ?? 0);
    if (rowId <= 0) return true;
    if (rowActionLoading.value[rowId]) return true;
    if (action === 'approve') return !isTransshipmentProposed(status);
    if (action === 'cancel') return isTransshipmentCancelled(status);
    return true;
}

function setRowLoading(rowId, loading) {
    rowActionLoading.value = {
        ...rowActionLoading.value,
        [rowId]: loading,
    };
}

async function approveRow(row) {
    const rowId = Number(row?.id ?? 0);
    if (rowId <= 0 || isActionDisabled(row, 'approve')) return;
    setRowLoading(rowId, true);
    try {
        await axios.post(route('api.logistic-transshipments.approve', rowId));
        router.reload({ only: ['transshipments', 'transshipmentView', 'transshipmentCounts'] });
    } finally {
        setRowLoading(rowId, false);
    }
}

async function cancelRow(row) {
    const rowId = Number(row?.id ?? 0);
    if (rowId <= 0 || isActionDisabled(row, 'cancel')) return;
    setRowLoading(rowId, true);
    try {
        await axios.post(route('api.logistic-transshipments.cancel', rowId));
        router.reload({ only: ['transshipments', 'transshipmentView', 'transshipmentCounts'] });
    } finally {
        setRowLoading(rowId, false);
    }
}
</script>
