<template>
    <div class="grid min-w-0 grid-cols-2 gap-3" aria-label="Convoglio">
        <div v-for="part in parts" :key="part.label" class="flex min-w-0 items-start gap-2">
            <font-awesome-icon :icon="['fas', part.icon]" class="mt-1 h-6 w-7 shrink-0" role="img" :aria-label="part.label" />
            <div class="min-w-0 text-sm leading-snug">
                <p class="break-words font-medium">{{ part.vehicle?.name || part.fallback }}</p>
                <p v-if="part.vehicle?.plate" class="break-words text-xs opacity-70">{{ part.vehicle.plate }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
const props = defineProps({ vehicle: { type: Object, default: null }, trailer: { type: Object, default: null } });
const parts = computed(() => [
    { label: 'Motrice', icon: 'truck', vehicle: props.vehicle, fallback: 'Non assegnata' },
    { label: 'Rimorchio', icon: 'trailer', vehicle: props.trailer, fallback: 'Non assegnato' },
]);
</script>
