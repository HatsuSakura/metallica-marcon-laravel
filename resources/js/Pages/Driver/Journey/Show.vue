<template>
    <section>
        <HeaderForDashboard
            :backLinkUrl="returnToUrl"
            :backLinkText="'Viaggi'"
        >
            Dettaglio Viaggio
        </HeaderForDashboard>

        <div class="flex flex-row items-center justify-between gap-3 mb-4">
            <JourneyMainData :journey="journey" />
            <span class="badge badge-outline">
                Stato: {{ journey.state }}
            </span>
        </div>

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

                    <div v-if="stopOrdersCount(selectedStop) > 0" class="space-y-1">
                        <div class="font-semibold">Ordini collegati</div>
                        <div class="flex flex-col gap-1">
                            <div
                                v-for="order in stopOrders(selectedStop)"
                                :key="order.id"
                                class="text-sm"
                            >
                                #{{ String(order.id).padStart(9, '0') }} - {{ order.customer?.ragione_sociale ?? 'Cliente' }}
                            </div>
                        </div>
                    </div>
                </div>
            </Box>
        </div>
    </section>
</template>

<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Box from '@/Components/UI/Box.vue';
import HeaderForDashboard from '@/Components/UI/HeaderForDashboard.vue';
import JourneyMainData from './Components/JourneyMainData.vue';

const props = defineProps({
    journey: {
        type: Object,
        required: true,
    },
    returnTo: {
        type: String,
        default: '',
    },
});

const page = usePage();
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
    || route('driver.journey.index', { tab: 'correnti' })
);

const stops = computed(() => {
    const items = Array.isArray(journey.value?.stops) ? [...journey.value.stops] : [];
    return items.sort((a, b) => {
        const aSeq = a.sequence ?? a.planned_sequence ?? 0;
        const bSeq = b.sequence ?? b.planned_sequence ?? 0;
        return aSeq - bSeq;
    });
});

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

const stopTitle = (stop) => {
    if (!stop) return '-';
    if (stop.kind === 'technical') {
        return stop.technical_action?.label ?? stop.technical_action?.name ?? stop.description ?? 'Sosta tecnica';
    }
    return stop.customer?.ragione_sociale ?? `Cliente #${stop.customer_id ?? '-'}`;
};

const stopSubtitle = (stop) => {
    if (!stop) return null;
    if (stop.address_text) return stop.address_text;
    const firstOrder = stopOrders(stop)[0];
    return firstOrder?.site?.indirizzo ?? null;
};

const stopStatusLabel = (status) => {
    switch (status) {
        case 'in_progress':
            return 'In corso';
        case 'done':
            return 'Completata';
        case 'skipped':
            return 'Saltata';
        case 'cancelled':
            return 'Annullata';
        default:
            return 'Pianificata';
    }
};
</script>
