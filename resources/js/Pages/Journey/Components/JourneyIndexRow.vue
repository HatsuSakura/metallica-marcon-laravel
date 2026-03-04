<template>
  <div class="flex flex-row justify-between items-center">
    <div>
      Viaggio # {{ journey.id }} previsto il {{ dayjs(journey.planned_start_at).format('DD/MM/YYYY HH:mm') }}
      <br>
      Autista: {{ journey.driver?.name }} {{ journey.driver?.surname }}
      <br>
      Mezzo: {{ journey.vehicle?.name }} - {{ journey.vehicle?.plate }}
      <span v-if="journey.trailer">
        con {{ journey.trailer?.name }} - {{ journey.trailer?.plate }}
      </span>
    </div>

    <div>
      <Link
        :href="route('journey.edit', { journey: journey.id })"
        method="get"
        as="button"
        class="btn btn-primary btn-circle mr-2"
      >
        <font-awesome-icon :icon="['fas', 'pen']" class="text-lg" />
      </Link>

      <Link
        :href="route('journey.destroy', { journey: journey.id })"
        method="delete"
        as="button"
        class="btn btn-error btn-circle"
      >
        <font-awesome-icon :icon="['fas', 'trash-can']" class="text-lg" />
      </Link>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import dayjs from 'dayjs';

defineProps({
  journey: {
    type: Object,
    required: true,
  },
});
</script>
