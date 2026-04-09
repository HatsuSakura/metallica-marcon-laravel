<template>
  <LogisticKpiCard
    title="Viaggi"
    subtitle="Stato viaggi e avanzamento chiusure operative"
  >
    <template #badge>
      <div class="badge badge-secondary badge-lg">{{ activeCount }}</div>
    </template>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 text-sm">
      <div class="rounded border border-base-200 p-2">
        <div class="opacity-70">Creati</div>
        <div class="font-semibold">{{ kpis.created ?? 0 }}</div>
      </div>
      <div class="rounded border border-base-200 p-2">
        <div class="opacity-70">Attivi</div>
        <div class="font-semibold">{{ activeCount }}</div>
      </div>
      <div class="rounded border border-base-200 p-2">
        <div class="opacity-70">Eseguiti</div>
        <div class="font-semibold">{{ kpis.executed ?? 0 }}</div>
      </div>
      <div class="rounded border border-base-200 p-2">
        <div class="opacity-70">Chiusi</div>
        <div class="font-semibold">{{ kpis.closed ?? 0 }}</div>
      </div>
    </div>

    <div class="mt-3 flex flex-wrap gap-2 text-xs">
      <span class="badge badge-warning badge-outline">Rientro in ritardo: {{ kpis.late_return ?? 0 }}</span>
      <span class="badge badge-accent badge-outline">Da chiudere: {{ kpis.to_close ?? 0 }}</span>
      <span class="badge badge-error badge-outline">Da chiudere &gt;24h: {{ kpis.to_close_over_24h ?? 0 }}</span>
      <span class="badge badge-success badge-outline">Gestiti oggi: {{ kpis.managed_today ?? 0 }}</span>
    </div>

    <template #actions>
      <Link :href="route('journey.index')" class="btn btn-sm btn-primary">
        Apri Viaggi
      </Link>
      <Link :href="route('logistic-dispatch.index')" class="btn btn-sm btn-secondary">
        Apri Chiusure Viaggi
      </Link>
      <Link :href="route('journey.create')" class="btn btn-primary btn-sm">
        <font-awesome-icon :icon="['fas', 'map-location-dot']" class="text-lg"/>
        Pianifica nuovo viaggio
    </Link>
    </template>
  </LogisticKpiCard>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import LogisticKpiCard from './LogisticKpiCard.vue';

const props = defineProps({
  kpis: {
    type: Object,
    required: true,
  },
});

const activeCount = computed(() => props.kpis.active ?? 0);
</script>
