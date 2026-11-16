<template>
  <div class="flex flex-col gap-3 lg:flex-row lg:justify-between lg:items-center">
    <div>
      <JourneyStatusBadge :status="journey.status" /> Viaggio # {{ journey.id }} previsto il {{ dayjs(journey.planned_start_at).format('DD/MM/YYYY HH:mm') }}
      <br>
      Autista: {{ journey.driver?.name }} {{ journey.driver?.surname }}
      <br>
      Mezzo: {{ journey.vehicle?.name }} - {{ journey.vehicle?.plate }}
      <span v-if="journey.trailer">
        con {{ journey.trailer?.name }} - {{ journey.trailer?.plate }}
      </span>
    </div>

    <div class="flex flex-wrap items-center gap-2">
      <Link
        aria-label="Visualizza viaggio" :href="route('journey.show', { journey: journey.id, return_to: currentUrl })"
        method="get"
        as="button"
        class="btn btn-outline btn-circle min-h-12"
      >
        <font-awesome-icon :icon="['fas', 'eye']" class="text-lg" />
      </Link>

      <Link
        aria-label="Modifica viaggio" :href="route('journey.edit', { journey: journey.id })"
        method="get"
        as="button"
        class="btn btn-primary btn-outline btn-circle min-h-12"
      >
        <font-awesome-icon :icon="['fas', 'pen']" class="text-lg" />
      </Link>

      <RegistryDeleteAction :label="`viaggio ${journey.id}`" :disabled="isActive" disabled-reason="Un viaggio in corso non può essere eliminato" :busy="deleting === route('journey.destroy', {journey:journey.id})" @confirm="!isActive && removeRecord(route('journey.destroy', {journey:journey.id}))" />
    </div>
  </div>
</template>

<script setup>
import RegistryDeleteAction from '@/Components/Registry/RegistryDeleteAction.vue'
import {useRegistryDelete} from '@/Composables/useRegistryDelete'
import JourneyStatusBadge from '@/Components/UI/JourneyStatusBadge.vue'
import {JOURNEY_STATUS, normalizeJourneyStatus} from '@/Constants/journeyStatus'
const {deleting, removeRecord} = useRegistryDelete()

import { Link, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { computed } from 'vue';

const props = defineProps({
  journey: {
    type: Object,
    required: true,
  },
});

const page = usePage();
const isActive = computed(() => normalizeJourneyStatus(props.journey.status) === JOURNEY_STATUS.ACTIVE);
const currentUrl = computed(() => page.url || route('journey.index'));
</script>
