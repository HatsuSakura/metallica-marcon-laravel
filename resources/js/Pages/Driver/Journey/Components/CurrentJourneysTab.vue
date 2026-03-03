<template>
    <div v-if="journeys.length > 0" class="flex flex-col items-center justify-center gap-2">
        <Box v-for="journey in journeys" :key="journey.id" class="w-3/4">
            <div class="flex flex-row justify-between items-start mb-4">
                <div class="flex items-center gap-4">
                    <JourneyMainData :journey="journey" />
                    <span class="badge badge-outline">{{ journey.state }}</span>
                </div>
                <div v-if="journey.state != 'eseguito'" class="flex flex-col items-end gap-2">
                    <span
                        v-if="journey.state === 'creato'"
                        :class="{ tooltip: isBlockedByActiveJourney(journey) } "
                        :data-tip="isBlockedByActiveJourney(journey) ? 'Hai già un viaggio attivo' : null"
                        class="tooltip-warning"
                    >
                        <button
                            type="button"
                            class="btn btn-success btn-outline btn-sm"
                            :disabled="isStartButtonDisabled(journey)"
                            @click="$emit('start-journey', journey)"
                        >
                            <font-awesome-icon :icon="['fas', 'play']" class="text-xl"/>
                            Inizia viaggio
                        </button>
                    </span>
                    <span
                        v-else
                        class="btn btn-success btn-outline btn-sm pointer-events-none cursor-default"
                        aria-label="Viaggio in corso"
                    >
                        <font-awesome-icon :icon="['fas', 'truck-moving']" class="text-xl"/>
                        Viaggio in corso
                    </span>
                    <button
                        v-if="journey.stops_count > 1"
                        type="button"
                        class="btn btn-outline btn-sm"
                        @click="$emit('toggle-reorder', journey)"
                    >
                        <font-awesome-icon
                            :icon="['fas', isReorderEnabled(journey.id) ? 'floppy-disk' : 'arrows-up-down-left-right']"
                            class="text-xl"
                        />
                        {{ isReorderEnabled(journey.id) ? 'Salva Ordine' : 'Riordina Fermate' }}
                    </button>
                    <Link
                        :href="route('driver.journey.edit', {journey: journey.id} )"
                        method="get"
                        as="button"
                        class="btn btn-primary btn-outline btn-sm"
                    >
                        <font-awesome-icon :icon="['fas', 'pen']" class="text-2xl"/>
                        Gestisci Viaggio
                    </Link>
                </div>
                <div v-else class="flex items-center gap-2">
                    <span
                        class="btn btn-success btn-outline btn-sm pointer-events-none cursor-default"
                        aria-label="Viaggio concluso"
                    >
                        <font-awesome-icon :icon="['fas', 'check']" class="text-2xl"/>
                        Viaggio Concluso
                    </span>
                </div>
            </div>

            <JourneyOrdersList
                :journey="journey"
                :warehouses="warehouses"
                :is_double_load="journey.is_double_load"
                :reorder-enabled="isReorderEnabled(journey.id)"
                @reorder-changed="(stopIds) => $emit('reorder-changed', journey.id, stopIds)"
            />
        </Box>
    </div>
    <EmptyState v-else>
        <div class="flex flex-col items-center gap-4">
            <div>Non ci sono viaggi correnti</div>
        </div>
    </EmptyState>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import Box from '@/Components/UI/Box.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import JourneyMainData from './JourneyMainData.vue';
import JourneyOrdersList from './JourneyOrdersList.vue';

defineProps({
    journeys: {
        type: Array,
        required: true,
    },
    warehouses: {
        type: [Array, Object],
        required: true,
    },
    isBlockedByActiveJourney: {
        type: Function,
        required: true,
    },
    isStartButtonDisabled: {
        type: Function,
        required: true,
    },
    isReorderEnabled: {
        type: Function,
        required: true,
    },
});

defineEmits([
    'start-journey',
    'toggle-reorder',
    'reorder-changed',
]);
</script>
