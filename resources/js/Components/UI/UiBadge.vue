<template>
    <span class="badge h-auto min-h-7" :class="[tones[tone], { 'badge-outline': outline }]"><slot /></span>
</template>

<script setup>
defineProps({
    tone: { type: String, default: 'neutral', validator: value => ['neutral', 'primary', 'secondary', 'success', 'info', 'warning', 'error'].includes(value) },
    outline: Boolean,
});
const tones = { neutral: 'badge-neutral', primary: 'badge-primary', secondary: 'badge-secondary', success: 'badge-success', info: 'badge-info', warning: 'badge-warning', error: 'badge-error' };
</script>
