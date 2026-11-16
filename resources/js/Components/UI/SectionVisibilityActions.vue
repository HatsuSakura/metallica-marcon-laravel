<template>
    <div class="flex flex-wrap gap-2" aria-label="Visibilità sezioni">
        <AppAction :disabled="disabled" @click="$emit('open-all')"><font-awesome-icon :icon="['fas','chevron-down']" aria-hidden="true" />Apri tutto</AppAction>
        <AppAction :disabled="disabled" @click="$emit('close-all')"><font-awesome-icon :icon="['fas','chevron-up']" aria-hidden="true" />Chiudi tutto</AppAction>
    </div>
</template>
<script setup>
import AppAction from './AppAction.vue'
defineProps({disabled:Boolean})
defineEmits(['open-all','close-all'])
</script>
