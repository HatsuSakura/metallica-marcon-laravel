<template>
    <UiBadge :tone="unassigned ? 'warning' : dangerous ? 'error' : 'primary'">
        {{ unassigned ? 'CER non indicato' : `CER ${code}` }}<span v-if="dangerous" class="sr-only"> Pericoloso</span>
    </UiBadge>
</template>
<script setup>
import UiBadge from './UiBadge.vue'
defineProps({code:[String,Number],dangerous:Boolean,unassigned:Boolean})
</script>
