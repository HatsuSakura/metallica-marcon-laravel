<template>
<section>
    <HeaderForDashboard
        :backLinkRoute="'driver.home'"
        :backLinkText="'Dashboard'"
    >
        Viaggi in carico a me
    </HeaderForDashboard>

    <div role="tablist" class="tabs tabs-boxed mb-4 w-fit">
        <Link
            :href="route('driver.journey.index', { tab: 'correnti' })"
            class="tab"
            :class="{ 'tab-active': activeTab === 'correnti' }"
            preserve-scroll
        >
            Viaggi correnti
        </Link>
        <Link
            :href="route('driver.journey.index', { tab: 'storico' })"
            class="tab"
            :class="{ 'tab-active': activeTab === 'storico' }"
            preserve-scroll
        >
            Storico viaggi
        </Link>
    </div>

    <CurrentJourneysTab
        v-if="activeTab === 'correnti'"
        :journeys="currentJourneysList"
        :warehouses="props.warehouses"
        :get-start-block-reason="getStartBlockReason"
        :is-start-button-disabled="isStartButtonDisabled"
        :is-reorder-enabled="isReorderEnabled"
        @start-journey="startJourney"
        @toggle-reorder="toggleReorder"
        @reorder-changed="onReorderChanged"
    />

    <JourneyHistoryTab
        v-else
        :history-journeys="props.historyJourneys"
    />
</section>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import store from '@/store';
import HeaderForDashboard from '@/Components/UI/HeaderForDashboard.vue';
import CurrentJourneysTab from './Components/CurrentJourneysTab.vue';
import JourneyHistoryTab from './Components/JourneyHistoryTab.vue';
import { isJourneyCreated } from '@/Constants/journeyStatus';
import { JOURNEY_STOP_STATUS, normalizeJourneyStopStatus } from '@/Constants/journeyStopStatus';
import { ORDER_DOCUMENTS_STATUS, ORDER_STATUS, normalizeOrderDocumentsStatus, normalizeOrderStatus } from '@/Constants/orderStatus';

const props = defineProps({
    currentJourneys: {
        type: [Array, Object],
        required: true,
    },
    historyJourneys: {
        type: Object,
        required: true,
    },
    warehouses: {
        type: [Array, Object],
        required: true,
    },
    hasActiveJourney: Boolean,
    activeTab: {
        type: String,
        default: 'correnti',
    },
});

const reorderState = reactive({});
const reorderDrafts = reactive({});
const startingState = reactive({});
const hasActiveJourney = computed(() => !!props.hasActiveJourney);
const currentJourneysList = computed(() => Array.isArray(props.currentJourneys) ? props.currentJourneys : []);
const activeTab = computed(() => (props.activeTab === 'storico' ? 'storico' : 'correnti'));

const hasAllOrdersReady = (journey) => {
    const byId = new Map();
    for (const stop of journey?.stops ?? []) {
        for (const stopOrder of stop?.stop_orders ?? []) {
            const order = stopOrder?.order;
            if (!order?.id || byId.has(order.id)) continue;
            byId.set(order.id, order);
        }
    }

    const orders = Array.from(byId.values());
    if (orders.length === 0) return false;

    return orders.every((order) =>
        normalizeOrderStatus(order.status) === ORDER_STATUS.READY
        && normalizeOrderDocumentsStatus(order.documents_status) === ORDER_DOCUMENTS_STATUS.GENERATED
    );
};

const canStartJourney = (journey) => {
    if (!isJourneyCreated(journey.status)) return false;
    if (hasActiveJourney.value) return false;
    return hasAllOrdersReady(journey);
};

const getStartBlockReason = (journey) => {
    if (!isJourneyCreated(journey.status)) return '';
    if (startingState[journey.id]) return 'Avvio in corso...';
    if (hasActiveJourney.value) return 'Hai gia un viaggio attivo';
    if (!hasAllOrdersReady(journey)) return 'Tutti gli ordini del viaggio devono essere READY con documenti generati';
    return '';
};

const isStartButtonDisabled = (journey) => {
    if (!isJourneyCreated(journey.status)) return true;
    return !!getStartBlockReason(journey);
};

const isReorderEnabled = (journeyId) => !!reorderState[journeyId];

const toggleReorder = async (journey) => {
    const journeyId = journey.id;
    if (isReorderEnabled(journeyId)) {
        await saveReorder(journey);
        return;
    }
    reorderState[journeyId] = true;
};

const onReorderChanged = (journeyId, stopIds) => {
    reorderDrafts[journeyId] = stopIds;
};

const saveReorder = async (journey) => {
    const journeyId = journey.id;
    const stopIds =
        reorderDrafts[journeyId] ??
        (journey.stops || [])
            .filter((stop) => [JOURNEY_STOP_STATUS.PLANNED, JOURNEY_STOP_STATUS.IN_PROGRESS].includes(normalizeJourneyStopStatus(stop.status)))
            .map((stop) => stop.id);

    if (!stopIds || stopIds.length === 0) {
        reorderState[journeyId] = false;
        return;
    }

    try {
        await axios.put(`/api/driver/journeys/${journeyId}/stops/reorder`, {
            stop_ids: stopIds,
        });
        reorderState[journeyId] = false;
        router.reload({ only: ['currentJourneys'], preserveScroll: true });
    } catch (error) {
        const message = error?.response?.data?.message || 'Errore durante il riordino delle fermate.';
        store.dispatch('flash/queueMessage', { type: 'error', text: message });
    }
};

const startJourney = async (journey) => {
    const journeyId = journey.id;

    if (!canStartJourney(journey)) {
        if (hasActiveJourney.value) {
            store.dispatch('flash/queueMessage', { type: 'warning', text: 'Hai gia un viaggio attivo.' });
        } else {
            store.dispatch('flash/queueMessage', {
                type: 'warning',
                text: 'Impossibile avviare: tutti gli ordini del viaggio devono essere READY con documenti generati.',
            });
        }
        return;
    }

    if (startingState[journeyId]) return;

    startingState[journeyId] = true;
    try {
        await axios.post(`/api/driver/journeys/${journeyId}/start`);
        store.dispatch('flash/queueMessage', { type: 'success', text: 'Viaggio avviato correttamente.' });
        router.reload({ only: ['currentJourneys', 'hasActiveJourney'], preserveScroll: true });
    } catch (error) {
        const message = error?.response?.data?.message || 'Errore durante l\'avvio del viaggio.';
        store.dispatch('flash/queueMessage', { type: 'error', text: message });
    } finally {
        startingState[journeyId] = false;
    }
};
</script>
