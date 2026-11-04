<template>
    <div v-if="description.rows.length" class="text-sm break-words">
        <p v-if="showLabel" class="mb-1 font-medium">{{ description.label }}</p>
        <JourneyContainerQuantities :groups="description.groups" :completed="completed" />
        <details v-if="description.changed && compare" class="mt-1">
            <summary class="min-h-12 cursor-pointer py-3 text-primary">Confronta con il ritiro previsto</summary>
            <p>Contenitori da ritirare nell’ordine: {{ description.plannedSummary }}</p>
        </details>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import JourneyContainerQuantities from './JourneyContainerQuantities.vue';
import { describeContainers } from '@/utils/journeyContainers';
const props = defineProps({
    items: { type: Array, default: () => [] },
    census: { type: Array, default: () => [] },
    completed: Boolean,
    compare: Boolean,
    showLabel: { type: Boolean, default: true },
});
const description = computed(() => describeContainers(props.items, props.census, props.completed));
</script>
