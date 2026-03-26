<template>
  <LogisticKpiCard
    title="Appoggi & Trasbordi"
    subtitle="Controllo operativo su appoggi attivi e trasbordi"
  >
    <template #badge>
      <div class="badge badge-accent badge-lg">{{ activeFlows }}</div>
    </template>

    <div class="grid grid-cols-2 lg:grid-cols-3 gap-2 text-sm">
      <div class="rounded border border-base-200 p-2">
        <div class="opacity-70">Appoggi attivi</div>
        <div class="font-semibold">{{ kpis.groundings_active ?? 0 }}</div>
      </div>
      <div class="rounded border border-base-200 p-2">
        <div class="opacity-70">Appoggi &gt;24h</div>
        <div class="font-semibold">{{ kpis.groundings_over_24h ?? 0 }}</div>
      </div>
      <div class="rounded border border-base-200 p-2">
        <div class="opacity-70">Trasbordi attivi</div>
        <div class="font-semibold">{{ kpis.transshipments_active ?? 0 }}</div>
      </div>
    </div>

    <div class="mt-3 flex flex-wrap gap-2 text-xs">
      <span class="badge badge-info badge-outline">Proposti: {{ kpis.transshipments_proposed ?? 0 }}</span>
      <span class="badge badge-ghost">Annullati ultimi 7gg: {{ kpis.transshipments_cancelled_7d ?? 0 }}</span>
    </div>

    <template #actions>
      <Link :href="route('dashboard.logistic.operations')" class="btn btn-sm btn-accent">Apri Appoggi & Trasbordi</Link>
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

const activeFlows = computed(() => (props.kpis.groundings_active ?? 0) + (props.kpis.transshipments_active ?? 0));
</script>
