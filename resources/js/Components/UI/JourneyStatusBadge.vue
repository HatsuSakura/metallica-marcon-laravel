<template>
    <UiBadge :tone="presentation.tone" :outline="presentation.outline"><slot>{{ presentation.label }}</slot></UiBadge>
</template>

<script setup>
import { computed } from 'vue';
import UiBadge from './UiBadge.vue';
import { normalizeJourneyStatus } from '@/Constants/journeyStatus';
import { journeyStopStatusLabel, normalizeJourneyStopStatus } from '@/Constants/journeyStopStatus';

const props = defineProps({ status: { type: [String, Object], default: '' }, kind: { type: String, default: 'journey' } });
const presentation = computed(() => {
    const stop = props.kind === 'stop';
    const status = stop ? normalizeJourneyStopStatus(props.status) : normalizeJourneyStatus(props.status);
    const label = stop ? journeyStopStatusLabel(status) : ({ creato: 'Da iniziare', attivo: 'In corso', eseguito: 'Eseguito', chiuso: 'Chiuso' }[status] ?? 'Stato non disponibile');
    const tone = ['attivo', 'in_progress', 'eseguito', 'chiuso', 'done'].includes(status) ? 'success' : status === 'skipped' ? 'warning' : 'neutral';
    return { label, tone, outline: tone === 'neutral' && !['planned', 'creato'].includes(status) };
});
</script>
