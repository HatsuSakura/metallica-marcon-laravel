<template>
    <DashboardHeader>
        Dashboard Logistica
    </DashboardHeader>

    <div class="space-y-6">
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <LogisticOrdersWidget :kpis="orderKpis" />
            <LogisticJourneyWidget :kpis="journeyKpis" />
            <LogisticOperationsWidget :kpis="operationsKpis" />

            <section class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-4 space-y-4">
                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-lg font-semibold">Alert Data Fissa</div>
                                <div class="text-sm opacity-70">
                                    Ordini con data fissa già superata e ancora da recuperare.
                                </div>
                            </div>
                            <div class="badge badge-error badge-lg">
                                {{ overdueFixedOrders.length }}
                            </div>
                        </div>

                        <div v-if="overdueFixedOrders.length === 0" class="alert alert-success mt-4">
                            <span>Nessuna criticità sugli ordini con data fissa.</span>
                        </div>

                        <div v-else class="overflow-x-auto mt-4">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Ordine</th>
                                        <th>Cliente / Sede</th>
                                        <th>Data fissa</th>
                                        <th>Stato</th>
                                        <th class="text-right">Azione</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="order in overdueFixedOrders" :key="`fixed-overdue-${order.id}`">
                                        <td>
                                            <div class="font-medium">{{ order.legacy_code ?? `#${order.id}` }}</div>
                                        </td>
                                        <td>
                                            <div>{{ order.customer?.company_name ?? '-' }}</div>
                                            <div class="text-xs opacity-70">{{ order.site?.name ?? order.site?.address ?? '-' }}</div>
                                        </td>
                                        <td>{{ formatDateTime(order.fixed_withdraw_at) }}</td>
                                        <td>
                                            <span class="badge badge-error badge-outline">
                                                {{ order.status ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <Link
                                                :href="route('order.edit', { order: order.id })"
                                                class="btn btn-xs btn-error"
                                            >
                                                Apri ordine
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider my-0" />

                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-lg font-semibold">Prossimi Ordini Con Data Fissa</div>
                                <div class="text-sm opacity-70">
                                    Prossimi 10 ordini ordinati per data fissa più vicina.
                                </div>
                            </div>
                            <div class="badge badge-primary badge-lg">
                                {{ upcomingFixedOrders.length }}
                            </div>
                        </div>

                        <div v-if="upcomingFixedOrders.length === 0" class="text-sm opacity-70 mt-4">
                            Nessun ordine con data fissa futura.
                        </div>

                        <div v-else class="overflow-x-auto mt-4">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Ordine</th>
                                        <th>Cliente / Sede</th>
                                        <th>Data fissa</th>
                                        <th>Stato</th>
                                        <th class="text-right">Azione</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="order in upcomingFixedOrders" :key="`fixed-upcoming-${order.id}`">
                                        <td>
                                            <div class="font-medium">{{ order.legacy_code ?? `#${order.id}` }}</div>
                                        </td>
                                        <td>
                                            <div>{{ order.customer?.company_name ?? '-' }}</div>
                                            <div class="text-xs opacity-70">{{ order.site?.name ?? order.site?.address ?? '-' }}</div>
                                        </td>
                                        <td>{{ formatDateTime(order.fixed_withdraw_at) }}</td>
                                        <td>
                                            <span class="badge badge-outline">
                                                {{ order.status ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <Link
                                                :href="route('order.edit', { order: order.id })"
                                                class="btn btn-xs btn-outline"
                                            >
                                                Apri ordine
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="text-xs opacity-60">
            Snapshot dashboard: {{ formatDateTime(fixedOrdersSnapshotAt) }}
        </div>
    </div>
</template>

<script setup>
import DashboardHeader from '@/Components/UI/HeaderForDashboard.vue';
import { Link } from '@inertiajs/vue3';
import LogisticOrdersWidget from './Components/LogisticOrdersWidget.vue';
import LogisticJourneyWidget from './Components/LogisticJourneyWidget.vue';
import LogisticOperationsWidget from './Components/LogisticOperationsWidget.vue';

defineProps({
    overdueFixedOrders: {
        type: Array,
        default: () => [],
    },
    upcomingFixedOrders: {
        type: Array,
        default: () => [],
    },
    fixedOrdersSnapshotAt: {
        type: String,
        default: null,
    },
    orderKpis: {
        type: Object,
        default: () => ({}),
    },
    journeyKpis: {
        type: Object,
        default: () => ({}),
    },
    operationsKpis: {
        type: Object,
        default: () => ({}),
    },
});

function formatDateTime(value) {
    if (!value) return '-';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '-';
    return date.toLocaleString('it-IT');
}
</script>
