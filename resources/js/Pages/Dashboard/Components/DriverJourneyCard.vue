<template>
    <article class="flex min-w-0 flex-col rounded-2xl border bg-base-100 p-4 sm:p-5" :class="isActive ? 'border-success' : 'border-base-300'">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="font-semibold">{{ position ? `Viaggio ${position}` : 'Viaggio' }} <span class="font-normal text-sm opacity-70">#{{ journey.id }}</span></h3>
            <JourneyStatusBadge :status="journey.status" class="whitespace-normal" />
        </div>
        <p v-if="showDate" class="mt-2 text-sm">{{ dateLabel(journey.planned_start_at) }}</p>
        <dl class="my-4 grid grid-cols-2 gap-3">
            <div><dt class="text-xs opacity-70">Partenza prevista</dt><dd class="text-xl font-semibold tabular-nums">{{ timeLabel(journey.planned_start_at) }}</dd></div>
            <div><dt class="text-xs opacity-70">Partenza effettiva</dt><dd class="text-xl font-semibold tabular-nums">{{ timeLabel(journey.actual_start_at) }}</dd></div>
        </dl>
        <JourneyVehicleSummary :vehicle="journey.vehicle" :trailer="journey.trailer" class="mb-4" />
        <div class="mt-auto space-y-2">
            <div class="flex flex-wrap justify-between gap-1 text-sm"><span>{{ journey.completed_stops_count }} di {{ journey.stops_count }} tappe completate</span><span v-if="journey.stops_count">{{ progress }}%</span></div>
            <progress class="progress progress-primary block w-full" :value="progress" max="100" :aria-label="`${journey.completed_stops_count} tappe completate su ${journey.stops_count}`"></progress>
            <p v-if="journey.skipped_stops_count || journey.cancelled_stops_count" class="text-xs opacity-70">{{ journey.skipped_stops_count }} saltate · {{ journey.cancelled_stops_count }} annullate</p>
            <p v-if="!journey.stops_count" class="text-xs opacity-70">Nessuna tappa pianificata</p>
            <Link :href="journeyUrl" class="btn min-h-12 w-full mt-2" :class="isActive ? 'btn-primary' : 'btn-outline'">{{ isActive ? 'Riprendi viaggio' : isOpen ? 'Apri viaggio' : 'Vedi riepilogo' }}</Link>
        </div>
    </article>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { JOURNEY_STATUS } from '@/Constants/journeyStatus';
import JourneyStatusBadge from '@/Components/UI/JourneyStatusBadge.vue';
import JourneyVehicleSummary from '@/Components/Journey/JourneyVehicleSummary.vue';

const props = defineProps({
    journey: { type: Object, required: true }, position: { type: Number, default: null },
    returnTo: { type: String, required: true }, showDate: Boolean,
});
const isActive = computed(() => props.journey.status === JOURNEY_STATUS.ACTIVE);
const isOpen = computed(() => [JOURNEY_STATUS.CREATED, JOURNEY_STATUS.ACTIVE].includes(props.journey.status));
const progress = computed(() => props.journey.stops_count ? Math.round(props.journey.completed_stops_count / props.journey.stops_count * 100) : 0);
const journeyUrl = computed(() => route(isOpen.value ? 'driver.journey.edit' : 'driver.journey.show', { journey: props.journey.id, return_to: props.returnTo }));
const timeLabel = (value) => value ? new Intl.DateTimeFormat('it-IT', { hour: '2-digit', minute: '2-digit', timeZone: 'Europe/Rome' }).format(new Date(value)) : '—';
const dateLabel = (value) => value ? new Intl.DateTimeFormat('it-IT', { dateStyle: 'medium', timeZone: 'Europe/Rome' }).format(new Date(value)) : 'Data non disponibile';
</script>
