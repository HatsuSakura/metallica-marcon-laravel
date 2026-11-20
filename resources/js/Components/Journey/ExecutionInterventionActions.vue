<template>
    <div v-if="active" class="space-y-2 rounded-xl border border-warning p-3" role="status">
        <p class="font-semibold">Modifiche al viaggio da confermare ({{ count }})</p>
        <p class="text-sm">Le modifiche diventano operative insieme alla conferma. Verrà inviato un unico riepilogo agli interessati.</p>
        <div class="flex flex-wrap gap-2"><AppAction variant="cancel" :disabled="busy" @click="$emit('cancel')">Annulla modifiche</AppAction><AppAction variant="confirm" :busy="busy" :disabled="!count" @click="$emit('confirm')">Conferma modifiche</AppAction></div>
    </div>
    <AppAction v-else variant="function" :disabled="busy" @click="$emit('begin')"><font-awesome-icon :icon="['fas','pencil']" aria-hidden="true" />Prepara modifiche al viaggio</AppAction>
</template>
<script setup>
import AppAction from '@/Components/UI/AppAction.vue'
defineProps({active:Boolean,count:Number,busy:Boolean})
defineEmits(['begin','confirm','cancel'])
</script>
