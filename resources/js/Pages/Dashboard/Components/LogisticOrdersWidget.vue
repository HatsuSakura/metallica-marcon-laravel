<template>
  <LogisticKpiCard
    title="Ordini"
    subtitle="Snapshot backlog e urgenze operative"
  >
    <template #badge>
      <div class="badge badge-primary badge-lg">{{ kpis.open_total }}</div>
    </template>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 text-sm">
      <div class="rounded border border-base-200 p-2">
        <div class="opacity-70">Creati</div>
        <div class="font-semibold">{{ kpis.created }}</div>
      </div>
      <div class="rounded border border-base-200 p-2">
        <div class="opacity-70">Pronti</div>
        <div class="font-semibold">{{ kpis.ready }}</div>
      </div>
      <div class="rounded border border-base-200 p-2">
        <div class="opacity-70">Pianificati</div>
        <div class="font-semibold">{{ kpis.planned }}</div>
      </div>
      <div class="rounded border border-base-200 p-2">
        <div class="opacity-70">Eseguiti</div>
        <div class="font-semibold">{{ kpis.executed }}</div>
      </div>
    </div>

    <div class="mt-3 flex flex-wrap gap-2 text-xs">
      <span class="badge badge-error badge-outline">Data fissa scaduti: {{ kpis.fixed_overdue }}</span>
      <span class="badge badge-warning badge-outline">Data fissa oggi: {{ kpis.fixed_today }}</span>
      <span class="badge badge-info badge-outline">Data fissa 3gg: {{ kpis.fixed_next_3d }}</span>
      <span class="badge badge-neutral">READY senza documenti: {{ kpis.docs_misaligned }}</span>
    </div>

    <template #actions>
      <Link :href="route('order.index')" class="btn btn-sm btn-primary">Apri Ordini</Link>
    </template>
  </LogisticKpiCard>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import LogisticKpiCard from './LogisticKpiCard.vue';

defineProps({
  kpis: {
    type: Object,
    required: true,
  },
});
</script>
