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
        :is-blocked-by-active-journey="isBlockedByActiveJourney"
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

const canStartJourney = (journey) => {
    if (journey.status !== 'creato') return false;
    return !hasActiveJourney.value;
};

const isBlockedByActiveJourney = (journey) => {
    if (journey.status !== 'creato') return false;
    return !canStartJourney(journey);
};

const isStartButtonDisabled = (journey) => {
    return !canStartJourney(journey) || !!startingState[journey.id];
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
            .filter((stop) => ['planned', 'in_progress'].includes(stop.status))
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
        store.dispatch('flash/queueMessage', { type: 'warning', text: 'Hai gia un viaggio attivo.' });
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
