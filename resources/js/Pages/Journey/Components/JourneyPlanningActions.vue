<template>
    <label class="flex items-center gap-2 text-sm"><span>Finestra</span><input type="checkbox" class="toggle toggle-primary" :checked="mapMode === 'split'" aria-label="Mappa affiancata" @change="$emit('update:mapMode', $event.target.checked ? 'split' : 'window')" /><span>Affiancata</span></label>
    <AppAction :aria-pressed="mapOpen" @click="$emit('toggle-map')"><font-awesome-icon :icon="['fas','map-location-dot']" aria-hidden="true" />{{ mapOpen ? 'Nascondi mappa' : 'Mostra mappa' }}</AppAction>
</template>
<script setup>
import AppAction from '@/Components/UI/AppAction.vue'
defineProps({mapOpen:Boolean,mapMode:String})
defineEmits(['toggle-map','update:mapMode'])
</script>
