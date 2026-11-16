<template>
    <div class="space-y-2">
        <p>Rimangono ordini sul rimorchio o a riempimento: spostali in motrice, riportali agli ordini aperti oppure configura un rimorchio.</p>
        <div class="flex flex-wrap gap-2">
            <AppAction variant="function" :disabled="busy || !canMoveToTruck" @click="$emit('move-to-truck')">Sposta in motrice</AppAction>
            <AppAction variant="function" :disabled="busy" @click="$emit('return-to-orders')">Riporta agli ordini aperti</AppAction>
        </div>
    </div>
</template>
<script setup>
import AppAction from '@/Components/UI/AppAction.vue'
defineProps({canMoveToTruck:Boolean, busy:Boolean})
defineEmits(['move-to-truck','return-to-orders'])
</script>
