<template>
<section class="flex items-center gap-2 justify-between">

    <Link :href="route('logistic.home')" class="btn btn-ghost btn-sm">
        <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-lg"/>
        Torna a Dashboard
    </Link>

    <Link :href="route('journey.create')" class="btn btn-primary btn-sm">
        <font-awesome-icon :icon="['fas', 'map-location-dot']" class="text-lg"/>
        Pianifica nuovo viaggio
    </Link>
</section>

<section class="mt-4">
    <div class="mb-4 px-3 pb-3 pt-0 rounded-box">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
            <label class="form-control w-full">
                <div class="label"><span class="label-text">Dal</span></div>
                <input v-model="filters.date_from" type="date" class="input input-bordered input-sm w-full" />
            </label>

            <label class="form-control w-full">
                <div class="label"><span class="label-text">Al</span></div>
                <input v-model="filters.date_to" type="date" class="input input-bordered input-sm w-full" />
            </label>

            <label class="form-control w-full">
                <div class="label"><span class="label-text">Autista</span></div>
                <select v-model="filters.driver_id" class="select select-bordered select-sm w-full">
                    <option value="">Tutti</option>
                    <option v-for="driver in props.drivers" :key="driver.id" :value="String(driver.id)">
                        {{ driver.name }} {{ driver.surname }}
                    </option>
                </select>
            </label>

            <label class="form-control w-full">
                <div class="label"><span class="label-text">Targa mezzo</span></div>
                <select v-model="filters.vehicle_id" class="select select-bordered select-sm w-full">
                    <option value="">Tutti</option>
                    <option v-for="vehicle in props.vehiclesForFilter" :key="vehicle.id" :value="String(vehicle.id)">
                        {{ vehicle.plate }} - {{ vehicle.name }}
                    </option>
                </select>
            </label>
        </div>

        <div class="mt-3 flex justify-between items-center gap-2">
            <div role="tablist" class="tabs tabs-boxed w-fit"> <!-- tab-lifted -->
                <Link :href="tabHref('tutti')" class="tab" :class="{ 'tab-active': activeTab === 'tutti' }" preserve-scroll>tutti</Link>
                <Link :href="tabHref('creato')" class="tab" :class="{ 'tab-active': activeTab === 'creato' }" preserve-scroll>creato</Link>
                <Link :href="tabHref('attivo')" class="tab" :class="{ 'tab-active': activeTab === 'attivo' }" preserve-scroll>attivo</Link>
                <Link :href="tabHref('eseguito')" class="tab" :class="{ 'tab-active': activeTab === 'eseguito' }" preserve-scroll>eseguito</Link>
                <Link :href="tabHref('chiuso')" class="tab" :class="{ 'tab-active': activeTab === 'chiuso' }" preserve-scroll>chiuso</Link>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="btn btn-sm btn-primary" @click="applyFilters">Applica filtri</button>
                <button type="button" class="btn btn-sm btn-outline" @click="resetFilters">Reset</button>
            </div>
        </div>
    </div>



    <template v-if="activeTab === 'tutti'">
        <EmptyState v-if="!props.allJourneys?.data?.length">Nessun viaggio</EmptyState>
        <div v-else>
            <Box v-for="journey in props.allJourneys.data" :key="journey.id" class="mb-2">
                <JourneyIndexRow :journey="journey" />
            </Box>
            <Pagination :links="props.allJourneys.links" />
        </div>
    </template>

    <template v-else-if="activeTab === 'creato'">
        <EmptyState v-if="createdList.length === 0">Nessun viaggio in stato creato</EmptyState>
        <div v-else>
            <Box v-for="journey in createdList" :key="journey.id" class="mb-2">
                <JourneyIndexRow :journey="journey" />
            </Box>
        </div>
    </template>

    <template v-else-if="activeTab === 'attivo'">
        <EmptyState v-if="activeList.length === 0">Nessun viaggio in stato attivo</EmptyState>
        <div v-else>
            <Box v-for="journey in activeList" :key="journey.id" class="mb-2">
                <JourneyIndexRow :journey="journey" />
            </Box>
        </div>
    </template>

    <template v-else-if="activeTab === 'eseguito'">
        <EmptyState v-if="!props.executedJourneys?.data?.length">Nessun viaggio in stato eseguito</EmptyState>
        <div v-else>
            <Box v-for="journey in props.executedJourneys.data" :key="journey.id" class="mb-2">
                <JourneyIndexRow :journey="journey" />
            </Box>
            <Pagination :links="props.executedJourneys.links" />
        </div>
    </template>

    <template v-else>
        <EmptyState v-if="!props.closedJourneys?.data?.length">Nessun viaggio in stato chiuso</EmptyState>
        <div v-else>
            <Box v-for="journey in props.closedJourneys.data" :key="journey.id" class="mb-2">
                <JourneyIndexRow :journey="journey" />
            </Box>
            <Pagination :links="props.closedJourneys.links" />
        </div>
    </template>
</section>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import Box from '@/Components/UI/Box.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import JourneyIndexRow from '@/Pages/Journey/Components/JourneyIndexRow.vue';
import dayjs from 'dayjs';

const props = defineProps({
    createdJourneys: {
        type: [Array, Object],
        required: true,
    },
    activeJourneys: {
        type: [Array, Object],
        required: true,
    },
    allJourneys: {
        type: Object,
        required: true,
    },
    executedJourneys: {
        type: Object,
        required: true,
    },
    closedJourneys: {
        type: Object,
        required: true,
    },
    drivers: {
        type: Array,
        required: true,
    },
    vehiclesForFilter: {
        type: Array,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    activeTab: {
        type: String,
        default: 'tutti',
    },
});

const filters = reactive({
    date_from: props.filters?.date_from ?? '',
    date_to: props.filters?.date_to ?? '',
    driver_id: props.filters?.driver_id ? String(props.filters.driver_id) : '',
    vehicle_id: props.filters?.vehicle_id ? String(props.filters.vehicle_id) : '',
});
const appliedFilters = reactive({
    date_from: props.filters?.date_from ?? '',
    date_to: props.filters?.date_to ?? '',
    driver_id: props.filters?.driver_id ? String(props.filters.driver_id) : '',
    vehicle_id: props.filters?.vehicle_id ? String(props.filters.vehicle_id) : '',
});

const activeTab = computed(() => ['tutti', 'creato', 'attivo', 'eseguito', 'chiuso'].includes(props.activeTab) ? props.activeTab : 'tutti');
const createdRawList = computed(() => Array.isArray(props.createdJourneys) ? props.createdJourneys : []);
const activeRawList = computed(() => Array.isArray(props.activeJourneys) ? props.activeJourneys : []);
const isLocalFilterTab = computed(() => ['creato', 'attivo'].includes(activeTab.value));

watch(
    () => props.filters,
    (newFilters) => {
        const next = {
            date_from: newFilters?.date_from ?? '',
            date_to: newFilters?.date_to ?? '',
            driver_id: newFilters?.driver_id ? String(newFilters.driver_id) : '',
            vehicle_id: newFilters?.vehicle_id ? String(newFilters.vehicle_id) : '',
        };

        filters.date_from = next.date_from;
        filters.date_to = next.date_to;
        filters.driver_id = next.driver_id;
        filters.vehicle_id = next.vehicle_id;

        appliedFilters.date_from = next.date_from;
        appliedFilters.date_to = next.date_to;
        appliedFilters.driver_id = next.driver_id;
        appliedFilters.vehicle_id = next.vehicle_id;
    }
);

const cleanedFilterQuery = computed(() => {
    const query = {};
    if (appliedFilters.date_from) query.date_from = appliedFilters.date_from;
    if (appliedFilters.date_to) query.date_to = appliedFilters.date_to;
    if (appliedFilters.driver_id) query.driver_id = appliedFilters.driver_id;
    if (appliedFilters.vehicle_id) query.vehicle_id = appliedFilters.vehicle_id;
    return query;
});

const matchFrontendFilters = (journey) => {
    if (!journey) return false;

    const dt = journey.planned_start_at ? dayjs(journey.planned_start_at) : null;
    if (appliedFilters.date_from && dt && dt.isBefore(dayjs(appliedFilters.date_from), 'day')) return false;
    if (appliedFilters.date_to && dt && dt.isAfter(dayjs(appliedFilters.date_to), 'day')) return false;

    if (appliedFilters.driver_id && Number(journey.driver_id) !== Number(appliedFilters.driver_id)) return false;

    if (appliedFilters.vehicle_id && Number(journey.vehicle_id) !== Number(appliedFilters.vehicle_id)) return false;

    return true;
};

const createdList = computed(() => createdRawList.value.filter(matchFrontendFilters));
const activeList = computed(() => activeRawList.value.filter(matchFrontendFilters));

const tabHref = (tab) => route('journey.index', { tab, ...cleanedFilterQuery.value });

const applyFilters = () => {
    appliedFilters.date_from = filters.date_from;
    appliedFilters.date_to = filters.date_to;
    appliedFilters.driver_id = filters.driver_id;
    appliedFilters.vehicle_id = filters.vehicle_id;

    if (isLocalFilterTab.value) return;

    router.get(
        route('journey.index'),
        {
            tab: activeTab.value,
            ...(filters.date_from ? { date_from: filters.date_from } : {}),
            ...(filters.date_to ? { date_to: filters.date_to } : {}),
            ...(filters.driver_id ? { driver_id: filters.driver_id } : {}),
            ...(filters.vehicle_id ? { vehicle_id: filters.vehicle_id } : {}),
        },
        {
            preserveScroll: true,
            replace: true,
        }
    );
};

const resetFilters = () => {
    filters.date_from = '';
    filters.date_to = '';
    filters.driver_id = '';
    filters.vehicle_id = '';

    appliedFilters.date_from = '';
    appliedFilters.date_to = '';
    appliedFilters.driver_id = '';
    appliedFilters.vehicle_id = '';

    if (isLocalFilterTab.value) return;

    router.get(
        route('journey.index'),
        { tab: activeTab.value },
        {
            preserveScroll: true,
            replace: true,
        }
    );
};

</script>
