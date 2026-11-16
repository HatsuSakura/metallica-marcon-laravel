<template>
    <section class="space-y-2 rounded-xl border border-base-300 bg-base-200 p-3 text-sm" aria-label="Avvio viaggio per riordino tappe">
        <h3 class="font-semibold">Avvia il viaggio per riordinare le tappe</h3>
        <p>Il riordino modifica il viaggio operativo. Se devi correggere il piano prima della partenza, contatta la Logistica.</p>
        <p>Avviando ora, verrà registrato l’inizio effettivo del viaggio.</p>
        <p v-if="error" role="alert" class="text-error">{{ error }}</p>
        <div class="grid grid-cols-2 gap-2">
            <AppAction type="button" :disabled="busy" @click="$emit('cancel')" variant="cancel">Annulla</AppAction>
            <AppAction type="button" class="whitespace-normal px-2 py-2" :disabled="busy" @click="$emit('confirm')" variant="confirm">{{ busy ? 'Avvio…' : 'Avvia e riordina' }}</AppAction>
        </div>
    </section>
</template>
<script setup>
import AppAction from '@/Components/UI/AppAction.vue'
defineProps({busy:Boolean,error:String})
defineEmits(['confirm','cancel'])
</script>
