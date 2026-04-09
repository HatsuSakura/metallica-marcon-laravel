<template>
    <section>
        <div class="flex items-center justify-between gap-3 mb-4">
            <Link :href="returnToUrl" class="btn btn-ghost btn-sm">
                <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-lg" />
                Torna a Viaggi
            </Link>

            <Link :href="route('journey.edit', { journey: journey.id })" class="btn btn-primary btn-sm">
                <font-awesome-icon :icon="['fas', 'pen']" class="text-lg" />
                Modifica viaggio
            </Link>
        </div>

        <div class="card bg-base-100 border border-base-200 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div class="space-y-1">
                        <div class="text-lg font-semibold">
                            Viaggio #{{ journey.id }}
                        </div>
                        <div class="text-sm opacity-80">
                            Previsto il {{ formatDateTime(journey.planned_start_at) }}
                        </div>
                        <div class="text-sm opacity-80">
                            Autista: {{ journey.driver?.name ?? '-' }} {{ journey.driver?.surname ?? '' }}
                        </div>
                        <div class="text-sm opacity-80">
                            Mezzo: {{ journey.vehicle?.name ?? '-' }} - {{ journey.vehicle?.plate ?? '-' }}
                            <span v-if="journey.trailer">
                                | Rimorchio: {{ journey.trailer?.name ?? '-' }} - {{ journey.trailer?.plate ?? '-' }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="badge badge-outline">
                            Stato: {{ journey.status }}
                        </span>
                        <span class="badge badge-outline">
                            Tappe: {{ journey.stops_count ?? stops.length }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <JourneyDocumentGenerationPanel
            :journey-id="journey.id"
            :selected-orders="journeyOrders"
        />

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <Box class="lg:col-span-1">
                <template #header>
                    <div class="font-semibold">Tappe viaggio</div>
                </template>

                <div v-if="stops.length === 0" class="text-sm opacity-70">
                    Nessuna tappa disponibile.
                </div>

                <div v-else class="space-y-2">
                    <button
                        v-for="stop in stops"
                        :key="stop.id"
                        type="button"
                        class="btn btn-ghost w-full justify-start"
                        :class="selectedStopId === stop.id ? 'btn-active' : ''"
                        @click="selectedStopId = stop.id"
                    >
                        <div class="flex items-start gap-2 w-full">
                            <span class="badge badge-outline">
                                {{ stopStatusLabel(stop.status) }}
                            </span>
                            <div class="min-w-0 text-left">
                                <div class="font-semibold truncate">{{ stopTitle(stop) }}</div>
                                <div v-if="stopSubtitle(stop)" class="text-xs opacity-70 truncate">
                                    {{ stopSubtitle(stop) }}
                                </div>
                            </div>
                        </div>
                    </button>
                </div>
            </Box>

            <Box class="lg:col-span-2">
                <template #header>
                    <div class="font-semibold">Dettaglio tappa</div>
                </template>

                <div v-if="!selectedStop" class="text-sm opacity-70">
                    Seleziona una tappa per vedere i dettagli.
                </div>

                <div v-else class="space-y-4">
                    <div>
                        <div class="text-lg font-semibold">{{ stopTitle(selectedStop) }}</div>
                        <div v-if="stopSubtitle(selectedStop)" class="text-sm opacity-80">
                            {{ stopSubtitle(selectedStop) }}
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="badge badge-outline">
                                Stato: {{ stopStatusLabel(selectedStop.status) }}
                            </span>
                            <span v-if="selectedStop.kind === 'technical'" class="badge badge-neutral">
                                Tecnica
                            </span>
                        </div>
                    </div>

                    <div v-if="stopOrdersCount(selectedStop) > 0" class="space-y-2">
                        <div class="font-semibold">Ordini collegati</div>
                        <div class="space-y-2">
                            <div
                                v-for="order in stopOrders(selectedStop)"
                                :key="order.id"
                                class="rounded-box border border-base-200 bg-base-100 p-3 text-sm"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div clasS="flex items-start gap-4">

                                        <div>
                                            <div class="font-semibold">
                                                Ordine {{ order.legacy_code ?? `#${order.id}` }}
                                            </div>
                                            <div class="text-sm opacity-80">
                                                {{ order.customer?.company_name ?? 'Cliente' }}
                                            </div>
                                            <div class="text-xs opacity-70">
                                                {{ order.site?.name ?? order.site?.address ?? '-' }}
                                            </div>
                                        </div>

                                    </div>
                                    <span class="badge badge-outline">
                                        {{ order.status ?? '-' }}
                                    </span>
                                </div>

                                <div v-if="orderItems(order).length > 0" class="mt-2">
                                    <div tabindex="0" class="collapse collapse-arrow journey-items-collapse">
                                        <input type="checkbox" />
                                        <div class="collapse-title text-sm font-semibold">
                                            Dettaglio elementi ({{ orderItems(order).length }})
                                        </div>
                                        <div class="collapse-content pt-0">
                                            <ul class="list-disc ml-5">
                                                <li
                                                    v-for="item in orderItems(order)"
                                                    :key="item.id ?? item.uuid ?? item.temp_id"
                                                >
                                                    {{ itemSummary(item) }}
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="order.notes" class="mt-1 whitespace-pre-line">
                                    <span class="font-semibold">Note Ordine:</span>
                                    {{ order.notes }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-sm opacity-70">
                        Nessun ordine collegato a questa tappa.
                    </div>
                </div>
            </Box>
        </div>

        <AuditCollapse
            :audits="props.audits || []"
            :is-admin="Boolean(page.props.user?.is_admin)"
            :field-labels="auditFieldLabels"
        />
    </section>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import Box from '@/Components/UI/Box.vue';
import AuditCollapse from '@/Components/AuditCollapse.vue';
import JourneyDocumentGenerationPanel from './Components/JourneyDocumentGenerationPanel.vue';
import { journeyStopStatusLabel } from '@/Constants/journeyStopStatus';

const props = defineProps({
    journey: {
        type: Object,
        required: true,
    },
    returnTo: {
        type: String,
        default: '',
    },
    audits: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const auditFieldLabels = {
    driver_id: 'Autista',
    vehicle_id: 'Motrice',
    trailer_id: 'Rimorchio',
    vehicle_cargo_id: 'Cassone motrice',
    trailer_cargo_id: 'Cassone rimorchio',
    planned_start_at: 'Inizio pianificato',
    planned_end_at: 'Fine pianificata',
    actual_start_at: 'Inizio effettivo',
    actual_end_at: 'Fine effettiva',
    status: 'Stato viaggio',
    dispatch_status: 'Stato dispatch',
    notes: 'Note',
};
const journey = computed(() => props.journey);
const returnToFromQuery = computed(() => {
    const rawUrl = page.url || '';
    const queryString = rawUrl.includes('?') ? rawUrl.slice(rawUrl.indexOf('?') + 1) : '';
    const params = new URLSearchParams(queryString);
    const value = params.get('return_to');
    if (!value) return null;
    return value.startsWith('/') ? value : null;
});

const returnToUrl = computed(() =>
    returnToFromQuery.value
    || props.returnTo
    || route('journey.index')
);

const stops = computed(() => {
    const items = Array.isArray(journey.value?.stops) ? [...journey.value.stops] : [];
    return items.sort((a, b) => {
        const aSeq = a.sequence ?? a.planned_sequence ?? 0;
        const bSeq = b.sequence ?? b.planned_sequence ?? 0;
        return aSeq - bSeq;
    });
});

const journeyOrders = computed(() => Array.isArray(journey.value?.orders) ? journey.value.orders : []);

const selectedStopId = ref(stops.value[0]?.id ?? null);
const selectedStop = computed(() => stops.value.find((stop) => stop.id === selectedStopId.value) || null);

const stopOrders = (stop) => {
    if (!stop) return [];
    if (Array.isArray(stop.orders)) return stop.orders;
    const stopOrdersList = stop.stop_orders ?? stop.stopOrders;
    if (!Array.isArray(stopOrdersList)) return [];
    return stopOrdersList.map((so) => so?.order ?? so).filter(Boolean);
};

const stopOrdersCount = (stop) => stopOrders(stop).length;

const orderItems = (order) => {
    if (!order || !Array.isArray(order.items)) return [];
    return order.items;
};

const itemSummary = (item) => {
    const cer = item?.cer_code?.code ?? item?.cerCode?.code ?? '-';
    const description = item?.description ? ` - ${item.description}` : '';
    const weight = item?.weight_declared ? ` (${item.weight_declared} kg)` : '';
    return `CER ${cer}${description}${weight}`;
};

const stopTitle = (stop) => {
    if (!stop) return '-';
    if (stop.kind === 'technical') {
        return stop.technical_action?.label ?? stop.technical_action?.name ?? stop.description ?? 'Sosta tecnica';
    }
    return stop.customer?.company_name ?? `Cliente #${stop.customer_id ?? '-'}`;
};

const stopSubtitle = (stop) => {
    if (!stop) return null;
    if (stop.address_text) return stop.address_text;
    const firstOrder = stopOrders(stop)[0];
    return firstOrder?.site?.address ?? null;
};

const stopStatusLabel = (status) => journeyStopStatusLabel(status);

const formatDateTime = (value) => {
    if (!value) return '-';
    return dayjs(value).format('DD/MM/YYYY HH:mm');
};
</script>

<style scoped>
.journey-items-collapse .collapse-title {
    padding: 0.125rem 0.75rem !important;
    min-height: 1.75rem !important;
    line-height: 1.1rem !important;
}

.journey-items-collapse > input[type="checkbox"] {
    min-height: 1.75rem !important;
    height: 1.75rem !important;
}

.journey-items-collapse > input[type="checkbox"] ~ .collapse-content {
    padding-top: 0 !important;
}

.journey-items-collapse.collapse-arrow > .collapse-title:after {
    top: 50% !important;
    inset-inline-end: 1rem !important;
    --tw-translate-y: -50% !important;
}
</style>
